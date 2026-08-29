import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/homework_repository.dart';
import 'homework_detail_page.dart';
import '../../../core/navigation/page_transitions.dart';

class HomeworkPage extends ConsumerWidget {
  const HomeworkPage({super.key, required this.role});
  final String role;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final teacher = role.toLowerCase().contains('teacher');
    return ModulePage(
      title: 'Homework',
      subtitle: teacher
          ? 'Create, review and evaluate class work.'
          : 'Stay ahead of assignments and submissions.',
      icon: Icons.assignment_rounded,
      colors: const [Color(0xffff5f6d), Color(0xffff8a5b)],
      children: [
        ref
            .watch(homeworkListProvider)
            .when(
              loading: () => const Padding(
                padding: EdgeInsets.symmetric(vertical: 60),
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => InfoRow(
                icon: Icons.refresh_rounded,
                title: 'Could not load homework',
                subtitle: 'Tap to try again',
                color: const Color(0xffff6b6b),
                onTap: () => ref.invalidate(homeworkListProvider),
              ),
              data: (items) {
                if (items.isEmpty) {
                  return const InfoRow(
                    icon: Icons.assignment_turned_in_rounded,
                    title: 'No homework yet',
                    subtitle: 'Assignments will appear here once posted.',
                    color: Color(0xff829ab1),
                    trailing: SizedBox.shrink(),
                  );
                }
                return Column(
                  children: items.asMap().entries.map((entry) {
                    final index = entry.key;
                    final item = entry.value;
                    final submitted = item['submitted'] == true;
                    return InfoRow(
                          icon: Icons.menu_book_rounded,
                          title: item['subject']?.toString() ?? 'Homework',
                          subtitle: teacher
                              ? '${item['class_name'] ?? ''} ${item['section_name'] ?? ''} · Due ${item['due_date']}'
                              : 'Due ${item['due_date']}${submitted ? ' · Submitted' : ''}',
                          color: teacher
                              ? const Color(0xff725cff)
                              : (submitted
                                    ? const Color(0xff00a896)
                                    : const Color(0xffff6b6b)),
                          trailing: teacher
                              ? null
                              : Icon(
                                  submitted
                                      ? Icons.check_circle_rounded
                                      : Icons.pending_actions_rounded,
                                  color: submitted
                                      ? const Color(0xff00a896)
                                      : const Color(0xffff6b6b),
                                ),
                          onTap: () => Navigator.of(context).push(
                            moduleRoute<void>(
                              HomeworkDetailPage(
                                homeworkId: item['id'] as int,
                                isTeacher: teacher,
                                initial: item,
                              ),
                            ),
                          ),
                        )
                        .animate(delay: (index * 40).ms)
                        .fadeIn(duration: 280.ms)
                        .slideX(begin: .05, end: 0);
                  }).toList(),
                );
              },
            ),
      ],
    );
  }
}
