import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';

/// The catalogue search/filter state driving [libraryBooksProvider] - a
/// search query and/or category id, matching the `?q=`/`?category_id=`
/// params `Library::books()` already supports server-side.
class LibraryFilter {
  const LibraryFilter({this.query = '', this.categoryId});
  final String query;
  final int? categoryId;

  LibraryFilter copyWith({
    String? query,
    int? categoryId,
    bool clearCategory = false,
  }) {
    return LibraryFilter(
      query: query ?? this.query,
      categoryId: clearCategory ? null : (categoryId ?? this.categoryId),
    );
  }
}

final libraryFilterProvider = StateProvider<LibraryFilter>(
  (ref) => const LibraryFilter(),
);

final libraryBooksProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final filter = ref.watch(libraryFilterProvider);
      final response = await ref
          .watch(dioProvider)
          .get(
            'library/books',
            queryParameters: {
              if (filter.query.isNotEmpty) 'q': filter.query,
              if (filter.categoryId != null) 'category_id': filter.categoryId,
            },
          );
      return (response.data['data'] as List)
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

final libraryCategoriesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final response = await ref.watch(dioProvider).get('library/categories');
      return (response.data['data'] as List)
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

final libraryIssuesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final response = await ref.watch(dioProvider).get('library/issues');
      return (response.data['data'] as List)
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

final libraryBookDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, bookId) async {
      final response = await ref
          .watch(dioProvider)
          .get('library/books/$bookId');
      return Map<String, dynamic>.from(response.data['data']);
    });

class LibraryRepository {
  LibraryRepository(this._ref);
  final Ref _ref;

  Future<Uint8List> readEbook(int bookId) async {
    final response = await _ref
        .read(dioProvider)
        .get<List<int>>(
          'library/books/$bookId/read',
          options: Options(responseType: ResponseType.bytes),
        );
    return Uint8List.fromList(response.data!);
  }

  /// Whole-file fetch through the already-authenticated Dio client, same
  /// approach as [readEbook] - avoids handing just_audio a bare URL+token
  /// that would go stale mid-playback if the (short-lived) access token
  /// rotates while a long audiobook is still playing.
  Future<Uint8List> readAudiobook(int bookId) async {
    final response = await _ref
        .read(dioProvider)
        .get<List<int>>(
          'library/books/$bookId/listen',
          options: Options(responseType: ResponseType.bytes),
        );
    return Uint8List.fromList(response.data!);
  }
}

final libraryRepositoryProvider = Provider((ref) => LibraryRepository(ref));
