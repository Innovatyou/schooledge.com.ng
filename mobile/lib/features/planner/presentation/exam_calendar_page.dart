import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/widgets/depth_card.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/exam_calendar_repository.dart';

class ExamCalendarPage extends ConsumerWidget {
  const ExamCalendarPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) => ModulePage(
    title: 'Exam calendar',
    subtitle: 'Every upcoming exam date, time and hall.',
    icon: Icons.event_note_rounded,
    colors: const [Color(0xffff9f1c), Color(0xffffc857)],
    children: [
      ref
          .watch(examCalendarProvider)
          .when(
            loading: () => const Padding(
              padding: EdgeInsets.symmetric(vertical: 60),
              child: Center(child: CircularProgressIndicator()),
            ),
            error: (error, _) => InfoRow(
              icon: Icons.refresh_rounded,
              title: 'Could not load the exam calendar',
              subtitle: 'Tap to try again',
              color: const Color(0xffff6b6b),
              onTap: () => ref.invalidate(examCalendarProvider),
            ),
            data: (exams) => _ExamList(exams: exams),
          ),
    ],
  );
}

class _ExamList extends StatelessWidget {
  const _ExamList({required this.exams});
  final List<Map<String, dynamic>> exams;

  @override
  Widget build(BuildContext context) {
    if (exams.isEmpty) {
      return const InfoRow(
        icon: Icons.event_available_rounded,
        title: 'No exams scheduled',
        subtitle: 'Your school has not published an exam schedule yet.',
        color: Color(0xff829ab1),
        trailing: SizedBox.shrink(),
      );
    }
    final today = DateTime.now();
    final sorted = [...exams]
      ..sort(
        (a, b) =>
            (a['exam_date'] as String).compareTo(b['exam_date'] as String),
      );
    final next = sorted.firstWhere(
      (exam) =>
          !_dateOf(exam).isBefore(DateTime(today.year, today.month, today.day)),
      orElse: () => sorted.first,
    );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        DepthCard(
          color: const Color(0xfffff4d6),
          child: Row(
            children: [
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xffffd166), Color(0xffff8a5b)],
                  ),
                  borderRadius: BorderRadius.circular(18),
                ),
                child: const Icon(
                  Icons.event_note_rounded,
                  color: Colors.white,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Next exam',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: Color(0xff7b6131),
                      ),
                    ),
                    Text(
                      '${next['exam_name']} · ${next['subject_name']}',
                      style: const TextStyle(fontWeight: FontWeight.w900),
                    ),
                    Text(
                      '${DateFormat('EEE, d MMM').format(_dateOf(next))} · ${next['time_start']}-${next['time_end']} · ${next['hall_name'] ?? 'Hall TBA'}',
                      style: const TextStyle(color: Color(0xff7b6131)),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        const SectionTitle('All exams'),
        ...sorted.asMap().entries.map((entry) {
          final exam = entry.value;
          final isPast = _dateOf(
            exam,
          ).isBefore(DateTime(today.year, today.month, today.day));
          return InfoRow(
            icon: Icons.menu_book_rounded,
            title: '${exam['subject_name']}',
            subtitle:
                '${exam['exam_name']} · ${DateFormat('EEE, d MMM').format(_dateOf(exam))} · ${exam['time_start']}-${exam['time_end']}'
                '${exam['hall_name'] != null ? ' · ${exam['hall_name']}' : ''}'
                '${exam['class_name'] != null ? ' · ${exam['class_name']} ${exam['section_name'] ?? ''}' : ''}',
            color: isPast ? const Color(0xff829ab1) : const Color(0xffffa62b),
            trailing: const SizedBox.shrink(),
          ).animate(delay: (entry.key * 40).ms).fadeIn(duration: 280.ms);
        }),
      ],
    );
  }

  DateTime _dateOf(Map<String, dynamic> exam) =>
      DateTime.parse(exam['exam_date'] as String);
}
