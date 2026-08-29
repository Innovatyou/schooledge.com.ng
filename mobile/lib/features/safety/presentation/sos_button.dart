import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import '../../../core/session/current_user_provider.dart';
import '../data/safety_repository.dart';

/// Persistent quick-action pair, mounted once at the app shell level (not
/// per-tab) so it's reachable from anywhere. Two DELIBERATELY separate
/// controls, not one button with tap-vs-hold: a plain tap shares location
/// (low urgency), while SOS only fires on a ~2s press-and-hold - never a
/// plain tap - so it can't be triggered by an accidental touch the way a
/// single SOS tap could be.
class SafetyQuickActions extends ConsumerWidget {
  const SafetyQuickActions({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final role = ref
        .watch(currentUserProvider)
        .maybeWhen(
          data: (data) =>
              (data['membership']?['role']?['name'] ?? '').toString(),
          orElse: () => '',
        );
    final canSend =
        role.toLowerCase().contains('student') ||
        role.toLowerCase().contains('teacher');
    if (!canSend) return const SizedBox.shrink();
    return const _SafetyQuickActionsContent();
  }
}

class _SafetyQuickActionsContent extends ConsumerStatefulWidget {
  const _SafetyQuickActionsContent();

  @override
  ConsumerState<_SafetyQuickActionsContent> createState() =>
      _SafetyQuickActionsContentState();
}

class _SafetyQuickActionsContentState
    extends ConsumerState<_SafetyQuickActionsContent>
    with SingleTickerProviderStateMixin {
  late final AnimationController _holdController = AnimationController(
    vsync: this,
    duration: const Duration(seconds: 2),
  );
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _holdController.addStatusListener((status) {
      if (status == AnimationStatus.completed) _triggerSos();
    });
  }

  @override
  void dispose() {
    _holdController.dispose();
    super.dispose();
  }

  Future<Position?> _capturePosition() async {
    try {
      return await ref.read(safetyRepositoryProvider).currentPosition();
    } on LocationPermissionDenied {
      if (mounted) await _showLocationDeniedDialog();
      return null;
    } on LocationTimedOut {
      if (mounted) {
        _showError(
          "Couldn't get a location fix in time. Make sure GPS/location is on, move to an open area and try again.",
        );
      }
      return null;
    } catch (_) {
      if (mounted) _showError('Could not get your location. Try again.');
      return null;
    }
  }

  Future<void> _shareLocation() async {
    if (_busy) return;
    setState(() => _busy = true);
    HapticFeedback.selectionClick();
    final position = await _capturePosition();
    if (position != null) {
      try {
        await ref
            .read(safetyRepositoryProvider)
            .submitAlert(alertType: 'share', position: position);
        ref.invalidate(safetyAlertsProvider);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Your location was shared.')),
          );
        }
      } on DioException catch (error) {
        _showError(
          _messageFor(error, 'Could not share your location. Try again.'),
        );
      } catch (_) {
        _showError('Could not share your location. Try again.');
      }
    }
    if (mounted) setState(() => _busy = false);
  }

  Future<void> _triggerSos() async {
    _holdController.stop();
    if (_busy) {
      _holdController.reset();
      return;
    }
    setState(() => _busy = true);
    HapticFeedback.heavyImpact();
    final position = await _capturePosition();
    if (position != null) {
      try {
        await ref
            .read(safetyRepositoryProvider)
            .submitAlert(alertType: 'sos', position: position);
        ref.invalidate(safetyAlertsProvider);
        if (mounted) {
          await showDialog<void>(
            context: context,
            builder: (context) => AlertDialog(
              icon: const Icon(
                Icons.check_circle_rounded,
                color: Color(0xff00a896),
                size: 44,
              ),
              title: const Text('SOS sent'),
              content: const Text(
                'Your location has been sent to your school and family.',
              ),
              actions: [
                FilledButton(
                  onPressed: () => Navigator.of(context).pop(),
                  child: const Text('OK'),
                ),
              ],
            ),
          );
        }
      } on DioException catch (error) {
        _showError(_messageFor(error, 'Could not send your SOS. Try again.'));
      } catch (_) {
        _showError('Could not send your SOS. Try again.');
      }
    }
    if (mounted) setState(() => _busy = false);
    _holdController.reset();
  }

  void _showError(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
        duration: const Duration(seconds: 6),
      ),
    );
  }

  /// Same pattern used app-wide (e.g. TeacherAttendancePage, RosterPage):
  /// surface the backend's own error.message (e.g. "This is a read-only
  /// demo school...") instead of a generic retry string whenever it exists.
  String _messageFor(DioException error, String fallback) {
    final body = error.response?.data;
    return body is Map && body['error'] is Map
        ? ((body['error'] as Map)['message'] ?? fallback).toString()
        : fallback;
  }

  Future<void> _showLocationDeniedDialog() => showDialog<void>(
    context: context,
    builder: (context) => AlertDialog(
      title: const Text('Location needed'),
      content: const Text(
        'Turn on location access for SchoolEdge to share your location or send an SOS. Location is only read when you tap these buttons - never in the background.',
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: () {
            Navigator.of(context).pop();
            Geolocator.openAppSettings();
          },
          child: const Text('Open Settings'),
        ),
      ],
    ),
  );

  @override
  Widget build(BuildContext context) => Positioned(
    right: 16,
    bottom: 88,
    child: Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Tooltip(
          message: 'Share my location',
          child: FloatingActionButton.small(
            heroTag: 'safety_share',
            backgroundColor: const Color(0xff168aad),
            onPressed: _busy ? null : _shareLocation,
            child: const Icon(
              Icons.share_location_rounded,
              color: Colors.white,
            ),
          ),
        ),
        const SizedBox(height: 10),
        Tooltip(
          message: 'Hold for 2 seconds to send an SOS',
          child: GestureDetector(
            onLongPressStart: (_) {
              if (_busy) return;
              HapticFeedback.mediumImpact();
              _holdController.forward(from: 0);
            },
            onLongPressEnd: (_) => _holdController.reverse(),
            onLongPressCancel: () => _holdController.reverse(),
            child: AnimatedBuilder(
              animation: _holdController,
              builder: (context, child) => Container(
                width: 64,
                height: 64,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: const LinearGradient(
                    colors: [Color(0xffff6b6b), Color(0xffd64545)],
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: .3),
                      blurRadius: 14,
                      offset: const Offset(0, 6),
                    ),
                  ],
                ),
                child: Stack(
                  alignment: Alignment.center,
                  children: [
                    if (_holdController.value > 0)
                      SizedBox(
                        width: 64,
                        height: 64,
                        child: CircularProgressIndicator(
                          value: _holdController.value,
                          strokeWidth: 4,
                          color: Colors.white,
                          backgroundColor: Colors.white24,
                        ),
                      ),
                    _busy
                        ? const SizedBox(
                            width: 22,
                            height: 22,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : const Text(
                            'SOS',
                            style: TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.w900,
                              fontSize: 14,
                            ),
                          ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ],
    ),
  );
}
