import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/network/api_client.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/live_classes_repository.dart';

class LiveClassesPage extends ConsumerStatefulWidget {
  const LiveClassesPage({super.key});
  @override
  ConsumerState<LiveClassesPage> createState() => _LiveClassesPageState();
}

class _LiveClassesPageState extends ConsumerState<LiveClassesPage> {
  int? _joining;

  @override
  Widget build(BuildContext context) => ModulePage(
    title: 'Online Class',
    subtitle: 'Join your scheduled live classes.',
    icon: Icons.videocam_rounded,
    colors: const [Color(0xffbc4749), Color(0xffe76f51)],
    children: [
      ref
          .watch(liveClassesProvider)
          .when(
            loading: () => const Padding(
              padding: EdgeInsets.symmetric(vertical: 60),
              child: Center(child: CircularProgressIndicator()),
            ),
            error: (error, _) => InfoRow(
              icon: Icons.refresh_rounded,
              title: 'Could not load live classes',
              subtitle: 'Tap to try again',
              color: const Color(0xffff6b6b),
              onTap: () => ref.invalidate(liveClassesProvider),
            ),
            data: (sessions) {
              if (sessions.isEmpty) {
                return const InfoRow(
                  icon: Icons.event_busy_rounded,
                  title: 'No live classes scheduled',
                  subtitle: 'Check back closer to class time.',
                  color: Color(0xff829ab1),
                  trailing: SizedBox.shrink(),
                );
              }
              return Column(
                children: sessions.map((session) {
                  final id = session['id'] as int;
                  return InfoRow(
                    icon: Icons.videocam_rounded,
                    title: session['title']?.toString() ?? 'Live class',
                    subtitle:
                        '${session['class_name'] ?? ''} · ${session['date']} · '
                        '${session['start_time']}-${session['end_time']}',
                    color: const Color(0xffe76f51),
                    trailing: _joining == id
                        ? const SizedBox.square(
                            dimension: 22,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : FilledButton(
                            onPressed: _joining == null
                                ? () => _join(context, id)
                                : null,
                            child: const Text('Join'),
                          ),
                  );
                }).toList(),
              );
            },
          ),
    ],
  );

  Future<void> _join(BuildContext context, int id) async {
    setState(() => _joining = id);
    try {
      final response = await ref
          .read(dioProvider)
          .get('live-classes/$id/join');
      final data = Map<String, dynamic>.from(response.data['data']);
      final joinUrl = data['join_url'] as String?;
      if (joinUrl == null) {
        if (context.mounted) {
          showModuleMessage(context, 'This class cannot be joined right now.');
        }
        return;
      }
      await launchUrl(Uri.parse(joinUrl), mode: LaunchMode.externalApplication);
    } on DioException catch (error) {
      if (context.mounted) {
        final data = error.response?.data;
        final message = data is Map && data['error'] is Map
            ? ((data['error'] as Map)['message'] ?? 'Could not join this class.')
                  .toString()
            : 'Could not join this class.';
        showModuleMessage(context, message);
      }
    } finally {
      if (mounted) setState(() => _joining = null);
    }
  }
}
