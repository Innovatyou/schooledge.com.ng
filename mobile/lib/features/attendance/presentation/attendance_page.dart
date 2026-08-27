import 'package:flutter/material.dart';
import '../../../core/widgets/depth_card.dart';
import '../../../core/widgets/module_ui.dart';

class AttendancePage extends StatelessWidget {
  const AttendancePage({super.key, required this.role});
  final String role;
  @override
  Widget build(BuildContext context) => ModulePage(
    title: 'Attendance',
    subtitle: role.toLowerCase().contains('teacher')
        ? 'Take attendance and monitor your class.'
        : 'Your presence, punctuality and monthly record.',
    icon: Icons.fact_check_rounded,
    colors: const [Color(0xff00897b), Color(0xff16b39a)],
    children: [
      const Row(
        children: [
          StatTile(
            value: '94%',
            label: 'Present',
            icon: Icons.check_circle_rounded,
            color: Color(0xff00a896),
          ),
          SizedBox(width: 12),
          StatTile(
            value: '2',
            label: 'Absent',
            icon: Icons.cancel_rounded,
            color: Color(0xffff6b6b),
          ),
          SizedBox(width: 12),
          StatTile(
            value: '1',
            label: 'Late',
            icon: Icons.timelapse_rounded,
            color: Color(0xffffa62b),
          ),
        ],
      ),
      const SectionTitle('August overview'),
      const DepthCard(
        child: Column(
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Monthly attendance',
                  style: TextStyle(fontWeight: FontWeight.w900),
                ),
                Text(
                  '94%',
                  style: TextStyle(
                    fontWeight: FontWeight.w900,
                    color: Color(0xff00897b),
                  ),
                ),
              ],
            ),
            SizedBox(height: 14),
            ClipRRect(
              borderRadius: BorderRadius.all(Radius.circular(20)),
              child: LinearProgressIndicator(
                value: .94,
                minHeight: 14,
                backgroundColor: Color(0xffd9f2ed),
                color: Color(0xff00a896),
              ),
            ),
          ],
        ),
      ),
      const SectionTitle('Recent days'),
      const InfoRow(
        icon: Icons.check_rounded,
        title: 'Wednesday, 27 August',
        subtitle: 'Present · Checked in 7:42 AM',
        color: Color(0xff00a896),
        trailing: Text(
          'PRESENT',
          style: TextStyle(
            color: Color(0xff00897b),
            fontSize: 10,
            fontWeight: FontWeight.w900,
          ),
        ),
      ),
      const InfoRow(
        icon: Icons.check_rounded,
        title: 'Tuesday, 26 August',
        subtitle: 'Present · Checked in 7:38 AM',
        color: Color(0xff00a896),
        trailing: Text(
          'PRESENT',
          style: TextStyle(
            color: Color(0xff00897b),
            fontSize: 10,
            fontWeight: FontWeight.w900,
          ),
        ),
      ),
      const InfoRow(
        icon: Icons.schedule_rounded,
        title: 'Monday, 25 August',
        subtitle: 'Late · Checked in 8:14 AM',
        color: Color(0xffffa62b),
        trailing: Text(
          'LATE',
          style: TextStyle(
            color: Color(0xffb56b00),
            fontSize: 10,
            fontWeight: FontWeight.w900,
          ),
        ),
      ),
    ],
  );
}
