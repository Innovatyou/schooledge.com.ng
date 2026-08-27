import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/network/api_client.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/attendance_repository.dart';

class TeacherAttendancePage extends ConsumerWidget {
  const TeacherAttendancePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) => ModulePage(
    title: 'Attendance',
    subtitle: 'Take attendance for your classes.',
    icon: Icons.fact_check_rounded,
    colors: const [Color(0xff00897b), Color(0xff16b39a)],
    children: [
      ref
          .watch(teacherClassesProvider)
          .when(
            loading: () => const Padding(
              padding: EdgeInsets.symmetric(vertical: 60),
              child: Center(child: CircularProgressIndicator()),
            ),
            error: (error, _) => InfoRow(
              icon: Icons.refresh_rounded,
              title: 'Could not load your classes',
              subtitle: 'Tap to try again',
              color: const Color(0xffff6b6b),
              onTap: () => ref.invalidate(teacherClassesProvider),
            ),
            data: (classes) {
              if (classes.isEmpty) {
                return const InfoRow(
                  icon: Icons.school_rounded,
                  title: 'No classes assigned',
                  subtitle: 'You are not assigned to a class yet.',
                  color: Color(0xff829ab1),
                  trailing: SizedBox.shrink(),
                );
              }
              return Column(
                children: classes.map((c) {
                  return InfoRow(
                    icon: Icons.groups_rounded,
                    title: '${c['class_name']} ${c['section_name']}',
                    subtitle: 'Tap to take attendance',
                    color: const Color(0xff00a896),
                    onTap: () => Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => RosterPage(
                          classId: c['class_id'] as int,
                          sectionId: c['section_id'] as int,
                          title: '${c['class_name']} ${c['section_name']}',
                        ),
                      ),
                    ),
                  );
                }).toList(),
              );
            },
          ),
    ],
  );
}

class RosterPage extends ConsumerStatefulWidget {
  const RosterPage({
    super.key,
    required this.classId,
    required this.sectionId,
    required this.title,
  });
  final int classId;
  final int sectionId;
  final String title;

  @override
  ConsumerState<RosterPage> createState() => _RosterPageState();
}

class _RosterPageState extends ConsumerState<RosterPage> {
  late DateTime _date = DateTime.now();
  List<Map<String, dynamic>>? _students;
  final Map<int, String> _status = {};
  bool _loading = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final dateStr = DateFormat('yyyy-MM-dd').format(_date);
      final response = await ref
          .read(dioProvider)
          .get(
            'attendance/roster/${widget.classId}/${widget.sectionId}',
            queryParameters: {'date': dateStr},
          );
      final data = Map<String, dynamic>.from(response.data['data']);
      final students = (data['students'] as List).cast<Map<String, dynamic>>();
      _status.clear();
      for (final student in students) {
        _status[student['enroll_id'] as int] = (student['status'] as String?) ?? 'P';
      }
      if (mounted) setState(() => _students = students);
    } on DioException {
      if (mounted) setState(() => _error = 'Could not load the class roster.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime.now().subtract(const Duration(days: 90)),
      lastDate: DateTime.now(),
    );
    if (picked != null) {
      setState(() => _date = picked);
      await _load();
    }
  }

  Future<void> _save() async {
    setState(() => _saving = true);
    try {
      final entries = _status.entries
          .map((entry) => {'enroll_id': entry.key, 'status': entry.value})
          .toList();
      await ref
          .read(dioProvider)
          .post(
            'attendance/capture',
            data: {
              'class_id': widget.classId,
              'section_id': widget.sectionId,
              'date': DateFormat('yyyy-MM-dd').format(_date),
              'entries': entries,
            },
          );
      if (mounted) {
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(
            const SnackBar(content: Text('Attendance saved.')),
          );
      }
    } on DioException catch (error) {
      final data = error.response?.data;
      final message = data is Map && data['error'] is Map
          ? ((data['error'] as Map)['message'] ?? 'Could not save attendance.').toString()
          : 'Could not save attendance.';
      if (mounted) {
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(SnackBar(content: Text(message)));
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: Text(widget.title),
      actions: [
        IconButton(
          onPressed: _pickDate,
          icon: const Icon(Icons.calendar_month_rounded),
        ),
      ],
    ),
    body: _loading
        ? const Center(child: CircularProgressIndicator())
        : _error != null
        ? Center(
            child: FilledButton.icon(
              onPressed: _load,
              icon: const Icon(Icons.refresh),
              label: const Text('Try again'),
            ),
          )
        : ListView(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
            children: [
              Text(
                DateFormat('EEEE, d MMMM y').format(_date),
                style: const TextStyle(fontWeight: FontWeight.w800),
              ),
              const SizedBox(height: 12),
              for (final student in _students ?? const [])
                Card(
                  margin: const EdgeInsets.only(bottom: 8),
                  child: ListTile(
                    title: Text(student['name'] as String),
                    subtitle: Text('Roll ${student['roll']}'),
                    trailing: SegmentedButton<String>(
                      segments: const [
                        ButtonSegment(value: 'P', label: Text('P')),
                        ButtonSegment(value: 'L', label: Text('L')),
                        ButtonSegment(value: 'A', label: Text('A')),
                      ],
                      selected: {_status[student['enroll_id'] as int] ?? 'P'},
                      onSelectionChanged: (selection) => setState(
                        () => _status[student['enroll_id'] as int] = selection.first,
                      ),
                    ),
                  ),
                ),
            ],
          ),
    floatingActionButton: _students == null
        ? null
        : FloatingActionButton.extended(
            onPressed: _saving ? null : _save,
            icon: _saving
                ? const SizedBox.square(
                    dimension: 18,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Icon(Icons.save_rounded),
            label: const Text('Save attendance'),
          ),
  );
}
