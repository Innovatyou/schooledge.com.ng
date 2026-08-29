import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import '../../../core/network/api_client.dart';

final safetyAlertsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final response = await ref.watch(dioProvider).get('safety/alerts');
      return (response.data['data']['alerts'] as List)
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

/// Thrown when the device denies (or has permanently denied) location
/// access, so the UI can show a clear "enable location" message instead of
/// a generic error.
class LocationPermissionDenied implements Exception {}

/// Thrown when no fix arrives within the time limit (weak/no signal - common
/// indoors or on an emulator with no GPS hardware), so the UI can show a
/// specific "couldn't get a fix" message instead of spinning forever. Without
/// a timeLimit, Geolocator.getCurrentPosition() can hang indefinitely if the
/// platform never delivers a location, which would leave the SOS/share
/// button stuck busy with no way out - the exact failure mode this exists to
/// prevent.
class LocationTimedOut implements Exception {}

class SafetyRepository {
  SafetyRepository(this._ref);
  final Ref _ref;

  /// One-shot GPS fix - never a continuous stream/background subscription.
  /// LocationAccuracy.medium (network/Wi-Fi based, not GPS-fusion) rather
  /// than .high - the high-accuracy tier pulls in Bluetooth-assisted
  /// positioning on Android, which is unnecessary precision for "which
  /// school/area is this alert from" and measurably slower to resolve.
  Future<Position> currentPosition() async {
    if (!await Geolocator.isLocationServiceEnabled()) {
      throw LocationPermissionDenied();
    }
    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied ||
        permission == LocationPermission.deniedForever) {
      throw LocationPermissionDenied();
    }
    try {
      return await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.medium,
          timeLimit: Duration(seconds: 12),
        ),
      );
    } on TimeoutException {
      throw LocationTimedOut();
    }
  }

  Future<void> submitAlert({
    required String alertType,
    required Position position,
    String? note,
  }) async {
    await _ref
        .read(dioProvider)
        .post(
          'safety/alerts',
          data: {
            'alert_type': alertType,
            'latitude': position.latitude,
            'longitude': position.longitude,
            'accuracy_meters': position.accuracy,
            if (note != null && note.isNotEmpty) 'note': note,
          },
        );
  }

  Future<void> acknowledge(int alertId) async {
    await _ref.read(dioProvider).post('safety/alerts/$alertId/acknowledge');
  }
}

final safetyRepositoryProvider = Provider((ref) => SafetyRepository(ref));
