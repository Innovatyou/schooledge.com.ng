import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/widgets/module_ui.dart';

class LookupPage extends ConsumerStatefulWidget {
  const LookupPage({super.key});
  @override
  ConsumerState<LookupPage> createState() => _LookupPageState();
}

class _LookupPageState extends ConsumerState<LookupPage> {
  final _controller = TextEditingController();
  List<Map<String, dynamic>>? _results;
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _search(String query) async {
    if (query.trim().length < 2) {
      setState(() {
        _results = null;
        _error = null;
      });
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final response = await ref
          .read(dioProvider)
          .get('admin/lookup', queryParameters: {'q': query.trim()});
      final results = (response.data['data'] as List).map((item) => Map<String, dynamic>.from(item)).toList();
      if (mounted) setState(() => _results = results);
    } on DioException {
      if (mounted) setState(() => _error = 'Could not search right now.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Lookup')),
    body: Column(
      children: [
        Padding(
          padding: const EdgeInsets.all(20),
          child: TextField(
            controller: _controller,
            onChanged: _search,
            decoration: const InputDecoration(
              hintText: 'Search students, staff, parents…',
              prefixIcon: Icon(Icons.search_rounded),
            ),
          ),
        ),
        if (_loading) const Center(child: CircularProgressIndicator()),
        if (_error != null) Padding(padding: const EdgeInsets.all(20), child: Text(_error!)),
        if (_results != null)
          Expanded(
            child: _results!.isEmpty
                ? const Center(child: Text('No matches.'))
                : ListView(
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    children: _results!.map((result) {
                      return InfoRow(
                        icon: _iconFor(result['type'] as String),
                        title: result['name']?.toString() ?? '',
                        subtitle: '${_labelFor(result['type'] as String)}${result['detail'] != null ? ' · ${result['detail']}' : ''}',
                        color: _colorFor(result['type'] as String),
                        trailing: const SizedBox.shrink(),
                      );
                    }).toList(),
                  ),
          ),
      ],
    ),
  );

  IconData _iconFor(String type) => switch (type) {
    'student' => Icons.school_rounded,
    'staff' => Icons.badge_rounded,
    _ => Icons.family_restroom_rounded,
  };

  String _labelFor(String type) => switch (type) {
    'student' => 'Student',
    'staff' => 'Staff',
    _ => 'Parent',
  };

  Color _colorFor(String type) => switch (type) {
    'student' => const Color(0xff725cff),
    'staff' => const Color(0xff00a896),
    _ => const Color(0xffffa62b),
  };
}
