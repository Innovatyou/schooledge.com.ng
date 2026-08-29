import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/module_ui.dart';
import '../../../core/navigation/page_transitions.dart';
import '../data/chat_repository.dart';
import 'chat_thread_page.dart';

/// Classmates doubling as the recents list, matching the chat-app convention
/// where "who can I message" and "who have I messaged" are the same screen -
/// there's no separate contact-picker step since the classmate list is
/// already small and fully known (same class+section only).
class ChatListPage extends ConsumerWidget {
  const ChatListPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final session = ref.watch(chatSessionProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('Classmates')),
      body: session.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text(
                  'Could not connect to classmate chat.',
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 16),
                FilledButton.icon(
                  onPressed: () => ref.invalidate(chatSessionProvider),
                  icon: const Icon(Icons.refresh),
                  label: const Text('Try again'),
                ),
              ],
            ),
          ),
        ),
        data: (_) => ref
            .watch(classmatesProvider)
            .when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (error, _) => Center(
                child: FilledButton.icon(
                  onPressed: () => ref.invalidate(classmatesProvider),
                  icon: const Icon(Icons.refresh),
                  label: const Text('Try again'),
                ),
              ),
              data: (classmates) {
                if (classmates.isEmpty) {
                  return const Padding(
                    padding: EdgeInsets.all(24),
                    child: Center(
                      child: Text(
                        'No classmates are on the app yet.',
                        textAlign: TextAlign.center,
                      ),
                    ),
                  );
                }
                final conversations = ref
                    .watch(conversationsStreamProvider)
                    .valueOrNull;
                final byId =
                    <String, QueryDocumentSnapshot<Map<String, dynamic>>>{
                      for (final doc in conversations ?? []) doc.id: doc,
                    };
                return ListView.builder(
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  itemCount: classmates.length,
                  itemBuilder: (context, index) {
                    final classmate = classmates[index];
                    final conversationId =
                        classmate['conversation_id'] as String;
                    final conversation = byId[conversationId]?.data();
                    return InfoRow(
                      icon: Icons.person_rounded,
                      title: classmate['name']?.toString() ?? '',
                      subtitle:
                          conversation?['lastMessagePreview']?.toString() ??
                          'Say hello!',
                      color: const Color(0xffb83b96),
                      onTap: () => Navigator.of(context).push(
                        moduleRoute<void>(
                          ChatThreadPage(
                            peerMembershipId: classmate['membership_id'] as int,
                            peerName: classmate['name']?.toString() ?? '',
                            conversationId: conversationId,
                          ),
                        ),
                      ),
                    );
                  },
                );
              },
            ),
      ),
    );
  }
}
