import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';

final adminSummaryProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final response = await ref.watch(dioProvider).get('admin/summary');
  return Map<String, dynamic>.from(response.data['data']);
});

final approvalsProvider = FutureProvider.autoDispose<List<Map<String, dynamic>>>((
  ref,
) async {
  final response = await ref.watch(dioProvider).get('admin/approvals');
  return (response.data['data'] as List)
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
});
