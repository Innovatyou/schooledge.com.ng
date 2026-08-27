import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/theme/theme_mode_controller.dart';
import '../../../core/widgets/depth_card.dart';
import '../../../core/widgets/module_ui.dart';

class ProfilePage extends ConsumerWidget {
  const ProfilePage({super.key, required this.data, required this.onLogout});
  final Map<String, dynamic> data;
  final VoidCallback onLogout;
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final name = (data['name'] ?? 'SchoolEdge User').toString();
    final role = (data['membership']?['role']?['name'] ?? 'Member').toString();
    final school =
        (data['membership']?['school']?['school_name'] ?? 'My School')
            .toString();
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 120),
      children: [
        const Text(
          'Profile',
          style: TextStyle(
            fontSize: 30,
            fontWeight: FontWeight.w900,
            color: Color(0xff102a43),
          ),
        ),
        const SizedBox(height: 20),
        DepthCard(
          color: const Color(0xffe8eef8),
          child: Column(
            children: [
              CircleAvatar(
                radius: 42,
                backgroundColor: const Color(0xff163a70),
                child: Text(
                  name.isEmpty ? 'S' : name[0].toUpperCase(),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 32,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              const SizedBox(height: 12),
              Text(
                name,
                style: const TextStyle(
                  fontSize: 21,
                  fontWeight: FontWeight.w900,
                ),
              ),
              Text(
                '$role · $school',
                textAlign: TextAlign.center,
                style: const TextStyle(color: Color(0xff627d98)),
              ),
            ],
          ),
        ),
        const SectionTitle('Account'),
        InfoRow(
          icon: Icons.badge_rounded,
          title: 'Personal information',
          subtitle: 'Name, contact and school identity',
          color: const Color(0xff725cff),
          onTap: () =>
              showModuleMessage(context, 'Personal information opened'),
        ),
        InfoRow(
          icon: Icons.switch_account_rounded,
          title: 'School memberships',
          subtitle: 'Switch school or role',
          color: const Color(0xff00a896),
          onTap: () => showModuleMessage(context, 'Membership switcher opened'),
        ),
        InfoRow(
          icon: Icons.notifications_rounded,
          title: 'Notifications',
          subtitle: 'Messages, homework and payment alerts',
          color: const Color(0xffffa62b),
          onTap: () =>
              showModuleMessage(context, 'Notification preferences opened'),
        ),
        InfoRow(
          icon: Icons.dark_mode_rounded,
          title: 'Appearance',
          subtitle: _themeLabel(ref.watch(themeModeControllerProvider)),
          color: const Color(0xff163a70),
          onTap: () => _pickTheme(context, ref),
        ),
        InfoRow(
          icon: Icons.security_rounded,
          title: 'Security',
          subtitle: 'Password, OTP and signed-in devices',
          color: const Color(0xffff6b6b),
          onTap: () => showModuleMessage(context, 'Security settings opened'),
        ),
        const SectionTitle('SchoolEdge'),
        InfoRow(
          icon: Icons.help_rounded,
          title: 'Help & support',
          subtitle: 'Get assistance from your school',
          color: const Color(0xff168aad),
          onTap: () => showModuleMessage(context, 'Support centre opened'),
        ),
        const SizedBox(height: 8),
        OutlinedButton.icon(
          onPressed: onLogout,
          icon: const Icon(Icons.logout_rounded),
          label: const Text('Sign out'),
          style: OutlinedButton.styleFrom(
            foregroundColor: const Color(0xffd64545),
            padding: const EdgeInsets.all(16),
          ),
        ),
      ],
    );
  }

  String _themeLabel(ThemeMode mode) => switch (mode) {
    ThemeMode.light => 'Light',
    ThemeMode.dark => 'Dark',
    ThemeMode.system => 'Match system',
  };

  Future<void> _pickTheme(BuildContext context, WidgetRef ref) => showModalBottomSheet<void>(
    context: context,
    showDragHandle: true,
    builder: (sheetContext) => SafeArea(
      child: RadioGroup<ThemeMode>(
        groupValue: ref.watch(themeModeControllerProvider),
        onChanged: (value) {
          if (value != null) {
            ref.read(themeModeControllerProvider.notifier).setMode(value);
          }
          Navigator.of(sheetContext).pop();
        },
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            for (final mode in ThemeMode.values)
              RadioListTile<ThemeMode>(
                value: mode,
                title: Text(_themeLabel(mode)),
              ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    ),
  );
}
