import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';

final notificationsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final response = await ref.watch(dioProvider).get('notifications');
      return (response.data['data'] as List)
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

final unreadCountProvider = FutureProvider.autoDispose<int>((ref) async {
  final response = await ref
      .watch(dioProvider)
      .get('notifications/unread-count');
  return response.data['data']['count'] as int;
});

final notificationPreferencesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final response = await ref
          .watch(dioProvider)
          .get('notifications/preferences');
      return (response.data['data'] as List)
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

const categoryLabels = {
  'message': 'Messages',
  'homework': 'Homework',
  'payment': 'Payments',
  'announcement': 'Announcements',
  'live_class': 'Online classes',
};
