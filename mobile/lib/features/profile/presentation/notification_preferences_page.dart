import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../notifications/data/notifications_repository.dart';

class NotificationPreferencesPage extends ConsumerWidget {
  const NotificationPreferencesPage({super.key});

  Future<void> _update(
    WidgetRef ref,
    Map<String, dynamic> preference,
    String key,
    bool value,
  ) async {
    await ref.read(dioProvider).put(
      'notifications/preferences',
      data: {
        'category': preference['category'],
        'inbox_enabled': key == 'inbox_enabled' ? value : preference['inbox_enabled'],
        'push_enabled': key == 'push_enabled' ? value : preference['push_enabled'],
        'email_enabled': key == 'email_enabled' ? value : preference['email_enabled'],
      },
    );
    ref.invalidate(notificationPreferencesProvider);
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final preferences = ref.watch(notificationPreferencesProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('Notifications')),
      body: preferences.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(
          child: FilledButton.icon(
            onPressed: () => ref.invalidate(notificationPreferencesProvider),
            icon: const Icon(Icons.refresh),
            label: const Text('Try again'),
          ),
        ),
        data: (rows) => ListView(
          padding: const EdgeInsets.all(20),
          children: rows.map((preference) {
            final category = preference['category'] as String;
            return Card(
              margin: const EdgeInsets.only(bottom: 12),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      categoryLabels[category] ?? category,
                      style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 15),
                    ),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Notification inbox'),
                      value: preference['inbox_enabled'] as bool,
                      onChanged: (value) => _update(ref, preference, 'inbox_enabled', value),
                    ),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Push notifications'),
                      value: preference['push_enabled'] as bool,
                      onChanged: (value) => _update(ref, preference, 'push_enabled', value),
                    ),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Email'),
                      value: preference['email_enabled'] as bool,
                      onChanged: (value) => _update(ref, preference, 'email_enabled', value),
                    ),
                  ],
                ),
              ),
            );
          }).toList(),
        ),
      ),
    );
  }
}
