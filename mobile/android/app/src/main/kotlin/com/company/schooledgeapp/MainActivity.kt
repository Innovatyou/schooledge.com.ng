package com.company.schooledgeapp

import io.flutter.embedding.android.FlutterFragmentActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

// local_auth (biometric/Face ID sign-in) requires a FragmentActivity host.
class MainActivity : FlutterFragmentActivity() {
    // Lets the Dart side read the actual Gradle product flavor this build was
    // compiled with (BuildConfig.FLAVOR is generated automatically, no extra
    // wiring needed) as a safety net for --dart-define=APP_ENV being left off
    // a `--flavor production` build - see AppConfig.applyNativeFlavor().
    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, "schooledge/build_flavor")
            .setMethodCallHandler { call, result ->
                if (call.method == "getFlavor") result.success(BuildConfig.FLAVOR) else result.notImplemented()
            }
    }
}
