import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/widgets/depth_card.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/events_repository.dart';
import '../data/timetable_repository.dart';
import 'event_detail_page.dart';

class PlannerPage extends ConsumerStatefulWidget {
  const PlannerPage({super.key});
  @override
  ConsumerState<PlannerPage> createState() => _PlannerPageState();
}

class _PlannerPageState extends ConsumerState<PlannerPage> {
  late DateTime _selectedDate = DateTime.now();

  @override
  Widget build(BuildContext context) {
    final weekStart = _selectedDate.subtract(Duration(days: _selectedDate.weekday % 7));
    final weekday = DateFormat('EEEE').format(_selectedDate).toLowerCase();
    final timetable = ref.watch(timetableProvider(weekday));

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 120),
      children: [
        Text(
          'Planner',
          style: TextStyle(
            fontSize: 30,
            fontWeight: FontWeight.w900,
            color: Theme.of(context).colorScheme.onSurface,
          ),
        ),
        const SizedBox(height: 6),
        Text(
          DateFormat('EEEE, d MMMM').format(_selectedDate),
          style: const TextStyle(color: Color(0xff627d98)),
        ),
        const SizedBox(height: 20),
        SizedBox(
          height: 78,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: 7,
            separatorBuilder: (_, _) => const SizedBox(width: 9),
            itemBuilder: (_, index) {
              final date = weekStart.add(Duration(days: index));
              final selected = _isSameDay(date, _selectedDate);
              return GestureDetector(
                onTap: () => setState(() => _selectedDate = date),
                child: Container(
                  width: 58,
                  decoration: BoxDecoration(
                    color: selected
                        ? const Color(0xff163a70)
                        : Theme.of(context).colorScheme.surfaceContainerHigh,
                    borderRadius: BorderRadius.circular(19),
                  ),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        DateFormat('EEE').format(date),
                        style: TextStyle(
                          fontSize: 11,
                          color: selected
                              ? Colors.white70
                              : Theme.of(context).colorScheme.onSurfaceVariant,
                        ),
                      ),
                      Text(
                        '${date.day}',
                        style: TextStyle(
                          fontSize: 19,
                          fontWeight: FontWeight.w900,
                          color: selected
                              ? Colors.white
                              : Theme.of(context).colorScheme.onSurface,
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
        const SectionTitle('Schedule'),
        timetable.when(
          loading: () => const Padding(
            padding: EdgeInsets.symmetric(vertical: 24),
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (error, _) => InfoRow(
            icon: Icons.refresh_rounded,
            title: 'Could not load the timetable',
            subtitle: 'Tap to try again',
            color: const Color(0xffff6b6b),
            onTap: () => ref.invalidate(timetableProvider(weekday)),
          ),
          data: (data) {
            final periods = (data['periods'] as List).cast<Map<String, dynamic>>();
            if (periods.isEmpty) {
              return const InfoRow(
                icon: Icons.free_breakfast_rounded,
                title: 'No classes scheduled',
                subtitle: 'Enjoy the day off!',
                color: Color(0xff829ab1),
                trailing: SizedBox.shrink(),
              );
            }
            return Column(
              children: periods.map((period) {
                final isBreak = period['is_break'] == true;
                return _TimelineItem(
                  time: period['time_start'] as String,
                  title: period['subject'] as String,
                  subtitle: [
                    if (period['class_name'] != null) period['class_name'] as String,
                    if (period['room'] != null && (period['room'] as String).isNotEmpty)
                      period['room'] as String,
                  ].join(' · '),
                  color: isBreak ? const Color(0xff829ab1) : const Color(0xff725cff),
                );
              }).toList(),
            );
          },
        ),
        const SectionTitle('Upcoming'),
        ref
            .watch(upcomingEventsProvider)
            .when(
              loading: () => const Padding(
                padding: EdgeInsets.symmetric(vertical: 24),
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => InfoRow(
                icon: Icons.refresh_rounded,
                title: 'Could not load events',
                subtitle: 'Tap to try again',
                color: const Color(0xffff6b6b),
                onTap: () => ref.invalidate(upcomingEventsProvider),
              ),
              data: (events) {
                if (events.isEmpty) {
                  return const InfoRow(
                    icon: Icons.event_available_rounded,
                    title: 'No upcoming events',
                    subtitle: 'Announcements from your school will appear here.',
                    color: Color(0xff829ab1),
                    trailing: SizedBox.shrink(),
                  );
                }
                return Column(
                  children: events.map((event) {
                    return InfoRow(
                      icon: Icons.event_rounded,
                      title: event['title']?.toString() ?? 'Event',
                      subtitle:
                          '${event['start_date'] ?? ''}${event['type'] != null ? ' · ${event['type']}' : ''}',
                      color: const Color(0xffffa62b),
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute<void>(
                          builder: (_) => EventDetailPage(
                            eventId: event['id'] as int,
                            initial: event,
                          ),
                        ),
                      ),
                    );
                  }).toList(),
                );
              },
            ),
      ],
    );
  }

  bool _isSameDay(DateTime a, DateTime b) =>
      a.year == b.year && a.month == b.month && a.day == b.day;
}

class _TimelineItem extends StatelessWidget {
  const _TimelineItem({
    required this.time,
    required this.title,
    required this.subtitle,
    required this.color,
  });
  final String time, title, subtitle;
  final Color color;
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 68,
          child: Padding(
            padding: const EdgeInsets.only(top: 16),
            child: Text(
              time,
              style: const TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w800,
                color: Color(0xff627d98),
              ),
            ),
          ),
        ),
        Expanded(
          child: DepthCard(
            padding: const EdgeInsets.all(15),
            child: Row(
              children: [
                Container(
                  width: 5,
                  height: 48,
                  decoration: BoxDecoration(
                    color: color,
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
                const SizedBox(width: 13),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: const TextStyle(fontWeight: FontWeight.w900),
                      ),
                      if (subtitle.isNotEmpty)
                        Text(
                          subtitle,
                          style: const TextStyle(
                            fontSize: 12,
                            color: Color(0xff627d98),
                          ),
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    ),
  );
}
