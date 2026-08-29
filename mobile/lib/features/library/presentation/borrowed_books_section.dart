import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/depth_card.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/library_repository.dart';

class BorrowedBooksSection extends ConsumerWidget {
  const BorrowedBooksSection({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) => ref
      .watch(libraryIssuesProvider)
      .when(
        loading: () => const Padding(
          padding: EdgeInsets.all(28),
          child: Center(child: CircularProgressIndicator()),
        ),
        error: (_, _) => InfoRow(
          icon: Icons.refresh_rounded,
          title: 'Could not load borrowed books',
          subtitle: 'Tap to try again',
          color: const Color(0xffff6b6b),
          onTap: () => ref.invalidate(libraryIssuesProvider),
        ),
        data: (issues) {
          if (issues.isEmpty) {
            return const InfoRow(
              icon: Icons.library_books_outlined,
              title: 'No collected books',
              subtitle: 'Books issued by your school will appear here.',
              color: Color(0xff829ab1),
              trailing: SizedBox.shrink(),
            );
          }
          return Column(
            children: issues
                .map((issue) => _BorrowedBookCard(issue: issue))
                .toList(),
          );
        },
      );
}

class _BorrowedBookCard extends StatelessWidget {
  const _BorrowedBookCard({required this.issue});
  final Map<String, dynamic> issue;

  @override
  Widget build(BuildContext context) {
    final status = issue['status']?.toString() ?? 'issued';
    final color = switch (status) {
      'returned' => const Color(0xff00a896),
      'lost' => const Color(0xffd64545),
      'overdue' => const Color(0xffff6b35),
      'rejected' => const Color(0xffd64545),
      'pending' => const Color(0xffffa62b),
      _ => const Color(0xff168aad),
    };
    final fine = (issue['outstanding_fine'] as num?)?.toDouble() ?? 0;
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: DepthCard(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 48,
                  height: 58,
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: .13),
                    borderRadius: BorderRadius.circular(13),
                  ),
                  child: Icon(Icons.menu_book_rounded, color: color),
                ),
                const SizedBox(width: 13),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        issue['title']?.toString() ?? 'Book',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      Text(
                        issue['author']?.toString() ?? 'Unknown author',
                        style: const TextStyle(
                          color: Color(0xff627d98),
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 6,
                  ),
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: .13),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    _statusLabel(status),
                    style: TextStyle(
                      color: color,
                      fontSize: 10,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
              ],
            ),
            const Divider(height: 26),
            Row(
              children: [
                Expanded(
                  child: _DateValue(
                    label: 'Collected',
                    value: issue['issued_date']?.toString(),
                  ),
                ),
                Expanded(
                  child: _DateValue(
                    label: issue['is_returned'] == true
                        ? 'Returned'
                        : 'Return by',
                    value: issue['is_returned'] == true
                        ? issue['return_date']?.toString()
                        : issue['due_date']?.toString(),
                  ),
                ),
              ],
            ),
            if (fine > 0) ...[
              const SizedBox(height: 12),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(11),
                decoration: BoxDecoration(
                  color: const Color(0xffffece8),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '${status == 'lost' ? 'Lost-book fine' : 'Outstanding fine'}: ₦${fine.toStringAsFixed(2)}',
                  style: const TextStyle(
                    color: Color(0xffb9382e),
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  String _statusLabel(String status) => switch (status) {
    'returned' => 'RETURNED',
    'lost' => 'LOST',
    'overdue' => 'NOT RETURNED',
    'rejected' => 'REJECTED',
    'pending' => 'PENDING',
    _ => 'COLLECTED',
  };
}

class _DateValue extends StatelessWidget {
  const _DateValue({required this.label, required this.value});
  final String label;
  final String? value;
  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(
        label,
        style: const TextStyle(
          color: Color(0xff829ab1),
          fontSize: 11,
          fontWeight: FontWeight.w700,
        ),
      ),
      const SizedBox(height: 3),
      Text(
        value?.isNotEmpty == true ? value! : '—',
        style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 13),
      ),
    ],
  );
}
