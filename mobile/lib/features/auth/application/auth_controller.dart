import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dio/dio.dart';
import '../../../core/auth/biometric_service.dart';
import '../data/auth_repository.dart';

enum AuthStage { restoring, signedOut, otp, signedIn }

class AuthState {
  const AuthState(
    this.stage, {
    this.challengeToken,
    this.otpType,
    this.destination,
    this.isBusy = false,
    this.error,
  });
  final AuthStage stage;
  final String? challengeToken;
  final String? otpType;
  final String? destination;
  final bool isBusy;
  final String? error;
  AuthState copyWith({bool? isBusy, String? error}) => AuthState(
    stage,
    challengeToken: challengeToken,
    otpType: otpType,
    destination: destination,
    isBusy: isBusy ?? this.isBusy,
    error: error,
  );
}

final authControllerProvider = StateNotifierProvider<AuthController, AuthState>(
  (ref) => AuthController(
    ref.watch(authRepositoryProvider),
    ref.watch(biometricServiceProvider),
  ),
);

class AuthController extends StateNotifier<AuthState> {
  AuthController(this._repository, this._biometrics)
    : super(const AuthState(AuthStage.restoring)) {
    _restore();
  }
  final AuthRepository _repository;
  final BiometricService _biometrics;

  // Carried from login() through the optional OTP step so "remember me" is
  // applied at the point the user actually ends up signed in, whichever path
  // got them there.
  bool _pendingRemember = false;
  String? _pendingUsername;
  String? _pendingPassword;

  Future<void> _restore() async => state = AuthState(
    await _repository.isSignedIn() ? AuthStage.signedIn : AuthStage.signedOut,
  );

  Future<bool> biometricLoginAvailable() async =>
      await _biometrics.isAvailable() &&
      await _repository.savedCredentials() != null;

  Future<String?> savedUsername() async =>
      (await _repository.savedCredentials())?.$1;

  Future<void> loginWithBiometrics() async {
    final saved = await _repository.savedCredentials();
    if (saved == null) return;
    if (!await _biometrics.authenticate()) return;
    await login(saved.$1, saved.$2, rememberMe: true);
  }

  Future<void> login(
    String username,
    String password, {
    bool rememberMe = false,
  }) async {
    state = const AuthState(AuthStage.signedOut, isBusy: true);
    _pendingRemember = rememberMe;
    _pendingUsername = username;
    _pendingPassword = password;
    try {
      final result = await _repository.login(username, password);
      if (result['requires_otp'] == true) {
        final challenge = Map<String, dynamic>.from(result['challenge']);
        state = AuthState(
          AuthStage.otp,
          challengeToken: challenge['token'],
          otpType: challenge['type'],
          destination: challenge['destination'],
        );
      } else {
        await _applyRememberMe();
        state = const AuthState(AuthStage.signedIn);
      }
    } catch (error) {
      state = AuthState(AuthStage.signedOut, error: _message(error));
    }
  }

  Future<void> verifyOtp(String code) async {
    final current = state;
    state = current.copyWith(isBusy: true);
    try {
      await _repository.verifyOtp(current.challengeToken!, code);
      await _applyRememberMe();
      state = const AuthState(AuthStage.signedIn);
    } catch (error) {
      state = current.copyWith(isBusy: false, error: _message(error));
    }
  }

  Future<void> _applyRememberMe() async {
    if (_pendingRemember && _pendingUsername != null && _pendingPassword != null) {
      await _repository.rememberCredentials(_pendingUsername!, _pendingPassword!);
    } else if (!_pendingRemember) {
      await _repository.forgetCredentials();
    }
    _pendingUsername = null;
    _pendingPassword = null;
  }

  Future<void> resendOtp() async {
    final current = state;
    state = current.copyWith(isBusy: true);
    try {
      await _repository.resendOtp(current.challengeToken!);
      state = current.copyWith(isBusy: false);
    } catch (error) {
      state = current.copyWith(isBusy: false, error: _message(error));
    }
  }

  void cancelOtp() => state = const AuthState(AuthStage.signedOut);
  Future<void> logout() async {
    await _repository.logout();
    state = const AuthState(AuthStage.signedOut);
  }

  // Keeps a status/type hint on truly-unexpected failures (a non-JSON body,
  // a response shape the app doesn't recognise, or a non-Dio exception like a
  // JSON-decode/type error) instead of a dead-end generic string, so a report
  // like "something went wrong" comes with something a next debugging pass
  // can act on.
  String _message(Object error) {
    // TEMPORARY diagnostic - remove once the live-device login investigation
    // is done. The UI only ever shows the friendly string below, so without
    // this the real DioException/status/body never surfaces anywhere visible.
    if (kDebugMode) {
      if (error is DioException) {
        debugPrint(
          'LOGIN DEBUG: DioException type=${error.type} '
          'status=${error.response?.statusCode} '
          'uri=${error.requestOptions.uri} '
          'body=${error.response?.data} '
          'message=${error.message}',
        );
      } else {
        debugPrint('LOGIN DEBUG: non-Dio error ${error.runtimeType}: $error');
      }
    }
    if (error is DioException) {
      final body = error.response?.data;
      if (body is Map &&
          body['error'] is Map &&
          body['error']['message'] != null) {
        return body['error']['message'].toString();
      }
      if (error.type == DioExceptionType.connectionError) {
        return 'Cannot reach SchoolEdge. Check your connection and try again.';
      }
      if (error.type == DioExceptionType.connectionTimeout ||
          error.type == DioExceptionType.receiveTimeout ||
          error.type == DioExceptionType.sendTimeout) {
        return 'SchoolEdge is taking too long to respond. Please try again.';
      }
      final status = error.response?.statusCode;
      if (status != null) {
        return 'Something went wrong on the server (error $status). Please try again or contact support.';
      }
    }
    return 'Something went wrong. Please try again.';
  }
}
