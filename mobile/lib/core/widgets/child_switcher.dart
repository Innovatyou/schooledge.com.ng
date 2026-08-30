import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../session/current_user_provider.dart';

/// Shown above a student-scoped page's content whenever the signed-in parent
/// has more than one linked child in this school, so they can pick which
/// child's data (fees, wallet, ...) the page below is showing. Renders
/// nothing for a student, or a parent with only one linked child - exactly
/// today's behavior in that case. Selection is persisted on-device per
/// membership id so it survives app restarts.
class ChildSwitcher extends ConsumerStatefulWidget {
  const ChildSwitcher({super.key});
  @override
  ConsumerState<ChildSwitcher> createState() => _ChildSwitcherState();
}

class _ChildSwitcherState extends ConsumerState<ChildSwitcher> {
  static const _prefsKeyPrefix = 'schooledge.selected_child.';
  bool _restoreStarted = false;

  Future<void> _restore(int membershipId) async {
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getInt('$_prefsKeyPrefix$membershipId');
    if (saved != null && mounted) {
      ref.read(selectedChildIdProvider.notifier).state = saved;
    }
  }

  Future<void> _select(int membershipId, int studentId) async {
    ref.read(selectedChildIdProvider.notifier).state = studentId;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt('$_prefsKeyPrefix$membershipId', studentId);
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(currentUserProvider).valueOrNull;
    if (user == null) return const SizedBox.shrink();
    final roleId = int.tryParse(
      user['membership']?['role']?['id']?.toString() ?? '',
    );
    if (roleId != 6) return const SizedBox.shrink();
    final children = (user['children'] as List?)?.cast<Map<String, dynamic>>();
    if (children == null || children.length < 2) return const SizedBox.shrink();

    final membershipId = int.tryParse(
      user['membership']?['id']?.toString() ?? '',
    );
    if (!_restoreStarted && membershipId != null) {
      _restoreStarted = true;
      _restore(membershipId);
    }

    final selected = ref.watch(selectedChildIdProvider) ?? children.first['id'] as int;
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: SizedBox(
        height: 40,
        child: ListView.separated(
          scrollDirection: Axis.horizontal,
          itemCount: children.length,
          separatorBuilder: (context, index) => const SizedBox(width: 8),
          itemBuilder: (context, index) {
            final child = children[index];
            final studentId = child['id'] as int;
            return ChoiceChip(
              label: Text(child['name']?.toString() ?? 'Student'),
              selected: studentId == selected,
              onSelected: (_) {
                if (membershipId != null) _select(membershipId, studentId);
              },
            );
          },
        ),
      ),
    );
  }
}
