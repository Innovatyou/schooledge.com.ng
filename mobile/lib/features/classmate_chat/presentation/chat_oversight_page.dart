import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:just_audio/just_audio.dart';
import '../../../core/widgets/depth_card.dart';
import '../data/chat_repository.dart';

/// Teacher/admin-only moderation view of a classroom's chat activity, fetched
/// live from Firestore server-side (Chat::oversight()) - not a realtime
/// stream, just a manual "load what's there right now" read, refreshed by
/// picking a class again or pulling to refresh.
class ChatOversightPage extends ConsumerStatefulWidget {
  const ChatOversightPage({super.key});

  @override
  ConsumerState<ChatOversightPage> createState() => _ChatOversightPageState();
}

class _ChatOversightPageState extends ConsumerState<ChatOversightPage> {
  Map<String, dynamic>? _selectedClass;

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Chat oversight')),
    body: ListView(
      padding: const EdgeInsets.all(20),
      children: [
        const Text(
          'Every conversation you open here is logged for your school\'s records.',
          style: TextStyle(color: Color(0xff627d98)),
        ),
        const SizedBox(height: 16),
        ref
            .watch(oversightClassesProvider)
            .when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (error, _) => FilledButton.icon(
                onPressed: () => ref.invalidate(oversightClassesProvider),
                icon: const Icon(Icons.refresh),
                label: const Text('Try again'),
              ),
              data: (classes) {
                if (classes.isEmpty) {
                  return const Text('No classes are available to review.');
                }
                _selectedClass ??= classes.first;
                return DropdownButtonFormField<Map<String, dynamic>>(
                  initialValue: _selectedClass,
                  decoration: const InputDecoration(labelText: 'Class'),
                  items: classes
                      .map(
                        (cls) => DropdownMenuItem(
                          value: cls,
                          child: Text(
                            '${cls['class_name']} - ${cls['section_name']}',
                          ),
                        ),
                      )
                      .toList(),
                  onChanged: (value) => setState(() => _selectedClass = value),
                );
              },
            ),
        const SizedBox(height: 20),
        if (_selectedClass != null)
          ref
              .watch(
                oversightConversationsProvider(
                  _selectedClass!['classroom_key'] as String,
                ),
              )
              .when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => FilledButton.icon(
                  onPressed: () => ref.invalidate(
                    oversightConversationsProvider(
                      _selectedClass!['classroom_key'] as String,
                    ),
                  ),
                  icon: const Icon(Icons.refresh),
                  label: const Text('Try again'),
                ),
                data: (conversations) {
                  if (conversations.isEmpty) {
                    return const Text('No chat activity in this class yet.');
                  }
                  return Column(
                    children: conversations
                        .map(
                          (conversation) =>
                              _ConversationCard(conversation: conversation),
                        )
                        .toList(),
                  );
                },
              ),
      ],
    ),
  );
}

class _ConversationCard extends StatefulWidget {
  const _ConversationCard({required this.conversation});
  final Map<String, dynamic> conversation;

  @override
  State<_ConversationCard> createState() => _ConversationCardState();
}

class _ConversationCardState extends State<_ConversationCard> {
  bool _expanded = false;

  @override
  Widget build(BuildContext context) {
    final names =
        (widget.conversation['participant_names'] as List?)
            ?.cast<Object?>()
            .map((n) => n.toString())
            .join(' & ') ??
        'Unknown';
    final preview = (widget.conversation['fields']?['lastMessagePreview'] ?? '')
        .toString();
    final messages =
        (widget.conversation['messages'] as List?)
            ?.cast<Map<String, dynamic>>() ??
        const [];
    return DepthCard(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          InkWell(
            onTap: () => setState(() => _expanded = !_expanded),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        names,
                        style: const TextStyle(fontWeight: FontWeight.w900),
                      ),
                      if (preview.isNotEmpty)
                        Text(
                          preview,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(color: Color(0xff627d98)),
                        ),
                    ],
                  ),
                ),
                Icon(
                  _expanded
                      ? Icons.expand_less_rounded
                      : Icons.expand_more_rounded,
                ),
              ],
            ),
          ),
          if (_expanded) ...[
            const Divider(height: 24),
            if (messages.isEmpty)
              const Text('No messages in this conversation.')
            else
              ...messages.map((message) => _MessageRow(message: message)),
          ],
        ],
      ),
    );
  }
}

class _MessageRow extends StatelessWidget {
  const _MessageRow({required this.message});
  final Map<String, dynamic> message;

  @override
  Widget build(BuildContext context) {
    final fields = message['fields'] as Map<String, dynamic>? ?? const {};
    final senderName = (message['sender_name'] ?? 'Unknown').toString();
    final isAudio = fields['type'] == 'audio';
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            senderName,
            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 2),
          isAudio
              ? _OversightVoiceNotePlayer(noteId: fields['audioNoteId'])
              : Text(fields['text']?.toString() ?? ''),
        ],
      ),
    );
  }
}

class _OversightVoiceNotePlayer extends ConsumerStatefulWidget {
  const _OversightVoiceNotePlayer({required this.noteId});
  final dynamic noteId;

  @override
  ConsumerState<_OversightVoiceNotePlayer> createState() =>
      _OversightVoiceNotePlayerState();
}

class _OversightVoiceNotePlayerState
    extends ConsumerState<_OversightVoiceNotePlayer> {
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
            ? const SizedBox.square(
                dimension: 16,
                child: CircularProgressIndicator(strokeWidth: 2),
              )
            : Icon(
                _loaded && _player.playing
                    ? Icons.pause_circle_rounded
                    : Icons.play_circle_rounded,
              ),
      ),
      const Text('Voice note'),
    ],
  );
}
