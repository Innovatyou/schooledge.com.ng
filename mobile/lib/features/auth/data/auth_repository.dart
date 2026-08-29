import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/push/push_service.dart';
import '../../../core/storage/token_storage.dart';
import '../domain/auth_tokens.dart';

final authRepositoryProvider = Provider(
  (ref) => AuthRepository(
    ref.watch(dioProvider),
    ref.watch(tokenStorageProvider),
    ref.watch(pushServiceProvider),
  ),
);

/// TargetPlatform's own names ("TargetPlatform.android") aren't what a human (or
/// a "signed-in devices" list) wants to read, so this maps to the same short
/// platform strings the backend already treats as free-form (any string works,
/// this just keeps them tidy and consistent).
String _platformName() => switch (defaultTargetPlatform) {
  TargetPlatform.android => 'android',
  TargetPlatform.iOS => 'ios',
  TargetPlatform.windows => 'windows',
  TargetPlatform.macOS => 'macos',
  TargetPlatform.linux => 'linux',
  _ => 'other',
};

class AuthRepository {
  AuthRepository(this._dio, this._storage, this._push);
  final Dio _dio;
  final TokenStorage _storage;
  final PushService _push;
  Future<Map<String, dynamic>> login(String username, String password) async {
    final response = await _dio.post(
      'auth/login',
      data: {
        'username': username,
        'password': password,
        'installation_id': await _storage.installationId(),
        'platform': _platformName(),
      },
    );
    final data = Map<String, dynamic>.from(response.data['data']);
    if (data['requires_otp'] == true) return data;
    await _saveTokens(data['tokens']);
    return data;
  }

  Future<void> verifyOtp(String challengeToken, String code) async {
    final response = await _dio.post(
      'auth/otp/verify',
      data: {
        'challenge_token': challengeToken,
        'code': code,
        'platform': _platformName(),
      },
    );
    await _saveTokens(response.data['data']['tokens']);
  }

  Future<void> resendOtp(String challengeToken) async =>
      _dio.post('auth/otp/resend', data: {'challenge_token': challengeToken});
  Future<bool> isSignedIn() async => await _storage.refreshToken() != null;
  Future<void> logout() async {
    final refresh = await _storage.refreshToken();
    try {
      await _dio.post('auth/logout', data: {'refresh_token': refresh});
    } finally {
      await _storage.clear();
    }
  }

  /// "Remember me" - see TokenStorage.saveCredentials for the storage/trust
  /// rationale. Deliberately independent of logout()/clear(), which only
  /// drops the session tokens: signing out shouldn't force retyping the
  /// password next time if the user asked to be remembered.
  Future<void> rememberCredentials(String username, String password) =>
      _storage.saveCredentials(username, password);
  Future<void> forgetCredentials() => _storage.clearSavedCredentials();
  Future<(String, String)?> savedCredentials() => _storage.savedCredentials();

  Future<void> _saveTokens(dynamic value) async {
    final tokens = AuthTokens.fromJson(Map<String, dynamic>.from(value));
    await _storage.save(tokens.accessToken, tokens.refreshToken);
    unawaited(_push.registerForCurrentSession());
  }
}
