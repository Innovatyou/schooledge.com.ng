import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/session/current_user_provider.dart';
import '../../../core/widgets/module_ui.dart';

final membershipsProvider = FutureProvider.autoDispose<List<Map<String, dynamic>>>((
  ref,
) async {
  final response = await ref.watch(dioProvider).get('memberships');
  return (response.data['data'] as List)
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
});

class MembershipSwitcherPage extends ConsumerStatefulWidget {
  const MembershipSwitcherPage({super.key});
  @override
  ConsumerState<MembershipSwitcherPage> createState() => _MembershipSwitcherPageState();
}

class _MembershipSwitcherPageState extends ConsumerState<MembershipSwitcherPage> {
  int? _switchingId;

  Future<void> _switch(int membershipId) async {
    setState(() => _switchingId = membershipId);
    try {
      final response = await ref
          .read(dioProvider)
          .post('auth/switch-membership', data: {'membership_id': membershipId});
      final tokens = Map<String, dynamic>.from(response.data['data']['tokens']);
      await ref
          .read(tokenStorageProvider)
          .save(tokens['access_token'] as String, tokens['refresh_token'] as String);
      ref.invalidate(currentUserProvider);
      if (mounted) {
        Navigator.of(context).popUntil((route) => route.isFirst);
      }
    } on DioException catch (error) {
      if (mounted) {
        final data = error.response?.data;
        final message = data is Map && data['error'] is Map
            ? ((data['error'] as Map)['message'] ?? 'Could not switch school.').toString()
            : 'Could not switch school.';
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(SnackBar(content: Text(message)));
      }
    } finally {
      if (mounted) setState(() => _switchingId = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    final memberships = ref.watch(membershipsProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('School memberships')),
      body: memberships.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(
          child: FilledButton.icon(
            onPressed: () => ref.invalidate(membershipsProvider),
            icon: const Icon(Icons.refresh),
            label: const Text('Try again'),
          ),
        ),
        data: (rows) => ListView(
          padding: const EdgeInsets.all(20),
          children: rows.map((row) {
            final id = row['id'] as int;
            final school = row['school'] as Map?;
            final role = row['role'] as Map?;
            return InfoRow(
              icon: Icons.school_rounded,
              title: school?['school_name']?.toString() ?? 'School',
              subtitle: role?['name']?.toString() ?? '',
              color: row['is_default'] == true ? const Color(0xff00a896) : const Color(0xff163a70),
              trailing: _switchingId == id
                  ? const SizedBox.square(dimension: 18, child: CircularProgressIndicator(strokeWidth: 2))
                  : null,
              onTap: _switchingId == null ? () => _switch(id) : null,
            );
          }).toList(),
        ),
      ),
    );
  }
}
