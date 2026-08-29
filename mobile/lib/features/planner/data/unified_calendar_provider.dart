import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../domain/calendar_item.dart';
import 'events_repository.dart';
import 'exam_calendar_repository.dart';

/// Merges the existing events feed and exam calendar into one list of
/// per-day markers for the month grid - no new endpoint, both sources are
/// already fetched by Planner elsewhere.
final calendarItemsProvider = FutureProvider.autoDispose<List<CalendarItem>>((
  ref,
) async {
  final events = await ref.watch(upcomingEventsProvider.future);
  final exams = await ref.watch(examCalendarProvider.future);
  final items = <CalendarItem>[];

  for (final event in events) {
    final date = DateTime.tryParse((event['start_date'] ?? '').toString());
    if (date == null) continue;
    items.add(
      CalendarItem(
        date: DateTime(date.year, date.month, date.day),
        title: event['title']?.toString() ?? 'Event',
        subtitle: event['type']?.toString() ?? 'School event',
        color: const Color(0xffffa62b),
        icon: Icons.event_rounded,
      ),
    );
  }

  for (final exam in exams) {
    final date = DateTime.tryParse((exam['exam_date'] ?? '').toString());
    if (date == null) continue;
    items.add(
      CalendarItem(
        date: DateTime(date.year, date.month, date.day),
        title: exam['subject_name']?.toString() ?? 'Exam',
        subtitle: exam['exam_name']?.toString() ?? 'Exam',
        color: const Color(0xff725cff),
        icon: Icons.menu_book_rounded,
      ),
    );
  }

  items.sort((a, b) => a.date.compareTo(b.date));
  return items;
});
