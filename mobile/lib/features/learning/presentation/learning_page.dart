import 'dart:io';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:open_filex/open_filex.dart';
import '../../../core/network/api_client.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/resources_repository.dart';

class LearningPage extends ConsumerStatefulWidget {
  const LearningPage({super.key, required this.role});
  final String role;

  @override
  ConsumerState<LearningPage> createState() => _LearningPageState();
}

class _LearningPageState extends ConsumerState<LearningPage> {
  int? _downloadingId;

  @override
  Widget build(BuildContext context) => ModulePage(
    title: 'Learning',
    subtitle: widget.role.toLowerCase().contains('teacher')
        ? 'Resources shared with your classes.'
        : 'Study materials shared by your school.',
    icon: Icons.menu_book_rounded,
    colors: const [Color(0xff725cff), Color(0xffa855f7)],
    children: [
      const SectionTitle('Learning resources'),
      ref
          .watch(learningResourcesProvider)
          .when(
            loading: () => const Padding(
              padding: EdgeInsets.symmetric(vertical: 60),
              child: Center(child: CircularProgressIndicator()),
            ),
            error: (error, _) => InfoRow(
              icon: Icons.refresh_rounded,
              title: 'Could not load resources',
              subtitle: 'Tap to try again',
              color: const Color(0xffff6b6b),
              onTap: () => ref.invalidate(learningResourcesProvider),
            ),
            data: (resources) {
              if (resources.isEmpty) {
                return const InfoRow(
                  icon: Icons.folder_off_rounded,
                  title: 'No resources yet',
                  subtitle: 'Study materials from your school will appear here.',
                  color: Color(0xff829ab1),
                  trailing: SizedBox.shrink(),
                );
              }
              return Column(
                children: resources.map((resource) {
                  final id = resource['id'] as int;
                  return InfoRow(
                    icon: Icons.picture_as_pdf_rounded,
                    title: resource['title']?.toString() ?? 'Resource',
                    subtitle:
                        '${(resource['extension'] as String? ?? '').toUpperCase()}'
                        '${resource['subject'] != null ? ' · ${resource['subject']}' : ''}'
                        '${resource['date'] != null ? ' · ${resource['date']}' : ''}',
                    color: const Color(0xffff6b6b),
                    trailing: _downloadingId == id
                        ? const SizedBox.square(
                            dimension: 20,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.download_rounded),
                    onTap: _downloadingId == null ? () => _download(resource) : null,
                  );
                }).toList(),
              );
            },
          ),
    ],
  );

  Future<void> _download(Map<String, dynamic> resource) async {
    final id = resource['id'] as int;
    setState(() => _downloadingId = id);
    try {
      final response = await ref
          .read(dioProvider)
          .get<List<int>>(
            'resources/$id/download',
            options: Options(responseType: ResponseType.bytes),
          );
      final safeName = (resource['title'] ?? 'resource')
          .toString()
          .replaceAll(RegExp(r'[^A-Za-z0-9_-]+'), '-');
      final extension = (resource['extension'] as String?) ?? '';
      final file = File(
        '${Directory.systemTemp.path}${Platform.pathSeparator}$safeName${extension.isNotEmpty ? '.$extension' : ''}',
      );
      await file.writeAsBytes(response.data!, flush: true);
      if (mounted) await OpenFilex.open(file.path);
    } on DioException catch (error) {
      if (mounted) {
        final data = error.response?.data;
        final message = data is Map && data['error'] is Map
            ? ((data['error'] as Map)['message'] ?? 'Could not download this resource.')
                  .toString()
            : 'Could not download this resource.';
        showModuleMessage(context, message);
      }
    } finally {
      if (mounted) setState(() => _downloadingId = null);
    }
  }
}
