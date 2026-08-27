import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class TokenStorage {
  const TokenStorage(this._storage);
  final FlutterSecureStorage _storage;
  static const accessKey = 'mobile_access_token';
  static const refreshKey = 'mobile_refresh_token';
  Future<String?> accessToken() => _storage.read(key: accessKey);
  Future<String?> refreshToken() => _storage.read(key: refreshKey);
  Future<void> save(String access, String refresh) async {
    await _storage.write(key: accessKey, value: access);
    await _storage.write(key: refreshKey, value: refresh);
  }

  Future<void> clear() => _storage.deleteAll();
}
