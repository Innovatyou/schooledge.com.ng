import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';

final profileProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final response = await ref.watch(dioProvider).get('profile');
  return Map<String, dynamic>.from(response.data['data']);
});

final sessionsProvider = FutureProvider.autoDispose<List<Map<String, dynamic>>>(
  (ref) async {
    final response = await ref.watch(dioProvider).get('profile/sessions');
    return (response.data['data'] as List)
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  },
);
