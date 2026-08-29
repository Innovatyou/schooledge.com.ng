import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/widgets/depth_card.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/unified_calendar_provider.dart';
import '../domain/calendar_item.dart';

class CalendarMonthPage extends ConsumerStatefulWidget {
  const CalendarMonthPage({super.key});

  @override
  ConsumerState<CalendarMonthPage> createState() => _CalendarMonthPageState();
}

class _CalendarMonthPageState extends ConsumerState<CalendarMonthPage> {
  late DateTime _month = DateTime(DateTime.now().year, DateTime.now().month);
  DateTime _selectedDay = DateTime.now();

  void _changeMonth(int delta) => setState(() {
    _month = DateTime(_month.year, _month.month + delta);
  });

  @override
  Widget build(BuildContext context) => ModulePage(
    title: 'Calendar',
    subtitle: 'Classes, exams and school events in one view.',
    icon: Icons.calendar_month_rounded,
    colors: const [Color(0xff163a70), Color(0xff2a9d8f)],
    children: [
      ref
          .watch(calendarItemsProvider)
          .when(
            loading: () => const Padding(
              padding: EdgeInsets.symmetric(vertical: 60),
              child: Center(child: CircularProgressIndicator()),
            ),
            error: (error, _) => InfoRow(
              icon: Icons.refresh_rounded,
              title: 'Could not load the calendar',
              subtitle: 'Tap to try again',
              color: const Color(0xffff6b6b),
              onTap: () => ref.invalidate(calendarItemsProvider),
            ),
            data: (items) => _buildCalendar(context, items),
          ),
    ],
  );

  Widget _buildCalendar(BuildContext context, List<CalendarItem> items) {
    final scheme = Theme.of(context).colorScheme;
    final firstOfMonth = DateTime(_month.year, _month.month);
    final leadingBlanks = firstOfMonth.weekday % 7; // Sunday-first grid
    final daysInMonth = DateTime(_month.year, _month.month + 1, 0).day;
    final today = DateTime.now();

    List<CalendarItem> itemsOn(DateTime day) =>
        items.where((item) => item.isSameDay(day)).toList();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        DepthCard(
          child: Column(
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  IconButton(
                    onPressed: () => _changeMonth(-1),
                    icon: const Icon(Icons.chevron_left_rounded),
                  ),
                  Text(
                    DateFormat('MMMM y').format(_month),
                    style: const TextStyle(
                      fontWeight: FontWeight.w900,
                      fontSize: 17,
                    ),
                  ),
                  IconButton(
                    onPressed: () => _changeMonth(1),
                    icon: const Icon(Icons.chevron_right_rounded),
                  ),
                ],
              ),
              Row(
                children: const ['S', 'M', 'T', 'W', 'T', 'F', 'S']
                    .map(
                      (label) => Expanded(
                        child: Center(
                          child: Text(
                            label,
                            style: const TextStyle(
                              fontWeight: FontWeight.w700,
                              color: Color(0xff829ab1),
                              fontSize: 12,
                            ),
                          ),
                        ),
                      ),
                    )
                    .toList(),
              ),
              const SizedBox(height: 6),
              GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 7,
                ),
                itemCount: leadingBlanks + daysInMonth,
                itemBuilder: (context, index) {
                  if (index < leadingBlanks) return const SizedBox.shrink();
                  final day = DateTime(
                    _month.year,
                    _month.month,
                    index - leadingBlanks + 1,
                  );
                  final dayItems = itemsOn(day);
                  final isToday =
                      day.year == today.year &&
                      day.month == today.month &&
                      day.day == today.day;
                  final isSelected =
                      day.year == _selectedDay.year &&
                      day.month == _selectedDay.month &&
                      day.day == _selectedDay.day;
                  return GestureDetector(
                    onTap: () => setState(() => _selectedDay = day),
                    child: Container(
                      margin: const EdgeInsets.all(2),
                      decoration: BoxDecoration(
                        color: isSelected
                            ? const Color(0xff163a70)
                            : (isToday
                                  ? scheme.primary.withValues(alpha: .12)
                                  : null),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            '${day.day}',
                            style: TextStyle(
                              fontWeight: isToday
                                  ? FontWeight.w900
                                  : FontWeight.w600,
                              color: isSelected
                                  ? Colors.white
                                  : scheme.onSurface,
                            ),
                          ),
                          if (dayItems.isNotEmpty)
                            Padding(
                              padding: const EdgeInsets.only(top: 2),
                              child: Wrap(
                                spacing: 2,
                                children: dayItems
                                    .take(3)
                                    .map(
                                      (item) => Container(
                                        width: 5,
                                        height: 5,
                                        decoration: BoxDecoration(
                                          shape: BoxShape.circle,
                                          color: item.color,
                                        ),
                                      ),
                                    )
                                    .toList(),
                              ),
                            ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ],
          ),
        ),
        const SectionTitle('On this day'),
        ...() {
          final selected = itemsOn(_selectedDay);
          if (selected.isEmpty) {
            return [
              const InfoRow(
                icon: Icons.event_available_rounded,
                title: 'Nothing scheduled',
                subtitle: 'No exams or events on this day.',
                color: Color(0xff829ab1),
                trailing: SizedBox.shrink(),
              ),
            ];
          }
          return selected
              .map(
                (item) => InfoRow(
                  icon: item.icon,
                  title: item.title,
                  subtitle: item.subtitle,
                  color: item.color,
                  trailing: const SizedBox.shrink(),
                ),
              )
              .toList();
        }(),
      ],
    );
  }
}
