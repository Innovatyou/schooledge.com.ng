import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/session/current_user_provider.dart';

final feeSummaryProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final studentId = ref.watch(studentContextProvider);
  final response = await ref
      .watch(dioProvider)
      .get(
        'fees/summary',
        queryParameters: {'student_id': ?studentId},
      );
  return Map<String, dynamic>.from(response.data['data']);
});

final feeHistoryProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final studentId = ref.watch(studentContextProvider);
      final response = await ref
          .watch(dioProvider)
          .get(
            'fees/history',
            queryParameters: {'student_id': ?studentId},
          );
      return (response.data['data'] as List)
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

final feeGatewaysProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final response = await ref.watch(dioProvider).get('fees/gateways');
      return (response.data['data'] as List)
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

/// Formats an amount using the branch's own currency symbol/position and
/// thousand separators, e.g. "₦85,000.00".
String formatMoney(num amount, Map<String, dynamic>? currency) {
  final symbol = (currency?['symbol'] ?? '').toString();
  final position = int.tryParse(currency?['symbol_position']?.toString() ?? '1') ?? 1;
  final parts = amount.toStringAsFixed(2).split('.');
  final whole = parts[0].replaceAllMapped(
    RegExp(r'\B(?=(\d{3})+(?!\d))'),
    (match) => ',',
  );
  final formatted = '$whole.${parts[1]}';
  return position == 2 ? '$formatted$symbol' : '$symbol$formatted';
}
