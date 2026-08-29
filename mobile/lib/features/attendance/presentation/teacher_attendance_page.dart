import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/network/api_client.dart';
import '../../../core/widgets/module_ui.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../data/attendance_repository.dart';
import '../../../core/navigation/page_transitions.dart';

class TeacherAttendancePage extends ConsumerWidget {
  const TeacherAttendancePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) => ModulePage(
    title: 'Attendance',
    subtitle: 'Take attendance for your classes.',
    icon: Icons.fact_check_rounded,
    colors: const [Color(0xff00897b), Color(0xff16b39a)],
    children: [
      InfoRow(
        icon: Icons.qr_code_scanner_rounded,
        title: 'Scan attendance QR',
        subtitle: 'Scan a student’s rotating SchoolEdge pass',
        color: const Color(0xff6c5ce7),
        onTap: () => Navigator.of(
          context,
        ).push(moduleRoute<void>(const AttendanceScannerPage())),
      ),
      const SectionTitle('Your classes'),
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
                      moduleRoute<void>(
                        RosterPage(
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

class AttendanceScannerPage extends ConsumerStatefulWidget {
  const AttendanceScannerPage({super.key});
  @override
  ConsumerState<AttendanceScannerPage> createState() =>
      _AttendanceScannerPageState();
}

class _AttendanceScannerPageState extends ConsumerState<AttendanceScannerPage> {
  final MobileScannerController _controller = MobileScannerController(
    formats: const [BarcodeFormat.qrCode],
  );
  final TextEditingController _manualController = TextEditingController();
  final FocusNode _manualFocusNode = FocusNode();
  bool _submitting = false;

  Future<void> _scan(BarcodeCapture capture) async {
    if (_submitting) return;
    final token = capture.barcodes.firstOrNull?.rawValue;
    if (token == null || token.isEmpty) return;
    await _controller.stop();
    await _submitToken(token);
    if (mounted) await _controller.start();
  }

  Future<void> _submitManualToken() async {
    if (_submitting) return;
    final token = _manualController.text.trim();
    _manualController.clear();
    if (token.isEmpty) return;
    await _submitToken(token);
    if (mounted) _manualFocusNode.requestFocus();
  }

  // Shared by the camera detector and the hardware-scanner input field below —
  // a paired HID scanner just types the decoded QR text into a focused field.
  Future<void> _submitToken(String token) async {
    setState(() => _submitting = true);
    try {
      final response = await ref
          .read(dioProvider)
          .post('attendance/scan', data: {'token': token});
      final data = Map<String, dynamic>.from(response.data['data']);
      final student = Map<String, dynamic>.from(data['student']);
      if (!mounted) return;
      await showDialog<void>(
        context: context,
        builder: (context) => AlertDialog(
          icon: Icon(
            data['already_marked'] == true
                ? Icons.info_rounded
                : Icons.check_circle_rounded,
            color: const Color(0xff00a896),
            size: 44,
          ),
          title: Text(
            data['already_marked'] == true
                ? 'Already marked'
                : 'Attendance recorded',
          ),
          content: Text('${student['name']} is present for ${data['date']}.'),
          actions: [
            FilledButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Scan another'),
            ),
          ],
        ),
      );
    } on DioException catch (error) {
      final body = error.response?.data;
      final message = body is Map && body['error'] is Map
          ? ((body['error'] as Map)['message'] ??
                    'This QR code could not be accepted.')
                .toString()
          : 'This QR code could not be accepted.';
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    _manualController.dispose();
    _manualFocusNode.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: const Color(0xff071a33),
    appBar: AppBar(
      title: const Text('Scan attendance'),
      backgroundColor: const Color(0xff071a33),
      foregroundColor: Colors.white,
    ),
    body: Stack(
      fit: StackFit.expand,
      children: [
        MobileScanner(
          controller: _controller,
          onDetect: _scan,
          errorBuilder: (context, error) => Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Text(
                'Camera unavailable: ${error.errorDetails?.message ?? error.errorCode}',
                textAlign: TextAlign.center,
                style: const TextStyle(color: Colors.white),
              ),
            ),
          ),
        ),
        Center(
          child: Container(
            width: 260,
            height: 260,
            decoration: BoxDecoration(
              border: Border.all(color: const Color(0xffffc857), width: 4),
              borderRadius: BorderRadius.circular(32),
            ),
          ),
        ),
        Positioned(
          left: 24,
          right: 24,
          bottom: 44,
          child: Column(
            children: [
              if (_submitting)
                const CircularProgressIndicator(color: Color(0xffffc857)),
              const SizedBox(height: 12),
              const Text(
                'Place the student’s rotating QR pass inside the frame',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: .08),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: Colors.white24),
                ),
                child: Row(
                  children: [
                    const Icon(
                      Icons.keyboard_rounded,
                      color: Colors.white54,
                      size: 18,
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextField(
                        controller: _manualController,
                        focusNode: _manualFocusNode,
                        autofocus: true,
                        // Suppresses the on-screen keyboard while still accepting
                        // keystrokes from a paired hardware (Bluetooth HID) scanner.
                        keyboardType: TextInputType.none,
                        style: const TextStyle(color: Colors.white),
                        cursorColor: Colors.white,
                        textInputAction: TextInputAction.done,
                        decoration: const InputDecoration(
                          isDense: true,
                          border: InputBorder.none,
                          hintText: 'Hardware scanner input',
                          hintStyle: TextStyle(color: Colors.white38),
                        ),
                        onSubmitted: (_) => _submitManualToken(),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ],
    ),
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
        _status[student['enroll_id'] as int] =
            (student['status'] as String?) ?? 'P';
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
          ..showSnackBar(const SnackBar(content: Text('Attendance saved.')));
      }
    } on DioException catch (error) {
      final data = error.response?.data;
      final message = data is Map && data['error'] is Map
          ? ((data['error'] as Map)['message'] ?? 'Could not save attendance.')
                .toString()
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
                        () => _status[student['enroll_id'] as int] =
                            selection.first,
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
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white,
                    ),
                  )
                : const Icon(Icons.save_rounded),
            label: const Text('Save attendance'),
          ),
  );
}
