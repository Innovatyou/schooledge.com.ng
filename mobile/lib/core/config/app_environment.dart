enum AppEnvironment { saas, development, staging, production }

class AppConfig {
  const AppConfig._();
  static const environmentName = String.fromEnvironment(
    'APP_ENV',
    defaultValue: 'development',
  );

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

  static AppEnvironment get environment => AppEnvironment.values.firstWhere(
    (item) => item.name == environmentName,
    orElse: () => AppEnvironment.development,
  );
}
