import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';

/// The school's celebrations wall plus `for_me`: the celebrations about the
/// signed-in member (a parent gets their children's) and the school-wide
/// ones, which the home screen pops up with an animation.
final celebrationsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final response = await ref.watch(dioProvider).get('celebrations');
  return Map<String, dynamic>.from(response.data['data']);
});

List<Map<String, dynamic>> asMaps(Object? list) => (list as List? ?? const [])
    .map((item) => Map<String, dynamic>.from(item as Map))
    .toList();
