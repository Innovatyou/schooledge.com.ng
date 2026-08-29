import 'dart:io';
import 'package:dio/dio.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:open_filex/open_filex.dart';
import '../../../core/network/api_client.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/homework_repository.dart';

class HomeworkDetailPage extends ConsumerStatefulWidget {
  const HomeworkDetailPage({
    super.key,
    required this.homeworkId,
    required this.isTeacher,
    required this.initial,
  });
  final int homeworkId;
  final bool isTeacher;
  final Map<String, dynamic> initial;

  @override
  ConsumerState<HomeworkDetailPage> createState() => _HomeworkDetailPageState();
}

class _HomeworkDetailPageState extends ConsumerState<HomeworkDetailPage> {
  final _messageController = TextEditingController();
  PlatformFile? _pickedFile;
  bool _submitting = false;
  bool _downloadingAttachment = false;
  int? _downloadingSubmission;

  @override
  void initState() {
    super.initState();
    _messageController.text =
        (widget.initial['submission_message'] as String?) ?? '';
  }

  @override
  void dispose() {
    _messageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: Text(widget.initial['subject']?.toString() ?? 'Homework'),
    ),
    body: ListView(
      padding: const EdgeInsets.all(20),
      children: [
        Text(
          widget.initial['description']?.toString() ?? '',
          style: const TextStyle(fontSize: 15, height: 1.5),
        ),
        const SizedBox(height: 12),
        Text(
          'Due ${widget.initial['due_date']}',
          style: const TextStyle(fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 20),
        if (widget.initial['has_attachment'] == true)
          FilledButton.tonalIcon(
            onPressed: _downloadingAttachment ? null : _downloadAttachment,
            icon: _downloadingAttachment
                ? const SizedBox.square(
                    dimension: 16,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.attachment_rounded),
            label: const Text('Download assignment'),
          ),
        const SizedBox(height: 20),
        if (widget.isTeacher)
          _buildSubmissionsList()
        else
          _buildSubmissionForm(),
      ],
    ),
  );

  Widget _buildSubmissionForm() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      if (widget.initial['evaluation_status'] != null) ...[
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: const Color(0xffe9f9f5),
            borderRadius: BorderRadius.circular(16),
          ),
          child: Text(
            'Teacher feedback: ${widget.initial['evaluation_remark'] ?? 'Reviewed'}',
          ),
        ),
        const SizedBox(height: 16),
      ],
      const SectionTitle('Your submission'),
      TextField(
        controller: _messageController,
        maxLines: 4,
        decoration: const InputDecoration(
          hintText: 'Write a note about your submission…',
        ),
      ),
      const SizedBox(height: 12),
      OutlinedButton.icon(
        onPressed: _pickFile,
        icon: const Icon(Icons.attach_file_rounded),
        label: Text(_pickedFile?.name ?? 'Attach a file (optional)'),
      ),
      const SizedBox(height: 16),
      SizedBox(
        width: double.infinity,
        child: FilledButton(
          onPressed: _submitting ? null : _submit,
          child: _submitting
              ? const SizedBox.square(
                  dimension: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Text('Submit'),
        ),
      ),
    ],
  );

  Widget _buildSubmissionsList() => Consumer(
    builder: (context, ref, _) => ref
        .watch(homeworkSubmissionsProvider(widget.homeworkId))
        .when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => InfoRow(
            icon: Icons.refresh_rounded,
            title: 'Could not load submissions',
            subtitle: 'Tap to try again',
            color: const Color(0xffff6b6b),
            onTap: () =>
                ref.invalidate(homeworkSubmissionsProvider(widget.homeworkId)),
          ),
          data: (submissions) {
            if (submissions.isEmpty) {
              return const InfoRow(
                icon: Icons.inbox_rounded,
                title: 'No submissions yet',
                subtitle: 'Students have not submitted this homework.',
                color: Color(0xff829ab1),
                trailing: SizedBox.shrink(),
              );
            }
            return Column(
              children: submissions.map((submission) {
                final studentId = submission['student_id'] as int;
                return InfoRow(
                  icon: Icons.person_rounded,
                  title: submission['student_name']?.toString() ?? 'Student',
                  subtitle: submission['message']?.toString() ?? '',
                  color: const Color(0xff725cff),
                  trailing: submission['has_file'] == true
                      ? IconButton(
                          onPressed: _downloadingSubmission == studentId
                              ? null
                              : () => _downloadSubmission(studentId),
                          icon: _downloadingSubmission == studentId
                              ? const SizedBox.square(
                                  dimension: 16,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                  ),
                                )
                              : const Icon(Icons.download_rounded),
                        )
                      : null,
                );
              }).toList(),
            );
          },
        ),
  );

  Future<void> _pickFile() async {
    final files = await FilePicker.pickFiles();
    if (files.isNotEmpty) {
      setState(() => _pickedFile = files.first);
    }
  }

  Future<void> _submit() async {
    setState(() => _submitting = true);
    try {
      final formData = FormData.fromMap({
        'message': _messageController.text.trim(),
        if (_pickedFile?.path != null)
          'file': await MultipartFile.fromFile(
            _pickedFile!.path!,
            filename: _pickedFile!.name,
          ),
      });
      await ref
          .read(dioProvider)
          .post('homework/${widget.homeworkId}/submit', data: formData);
      ref.invalidate(homeworkListProvider);
      if (mounted) {
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(const SnackBar(content: Text('Homework submitted.')));
      }
    } on DioException catch (error) {
      if (mounted) _showError(error);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  Future<void> _downloadAttachment() async {
    setState(() => _downloadingAttachment = true);
    try {
      final response = await ref
          .read(dioProvider)
          .get<List<int>>(
            'homework/${widget.homeworkId}/download',
            options: Options(responseType: ResponseType.bytes),
          );
      final file = File(
        '${Directory.systemTemp.path}${Platform.pathSeparator}homework-${widget.homeworkId}',
      );
      await file.writeAsBytes(response.data!, flush: true);
      if (mounted) await OpenFilex.open(file.path);
    } on DioException catch (error) {
      if (mounted) _showError(error);
    } finally {
      if (mounted) setState(() => _downloadingAttachment = false);
    }
  }

  Future<void> _downloadSubmission(int studentId) async {
    setState(() => _downloadingSubmission = studentId);
    try {
      final response = await ref
          .read(dioProvider)
          .get<List<int>>(
            'homework/${widget.homeworkId}/submissions/$studentId/download',
            options: Options(responseType: ResponseType.bytes),
          );
      final file = File(
        '${Directory.systemTemp.path}${Platform.pathSeparator}submission-${widget.homeworkId}-$studentId',
      );
      await file.writeAsBytes(response.data!, flush: true);
      if (mounted) await OpenFilex.open(file.path);
    } on DioException catch (error) {
      if (mounted) _showError(error);
    } finally {
      if (mounted) setState(() => _downloadingSubmission = null);
    }
  }

  void _showError(DioException error) {
    final data = error.response?.data;
    final message = data is Map && data['error'] is Map
        ? ((data['error'] as Map)['message'] ?? 'Something went wrong.')
              .toString()
        : 'Something went wrong.';
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(message)));
  }
}
