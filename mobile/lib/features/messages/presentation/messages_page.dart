import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/messages_repository.dart';
import 'compose_page.dart';
import 'thread_page.dart';

class MessagesPage extends ConsumerWidget {
  const MessagesPage({super.key, this.embedded = false});
  final bool embedded;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final content = ref
        .watch(inboxProvider)
        .when(
          loading: () => const Padding(
            padding: EdgeInsets.symmetric(vertical: 60),
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (error, _) => InfoRow(
            icon: Icons.refresh_rounded,
            title: 'Could not load messages',
            subtitle: 'Tap to try again',
            color: const Color(0xffff6b6b),
            onTap: () => ref.invalidate(inboxProvider),
          ),
          data: (messages) {
            if (messages.isEmpty) {
              return const InfoRow(
                icon: Icons.forum_rounded,
                title: 'No messages yet',
                subtitle: 'Start a conversation with your school.',
                color: Color(0xff829ab1),
                trailing: SizedBox.shrink(),
              );
            }
            return Column(
              children: messages.map((message) {
                final read = message['read'] == true;
                return InfoRow(
                  icon: message['direction'] == 'sent' ? Icons.outbox_rounded : Icons.inbox_rounded,
                  title: message['with']?.toString() ?? '',
                  subtitle: '${message['subject']} — ${message['snippet']}',
                  color: read ? const Color(0xff829ab1) : const Color(0xff725cff),
                  trailing: read ? null : const _UnreadBadge(),
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute<void>(
                      builder: (_) => ThreadPage(messageId: message['id'] as int),
                    ),
                  ),
                );
              }).toList(),
            );
          },
        );

    final composeButton = FloatingActionButton(
      onPressed: () => Navigator.of(context).push(
        MaterialPageRoute<void>(builder: (_) => const ComposePage()),
      ),
      child: const Icon(Icons.edit_rounded),
    );

    if (embedded) {
      return Scaffold(
        floatingActionButton: composeButton,
        body: ListView(
          padding: const EdgeInsets.fromLTRB(20, 20, 20, 120),
          children: [
            Text(
              'Messages',
              style: TextStyle(
                fontSize: 30,
                fontWeight: FontWeight.w900,
                color: Theme.of(context).colorScheme.onSurface,
              ),
            ),
            const SizedBox(height: 6),
            const Text(
              'Stay connected with your school.',
              style: TextStyle(color: Color(0xff627d98)),
            ),
            const SizedBox(height: 20),
            content,
          ],
        ),
      );
    }
    return Scaffold(
      floatingActionButton: composeButton,
      body: ModulePage(
        title: 'Messages',
        subtitle: 'Private conversations with your school.',
        icon: Icons.forum_rounded,
        colors: const [Color(0xffb83b96), Color(0xffe66bb2)],
        children: [content],
      ),
    );
  }
}

class _UnreadBadge extends StatelessWidget {
  const _UnreadBadge();
  @override
  Widget build(BuildContext context) => const CircleAvatar(
    radius: 6,
    backgroundColor: Color(0xffd65db1),
  );
}
