import 'package:flutter/material.dart';
import '../../../core/widgets/module_ui.dart';

class LearningPage extends StatelessWidget {
  const LearningPage({super.key, required this.role});
  final String role;
  @override
  Widget build(BuildContext context) => ModulePage(
    title: 'Learning',
    subtitle: role.toLowerCase().contains('teacher')
        ? 'Plan lessons and guide every learner.'
        : 'Classes, resources and your daily timetable.',
    icon: Icons.menu_book_rounded,
    colors: const [Color(0xff725cff), Color(0xffa855f7)],
    children: [
      const Row(
        children: [
          StatTile(
            value: '6',
            label: 'Subjects',
            icon: Icons.auto_stories_rounded,
            color: Color(0xff725cff),
          ),
          SizedBox(width: 12),
          StatTile(
            value: '5',
            label: 'Today',
            icon: Icons.schedule_rounded,
            color: Color(0xff00a896),
          ),
        ],
      ),
      const SectionTitle('Today’s classes'),
      InfoRow(
        icon: Icons.calculate_rounded,
        title: 'Mathematics',
        subtitle: '8:00 AM · Room 12 · Algebra',
        color: const Color(0xff725cff),
        onTap: () =>
            showModuleMessage(context, 'Mathematics class details opened'),
      ),
      InfoRow(
        icon: Icons.science_rounded,
        title: 'Basic Science',
        subtitle: '10:00 AM · Laboratory · Energy',
        color: const Color(0xff00a896),
        onTap: () => showModuleMessage(context, 'Science resources opened'),
      ),
      InfoRow(
        icon: Icons.language_rounded,
        title: 'English Language',
        subtitle: '12:30 PM · Room 12 · Comprehension',
        color: const Color(0xffff8a5b),
        onTap: () => showModuleMessage(context, 'English class details opened'),
      ),
      const SectionTitle('Learning resources', action: 'View all'),
      InfoRow(
        icon: Icons.picture_as_pdf_rounded,
        title: 'Algebra revision notes',
        subtitle: 'PDF · 2.4 MB · Updated today',
        color: const Color(0xffff6b6b),
        onTap: () => showModuleMessage(
          context,
          'Secure download will use the school API',
        ),
      ),
    ],
  );
}
