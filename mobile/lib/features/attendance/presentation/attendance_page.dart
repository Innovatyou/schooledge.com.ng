import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/depth_card.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/attendance_repository.dart';
import 'attendance_qr_pass.dart';
import 'teacher_attendance_page.dart';

class AttendancePage extends ConsumerWidget {
  const AttendancePage({super.key, required this.role});
  final String role;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    if (role.toLowerCase().contains('teacher')) {
      return const TeacherAttendancePage();
    }
    final summary = ref.watch(attendanceSummaryProvider);
    return ModulePage(
      title: 'Attendance',
      subtitle: 'Your presence, punctuality and monthly record.',
      icon: Icons.fact_check_rounded,
      colors: const [Color(0xff00897b), Color(0xff16b39a)],
      children: [
        const AttendanceQrPass(),
        const SectionTitle('Attendance record'),
        summary.when(
          loading: () => const Padding(
            padding: EdgeInsets.symmetric(vertical: 60),
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (error, _) => InfoRow(
            icon: Icons.refresh_rounded,
            title: 'Could not load attendance',
            subtitle: 'Tap to try again',
            color: const Color(0xffff6b6b),
            onTap: () => ref.invalidate(attendanceSummaryProvider),
          ),
          data: (data) => _buildSummary(context, data),
        ),
      ],
    );
  }

  Widget _buildSummary(BuildContext context, Map<String, dynamic> data) {
    final present = data['present'] as int;
    final absent = data['absent'] as int;
    final late = data['late'] as int;
    final percent = (data['present_percent'] as num).toDouble();
    final recent = (data['recent'] as List).cast<Map<String, dynamic>>();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            StatTile(
              value: '$present',
              label: 'Present',
              icon: Icons.check_circle_rounded,
              color: const Color(0xff00a896),
            ),
            const SizedBox(width: 12),
            StatTile(
              value: '$absent',
              label: 'Absent',
              icon: Icons.cancel_rounded,
              color: const Color(0xffff6b6b),
            ),
            const SizedBox(width: 12),
            StatTile(
              value: '$late',
              label: 'Late',
              icon: Icons.timelapse_rounded,
              color: const Color(0xffffa62b),
            ),
          ],
        ),
        const SectionTitle('Overview'),
        DepthCard(
          child: Column(
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Attendance rate',
                    style: TextStyle(fontWeight: FontWeight.w900),
                  ),
                  Text(
                    '${percent.toStringAsFixed(1)}%',
                    style: const TextStyle(
                      fontWeight: FontWeight.w900,
                      color: Color(0xff00897b),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              ClipRRect(
                borderRadius: const BorderRadius.all(Radius.circular(20)),
                child: LinearProgressIndicator(
                  value: percent / 100,
                  minHeight: 14,
                  backgroundColor: const Color(0xffd9f2ed),
                  color: const Color(0xff00a896),
                ),
              ),
            ],
          ),
        ),
        const SectionTitle('Recent days'),
        if (recent.isEmpty)
          const InfoRow(
            icon: Icons.event_busy_rounded,
            title: 'No attendance recorded yet',
            subtitle:
                'Records will appear here once your school takes attendance.',
            color: Color(0xff829ab1),
            trailing: SizedBox.shrink(),
          )
        else
          ...recent.map(
            (day) => InfoRow(
              icon: _iconFor(day['status'] as String),
              title: day['date'] as String,
              subtitle:
                  (day['remark'] as String?) ??
                  _labelFor(day['status'] as String),
              color: _colorFor(day['status'] as String),
              trailing: Text(
                _labelFor(day['status'] as String).toUpperCase(),
                style: TextStyle(
                  color: _colorFor(day['status'] as String),
                  fontSize: 10,
                  fontWeight: FontWeight.w900,
                ),
              ),
            ),
          ),
      ],
    );
  }

  IconData _iconFor(String status) => switch (status) {
    'P' => Icons.check_rounded,
    'L' => Icons.schedule_rounded,
    _ => Icons.close_rounded,
  };

  String _labelFor(String status) => switch (status) {
    'P' => 'Present',
    'L' => 'Late',
    _ => 'Absent',
  };

  Color _colorFor(String status) => switch (status) {
    'P' => const Color(0xff00a896),
    'L' => const Color(0xffffa62b),
    _ => const Color(0xffff6b6b),
  };
}
