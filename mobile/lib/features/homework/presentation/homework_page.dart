import 'package:flutter/material.dart';
import '../../../core/widgets/module_ui.dart';

class HomeworkPage extends StatelessWidget {
  const HomeworkPage({super.key, required this.role});
  final String role;
  @override
  Widget build(BuildContext context) {
    final teacher = role.toLowerCase().contains('teacher');
    return ModulePage(
      title: 'Homework',
      subtitle: teacher
          ? 'Create, review and evaluate class work.'
          : 'Stay ahead of assignments and submissions.',
      icon: Icons.assignment_rounded,
      colors: const [Color(0xffff5f6d), Color(0xffff8a5b)],
      children: [
        Row(
          children: [
            StatTile(
              value: teacher ? '12' : '3',
              label: teacher ? 'To review' : 'Active',
              icon: Icons.pending_actions_rounded,
              color: const Color(0xffff6b6b),
            ),
            const SizedBox(width: 12),
            StatTile(
              value: teacher ? '28' : '14',
              label: teacher ? 'Submitted' : 'Completed',
              icon: Icons.task_alt_rounded,
              color: const Color(0xff00a896),
            ),
          ],
        ),
        SectionTitle(teacher ? 'Needs review' : 'Due soon'),
        InfoRow(
          icon: Icons.calculate_rounded,
          title: 'Algebra worksheet',
          subtitle: teacher
              ? '12 submissions · Due today'
              : 'Mathematics · Due today, 6:00 PM',
          color: const Color(0xff725cff),
          trailing: const _DueBadge('TODAY'),
          onTap: () => showModuleMessage(
            context,
            teacher
                ? 'Evaluation workspace opened'
                : 'Assignment details opened',
          ),
        ),
        InfoRow(
          icon: Icons.science_rounded,
          title: 'Forms of energy',
          subtitle: teacher
              ? '8 submissions · Due tomorrow'
              : 'Basic Science · Due tomorrow',
          color: const Color(0xff00a896),
          trailing: const _DueBadge('1 DAY'),
          onTap: () => showModuleMessage(context, 'Homework details opened'),
        ),
        const SectionTitle('Recently completed'),
        const InfoRow(
          icon: Icons.check_circle_rounded,
          title: 'Reading comprehension',
          subtitle: 'English · Submitted · Score 18/20',
          color: Color(0xff00a896),
          trailing: Icon(Icons.verified_rounded, color: Color(0xff00a896)),
        ),
      ],
    );
  }
}

class _DueBadge extends StatelessWidget {
  const _DueBadge(this.text);
  final String text;
  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
    decoration: BoxDecoration(
      color: const Color(0xffffe6e6),
      borderRadius: BorderRadius.circular(20),
    ),
    child: Text(
      text,
      style: const TextStyle(
        color: Color(0xffd64545),
        fontSize: 10,
        fontWeight: FontWeight.w900,
      ),
    ),
  );
}
