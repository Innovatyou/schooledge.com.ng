import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:qr_flutter/qr_flutter.dart';
import '../../../core/widgets/depth_card.dart';
import '../data/attendance_repository.dart';

/// The rotating attendance-pass QR, shared by the Attendance tab and the
/// Digital ID card (Profile) so both show the exact same live, signed,
/// ~20s-expiring code from GET attendance/qr-token — not a second static QR,
/// which would reintroduce the spoofing risk that token was built to avoid
/// (see Attendance::qr_token()'s docblock on the backend).
class AttendanceQrPass extends ConsumerStatefulWidget {
  const AttendanceQrPass({
    super.key,
    this.title = 'My attendance pass',
    this.caption =
        'This secure pass refreshes automatically. Show it to your teacher.',
    this.wrapInCard = true,
  });

  final String title;
  final String caption;
  final bool wrapInCard;

  @override
  ConsumerState<AttendanceQrPass> createState() => _AttendanceQrPassState();
}

class _AttendanceQrPassState extends ConsumerState<AttendanceQrPass> {
  Timer? _refreshTimer;

  @override
  void initState() {
    super.initState();
    _refreshTimer = Timer.periodic(
      const Duration(seconds: 15),
      (_) => ref.invalidate(attendanceQrTokenProvider),
    );
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final pass = ref.watch(attendanceQrTokenProvider);
    final body = Column(
      children: [
        Row(
          children: [
            const Icon(Icons.qr_code_2_rounded, color: Color(0xff00897b)),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                widget.title,
                style: const TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.w900,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        pass.when(
          loading: () => const SizedBox(
            height: 190,
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (_, _) => SizedBox(
            height: 150,
            child: Center(
              child: FilledButton.icon(
                onPressed: () => ref.invalidate(attendanceQrTokenProvider),
                icon: const Icon(Icons.refresh_rounded),
                label: const Text('Refresh QR pass'),
              ),
            ),
          ),
          data: (data) => Column(
            children: [
              Semantics(
                label: 'Attendance QR code',
                child: QrImageView(
                  data: data['token'].toString(),
                  size: 190,
                  eyeStyle: const QrEyeStyle(color: Color(0xff12345b)),
                ),
              ),
              const SizedBox(height: 8),
              Text(
                (data['student'] as Map?)?['name']?.toString() ?? '',
                style: const TextStyle(fontWeight: FontWeight.w800),
              ),
              const SizedBox(height: 4),
              Text(widget.caption, textAlign: TextAlign.center),
            ],
          ),
        ),
      ],
    );
    return widget.wrapInCard ? DepthCard(child: body) : body;
  }
}
