import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:local_auth/local_auth.dart';

final biometricServiceProvider = Provider((ref) => BiometricService());

/// Thin wrapper around local_auth - on iOS this prompts Face ID/Touch ID,
/// on Android the system BiometricPrompt (fingerprint or face unlock,
/// whatever the device has enrolled). There's no separate "facial
/// recognition" API on stock devices; both platforms fold face and
/// fingerprint into the same biometric prompt.
class BiometricService {
  final _auth = LocalAuthentication();

  Future<bool> isAvailable() async {
    try {
      final supported = await _auth.isDeviceSupported();
      final canCheck = await _auth.canCheckBiometrics;
      return supported && canCheck;
    } catch (_) {
      return false;
    }
  }

  Future<bool> authenticate() async {
    try {
      return await _auth.authenticate(
        localizedReason: 'Sign in to SchoolEdge',
        options: const AuthenticationOptions(
          biometricOnly: true,
          stickyAuth: true,
        ),
      );
    } catch (_) {
      return false;
    }
  }
}
