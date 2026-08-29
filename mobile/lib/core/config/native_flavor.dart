import 'package:flutter/services.dart';

/// Reads the Gradle product flavor (Android's BuildConfig.FLAVOR) this build
/// was actually compiled with - see AppConfig.applyNativeFlavor() for why.
/// No iOS side yet (this app has no per-flavor iOS schemes set up), so this
/// silently no-ops there; --dart-define=APP_ENV remains the only way to set
/// the environment on iOS until that exists.
Future<String?> readNativeBuildFlavor() async {
  const channel = MethodChannel('schooledge/build_flavor');
  try {
    return await channel.invokeMethod<String>('getFlavor');
  } catch (_) {
    return null;
  }
}
