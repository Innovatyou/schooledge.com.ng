import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/session/current_user_provider.dart';

final idCardProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final studentId = ref.watch(studentContextProvider);
  final response = await ref
      .watch(dioProvider)
      .get('profile/id-card', queryParameters: {'student_id': ?studentId});
  return Map<String, dynamic>.from(response.data['data']);
});
