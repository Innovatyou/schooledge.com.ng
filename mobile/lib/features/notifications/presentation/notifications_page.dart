import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/notifications_repository.dart';

class NotificationsPage extends ConsumerWidget {
  const NotificationsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final notifications = ref.watch(notificationsProvider);
    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          TextButton(
            onPressed: () async {
              await ref.read(dioProvider).post('notifications/read-all');
              ref.invalidate(notificationsProvider);
              ref.invalidate(unreadCountProvider);
            },
            child: const Text('Mark all read'),
          ),
        ],
      ),
      body: notifications.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(
          child: FilledButton.icon(
            onPressed: () => ref.invalidate(notificationsProvider),
            icon: const Icon(Icons.refresh),
            label: const Text('Try again'),
          ),
        ),
        data: (items) {
          if (items.isEmpty) {
            return const Center(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: Text('You have no notifications yet.'),
              ),
            );
          }
          return ListView(
            padding: const EdgeInsets.all(20),
            children: items.map((item) {
              final read = item['read'] == true;
              return InfoRow(
                icon: _iconFor(item['category'] as String?),
                title: item['title']?.toString() ?? '',
                subtitle: item['body']?.toString() ?? '',
                color: read ? const Color(0xff829ab1) : const Color(0xff725cff),
                onTap: () async {
                  if (!read) {
                    await ref.read(dioProvider).post('notifications/${item['id']}/read');
                    ref.invalidate(notificationsProvider);
                    ref.invalidate(unreadCountProvider);
                  }
                },
              );
            }).toList(),
          );
        },
      ),
    );
  }

  IconData _iconFor(String? category) => switch (category) {
    'message' => Icons.forum_rounded,
    'homework' => Icons.assignment_rounded,
    'payment' => Icons.account_balance_wallet_rounded,
    'announcement' => Icons.campaign_rounded,
    'live_class' => Icons.videocam_rounded,
    _ => Icons.notifications_rounded,
  };
}
