import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../data/admin_repository.dart';

class ApprovalsPage extends ConsumerStatefulWidget {
  const ApprovalsPage({super.key});
  @override
  ConsumerState<ApprovalsPage> createState() => _ApprovalsPageState();
}

class _ApprovalsPageState extends ConsumerState<ApprovalsPage> {
  int? _actingId;

  Future<void> _act(Map<String, dynamic> item, bool approve) async {
    final id = item['id'] as int;
    final type = item['type'] as String;
    setState(() => _actingId = id);
    try {
      final action = approve ? 'approve' : 'reject';
      await ref.read(dioProvider).post('admin/approvals/$type/$id/$action');
      ref.invalidate(approvalsProvider);
      ref.invalidate(adminSummaryProvider);
      if (mounted) {
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(SnackBar(content: Text(approve ? 'Approved.' : 'Rejected.')));
      }
    } on DioException catch (error) {
      if (mounted) {
        final data = error.response?.data;
        final message = data is Map && data['error'] is Map
            ? ((data['error'] as Map)['message'] ?? 'Could not complete this action.').toString()
            : 'Could not complete this action.';
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(SnackBar(content: Text(message)));
      }
    } finally {
      if (mounted) setState(() => _actingId = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    final approvals = ref.watch(approvalsProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('Approvals')),
      body: approvals.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(
          child: FilledButton.icon(
            onPressed: () => ref.invalidate(approvalsProvider),
            icon: const Icon(Icons.refresh),
            label: const Text('Try again'),
          ),
        ),
        data: (items) {
          if (items.isEmpty) {
            return const Center(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: Text('No pending approvals right now.'),
              ),
            );
          }
          return ListView(
            padding: const EdgeInsets.all(20),
            children: items.map((item) {
              final id = item['id'] as int;
              final busy = _actingId == id;
              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(item['title']?.toString() ?? '', style: const TextStyle(fontWeight: FontWeight.w900)),
                      const SizedBox(height: 4),
                      Text(item['subtitle']?.toString() ?? ''),
                      const SizedBox(height: 4),
                      Text(
                        '₦${(item['amount'] as num).toStringAsFixed(2)} · ${item['date']}',
                        style: const TextStyle(color: Color(0xff627d98)),
                      ),
                      const SizedBox(height: 12),
                      if (busy)
                        const Center(child: CircularProgressIndicator())
                      else
                        Row(
                          children: [
                            Expanded(
                              child: OutlinedButton(
                                onPressed: () => _act(item, false),
                                child: const Text('Reject'),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: FilledButton(
                                onPressed: () => _act(item, true),
                                child: const Text('Approve'),
                              ),
                            ),
                          ],
                        ),
                    ],
                  ),
                ),
              );
            }).toList(),
          );
        },
      ),
    );
  }
}
