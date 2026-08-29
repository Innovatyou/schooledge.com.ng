import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/safety_repository.dart';

class SafetyAlertsPage extends ConsumerWidget {
  const SafetyAlertsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) => ModulePage(
    title: 'Safety alerts',
    subtitle: 'Location shares and SOS alerts you\'re authorized to see.',
    icon: Icons.shield_rounded,
    colors: const [Color(0xffd64545), Color(0xffff8a5b)],
    children: [
      ref
          .watch(safetyAlertsProvider)
          .when(
            loading: () => const Padding(
              padding: EdgeInsets.symmetric(vertical: 60),
              child: Center(child: CircularProgressIndicator()),
            ),
            error: (error, _) => InfoRow(
              icon: Icons.refresh_rounded,
              title: 'Could not load alerts',
              subtitle: 'Tap to try again',
              color: const Color(0xffff6b6b),
              onTap: () => ref.invalidate(safetyAlertsProvider),
            ),
            data: (alerts) {
              if (alerts.isEmpty) {
                return const InfoRow(
                  icon: Icons.verified_user_rounded,
                  title: 'No alerts',
                  subtitle: 'Location shares and SOS alerts will appear here.',
                  color: Color(0xff00a896),
                  trailing: SizedBox.shrink(),
                );
              }
              return Column(
                children: alerts
                    .map((alert) => _AlertCard(alert: alert))
                    .toList(),
              );
            },
          ),
    ],
  );
}

class _AlertCard extends ConsumerStatefulWidget {
  const _AlertCard({required this.alert});
  final Map<String, dynamic> alert;

  @override
  ConsumerState<_AlertCard> createState() => _AlertCardState();
}

class _AlertCardState extends ConsumerState<_AlertCard> {
  bool _acknowledging = false;

  Future<void> _acknowledge() async {
    setState(() => _acknowledging = true);
    try {
      await ref
          .read(safetyRepositoryProvider)
          .acknowledge(widget.alert['id'] as int);
      ref.invalidate(safetyAlertsProvider);
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Could not acknowledge this alert.')),
        );
      }
    } finally {
      if (mounted) setState(() => _acknowledging = false);
    }
  }

  Future<void> _openInMaps() async {
    final lat = widget.alert['latitude'];
    final lng = widget.alert['longitude'];
    final uri = Uri.parse(
      'https://www.google.com/maps/search/?api=1&query=$lat,$lng',
    );
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    final alert = widget.alert;
    final isSos = alert['alert_type'] == 'sos';
    final status = alert['status'] as String;
    final createdAt = DateTime.tryParse(alert['created_at'] as String);
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Container(
        decoration: BoxDecoration(
          color: isSos
              ? const Color(0xfffdecea)
              : Theme.of(context).colorScheme.surfaceContainerHigh,
          borderRadius: BorderRadius.circular(24),
          border: Border.all(
            color: isSos ? const Color(0xffff6b6b) : Colors.transparent,
            width: isSos ? 1.5 : 0,
          ),
        ),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(
                  isSos ? Icons.warning_rounded : Icons.share_location_rounded,
                  color: isSos
                      ? const Color(0xffd64545)
                      : const Color(0xff168aad),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    isSos ? 'SOS' : 'Location shared',
                    style: TextStyle(
                      fontWeight: FontWeight.w900,
                      color: isSos ? const Color(0xffd64545) : null,
                    ),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 3,
                  ),
                  decoration: BoxDecoration(
                    color: status == 'acknowledged'
                        ? const Color(0xff00a896).withValues(alpha: .15)
                        : const Color(0xffffa62b).withValues(alpha: .15),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    status.toUpperCase(),
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w900,
                      color: status == 'acknowledged'
                          ? const Color(0xff00a896)
                          : const Color(0xffffa62b),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              '${alert['sender_name']} · ${alert['sender_role'] == 'student' ? 'Student' : 'Teacher'}',
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
            if (createdAt != null)
              Text(
                DateFormat('EEE, d MMM · h:mm a').format(createdAt),
                style: const TextStyle(color: Color(0xff627d98), fontSize: 12),
              ),
            if ((alert['note'] as String?)?.isNotEmpty ?? false)
              Padding(
                padding: const EdgeInsets.only(top: 6),
                child: Text(alert['note'] as String),
              ),
            const SizedBox(height: 12),
            Row(
              children: [
                OutlinedButton.icon(
                  onPressed: _openInMaps,
                  icon: const Icon(Icons.map_rounded, size: 18),
                  label: const Text('Open in Maps'),
                ),
                const SizedBox(width: 8),
                if (status != 'acknowledged')
                  FilledButton.icon(
                    onPressed: _acknowledging ? null : _acknowledge,
                    icon: _acknowledging
                        ? const SizedBox(
                            width: 14,
                            height: 14,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.check_rounded, size: 18),
                    label: const Text('Acknowledge'),
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
