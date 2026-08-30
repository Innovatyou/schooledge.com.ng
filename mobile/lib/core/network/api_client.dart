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
    // Dio's default User-Agent ("Dart/x.x (dart:io)") is a classic bot-scanner
    // signature - schooledge.com.ng's host fronts the app with LiteSpeed's Bot
    // Verification challenge, which was confirmed intercepting mobile login
    // requests with a 403 HTML CAPTCHA page instead of ever reaching the PHP
    // app. A real UA alone won't bypass a challenge gated on IP reputation,
    // but it removes one obvious signal - the actual fix is a server-side
    // bot-verification exception for the /api/v1/mobile/ path, since an API
    // client can never complete a JS+reCAPTCHA challenge.
    'User-Agent': 'SchoolEdgeMobile/1.0 (Android)',
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

  // Refresh tokens are single-use/rotating - when several requests 401 at
  // once (e.g. every home-screen tile's provider firing together right after
  // the 15-minute access token expires), each one hitting auth/refresh
  // independently means only the first actually succeeds; every other one
  // gets invalid_refresh_token for a token that's already been rotated away
  // and used to wipe local storage via storage.clear() - which could also
  // race with (and undo) the winning refresh's own storage.save(), logging
  // the user out of an otherwise-healthy session. Memoizing the in-flight
  // refresh so concurrent 401s all await the *same* attempt fixes this.
  Future<String?>? refreshing;
  Future<String?> refreshAccessToken() {
    return refreshing ??= () async {
      try {
        final refresh = await storage.refreshToken();
        if (refresh == null) return null;
        final response = await Dio(
          BaseOptions(
            baseUrl: '${AppConfig.apiBaseUrl.replaceAll(RegExp(r'/+$'), '')}/',
            headers: headers,
          ),
        ).post('auth/refresh', data: {'refresh_token': refresh});
        final tokens = response.data['data']['tokens'] as Map<String, dynamic>;
        await storage.save(
          tokens['access_token'] as String,
          tokens['refresh_token'] as String,
        );
        return tokens['access_token'] as String;
      } catch (_) {
        await storage.clear();
        return null;
      } finally {
        refreshing = null;
      }
    }();
  }

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
        final newToken = await refreshAccessToken();
        if (newToken == null) return handler.next(error);
        final request = error.requestOptions..extra['retried'] = true;
        request.headers['Authorization'] = 'Bearer $newToken';
        try {
          handler.resolve(await dio.fetch(request));
        } catch (_) {
          handler.next(error);
        }
      },
    ),
  );
  return dio;
});
