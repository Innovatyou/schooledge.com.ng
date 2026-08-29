import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';

final inboxProvider = FutureProvider.autoDispose<List<Map<String, dynamic>>>((
  ref,
) async {
  final response = await ref.watch(dioProvider).get('messages');
  return (response.data['data'] as List)
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
});

final threadProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final response = await ref.watch(dioProvider).get('messages/$id');
      return Map<String, dynamic>.from(response.data['data']);
    });

final contactsProvider = FutureProvider.autoDispose<List<Map<String, dynamic>>>(
  (ref) async {
    final response = await ref.watch(dioProvider).get('messages/contacts');
    return (response.data['data'] as List)
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  },
);
