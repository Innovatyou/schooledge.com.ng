import 'dart:io';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:open_filex/open_filex.dart';
import '../../../core/network/api_client.dart';
import '../../../core/widgets/module_ui.dart';

final publishedReportsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final response = await ref.watch(dioProvider).get('reports');
      return (response.data['data'] as List)
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

class ReportDownloadSection extends ConsumerStatefulWidget {
  const ReportDownloadSection({super.key});
  @override
  ConsumerState<ReportDownloadSection> createState() =>
      _ReportDownloadSectionState();
}

class _ReportDownloadSectionState extends ConsumerState<ReportDownloadSection> {
  int? downloading;

  @override
  Widget build(BuildContext context) => ref
      .watch(publishedReportsProvider)
      .when(
        loading: () => const Padding(
          padding: EdgeInsets.all(24),
          child: Center(child: CircularProgressIndicator()),
        ),
        error: (_, _) => InfoRow(
          icon: Icons.refresh_rounded,
          title: 'Could not load reports',
          subtitle: 'Tap to try again',
          color: const Color(0xffff6b6b),
          onTap: () => ref.invalidate(publishedReportsProvider),
        ),
        data: (reports) {
          if (reports.isEmpty) {
            return const InfoRow(
              icon: Icons.inbox_rounded,
              title: 'No published reports',
              subtitle: 'Your school has not published a report yet.',
              color: Color(0xff829ab1),
              trailing: SizedBox.shrink(),
            );
          }
          return Column(
            children: reports.map((report) {
              final id = report['id'] as int;
              return InfoRow(
                icon: Icons.picture_as_pdf_rounded,
                title: report['name']?.toString() ?? 'School report',
                subtitle: '${report['student_name'] ?? ''} · Secure PDF',
                color: const Color(0xffffa62b),
                trailing: downloading == id
                    ? const SizedBox.square(
                        dimension: 22,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.download_rounded),
                onTap: downloading == null ? () => _download(report) : null,
              );
            }).toList(),
          );
        },
      );

  Future<void> _download(Map<String, dynamic> report) async {
    final id = report['id'] as int;
    setState(() => downloading = id);
    try {
      final response = await ref
          .read(dioProvider)
          .get<List<int>>(
            'reports/$id/download',
            queryParameters: {'student_id': report['student_id']},
            options: Options(responseType: ResponseType.bytes),
          );
      final safeName = (report['name'] ?? 'school-report')
          .toString()
          .replaceAll(RegExp(r'[^A-Za-z0-9_-]+'), '-');
      final file = File(
        '${Directory.systemTemp.path}${Platform.pathSeparator}$safeName-report.pdf',
      );
      await file.writeAsBytes(response.data!, flush: true);
      if (mounted) await OpenFilex.open(file.path);
    } on DioException catch (error) {
      if (mounted) {
        showModuleMessage(
          context,
          error.response?.statusCode == 404
              ? 'This report is no longer available.'
              : 'Report download failed. Please try again.',
        );
      }
    } finally {
      if (mounted) setState(() => downloading = null);
    }
  }
}
