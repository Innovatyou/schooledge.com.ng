enum AppEnvironment { saas, development, staging, production }

class AppConfig {
  const AppConfig._();

  /// Empty (not defaulted to 'development') so applyNativeFlavor() below can
  /// tell "not provided" apart from an explicit choice.
  static const environmentName = String.fromEnvironment('APP_ENV');

  static AppEnvironment? _nativeFlavor;

  /// Safety net for exactly the mistake that shipped a debug build which
  /// silently pointed a real device at the emulator-only 10.0.2.2 address:
  /// `--flavor production` controls the Android build variant, it does NOT
  /// set --dart-define=APP_ENV, and those are easy to forget to pass
  /// together. Called once from main() with the actual Gradle flavor this
  /// build was compiled with (read natively, so it's never wrong); only
  /// takes effect when APP_ENV wasn't explicitly passed - an explicit
  /// --dart-define always wins.
  static void applyNativeFlavor(String? flavorName) {
    if (environmentName.isNotEmpty || flavorName == null) return;
    for (final env in AppEnvironment.values) {
      if (env.name == flavorName) {
        _nativeFlavor = env;
        return;
      }
    }
  }

  /// Explicit --dart-define=API_BASE_URL always wins; otherwise this falls
  /// back to a sensible default per APP_ENV so a production/saas build
  /// doesn't silently point at the emulator-only development default.
  static const _apiBaseUrlOverride = String.fromEnvironment('API_BASE_URL');
  static String get apiBaseUrl {
    if (_apiBaseUrlOverride.isNotEmpty) return _apiBaseUrlOverride;
    switch (environment) {
      case AppEnvironment.production:
      case AppEnvironment.saas:
        return 'https://schooledge.com.ng/api/v1/mobile';
      case AppEnvironment.staging:
      case AppEnvironment.development:
        return 'http://10.0.2.2/schooledge.ng/api/v1/mobile';
    }
  }

  static AppEnvironment get environment {
    if (environmentName.isNotEmpty) {
      return AppEnvironment.values.firstWhere(
        (item) => item.name == environmentName,
        orElse: () => AppEnvironment.development,
      );
    }
    return _nativeFlavor ?? AppEnvironment.development;
  }
}
