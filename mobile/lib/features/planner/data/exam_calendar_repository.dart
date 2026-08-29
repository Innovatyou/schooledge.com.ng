import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/session/current_user_provider.dart';

final examCalendarProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final studentId = ref.watch(studentContextProvider);
      final response = await ref
          .watch(dioProvider)
          .get('timetable/exams', queryParameters: {'student_id': ?studentId});
      return (response.data['data']['exams'] as List)
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });
