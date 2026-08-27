import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../config/app_environment.dart';
import '../storage/token_storage.dart';

final tokenStorageProvider = Provider(
  (ref) => const TokenStorage(FlutterSecureStorage()),
);

final dioProvider = Provider<Dio>((ref) {
  final storage = ref.watch(tokenStorageProvider);
  final apiUri = Uri.parse(AppConfig.apiBaseUrl);
  final headers = <String, dynamic>{
    'Accept': 'application/json',
    if (AppConfig.environment == AppEnvironment.development &&
        apiUri.host == '10.0.2.2')
      'Host': 'localhost',
  };
  final dio = Dio(
    BaseOptions(
      baseUrl: '${AppConfig.apiBaseUrl.replaceAll(RegExp(r'/+$'), '')}/',
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 20),
      headers: headers,
    ),
  );
  dio.interceptors.add(
    InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await storage.accessToken();
        if (token != null) options.headers['Authorization'] = 'Bearer $token';
        handler.next(options);
      },
      onError: (error, handler) async {
        if (error.response?.statusCode != 401 ||
            error.requestOptions.extra['retried'] == true) {
          return handler.next(error);
        }
        final refresh = await storage.refreshToken();
        if (refresh == null) return handler.next(error);
        try {
          final response = await Dio(
            BaseOptions(
              baseUrl:
                  '${AppConfig.apiBaseUrl.replaceAll(RegExp(r'/+$'), '')}/',
              headers: headers,
            ),
          ).post('auth/refresh', data: {'refresh_token': refresh});
          final tokens =
              response.data['data']['tokens'] as Map<String, dynamic>;
          await storage.save(
            tokens['access_token'] as String,
            tokens['refresh_token'] as String,
          );
          final request = error.requestOptions..extra['retried'] = true;
          request.headers['Authorization'] = 'Bearer ${tokens['access_token']}';
          handler.resolve(await dio.fetch(request));
        } catch (_) {
          await storage.clear();
          handler.next(error);
        }
      },
    ),
  );
  return dio;
});
