import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../data/messages_repository.dart';

class ComposePage extends ConsumerStatefulWidget {
  const ComposePage({super.key});

  @override
  ConsumerState<ComposePage> createState() => _ComposePageState();
}

class _ComposePageState extends ConsumerState<ComposePage> {
  Map<String, dynamic>? _contact;
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
    final contact = _contact;
    final subject = _subjectController.text.trim();
    final body = _bodyController.text.trim();
    if (contact == null || subject.isEmpty || body.isEmpty) {
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(const SnackBar(content: Text('Choose a recipient and fill in all fields.')));
      return;
    }
    setState(() => _sending = true);
    try {
      await ref.read(dioProvider).post(
        'messages',
        data: FormData.fromMap({
          'role_id': contact['role_id'],
          'receiver_id': contact['user_id'],
          'subject': subject,
          'message': body,
        }),
      );
      ref.invalidate(inboxProvider);
      if (mounted) Navigator.of(context).pop();
    } on DioException catch (error) {
      if (mounted) {
        final data = error.response?.data;
        final message = data is Map && data['error'] is Map
            ? ((data['error'] as Map)['message'] ?? 'Could not send message.').toString()
            : 'Could not send message.';
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(SnackBar(content: Text(message)));
      }
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('New message')),
    body: ListView(
      padding: const EdgeInsets.all(20),
      children: [
        ref
            .watch(contactsProvider)
            .when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (error, _) => FilledButton.icon(
                onPressed: () => ref.invalidate(contactsProvider),
                icon: const Icon(Icons.refresh),
                label: const Text('Try again'),
              ),
              data: (contacts) => DropdownButtonFormField<Map<String, dynamic>>(
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
              ? const SizedBox.square(dimension: 20, child: CircularProgressIndicator(strokeWidth: 2))
              : const Text('Send'),
        ),
      ],
    ),
  );
}
