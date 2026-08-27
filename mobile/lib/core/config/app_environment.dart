enum AppEnvironment { saas, development, staging, production }

class AppConfig {
  const AppConfig._();
  static const environmentName = String.fromEnvironment(
    'APP_ENV',
    defaultValue: 'development',
  );
  static const apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2/schooledge.ng/api/v1/mobile',
  );
  static AppEnvironment get environment => AppEnvironment.values.firstWhere(
    (item) => item.name == environmentName,
    orElse: () => AppEnvironment.development,
  );
}
