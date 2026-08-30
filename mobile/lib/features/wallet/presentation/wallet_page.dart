import 'dart:math';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/network/api_client.dart';
import '../../../core/session/current_user_provider.dart';
import '../../../core/theme/module_colors.dart';
import '../../../core/widgets/child_switcher.dart';
import '../../../core/widgets/depth_card.dart';
import '../../../core/widgets/module_ui.dart';
import '../../fees/data/fees_repository.dart' show feeGatewaysProvider, formatMoney;
import '../data/wallet_repository.dart';

class WalletPage extends ConsumerStatefulWidget {
  const WalletPage({super.key});
  @override
  ConsumerState<WalletPage> createState() => _WalletPageState();
}

class _WalletPageState extends ConsumerState<WalletPage> {
  bool _processing = false;

  @override
  Widget build(BuildContext context) {
    final module = Theme.of(context).extension<ModuleColors>()!;
    final summary = ref.watch(walletSummaryProvider);
    return ModulePage(
      title: 'Wallet',
      subtitle: "Fund it and pay any of your child's fees instantly.",
      icon: Icons.account_balance_wallet_rounded,
      colors: [module.wallet, const Color(0xff1f7a56)],
      children: [
        const ChildSwitcher(),
        summary.when(
          loading: () => const Padding(
            padding: EdgeInsets.symmetric(vertical: 60),
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (error, _) => InfoRow(
            icon: Icons.refresh_rounded,
            title: 'Could not load your wallet',
            subtitle: _errorMessage(error),
            color: const Color(0xffff6b6b),
            onTap: () => ref.invalidate(walletSummaryProvider),
          ),
          data: (data) => _buildBalance(context, data),
        ),
        const SectionTitle('Transaction history'),
        _buildHistory(
          context,
          summary.valueOrNull?['currency'] as Map<String, dynamic>?,
        ),
      ],
    );
  }

  Widget _buildBalance(BuildContext context, Map<String, dynamic> data) {
    final currency = data['currency'] as Map<String, dynamic>?;
    final balance = (data['balance'] as num).toDouble();
    return DepthCard(
      color: const Color(0xffe3f6ed),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            (data['student'] as Map?)?['name']?.toString() ?? 'Student',
            style: const TextStyle(
              color: Color(0xff1f7a56),
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 5),
          Text(
            'Wallet balance',
            style: TextStyle(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
          Text(
            formatMoney(balance, currency),
            style: const TextStyle(
              fontSize: 32,
              fontWeight: FontWeight.w900,
              color: Color(0xff102a43),
            ),
          ),
          const SizedBox(height: 16),
          FilledButton.icon(
            onPressed: _processing ? null : () => _topUp(context, currency),
            icon: const Icon(Icons.add_circle_rounded),
            label: const Text('Top up'),
          ),
        ],
      ),
    );
  }

  Widget _buildHistory(BuildContext context, Map<String, dynamic>? currency) =>
      Consumer(
        builder: (context, ref, _) => ref
            .watch(walletHistoryProvider)
            .when(
              loading: () => const Padding(
                padding: EdgeInsets.all(24),
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => InfoRow(
                icon: Icons.refresh_rounded,
                title: 'Could not load your transactions',
                subtitle: _errorMessage(error),
                color: const Color(0xffff6b6b),
                onTap: () => ref.invalidate(walletHistoryProvider),
              ),
              data: (transactions) {
                if (transactions.isEmpty) {
                  return const InfoRow(
                    icon: Icons.receipt_long_rounded,
                    title: 'No transactions yet',
                    subtitle: 'Top-ups and payments will appear here.',
                    color: Color(0xff829ab1),
                    trailing: SizedBox.shrink(),
                  );
                }
                return Column(
                  children: transactions.map((tx) {
                    final isCredit = tx['type'] == 'credit';
                    return InfoRow(
                      icon: isCredit
                          ? Icons.arrow_downward_rounded
                          : Icons.arrow_upward_rounded,
                      title: isCredit ? 'Wallet top-up' : 'Fee payment',
                      subtitle: '${tx['date']} · ${tx['remarks'] ?? ''}',
                      color: isCredit
                          ? const Color(0xff00a896)
                          : const Color(0xffff8a5b),
                      trailing: Text(
                        '${isCredit ? '+' : '-'}${formatMoney(tx['amount'] as num, currency)}',
                        style: TextStyle(
                          fontWeight: FontWeight.w900,
                          color: isCredit
                              ? const Color(0xff00897b)
                              : const Color(0xffbf4d2c),
                        ),
                      ),
                    );
                  }).toList(),
                );
              },
            ),
      );

  Future<void> _topUp(
    BuildContext context,
    Map<String, dynamic>? currency,
  ) async {
    final amountController = TextEditingController();
    final amount = await showModalBottomSheet<double>(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      builder: (sheetContext) => Padding(
        padding: EdgeInsets.only(
          left: 20,
          right: 20,
          bottom: MediaQuery.viewInsetsOf(sheetContext).bottom + 20,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Top up wallet',
              style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 14),
            TextField(
              controller: amountController,
              keyboardType: const TextInputType.numberWithOptions(
                decimal: true,
              ),
              autofocus: true,
              decoration: const InputDecoration(
                labelText: 'Amount',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: () {
                final value = double.tryParse(amountController.text.trim());
                Navigator.of(sheetContext).pop(value);
              },
              child: const Text('Continue'),
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
    if (amount == null || amount <= 0 || !context.mounted) return;

    List<Map<String, dynamic>> gateways;
    try {
      gateways = await ref.read(feeGatewaysProvider.future);
    } on DioException catch (error) {
      if (context.mounted) showModuleMessage(context, _dioErrorMessage(error));
      return;
    }
    if (!context.mounted) return;
    if (gateways.isEmpty) {
      showModuleMessage(
        context,
        'Online payment has not been set up for your school yet. Please contact your school administrator.',
      );
      return;
    }
    var gateway = gateways.first['code'] as String;
    if (gateways.length > 1) {
      final chosen = await showModalBottomSheet<String>(
        context: context,
        showDragHandle: true,
        builder: (sheetContext) => SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              for (final option in gateways)
                ListTile(
                  title: Text(option['name']?.toString() ?? ''),
                  onTap: () =>
                      Navigator.of(sheetContext).pop(option['code'] as String),
                ),
            ],
          ),
        ),
      );
      if (chosen == null || !context.mounted) return;
      gateway = chosen;
    }

    setState(() => _processing = true);
    try {
      final studentId = ref.read(studentContextProvider);
      final idempotencyKey =
          'wlt-${DateTime.now().microsecondsSinceEpoch}-${Random().nextInt(1 << 31)}';
      final response = await ref
          .read(dioProvider)
          .post(
            'wallet/topup/checkout',
            data: {
              'student_id': ?studentId,
              'amount': amount,
              'gateway': gateway,
              'idempotency_key': idempotencyKey,
            },
          );
      final result = Map<String, dynamic>.from(response.data['data']);
      final transactionId = result['transaction_id'] as int;
      final checkoutUrl = result['checkout_url'] as String?;
      if (checkoutUrl != null) {
        await launchUrl(
          Uri.parse(checkoutUrl),
          mode: LaunchMode.externalApplication,
        );
      }
      if (!context.mounted) return;
      final confirmed = await showDialog<bool>(
        context: context,
        builder: (dialogContext) => AlertDialog(
          title: const Text('Finish your top-up'),
          content: const Text(
            'Complete the payment in the browser that just opened, then come back '
            'here and tap "I\'ve paid" so we can confirm it.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(dialogContext).pop(false),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () => Navigator.of(dialogContext).pop(true),
              child: const Text("I've paid"),
            ),
          ],
        ),
      );
      if (confirmed == true && context.mounted) {
        await _verify(context, transactionId);
      }
    } on DioException catch (error) {
      if (context.mounted) showModuleMessage(context, _dioErrorMessage(error));
    } finally {
      if (mounted) setState(() => _processing = false);
    }
  }

  Future<void> _verify(BuildContext context, int transactionId) async {
    setState(() => _processing = true);
    try {
      final response = await ref
          .read(dioProvider)
          .post('wallet/topup/$transactionId/verify');
      final status = (response.data['data']['status'] as String?) ?? 'pending';
      ref.invalidate(walletSummaryProvider);
      ref.invalidate(walletHistoryProvider);
      if (!context.mounted) return;
      showModuleMessage(context, switch (status) {
        'success' => 'Top-up confirmed. Thank you!',
        'failed' =>
          'The payment could not be verified. If you were charged, please contact your school.',
        _ => 'Payment is still pending. Try confirming again shortly.',
      });
    } on DioException catch (error) {
      if (context.mounted) showModuleMessage(context, _dioErrorMessage(error));
    } finally {
      if (mounted) setState(() => _processing = false);
    }
  }

  String _dioErrorMessage(DioException error) {
    final data = error.response?.data;
    if (data is Map && data['error'] is Map) {
      return ((data['error'] as Map)['message'] ??
              'Something went wrong. Please try again.')
          .toString();
    }
    return 'Something went wrong. Please try again.';
  }

  String _errorMessage(Object error) =>
      error is DioException ? _dioErrorMessage(error) : 'Tap to try again';
}
