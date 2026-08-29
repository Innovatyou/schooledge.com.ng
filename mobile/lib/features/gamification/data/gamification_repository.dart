import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/session/current_user_provider.dart';

final gamificationMeProvider = FutureProvider.autoDispose<Map<String, dynamic>>(
  (ref) async {
    final studentId = ref.watch(studentContextProvider);
    final response = await ref
        .watch(dioProvider)
        .get('gamification/me', queryParameters: {'student_id': ?studentId});
    return Map<String, dynamic>.from(response.data['data']);
  },
);

final leaderboardProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final studentId = ref.watch(studentContextProvider);
      final response = await ref
          .watch(dioProvider)
          .get(
            'gamification/leaderboard',
            queryParameters: {'student_id': ?studentId},
          );
      return (response.data['data']['leaderboard'] as List)
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });
