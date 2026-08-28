import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../network/api_client.dart';

/// The signed-in user's `/me` payload (name, membership, and — for a parent —
/// their linked children in the current school). Shared across features so
/// they all agree on the same cached fetch instead of each hitting `/me` again.
final currentUserProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  final response = await ref.watch(dioProvider).get('me');
  return Map<String, dynamic>.from(response.data['data']);
});

/// The student id a fees/events/live-class request should act on: the
/// student's own id, or - for a parent - their first/only linked child in
/// this school (the app doesn't yet have a full child switcher; this keeps
/// every student-scoped feature usable today and is the single place to
/// extend once multi-child switching is built).
final studentContextProvider = Provider<int?>((ref) {
  final user = ref.watch(currentUserProvider).valueOrNull;
  if (user == null) return null;
  final roleId = int.tryParse(user['membership']?['role']?['id']?.toString() ?? '');
  if (roleId == 7) return user['id'] as int?;
  final children = (user['children'] as List?)?.cast<Map<String, dynamic>>();
  if (children == null || children.isEmpty) return null;
  return children.first['id'] as int?;
});
