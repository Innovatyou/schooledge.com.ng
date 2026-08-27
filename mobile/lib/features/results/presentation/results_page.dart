import 'package:flutter/material.dart';
import '../../../core/widgets/depth_card.dart';
import '../../../core/widgets/module_ui.dart';
import 'report_download_section.dart';

class ResultsPage extends StatelessWidget {
  const ResultsPage({super.key, required this.role});
  final String role;
  @override
  Widget build(BuildContext context) => ModulePage(
    title: 'Results',
    subtitle: 'Performance, grades and academic progress.',
    icon: Icons.workspace_premium_rounded,
    colors: const [Color(0xffff9f1c), Color(0xffffc857)],
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
              child: const Text(
                'A',
                style: TextStyle(
                  fontSize: 34,
                  fontWeight: FontWeight.w900,
                  color: Color(0xff7b4f00),
                ),
              ),
            ),
            const SizedBox(width: 16),
            const Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Excellent progress!',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900),
                  ),
                  SizedBox(height: 4),
                  Text(
                    'Term average 82.4% · Position 4th',
                    style: TextStyle(color: Color(0xff7b6131)),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
      const SectionTitle('Subject performance'),
      const _SubjectResult('Mathematics', 88, Color(0xff725cff)),
      const _SubjectResult('English Language', 84, Color(0xffff6b6b)),
      const _SubjectResult('Basic Science', 79, Color(0xff00a896)),
      const _SubjectResult('Social Studies', 76, Color(0xff168aad)),
      const SectionTitle('Published reports'),
      const ReportDownloadSection(),
    ],
  );
}

class _SubjectResult extends StatelessWidget {
  const _SubjectResult(this.name, this.score, this.color);
  final String name;
  final int score;
  final Color color;
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: DepthCard(
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(name, style: const TextStyle(fontWeight: FontWeight.w900)),
              Text(
                '$score%',
                style: TextStyle(fontWeight: FontWeight.w900, color: color),
              ),
            ],
          ),
          const SizedBox(height: 11),
          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: LinearProgressIndicator(
              value: score / 100,
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
