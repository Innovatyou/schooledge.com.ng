import 'dart:async';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:just_audio/just_audio.dart';
import 'package:record/record.dart';
import '../data/chat_repository.dart';

class ChatThreadPage extends ConsumerStatefulWidget {
  const ChatThreadPage({
    super.key,
    required this.peerMembershipId,
    required this.peerName,
    required this.conversationId,
  });
  final int peerMembershipId;
  final String peerName;
  final String conversationId;

  @override
  ConsumerState<ChatThreadPage> createState() => _ChatThreadPageState();
}

class _ChatThreadPageState extends ConsumerState<ChatThreadPage> {
  final _controller = TextEditingController();
  final _recorder = AudioRecorder();
  Timer? _typingDebounce;
  bool _typing = false;
  bool _recording = false;
  bool _sending = false;
  DateTime? _recordingStartedAt;

  String get _peerMembershipId => widget.peerMembershipId.toString();

  @override
  void dispose() {
    _typingDebounce?.cancel();
    _controller.dispose();
    _recorder.dispose();
    final session = ref.read(chatSessionProvider).valueOrNull;
    if (session != null && _typing) {
      ref
          .read(chatRepositoryProvider)
          .setTyping(
            conversationId: widget.conversationId,
            myMembershipId: session.membershipId,
            typing: false,
          );
    }
    super.dispose();
  }

  void _onTextChanged(String _, ChatSession session) {
    if (!_typing) {
      _typing = true;
      ref
          .read(chatRepositoryProvider)
          .setTyping(
            conversationId: widget.conversationId,
            myMembershipId: session.membershipId,
            typing: true,
          );
    }
    _typingDebounce?.cancel();
    _typingDebounce = Timer(const Duration(seconds: 2), () {
      _typing = false;
      ref
          .read(chatRepositoryProvider)
          .setTyping(
            conversationId: widget.conversationId,
            myMembershipId: session.membershipId,
            typing: false,
          );
    });
  }

  Future<void> _sendText(ChatSession session) async {
    final text = _controller.text.trim();
    if (text.isEmpty) return;
    _controller.clear();
    _typingDebounce?.cancel();
    _typing = false;
    await ref
        .read(chatRepositoryProvider)
        .setTyping(
          conversationId: widget.conversationId,
          myMembershipId: session.membershipId,
          typing: false,
        );
    await ref
        .read(chatRepositoryProvider)
        .sendText(
          session: session,
          conversationId: widget.conversationId,
          peerMembershipId: _peerMembershipId,
          text: text,
        );
  }

  Future<void> _startRecording() async {
    if (!await _recorder.hasPermission()) return;
    final dir = await Directory.systemTemp.createTemp('schooledge_voice');
    _recordingStartedAt = DateTime.now();
    await _recorder.start(
      const RecordConfig(),
      path:
          '${dir.path}/voice_note_${DateTime.now().microsecondsSinceEpoch}.m4a',
    );
    if (mounted) setState(() => _recording = true);
  }

  Future<void> _stopRecordingAndSend(ChatSession session) async {
    if (!_recording) return;
    final path = await _recorder.stop();
    final duration = _recordingStartedAt == null
        ? Duration.zero
        : DateTime.now().difference(_recordingStartedAt!);
    _recordingStartedAt = null;
    setState(() => _recording = false);
    if (path == null || duration < const Duration(milliseconds: 500)) return;
    setState(() => _sending = true);
    try {
      await ref
          .read(chatRepositoryProvider)
          .sendVoiceNote(
            session: session,
            conversationId: widget.conversationId,
            peerMembershipId: _peerMembershipId,
            filePath: path,
            duration: duration,
          );
    } on DioException catch (error) {
      if (mounted) _showError(_messageFor(error, 'Could not send voice note.'));
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  String _messageFor(DioException error, String fallback) {
    final body = error.response?.data;
    return body is Map && body['error'] is Map
        ? ((body['error'] as Map)['message'] ?? fallback).toString()
        : fallback;
  }

  void _showError(String message) {
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _confirmReport(String? excerpt) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Report this conversation?'),
        content: const Text(
          'Your class teacher and school admin will be notified.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Report'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;
    try {
      await ref
          .read(chatRepositoryProvider)
          .report(
            conversationId: widget.conversationId,
            reportedMembershipId: widget.peerMembershipId,
            messageExcerpt: excerpt,
          );
      if (mounted) _showError('Report sent.');
    } on DioException catch (error) {
      if (mounted) _showError(_messageFor(error, 'Could not send report.'));
    }
  }

  Future<void> _confirmBlock() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Block ${widget.peerName}?'),
        content: const Text(
          'They will no longer be able to message you in classmate chat.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Block'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;
    try {
      await ref.read(chatRepositoryProvider).block(widget.peerMembershipId);
      if (mounted) {
        _showError('${widget.peerName} is blocked.');
        Navigator.of(context).pop();
      }
    } on DioException catch (error) {
      if (mounted) _showError(_messageFor(error, 'Could not block.'));
    }
  }

  @override
  Widget build(BuildContext context) {
    final session = ref.watch(chatSessionProvider);
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.peerName),
        actions: [
          PopupMenuButton<String>(
            onSelected: (value) =>
                value == 'block' ? _confirmBlock() : _confirmReport(null),
            itemBuilder: (context) => const [
              PopupMenuItem(value: 'report', child: Text('Report')),
              PopupMenuItem(value: 'block', child: Text('Block')),
            ],
          ),
        ],
      ),
      body: session.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) =>
            const Center(child: Text('Could not connect to chat.')),
        data: (session) => Column(
          children: [
            Expanded(
              child: ref
                  .watch(messagesStreamProvider(widget.conversationId))
                  .when(
                    loading: () =>
                        const Center(child: CircularProgressIndicator()),
                    error: (error, _) =>
                        const Center(child: Text('Could not load messages.')),
                    data: (docs) => ListView.builder(
                      padding: const EdgeInsets.all(20),
                      itemCount: docs.length,
                      itemBuilder: (context, index) {
                        final data = docs[index].data();
                        final mine = data['senderId'] == session.membershipId;
                        return Align(
                          alignment: mine
                              ? Alignment.centerRight
                              : Alignment.centerLeft,
                          child: _MessageBubble(data: data, mine: mine),
                        );
                      },
                    ),
                  ),
            ),
            ref
                .watch(
                  peerTypingProvider((
                    conversationId: widget.conversationId,
                    peerMembershipId: _peerMembershipId,
                  )),
                )
                .maybeWhen(
                  data: (typing) => typing
                      ? const _TypingIndicator()
                      : const SizedBox.shrink(),
                  orElse: () => const SizedBox.shrink(),
                ),
            SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _controller,
                        enabled: !_recording,
                        onChanged: (value) => _onTextChanged(value, session),
                        decoration: const InputDecoration(hintText: 'Message…'),
                      ),
                    ),
                    const SizedBox(width: 8),
                    GestureDetector(
                      onLongPressStart: (_) => _startRecording(),
                      onLongPressEnd: (_) => _stopRecordingAndSend(session),
                      child: IconButton.filled(
                        onPressed: () {},
                        icon: _sending
                            ? const SizedBox.square(
                                dimension: 18,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                ),
                              )
                            : Icon(
                                _recording
                                    ? Icons.mic_rounded
                                    : Icons.mic_none_rounded,
                                color: _recording ? Colors.red : null,
                              ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    IconButton.filled(
                      onPressed: _sending ? null : () => _sendText(session),
                      icon: const Icon(Icons.send_rounded),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _MessageBubble extends StatelessWidget {
  const _MessageBubble({required this.data, required this.mine});
  final Map<String, dynamic> data;
  final bool mine;

  @override
  Widget build(BuildContext context) {
    final color = mine
        ? const Color(0xff163a70)
        : Theme.of(context).colorScheme.surfaceContainerHigh;
    final textColor = mine
        ? Colors.white
        : Theme.of(context).colorScheme.onSurface;
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      constraints: const BoxConstraints(maxWidth: 280),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(18),
      ),
      child: data['type'] == 'audio'
          ? _VoiceNoteBubble(noteId: data['audioNoteId'], textColor: textColor)
          : Text(
              data['text']?.toString() ?? '',
              style: TextStyle(color: textColor),
            ),
    );
  }
}

class _VoiceNoteBubble extends ConsumerStatefulWidget {
  const _VoiceNoteBubble({required this.noteId, required this.textColor});
  final dynamic noteId;
  final Color textColor;

  @override
  ConsumerState<_VoiceNoteBubble> createState() => _VoiceNoteBubbleState();
}

class _VoiceNoteBubbleState extends ConsumerState<_VoiceNoteBubble> {
  final _player = AudioPlayer();
  bool _loading = false;
  bool _loaded = false;

  Future<void> _toggle() async {
    if (_loaded) {
      _player.playing ? await _player.pause() : await _player.play();
      setState(() {});
      return;
    }
    setState(() => _loading = true);
    try {
      final file = await ref
          .read(chatRepositoryProvider)
          .downloadVoiceNote(widget.noteId);
      await _player.setFilePath(file.path);
      _loaded = true;
      await _player.play();
      _player.playerStateStream.listen((_) {
        if (mounted) setState(() {});
      });
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  void dispose() {
    _player.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      IconButton(
        onPressed: _loading ? null : _toggle,
        icon: _loading
            ? SizedBox.square(
                dimension: 18,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: widget.textColor,
                ),
              )
            : Icon(
                _loaded && _player.playing
                    ? Icons.pause_rounded
                    : Icons.play_arrow_rounded,
                color: widget.textColor,
              ),
      ),
      Icon(Icons.graphic_eq_rounded, color: widget.textColor),
    ],
  );
}

class _TypingIndicator extends StatelessWidget {
  const _TypingIndicator();

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 6),
    child: Align(
      alignment: Alignment.centerLeft,
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: List.generate(
          3,
          (index) =>
              Container(
                    margin: const EdgeInsets.symmetric(horizontal: 2),
                    width: 7,
                    height: 7,
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                      shape: BoxShape.circle,
                    ),
                  )
                  .animate(onPlay: (controller) => controller.repeat())
                  .moveY(
                    begin: 0,
                    end: -5,
                    duration: 300.ms,
                    delay: (index * 120).ms,
                  )
                  .then()
                  .moveY(begin: -5, end: 0, duration: 300.ms),
        ),
      ),
    ),
  );
}
