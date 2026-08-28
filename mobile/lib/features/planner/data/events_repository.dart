import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/session/current_user_provider.dart';

final upcomingEventsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final studentId = ref.watch(studentContextProvider);
      final response = await ref
          .watch(dioProvider)
          .get(
            'events',
            queryParameters: {'student_id': ?studentId},
          );
      return (response.data['data'] as List)
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

final eventDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final studentId = ref.watch(studentContextProvider);
      final response = await ref
          .watch(dioProvider)
          .get(
            'events/$id',
            queryParameters: {'student_id': ?studentId},
          );
      return Map<String, dynamic>.from(response.data['data']);
    });
