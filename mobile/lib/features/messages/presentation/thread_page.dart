import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../data/messages_repository.dart';

class ThreadPage extends ConsumerStatefulWidget {
  const ThreadPage({super.key, required this.messageId});
  final int messageId;

  @override
  ConsumerState<ThreadPage> createState() => _ThreadPageState();
}

class _ThreadPageState extends ConsumerState<ThreadPage> {
  final _controller = TextEditingController();
  bool _sending = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _reply() async {
    final text = _controller.text.trim();
    if (text.isEmpty) return;
    setState(() => _sending = true);
    try {
      await ref
          .read(dioProvider)
          .post('messages/${widget.messageId}/reply', data: {'message': text});
      _controller.clear();
      ref.invalidate(threadProvider(widget.messageId));
      ref.invalidate(inboxProvider);
    } on DioException catch (error) {
      if (mounted) {
        final data = error.response?.data;
        final message = data is Map && data['error'] is Map
            ? ((data['error'] as Map)['message'] ?? 'Could not send reply.')
                  .toString()
            : 'Could not send reply.';
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(SnackBar(content: Text(message)));
      }
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final thread = ref.watch(threadProvider(widget.messageId));
    return Scaffold(
      appBar: AppBar(
        title: Text(thread.valueOrNull?['with']?.toString() ?? 'Conversation'),
      ),
      body: thread.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(
          child: FilledButton.icon(
            onPressed: () => ref.invalidate(threadProvider(widget.messageId)),
            icon: const Icon(Icons.refresh),
            label: const Text('Try again'),
          ),
        ),
        data: (data) {
          final direction = data['direction'] as String;
          final replies = (data['replies'] as List)
              .cast<Map<String, dynamic>>();
          return Column(
            children: [
              Expanded(
                child: ListView(
                  padding: const EdgeInsets.all(20),
                  children: [
                    Text(
                      data['subject']?.toString() ?? '',
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(height: 12),
                    Align(
                      alignment: direction == 'sent'
                          ? Alignment.centerRight
                          : Alignment.centerLeft,
                      child: _Bubble(
                        text: data['body']?.toString() ?? '',
                        mine: direction == 'sent',
                      ),
                    ),
                    for (final reply in replies)
                      Align(
                        alignment: (reply['mine'] as bool)
                            ? Alignment.centerRight
                            : Alignment.centerLeft,
                        child: _Bubble(
                          text: reply['body']?.toString() ?? '',
                          mine: reply['mine'] as bool,
                        ),
                      ),
                  ],
                ),
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
                          decoration: const InputDecoration(
                            hintText: 'Write a reply…',
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      IconButton.filled(
                        onPressed: _sending ? null : _reply,
                        icon: _sending
                            ? const SizedBox.square(
                                dimension: 18,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                ),
                              )
                            : const Icon(Icons.send_rounded),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _Bubble extends StatelessWidget {
  const _Bubble({required this.text, required this.mine});
  final String text;
  final bool mine;

  @override
  Widget build(BuildContext context) => Container(
    margin: const EdgeInsets.only(bottom: 12),
    padding: const EdgeInsets.all(14),
    constraints: const BoxConstraints(maxWidth: 280),
    decoration: BoxDecoration(
      color: mine
          ? const Color(0xff163a70)
          : Theme.of(context).colorScheme.surfaceContainerHigh,
      borderRadius: BorderRadius.circular(18),
    ),
    child: Text(
      text,
      style: TextStyle(
        color: mine ? Colors.white : Theme.of(context).colorScheme.onSurface,
      ),
    ),
  );
}
