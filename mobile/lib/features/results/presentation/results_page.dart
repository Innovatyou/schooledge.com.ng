import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/depth_card.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/results_repository.dart';
import 'report_download_section.dart';

class ResultsPage extends ConsumerStatefulWidget {
  const ResultsPage({super.key, required this.role});
  final String role;

  @override
  ConsumerState<ResultsPage> createState() => _ResultsPageState();
}

class _ResultsPageState extends ConsumerState<ResultsPage> {
  int? _selectedExamId;

  @override
  Widget build(BuildContext context) => ModulePage(
    title: 'Results',
    subtitle: 'Performance, grades and academic progress.',
    icon: Icons.workspace_premium_rounded,
    colors: const [Color(0xffff9f1c), Color(0xffffc857)],
    children: [
      ref
          .watch(publishedExamsProvider)
          .when(
            loading: () => const Padding(
              padding: EdgeInsets.symmetric(vertical: 60),
              child: Center(child: CircularProgressIndicator()),
            ),
            error: (error, _) => InfoRow(
              icon: Icons.refresh_rounded,
              title: 'Could not load results',
              subtitle: 'Tap to try again',
              color: const Color(0xffff6b6b),
              onTap: () => ref.invalidate(publishedExamsProvider),
            ),
            data: (exams) {
              if (exams.isEmpty) {
                return const InfoRow(
                  icon: Icons.hourglass_empty_rounded,
                  title: 'No published results yet',
                  subtitle: 'Your school has not published results yet.',
                  color: Color(0xff829ab1),
                  trailing: SizedBox.shrink(),
                );
              }
              final examId = _selectedExamId ?? (exams.first['id'] as int);
              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (exams.length > 1)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: DropdownButtonFormField<int>(
                        initialValue: examId,
                        items: exams
                            .map(
                              (exam) => DropdownMenuItem(
                                value: exam['id'] as int,
                                child: Text(exam['name']?.toString() ?? ''),
                              ),
                            )
                            .toList(),
                        onChanged: (value) => setState(() => _selectedExamId = value),
                      ),
                    ),
                  _buildExamBreakdown(examId),
                ],
              );
            },
          ),
      const SectionTitle('Published reports'),
      const ReportDownloadSection(),
    ],
  );

  Widget _buildExamBreakdown(int examId) => Consumer(
    builder: (context, ref, _) => ref
        .watch(examResultProvider(examId))
        .when(
          loading: () => const Padding(
            padding: EdgeInsets.symmetric(vertical: 40),
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (error, _) => InfoRow(
            icon: Icons.refresh_rounded,
            title: 'Could not load this exam',
            subtitle: 'Tap to try again',
            color: const Color(0xffff6b6b),
            onTap: () => ref.invalidate(examResultProvider(examId)),
          ),
          data: (result) {
            final average = result['average_percent'] as num?;
            final subjects = (result['subjects'] as List).cast<Map<String, dynamic>>();
            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                DepthCard(
                  color: const Color(0xfffff4d6),
                  child: Row(
                    children: [
                      Container(
                        width: 72,
                        height: 72,
                        alignment: Alignment.center,
                        decoration: const BoxDecoration(
                          color: Color(0xffffd166),
                          shape: BoxShape.circle,
                        ),
                        child: Text(
                          average != null ? '${average.round()}%' : '-',
                          style: const TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.w900,
                            color: Color(0xff7b4f00),
                          ),
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              result['exam']?['name']?.toString() ?? 'Exam',
                              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              result['rank'] != null
                                  ? 'Position ${result['rank']}'
                                  : 'Term average ${average ?? '-'}%',
                              style: const TextStyle(color: Color(0xff7b6131)),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SectionTitle('Subject performance'),
                if (subjects.isEmpty)
                  const InfoRow(
                    icon: Icons.info_outline_rounded,
                    title: 'No subject marks recorded',
                    subtitle: 'Marks for this exam have not been entered yet.',
                    color: Color(0xff829ab1),
                    trailing: SizedBox.shrink(),
                  )
                else
                  ...subjects.map(
                    (subject) => _SubjectResult(
                      name: subject['name']?.toString() ?? 'Subject',
                      percent: (subject['percent'] as num?)?.toDouble(),
                      grade: (subject['grade'] as Map?)?['name']?.toString(),
                    ),
                  ),
              ],
            );
          },
        ),
  );
}

class _SubjectResult extends StatelessWidget {
  const _SubjectResult({required this.name, required this.percent, this.grade});
  final String name;
  final double? percent;
  final String? grade;

  @override
  Widget build(BuildContext context) {
    final color = switch (percent) {
      null => const Color(0xff829ab1),
      > 70 => const Color(0xff00a896),
      > 50 => const Color(0xff168aad),
      _ => const Color(0xffff6b6b),
    };
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: DepthCard(
        child: Column(
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(name, style: const TextStyle(fontWeight: FontWeight.w900)),
                Text(
                  percent != null ? '${percent!.toStringAsFixed(0)}%${grade != null ? ' · $grade' : ''}' : '-',
                  style: TextStyle(fontWeight: FontWeight.w900, color: color),
                ),
              ],
            ),
            const SizedBox(height: 11),
            ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: LinearProgressIndicator(
                value: (percent ?? 0) / 100,
                minHeight: 9,
                backgroundColor: color.withValues(alpha: .12),
                color: color,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
