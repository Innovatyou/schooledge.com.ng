import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/session/current_user_provider.dart';
import '../../attendance/data/attendance_repository.dart';
import '../data/messages_repository.dart';

class ComposePage extends ConsumerStatefulWidget {
  const ComposePage({super.key});

  @override
  ConsumerState<ComposePage> createState() => _ComposePageState();
}

class _ComposePageState extends ConsumerState<ComposePage> {
  Map<String, dynamic>? _contact;
  Map<String, dynamic>? _classInfo;
  bool _classMode = false;
  final _subjectController = TextEditingController();
  final _bodyController = TextEditingController();
  bool _sending = false;

  @override
  void dispose() {
    _subjectController.dispose();
    _bodyController.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    final subject = _subjectController.text.trim();
    final body = _bodyController.text.trim();
    final classInfo = _classInfo;
    final contact = _contact;
    if (subject.isEmpty ||
        body.isEmpty ||
        (_classMode && classInfo == null) ||
        (!_classMode && contact == null)) {
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(
          SnackBar(
            content: Text(
              _classMode
                  ? 'Choose a class and fill in all fields.'
                  : 'Choose a recipient and fill in all fields.',
            ),
          ),
        );
      return;
    }
    setState(() => _sending = true);
    try {
      int? recipientCount;
      if (_classMode) {
        final response = await ref
            .read(dioProvider)
            .post(
              'messages/broadcast',
              data: FormData.fromMap({
                'class_id': classInfo!['class_id'],
                'section_id': classInfo['section_id'],
                'subject': subject,
                'message': body,
              }),
            );
        recipientCount = response.data['data']['recipient_count'] as int?;
      } else {
        await ref
            .read(dioProvider)
            .post(
              'messages',
              data: FormData.fromMap({
                'role_id': contact!['role_id'],
                'receiver_id': contact['user_id'],
                'subject': subject,
                'message': body,
              }),
            );
      }
      ref.invalidate(inboxProvider);
      // A snackbar shown right before popping this page would be cut off
      // when its own Scaffold is torn down, so a class broadcast's
      // recipient count - useful feedback a single-recipient send doesn't
      // need - gets a dialog the user dismisses before returning instead.
      if (recipientCount != null && mounted) {
        await showDialog<void>(
          context: context,
          builder: (context) => AlertDialog(
            title: const Text('Message sent'),
            content: Text('Sent to $recipientCount student(s).'),
            actions: [
              FilledButton(
                onPressed: () => Navigator.of(context).pop(),
                child: const Text('OK'),
              ),
            ],
          ),
        );
      }
      if (mounted) Navigator.of(context).pop();
    } on DioException catch (error) {
      if (mounted) {
        final data = error.response?.data;
        final message = data is Map && data['error'] is Map
            ? ((data['error'] as Map)['message'] ?? 'Could not send message.')
                  .toString()
            : 'Could not send message.';
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(SnackBar(content: Text(message)));
      }
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  bool get _isTeacher => ref
      .watch(currentUserProvider)
      .maybeWhen(
        data: (data) => (data['membership']?['role']?['name'] ?? '')
            .toString()
            .toLowerCase()
            .contains('teacher'),
        orElse: () => false,
      );

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('New message')),
    body: ListView(
      padding: const EdgeInsets.all(20),
      children: [
        if (_isTeacher) ...[
          SegmentedButton<bool>(
            segments: const [
              ButtonSegment(value: false, label: Text('Message a person')),
              ButtonSegment(value: true, label: Text('Message my class')),
            ],
            selected: {_classMode},
            onSelectionChanged: (selection) =>
                setState(() => _classMode = selection.first),
          ),
          const SizedBox(height: 16),
        ],
        if (_classMode)
          ref
              .watch(teacherClassesProvider)
              .when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => FilledButton.icon(
                  onPressed: () => ref.invalidate(teacherClassesProvider),
                  icon: const Icon(Icons.refresh),
                  label: const Text('Try again'),
                ),
                data: (classes) =>
                    DropdownButtonFormField<Map<String, dynamic>>(
                      initialValue: _classInfo,
                      decoration: const InputDecoration(labelText: 'Class'),
                      items: classes
                          .map(
                            (cls) => DropdownMenuItem(
                              value: cls,
                              child: Text(
                                '${cls['class_name']} - ${cls['section_name']}',
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (value) => setState(() => _classInfo = value),
                    ),
              )
        else
          ref
              .watch(contactsProvider)
              .when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => FilledButton.icon(
                  onPressed: () => ref.invalidate(contactsProvider),
                  icon: const Icon(Icons.refresh),
                  label: const Text('Try again'),
                ),
                data: (contacts) =>
                    DropdownButtonFormField<Map<String, dynamic>>(
                      initialValue: _contact,
                      decoration: const InputDecoration(labelText: 'To'),
                      items: contacts
                          .map(
                            (contact) => DropdownMenuItem(
                              value: contact,
                              child: Text(contact['name']?.toString() ?? ''),
                            ),
                          )
                          .toList(),
                      onChanged: (value) => setState(() => _contact = value),
                    ),
              ),
        const SizedBox(height: 16),
        TextField(
          controller: _subjectController,
          decoration: const InputDecoration(labelText: 'Subject'),
        ),
        const SizedBox(height: 16),
        TextField(
          controller: _bodyController,
          maxLines: 6,
          decoration: const InputDecoration(labelText: 'Message'),
        ),
        const SizedBox(height: 24),
        FilledButton(
          onPressed: _sending ? null : _send,
          child: _sending
              ? const SizedBox.square(
                  dimension: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Text('Send'),
        ),
      ],
    ),
  );
}
