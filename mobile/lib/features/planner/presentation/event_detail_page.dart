import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/events_repository.dart';

class EventDetailPage extends ConsumerWidget {
  const EventDetailPage({super.key, required this.eventId, this.initial});
  final int eventId;
  final Map<String, dynamic>? initial;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final detail = ref.watch(eventDetailProvider(eventId));
    return Scaffold(
      appBar: AppBar(title: Text(initial?['title']?.toString() ?? 'Event')),
      body: detail.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(
          child: FilledButton.icon(
            onPressed: () => ref.invalidate(eventDetailProvider(eventId)),
            icon: const Icon(Icons.refresh),
            label: const Text('Try again'),
          ),
        ),
        data: (event) => ListView(
          padding: const EdgeInsets.all(20),
          children: [
            if (event['image_url'] != null)
              ClipRRect(
                borderRadius: BorderRadius.circular(20),
                child: Image.network(
                  event['image_url'] as String,
                  errorBuilder: (_, _, _) => const SizedBox.shrink(),
                ),
              ),
            const SizedBox(height: 16),
            if (event['type'] != null)
              Chip(label: Text(event['type'].toString())),
            const SizedBox(height: 8),
            Text(
              event['title']?.toString() ?? '',
              style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                const Icon(Icons.calendar_today_rounded, size: 16),
                const SizedBox(width: 6),
                Text(_dateRange(event)),
              ],
            ),
            const SizedBox(height: 20),
            Text(
              (event['body'] as String?)?.trim().isNotEmpty == true
                  ? event['body'] as String
                  : 'No further details were provided for this event.',
              style: const TextStyle(fontSize: 15, height: 1.5),
            ),
          ],
        ),
      ),
    );
  }

  String _dateRange(Map<String, dynamic> event) {
    final start = event['start_date']?.toString();
    final end = event['end_date']?.toString();
    if (start == null) return '';
    if (end == null || end == start) return start;
    return '$start - $end';
  }
}
