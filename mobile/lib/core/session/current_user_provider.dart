import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../network/api_client.dart';

/// The signed-in user's `/me` payload (name, membership, and — for a parent —
/// their linked children in the current school). Shared across features so
/// they all agree on the same cached fetch instead of each hitting `/me` again.
final currentUserProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  final response = await ref.watch(dioProvider).get('me');
  return Map<String, dynamic>.from(response.data['data']);
});

/// The parent's manually-selected child, set by ChildSwitcher
/// (core/widgets/child_switcher.dart) and restored from on-device storage
/// per membership id. Null means "no explicit choice yet" - studentContextProvider
/// below then falls back to the first linked child, same as before a switcher
/// existed.
final selectedChildIdProvider = StateProvider<int?>((ref) => null);

/// The student id a fees/wallet/events/live-class request should act on: the
/// student's own id, or - for a parent - the child picked in ChildSwitcher,
/// falling back to their first/only linked child if nothing has been picked
/// yet (or the school has only one child linked, in which case ChildSwitcher
/// never renders at all).
final studentContextProvider = Provider<int?>((ref) {
  final user = ref.watch(currentUserProvider).valueOrNull;
  if (user == null) return null;
  final roleId = int.tryParse(
    user['membership']?['role']?['id']?.toString() ?? '',
  );
  if (roleId == 7) return user['id'] as int?;
  final children = (user['children'] as List?)?.cast<Map<String, dynamic>>();
  if (children == null || children.isEmpty) return null;
  final selected = ref.watch(selectedChildIdProvider);
  if (selected != null &&
      children.any((child) => (child['id'] as int?) == selected)) {
    return selected;
  }
  return children.first['id'] as int?;
});
