import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dio/dio.dart';
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
  (ref) => AuthController(ref.watch(authRepositoryProvider)),
);

class AuthController extends StateNotifier<AuthState> {
  AuthController(this._repository)
    : super(const AuthState(AuthStage.restoring)) {
    _restore();
  }
  final AuthRepository _repository;
  Future<void> _restore() async => state = AuthState(
    await _repository.isSignedIn() ? AuthStage.signedIn : AuthStage.signedOut,
  );
  Future<void> login(String username, String password) async {
    state = const AuthState(AuthStage.signedOut, isBusy: true);
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
      state = const AuthState(AuthStage.signedIn);
    } catch (error) {
      state = current.copyWith(isBusy: false, error: _message(error));
    }
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

  String _message(Object error) {
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
    }
    return 'Something went wrong. Please try again.';
  }
}
