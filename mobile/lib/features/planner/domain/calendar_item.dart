import 'package:flutter/material.dart';

/// One dot on the unified month calendar - merges school events/holidays and
/// exam dates (two separate endpoints) into a single per-day marker list,
/// entirely on the client. No new backend endpoint - both sources already
/// exist (GET events, GET timetable/exams).
@immutable
class CalendarItem {
  const CalendarItem({
    required this.date,
    required this.title,
    required this.subtitle,
    required this.color,
    required this.icon,
  });

  final DateTime date;
  final String title;
  final String subtitle;
  final Color color;
  final IconData icon;

  bool isSameDay(DateTime other) =>
      date.year == other.year &&
      date.month == other.month &&
      date.day == other.day;
}
