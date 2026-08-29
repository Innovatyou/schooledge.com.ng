import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/profile_repository.dart';

class SecurityPage extends ConsumerStatefulWidget {
  const SecurityPage({super.key});
  @override
  ConsumerState<SecurityPage> createState() => _SecurityPageState();
}

class _SecurityPageState extends ConsumerState<SecurityPage> {
  final _currentController = TextEditingController();
  final _newController = TextEditingController();
  bool _changingPassword = false;
  int? _revokingId;

  @override
  void dispose() {
    _currentController.dispose();
    _newController.dispose();
    super.dispose();
  }

  Future<void> _changePassword() async {
    final current = _currentController.text;
    final updated = _newController.text;
    if (current.isEmpty || updated.length < 8) {
      showModuleMessage(
        context,
        'Enter your current password and a new password of at least 8 characters.',
      );
      return;
    }
    setState(() => _changingPassword = true);
    try {
      await ref
          .read(dioProvider)
          .post(
            'profile/change-password',
            data: {'current_password': current, 'new_password': updated},
          );
      _currentController.clear();
      _newController.clear();
      if (mounted) showModuleMessage(context, 'Password changed.');
    } on DioException catch (error) {
      if (mounted) _showError(error);
    } finally {
      if (mounted) setState(() => _changingPassword = false);
    }
  }

  Future<void> _revoke(int deviceId) async {
    setState(() => _revokingId = deviceId);
    try {
      await ref.read(dioProvider).post('profile/sessions/$deviceId/revoke');
      ref.invalidate(sessionsProvider);
    } on DioException catch (error) {
      if (mounted) _showError(error);
    } finally {
      if (mounted) setState(() => _revokingId = null);
    }
  }

  void _showError(DioException error) {
    final data = error.response?.data;
    final message = data is Map && data['error'] is Map
        ? ((data['error'] as Map)['message'] ?? 'Something went wrong.')
              .toString()
        : 'Something went wrong.';
    showModuleMessage(context, message);
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Security')),
    body: ListView(
      padding: const EdgeInsets.all(20),
      children: [
        const Text(
          'Change password',
          style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16),
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _currentController,
          obscureText: true,
          decoration: const InputDecoration(labelText: 'Current password'),
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _newController,
          obscureText: true,
          decoration: const InputDecoration(labelText: 'New password'),
        ),
        const SizedBox(height: 16),
        FilledButton(
          onPressed: _changingPassword ? null : _changePassword,
          child: _changingPassword
              ? const SizedBox.square(
                  dimension: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Text('Change password'),
        ),
        const SizedBox(height: 28),
        const SectionTitle('Signed-in devices'),
        ref
            .watch(sessionsProvider)
            .when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (error, _) => InfoRow(
                icon: Icons.refresh_rounded,
                title: 'Could not load devices',
                subtitle: 'Tap to try again',
                color: const Color(0xffff6b6b),
                onTap: () => ref.invalidate(sessionsProvider),
              ),
              data: (sessions) {
                if (sessions.isEmpty) {
                  return const InfoRow(
                    icon: Icons.devices_rounded,
                    title: 'No other devices',
                    subtitle: 'Sessions will appear here as you sign in.',
                    color: Color(0xff829ab1),
                    trailing: SizedBox.shrink(),
                  );
                }
                return Column(
                  children: sessions.map((session) {
                    final id = session['id'] as int;
                    final isCurrent = session['is_current'] == true;
                    return InfoRow(
                      icon: Icons.smartphone_rounded,
                      title:
                          '${session['platform'] ?? 'Device'}${isCurrent ? ' (this device)' : ''}',
                      subtitle:
                          'Last active ${session['last_seen_at'] ?? session['created_at']}',
                      color: isCurrent
                          ? const Color(0xff00a896)
                          : const Color(0xff829ab1),
                      trailing: isCurrent
                          ? null
                          : _revokingId == id
                          ? const SizedBox.square(
                              dimension: 18,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : IconButton(
                              onPressed: () => _revoke(id),
                              icon: const Icon(Icons.logout_rounded),
                            ),
                    );
                  }).toList(),
                );
              },
            ),
      ],
    ),
  );
}
