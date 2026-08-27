import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/session/current_user_provider.dart';

final attendanceSummaryProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final studentId = ref.watch(studentContextProvider);
  final response = await ref
      .watch(dioProvider)
      .get(
        'attendance/summary',
        queryParameters: {'student_id': ?studentId},
      );
  return Map<String, dynamic>.from(response.data['data']);
});

final teacherClassesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final response = await ref.watch(dioProvider).get('attendance/classes');
      return (response.data['data'] as List)
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });
