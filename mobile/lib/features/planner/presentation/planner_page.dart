import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/depth_card.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/events_repository.dart';
import 'event_detail_page.dart';

class PlannerPage extends ConsumerWidget {
  const PlannerPage({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) => ListView(
    padding: const EdgeInsets.fromLTRB(20, 20, 20, 120),
    children: [
      const Text(
        'Planner',
        style: TextStyle(
          fontSize: 30,
          fontWeight: FontWeight.w900,
          color: Color(0xff102a43),
        ),
      ),
      const SizedBox(height: 6),
      const Text(
        'Wednesday, 27 August',
        style: TextStyle(color: Color(0xff627d98)),
      ),
      const SizedBox(height: 20),
      SizedBox(
        height: 78,
        child: ListView.separated(
          scrollDirection: Axis.horizontal,
          itemCount: 7,
          separatorBuilder: (_, _) => const SizedBox(width: 9),
          itemBuilder: (_, index) {
            final selected = index == 3;
            return Container(
              width: 58,
              decoration: BoxDecoration(
                color: selected ? const Color(0xff163a70) : Colors.white,
                borderRadius: BorderRadius.circular(19),
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][index],
                    style: TextStyle(
                      fontSize: 11,
                      color: selected
                          ? Colors.white70
                          : const Color(0xff627d98),
                    ),
                  ),
                  Text(
                    '${24 + index}',
                    style: TextStyle(
                      fontSize: 19,
                      fontWeight: FontWeight.w900,
                      color: selected ? Colors.white : const Color(0xff102a43),
                    ),
                  ),
                ],
              ),
            );
          },
        ),
      ),
      const SectionTitle('Today’s schedule'),
      const _TimelineItem(
        '8:00 AM',
        'Mathematics',
        'Algebra · Room 12',
        Color(0xff725cff),
      ),
      const _TimelineItem(
        '10:00 AM',
        'Basic Science',
        'Energy · Laboratory',
        Color(0xff00a896),
      ),
      const _TimelineItem(
        '12:30 PM',
        'English Language',
        'Comprehension · Room 12',
        Color(0xffff8a5b),
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

class _TimelineItem extends StatelessWidget {
  const _TimelineItem(this.time, this.title, this.subtitle, this.color);
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
