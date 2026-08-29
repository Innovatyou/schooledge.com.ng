import 'dart:io';

import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:dio/dio.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';

/// Sorted, deterministic conversation id - both classmates resolve to the
/// same Firestore document with no lookup, and it doubles as the id
/// Chat::assertParticipant() parses server-side to authorize voice-note
/// access without ever calling Firestore.
String conversationIdFor(int membershipIdA, int membershipIdB) {
  final ids = [membershipIdA, membershipIdB]..sort();
  return '${ids[0]}_${ids[1]}';
}

class ChatSession {
  const ChatSession({required this.membershipId, required this.classroomKey});
  final String membershipId;
  final String classroomKey;
}

/// Signs in to Firebase using the app's own bearer-token identity (via
/// chat/token -> signInWithCustomToken) - resolved once per app session and
/// reused by every Firestore-backed provider below. Firebase Auth persists
/// its own session after this, so re-resolving on a cold provider restart is
/// cheap (no network call if already signed in with a still-valid claim set).
final chatSessionProvider = FutureProvider.autoDispose<ChatSession>((
  ref,
) async {
  if (Firebase.apps.isEmpty) await Firebase.initializeApp();
  final response = await ref.watch(dioProvider).post('chat/token');
  final data = response.data['data'] as Map;
  final token = data['firebase_token'] as String;
  await FirebaseAuth.instance.signInWithCustomToken(token);
  return ChatSession(
    membershipId: data['membership_id'].toString(),
    classroomKey: data['classroom_key'] as String,
  );
});

final classmatesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final response = await ref.watch(dioProvider).get('chat/classmates');
      return (response.data['data'] as List)
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

/// Oversight is teacher/admin-only and never touches Firestore from the
/// client - it's a plain authenticated REST call to Chat::oversightClasses()/
/// oversight(), which does the live Firestore fetch server-side. No
/// [chatSessionProvider]/Firebase sign-in needed here at all.
final oversightClassesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final response = await ref
          .watch(dioProvider)
          .get('chat/oversight/classes');
      return (response.data['data'] as List)
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

final oversightConversationsProvider = FutureProvider.autoDispose
    .family<List<Map<String, dynamic>>, String>((ref, classroomKey) async {
      final response = await ref
          .watch(dioProvider)
          .get('chat/oversight/$classroomKey');
      return (response.data['data']['conversations'] as List)
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

final _firestore = FirebaseFirestore.instance;

final conversationsStreamProvider =
    StreamProvider.autoDispose<
      List<QueryDocumentSnapshot<Map<String, dynamic>>>
    >((ref) async* {
      final session = await ref.watch(chatSessionProvider.future);
      yield* _firestore
          .collection('conversations')
          .where('participantIds', arrayContains: session.membershipId)
          .orderBy('lastMessageAt', descending: true)
          .snapshots()
          .map((snapshot) => snapshot.docs);
    });

final messagesStreamProvider = StreamProvider.autoDispose
    .family<List<QueryDocumentSnapshot<Map<String, dynamic>>>, String>((
      ref,
      conversationId,
    ) async* {
      await ref.watch(chatSessionProvider.future);
      yield* _firestore
          .collection('conversations')
          .doc(conversationId)
          .collection('messages')
          .orderBy('createdAt', descending: false)
          .snapshots()
          .map((snapshot) => snapshot.docs);
    });

/// True if the peer's typing doc says so AND was updated recently - a stale
/// doc (peer's app crashed/lost connection mid-type) must not leave the
/// indicator stuck on forever.
final peerTypingProvider = StreamProvider.autoDispose
    .family<bool, ({String conversationId, String peerMembershipId})>((
      ref,
      args,
    ) async* {
      yield* _firestore
          .collection('typingStatus')
          .doc('${args.conversationId}_${args.peerMembershipId}')
          .snapshots()
          .map((doc) {
            final data = doc.data();
            if (data == null || data['typing'] != true) return false;
            final updatedAt = data['updatedAt'] as Timestamp?;
            if (updatedAt == null) return false;
            return DateTime.now().difference(updatedAt.toDate()) <
                const Duration(seconds: 5);
          });
    });

class ChatRepository {
  ChatRepository(this._ref);
  final Ref _ref;

  Future<void> _touchConversation(
    String conversationId,
    String classroomKey,
    String myMembershipId,
    String peerMembershipId,
    String preview,
  ) {
    final ids = [myMembershipId, peerMembershipId]..sort();
    return _firestore.collection('conversations').doc(conversationId).set({
      'classroomKey': classroomKey,
      'participantIds': ids,
      'pairKey': '${ids[0]}_${ids[1]}',
      'lastMessageAt': FieldValue.serverTimestamp(),
      'lastMessagePreview': preview,
    }, SetOptions(merge: true));
  }

  Future<void> sendText({
    required ChatSession session,
    required String conversationId,
    required String peerMembershipId,
    required String text,
  }) async {
    await _touchConversation(
      conversationId,
      session.classroomKey,
      session.membershipId,
      peerMembershipId,
      text,
    );
    await _firestore
        .collection('conversations')
        .doc(conversationId)
        .collection('messages')
        .add({
          'senderId': session.membershipId,
          'type': 'text',
          'text': text,
          'createdAt': FieldValue.serverTimestamp(),
        });
  }

  Future<void> sendVoiceNote({
    required ChatSession session,
    required String conversationId,
    required String peerMembershipId,
    required String filePath,
    required Duration duration,
  }) async {
    final response = await _ref
        .read(dioProvider)
        .post(
          'chat/voice-notes',
          data: FormData.fromMap({
            'conversation_id': conversationId,
            'duration_ms': duration.inMilliseconds,
            'file': await MultipartFile.fromFile(filePath),
          }),
        );
    final noteId = response.data['data']['id'];
    await _touchConversation(
      conversationId,
      session.classroomKey,
      session.membershipId,
      peerMembershipId,
      'Voice note',
    );
    await _firestore
        .collection('conversations')
        .doc(conversationId)
        .collection('messages')
        .add({
          'senderId': session.membershipId,
          'type': 'audio',
          'audioNoteId': noteId,
          'audioDurationMs': duration.inMilliseconds,
          'createdAt': FieldValue.serverTimestamp(),
        });
  }

  /// Whole-file fetch through the authenticated Dio client, same reasoning as
  /// LibraryRepository.readAudiobook() - avoids handing just_audio a bare
  /// URL+token that could go stale mid-playback.
  Future<File> downloadVoiceNote(dynamic noteId) async {
    final response = await _ref
        .read(dioProvider)
        .get<List<int>>(
          'chat/voice-notes/$noteId',
          options: Options(responseType: ResponseType.bytes),
        );
    final tempDir = await Directory.systemTemp.createTemp('schooledge_voice');
    final file = File('${tempDir.path}/$noteId.audio');
    await file.writeAsBytes(response.data!);
    return file;
  }

  Future<void> setTyping({
    required String conversationId,
    required String myMembershipId,
    required bool typing,
  }) {
    final doc = _firestore
        .collection('typingStatus')
        .doc('${conversationId}_$myMembershipId');
    return typing
        ? doc.set({'typing': true, 'updatedAt': FieldValue.serverTimestamp()})
        : doc.delete();
  }

  Future<void> block(int peerMembershipId) => _ref
      .read(dioProvider)
      .post('chat/block', data: {'membership_id': peerMembershipId});

  Future<void> unblock(int peerMembershipId) => _ref
      .read(dioProvider)
      .post('chat/unblock', data: {'membership_id': peerMembershipId});

  Future<void> report({
    required String conversationId,
    required int reportedMembershipId,
    String? messageExcerpt,
  }) => _ref
      .read(dioProvider)
      .post(
        'chat/reports',
        data: {
          'conversation_id': conversationId,
          'reported_membership_id': reportedMembershipId,
          'message_excerpt': ?messageExcerpt,
        },
      );
}

final chatRepositoryProvider = Provider((ref) => ChatRepository(ref));
