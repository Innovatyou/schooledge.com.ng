import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

/// Per-book reading/listening position, client-local only (shared_preferences),
/// matching the same local-only-preference pattern already used by the
/// Planner to-do list (todo_local_store.dart) - there's no reason a "resume
/// where I left off" position needs a backend round-trip.
class LibraryProgressStore {
  const LibraryProgressStore._();

  static const _ebookKey = 'schooledge.library_ebook_progress';
  static const _audioKey = 'schooledge.library_audio_progress';

  static Future<int?> lastPage(int bookId) async {
    final map = await _read(_ebookKey);
    return map[bookId.toString()];
  }

  static Future<void> saveLastPage(int bookId, int page) async {
    final map = await _read(_ebookKey);
    map[bookId.toString()] = page;
    await _write(_ebookKey, map);
  }

  static Future<Duration?> lastAudioPosition(int bookId) async {
    final map = await _read(_audioKey);
    final seconds = map[bookId.toString()];
    return seconds == null ? null : Duration(seconds: seconds);
  }

  static Future<void> saveLastAudioPosition(
    int bookId,
    Duration position,
  ) async {
    final map = await _read(_audioKey);
    map[bookId.toString()] = position.inSeconds;
    await _write(_audioKey, map);
  }

  static Future<Map<String, int>> _read(String key) async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(key);
    if (raw == null) return {};
    return Map<String, int>.from(jsonDecode(raw) as Map);
  }

  static Future<void> _write(String key, Map<String, int> map) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(key, jsonEncode(map));
  }
}
