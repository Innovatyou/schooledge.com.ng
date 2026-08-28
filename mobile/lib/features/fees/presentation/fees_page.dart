import 'dart:io';
import 'dart:math';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:open_filex/open_filex.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/network/api_client.dart';
import '../../../core/session/current_user_provider.dart';
import '../../../core/widgets/depth_card.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/fees_repository.dart';

class FeesPage extends ConsumerStatefulWidget {
  const FeesPage({super.key});
  @override
  ConsumerState<FeesPage> createState() => _FeesPageState();
}

class _FeesPageState extends ConsumerState<FeesPage> {
  bool _processing = false;
  bool _downloadingInvoice = false;
  int? _downloadingReceiptId;

  @override
  Widget build(BuildContext context) {
    final summary = ref.watch(feeSummaryProvider);
    return ModulePage(
      title: 'Fees & Payments',
      subtitle: 'Balances, secure payments and receipts.',
      icon: Icons.account_balance_wallet_rounded,
      colors: const [Color(0xff126e82), Color(0xff168aad)],
      children: [
        summary.when(
          loading: () => const Padding(
            padding: EdgeInsets.symmetric(vertical: 60),
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (error, _) => InfoRow(
            icon: Icons.refresh_rounded,
            title: 'Could not load fees',
            subtitle: _errorMessage(error),
            color: const Color(0xffff6b6b),
            onTap: () => ref.invalidate(feeSummaryProvider),
          ),
          data: (data) => _buildSummary(context, data),
        ),
        const SectionTitle('Recent payments'),
        _buildHistory(context, summary.valueOrNull?['currency'] as Map<String, dynamic>?),
      ],
    );
  }

  Widget _buildSummary(BuildContext context, Map<String, dynamic> data) {
    final currency = data['currency'] as Map<String, dynamic>?;
    final balance = (data['balance'] as num).toDouble();
    final items = (data['items'] as List).cast<Map<String, dynamic>>();
    final transport = data['transport'] as Map<String, dynamic>?;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        DepthCard(
          color: const Color(0xffe2f5f8),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                (data['student'] as Map?)?['name']?.toString() ?? 'Student',
                style: const TextStyle(
                  color: Color(0xff486581),
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 5),
              Text(
                'Outstanding balance',
                style: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant),
              ),
              Text(
                formatMoney(balance, currency),
                style: const TextStyle(
                  fontSize: 32,
                  fontWeight: FontWeight.w900,
                  color: Color(0xff102a43),
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Invoice #${data['invoice_no']}',
                style: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: FilledButton.icon(
                      onPressed: _processing || balance <= 0
                          ? null
                          : () => _pickItemToPay(context, currency, items, transport),
                      icon: const Icon(Icons.lock_rounded),
                      label: Text(balance <= 0 ? 'Fully paid' : 'Pay securely'),
                    ),
                  ),
                  const SizedBox(width: 10),
                  IconButton.filledTonal(
                    onPressed: _downloadingInvoice ? null : () => _downloadInvoice(context),
                    icon: _downloadingInvoice
                        ? const SizedBox.square(
                            dimension: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.picture_as_pdf_rounded),
                    tooltip: 'Download invoice',
                  ),
                ],
              ),
            ],
          ),
        ),
        const SectionTitle('Fee breakdown'),
        for (final item in items)
          InfoRow(
            icon: Icons.school_rounded,
            title: item['name']?.toString() ?? 'Fee',
            subtitle: 'Due ${item['due_date'] ?? '-'}'
                '${(item['accruing_fine'] as num? ?? 0) > 0 ? ' · Fine ${formatMoney(item['accruing_fine'] as num, currency)}' : ''}',
            color: (item['balance'] as num) > 0
                ? const Color(0xffff8a5b)
                : const Color(0xff00a896),
            trailing: Text(
              formatMoney(item['amount'] as num, currency),
              style: const TextStyle(fontWeight: FontWeight.w900),
            ),
          ),
        if (transport != null)
          InfoRow(
            icon: Icons.directions_bus_rounded,
            title: 'Transport',
            subtitle: (transport['balance'] as num) > 0 ? 'Balance due' : 'Fully paid',
            color: (transport['balance'] as num) > 0
                ? const Color(0xffff8a5b)
                : const Color(0xff00a896),
            trailing: Text(
              formatMoney(transport['amount'] as num, currency),
              style: const TextStyle(fontWeight: FontWeight.w900),
            ),
          ),
      ],
    );
  }

  Widget _buildHistory(BuildContext context, Map<String, dynamic>? currency) =>
      Consumer(
        builder: (context, ref, _) => ref
            .watch(feeHistoryProvider)
            .when(
              loading: () => const Padding(
                padding: EdgeInsets.all(24),
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => InfoRow(
                icon: Icons.refresh_rounded,
                title: 'Could not load payment history',
                subtitle: _errorMessage(error),
                color: const Color(0xffff6b6b),
                onTap: () => ref.invalidate(feeHistoryProvider),
              ),
              data: (payments) {
                if (payments.isEmpty) {
                  return const InfoRow(
                    icon: Icons.receipt_long_rounded,
                    title: 'No payments yet',
                    subtitle: 'Payments will appear here once made.',
                    color: Color(0xff829ab1),
                    trailing: SizedBox.shrink(),
                  );
                }
                return Column(
                  children: payments.map((payment) {
                    final id = payment['id'] as int;
                    return InfoRow(
                      icon: Icons.receipt_long_rounded,
                      title: payment['fee_type']?.toString() ?? 'Payment',
                      subtitle: 'Paid ${payment['date']} · ${payment['pay_via'] ?? ''}',
                      color: const Color(0xff00a896),
                      trailing: _downloadingReceiptId == id
                          ? const SizedBox.square(
                              dimension: 20,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : Text(
                              formatMoney(payment['amount'] as num, currency),
                              style: const TextStyle(
                                fontWeight: FontWeight.w900,
                                color: Color(0xff00897b),
                              ),
                            ),
                      onTap: _downloadingReceiptId == null
                          ? () => _downloadReceipt(context, id)
                          : null,
                    );
                  }).toList(),
                );
              },
            ),
      );

  Future<void> _pickItemToPay(
    BuildContext context,
    Map<String, dynamic>? currency,
    List<Map<String, dynamic>> items,
    Map<String, dynamic>? transport,
  ) async {
    final unpaid = items.where((item) => (item['balance'] as num) > 0).toList();
    final selection = await showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      showDragHandle: true,
      builder: (sheetContext) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Padding(
              padding: EdgeInsets.fromLTRB(20, 4, 20, 12),
              child: Align(
                alignment: Alignment.centerLeft,
                child: Text(
                  'Choose what to pay',
                  style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800),
                ),
              ),
            ),
            for (final item in unpaid)
              ListTile(
                leading: const Icon(Icons.school_rounded),
                title: Text(item['name']?.toString() ?? 'Fee'),
                trailing: Text(formatMoney(item['balance'] as num, currency)),
                onTap: () => Navigator.of(sheetContext).pop(item),
              ),
            if (transport != null && (transport['balance'] as num) > 0)
              ListTile(
                leading: const Icon(Icons.directions_bus_rounded),
                title: const Text('Transport'),
                trailing: Text(formatMoney(transport['balance'] as num, currency)),
                onTap: () => Navigator.of(sheetContext).pop({
                  ...transport,
                  'name': 'Transport',
                  'transport_fee_details_id': transport['transport_fee_details_id'],
                }),
              ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
    if (selection == null || !context.mounted) return;
    await _pay(
      context,
      currency,
      allocationId: selection['allocation_id'] as int?,
      typeId: selection['type_id'] as int?,
      transportFeeDetailsId: selection['transport_fee_details_id'] as int?,
      balance: (selection['balance'] as num).toDouble(),
    );
  }

  Future<void> _pay(
    BuildContext context,
    Map<String, dynamic>? currency, {
    int? allocationId,
    int? typeId,
    int? transportFeeDetailsId,
    required double balance,
  }) async {
    if (balance <= 0) return;
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
                  onTap: () => Navigator.of(sheetContext).pop(option['code'] as String),
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
          'mob-${DateTime.now().microsecondsSinceEpoch}-${Random().nextInt(1 << 31)}';
      final response = await ref
          .read(dioProvider)
          .post(
            'fees/checkout',
            data: {
              'student_id': ?studentId,
              'allocation_id': ?allocationId,
              'type_id': ?typeId,
              'transport_fee_details_id': ?transportFeeDetailsId,
              'amount': balance,
              'gateway': gateway,
              'idempotency_key': idempotencyKey,
            },
          );
      final result = Map<String, dynamic>.from(response.data['data']);
      final transactionId = result['transaction_id'] as int;
      final checkoutUrl = result['checkout_url'] as String?;
      if (checkoutUrl != null) {
        await launchUrl(Uri.parse(checkoutUrl), mode: LaunchMode.externalApplication);
      }
      if (!context.mounted) return;
      final confirmed = await showDialog<bool>(
        context: context,
        builder: (dialogContext) => AlertDialog(
          title: const Text('Finish your payment'),
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
          .post('fees/checkout/$transactionId/verify');
      final status = (response.data['data']['status'] as String?) ?? 'pending';
      ref.invalidate(feeSummaryProvider);
      ref.invalidate(feeHistoryProvider);
      if (!context.mounted) return;
      showModuleMessage(
        context,
        switch (status) {
          'success' => 'Payment confirmed. Thank you!',
          'failed' =>
            'The payment could not be verified. If you were charged, please contact your school.',
          _ => 'Payment is still pending. Try confirming again shortly.',
        },
      );
    } on DioException catch (error) {
      if (context.mounted) showModuleMessage(context, _dioErrorMessage(error));
    } finally {
      if (mounted) setState(() => _processing = false);
    }
  }

  Future<void> _downloadInvoice(BuildContext context) async {
    setState(() => _downloadingInvoice = true);
    try {
      final studentId = ref.read(studentContextProvider);
      final response = await ref
          .read(dioProvider)
          .get<List<int>>(
            'fees/invoice/download',
            queryParameters: {'student_id': ?studentId},
            options: Options(responseType: ResponseType.bytes),
          );
      final file = File(
        '${Directory.systemTemp.path}${Platform.pathSeparator}'
        'invoice-${DateTime.now().millisecondsSinceEpoch}.pdf',
      );
      await file.writeAsBytes(response.data!, flush: true);
      if (context.mounted) await OpenFilex.open(file.path);
    } on DioException catch (error) {
      if (context.mounted) showModuleMessage(context, _dioErrorMessage(error));
    } finally {
      if (mounted) setState(() => _downloadingInvoice = false);
    }
  }

  Future<void> _downloadReceipt(BuildContext context, int paymentId) async {
    setState(() => _downloadingReceiptId = paymentId);
    try {
      final studentId = ref.read(studentContextProvider);
      final response = await ref
          .read(dioProvider)
          .get<List<int>>(
            'fees/receipt/$paymentId/download',
            queryParameters: {'student_id': ?studentId},
            options: Options(responseType: ResponseType.bytes),
          );
      final file = File(
        '${Directory.systemTemp.path}${Platform.pathSeparator}receipt-$paymentId.pdf',
      );
      await file.writeAsBytes(response.data!, flush: true);
      if (context.mounted) await OpenFilex.open(file.path);
    } on DioException catch (error) {
      if (context.mounted) showModuleMessage(context, _dioErrorMessage(error));
    } finally {
      if (mounted) setState(() => _downloadingReceiptId = null);
    }
  }

  String _dioErrorMessage(DioException error) {
    final data = error.response?.data;
    if (data is Map && data['error'] is Map) {
      return ((data['error'] as Map)['message'] ?? 'Something went wrong. Please try again.')
          .toString();
    }
    return 'Something went wrong. Please try again.';
  }

  String _errorMessage(Object error) =>
      error is DioException ? _dioErrorMessage(error) : 'Tap to try again';
}
