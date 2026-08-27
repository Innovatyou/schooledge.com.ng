import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/session/current_user_provider.dart';

final timetableProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, String>((ref, day) async {
      final studentId = ref.watch(studentContextProvider);
      final response = await ref
          .watch(dioProvider)
          .get(
            'timetable',
            queryParameters: {'day': day, 'student_id': ?studentId},
          );
      return Map<String, dynamic>.from(response.data['data']);
    });
