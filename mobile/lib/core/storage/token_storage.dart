import 'dart:math';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class TokenStorage {
  const TokenStorage(this._storage);
  final FlutterSecureStorage _storage;
  static const accessKey = 'mobile_access_token';
  static const refreshKey = 'mobile_refresh_token';
  static const installationKey = 'mobile_installation_id';

  Future<String?> accessToken() => _storage.read(key: accessKey);
  Future<String?> refreshToken() => _storage.read(key: refreshKey);
  Future<void> save(String access, String refresh) async {
    await _storage.write(key: accessKey, value: access);
    await _storage.write(key: refreshKey, value: refresh);
  }

  /// A stable per-install id, generated once and reused for the lifetime of the
  /// app install - lets the backend recognise "this device" across logins for
  /// the signed-in-devices list (Profile > Security) and, later, push targeting.
  Future<String> installationId() async {
    final existing = await _storage.read(key: installationKey);
    if (existing != null) return existing;
    final random = Random.secure();
    final generated = List.generate(
      32,
      (_) => random.nextInt(16).toRadixString(16),
    ).join();
    await _storage.write(key: installationKey, value: generated);
    return generated;
  }

  /// Clears the session only - the installation id is deliberately kept so the
  /// same device is still recognised (and its old sessions still listed/
  /// revocable) the next time someone signs in on it.
  Future<void> clear() async {
    await _storage.delete(key: accessKey);
    await _storage.delete(key: refreshKey);
  }
}
