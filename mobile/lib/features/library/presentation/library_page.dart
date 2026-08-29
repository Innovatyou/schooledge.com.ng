import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/library_repository.dart';
import 'audiobook_player_page.dart';
import 'book_reader_page.dart';
import 'borrowed_books_section.dart';
import '../../../core/navigation/page_transitions.dart';

/// Client-side "digital library only" toggle - not a server param, just
/// filters the already-fetched catalogue down to has_ebook || has_audiobook.
final _digitalOnlyProvider = StateProvider<bool>((ref) => false);

class LibraryPage extends ConsumerWidget {
  const LibraryPage({super.key});

  void _openBook(BuildContext context, Map<String, dynamic> book) {
    final hasEbook = book['has_ebook'] == true;
    final hasAudiobook = book['has_audiobook'] == true;
    final bookId = book['id'] as int;
    final title = book['title']?.toString() ?? 'Book';
    if (hasEbook && hasAudiobook) {
      showModalBottomSheet<void>(
        context: context,
        builder: (context) => SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ListTile(
                leading: const Icon(Icons.menu_book_rounded),
                title: const Text('Read the e-book'),
                onTap: () {
                  Navigator.of(context).pop();
                  Navigator.of(context).push(
                    moduleRoute<void>(
                      BookReaderPage(bookId: bookId, title: title),
                    ),
                  );
                },
              ),
              ListTile(
                leading: const Icon(Icons.headphones_rounded),
                title: const Text('Listen to the audiobook'),
                onTap: () {
                  Navigator.of(context).pop();
                  Navigator.of(context).push(
                    moduleRoute<void>(
                      AudiobookPlayerPage(bookId: bookId, title: title),
                    ),
                  );
                },
              ),
            ],
          ),
        ),
      );
    } else if (hasEbook) {
      Navigator.of(
        context,
      ).push(moduleRoute<void>(BookReaderPage(bookId: bookId, title: title)));
    } else if (hasAudiobook) {
      Navigator.of(context).push(
        moduleRoute<void>(AudiobookPlayerPage(bookId: bookId, title: title)),
      );
    } else {
      showModuleMessage(
        context,
        'This book only has physical copies available at the school library.',
      );
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final digitalOnly = ref.watch(_digitalOnlyProvider);
    return ModulePage(
      title: 'Library',
      subtitle:
          'Track collected books, return dates, fines and digital resources.',
      icon: Icons.local_library_rounded,
      colors: const [Color(0xff1d7874), Color(0xff2a9d8f)],
      children: [
        const SectionTitle('My collected books'),
        const BorrowedBooksSection(),
        const SectionTitle('School catalogue'),
        TextField(
          decoration: const InputDecoration(
            prefixIcon: Icon(Icons.search_rounded),
            hintText: 'Search by title or author',
            border: OutlineInputBorder(),
            isDense: true,
          ),
          onChanged: (value) => ref.read(libraryFilterProvider.notifier).state =
              ref.read(libraryFilterProvider).copyWith(query: value),
        ),
        const SizedBox(height: 12),
        SizedBox(
          height: 40,
          child: ref
              .watch(libraryCategoriesProvider)
              .maybeWhen(
                data: (categories) {
                  final filter = ref.watch(libraryFilterProvider);
                  return ListView(
                    scrollDirection: Axis.horizontal,
                    children: [
                      Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: FilterChip(
                          label: const Text('All'),
                          selected: filter.categoryId == null,
                          onSelected: (_) =>
                              ref.read(libraryFilterProvider.notifier).state =
                                  filter.copyWith(clearCategory: true),
                        ),
                      ),
                      Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: FilterChip(
                          label: const Text('Digital library'),
                          avatar: const Icon(
                            Icons.auto_stories_rounded,
                            size: 18,
                          ),
                          selected: digitalOnly,
                          onSelected: (value) =>
                              ref.read(_digitalOnlyProvider.notifier).state =
                                  value,
                        ),
                      ),
                      ...categories.map(
                        (category) => Padding(
                          padding: const EdgeInsets.only(right: 8),
                          child: FilterChip(
                            label: Text(category['name']?.toString() ?? ''),
                            selected: filter.categoryId == category['id'],
                            onSelected: (_) =>
                                ref
                                    .read(libraryFilterProvider.notifier)
                                    .state = filter.copyWith(
                                  categoryId: category['id'] as int,
                                ),
                          ),
                        ),
                      ),
                    ],
                  );
                },
                orElse: () => const SizedBox.shrink(),
              ),
        ),
        const SizedBox(height: 12),
        ref
            .watch(libraryBooksProvider)
            .when(
              loading: () => const Padding(
                padding: EdgeInsets.symmetric(vertical: 60),
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => InfoRow(
                icon: Icons.refresh_rounded,
                title: 'Could not load the library',
                subtitle: 'Tap to try again',
                color: const Color(0xffff6b6b),
                onTap: () => ref.invalidate(libraryBooksProvider),
              ),
              data: (allBooks) {
                final books = digitalOnly
                    ? allBooks
                          .where(
                            (book) =>
                                book['has_ebook'] == true ||
                                book['has_audiobook'] == true,
                          )
                          .toList()
                    : allBooks;
                if (books.isEmpty) {
                  return InfoRow(
                    icon: Icons.menu_book_rounded,
                    title: digitalOnly
                        ? 'No digital books yet'
                        : 'No books yet',
                    subtitle: digitalOnly
                        ? 'No e-books or audiobooks match this filter.'
                        : 'Your school has not added any books yet.',
                    color: const Color(0xff829ab1),
                    trailing: const SizedBox.shrink(),
                  );
                }
                return Column(
                  children: books.asMap().entries.map((entry) {
                    final index = entry.key;
                    final book = entry.value;
                    final hasEbook = book['has_ebook'] == true;
                    final hasAudiobook = book['has_audiobook'] == true;
                    final hasDigital = hasEbook || hasAudiobook;
                    final digitalLabel = hasEbook && hasAudiobook
                        ? 'E-book & audiobook available'
                        : hasEbook
                        ? 'E-book available'
                        : hasAudiobook
                        ? 'Audiobook available'
                        : 'Physical copy only';
                    return InfoRow(
                          icon: hasAudiobook && !hasEbook
                              ? Icons.headphones_rounded
                              : Icons.menu_book_rounded,
                          title: book['title']?.toString() ?? 'Book',
                          subtitle:
                              '${book['author'] ?? 'Unknown author'}'
                              '${book['category'] != null ? ' · ${book['category']}' : ''}'
                              ' · $digitalLabel',
                          color: hasDigital
                              ? const Color(0xff2a9d8f)
                              : const Color(0xff829ab1),
                          trailing: Icon(
                            hasDigital
                                ? Icons.north_east_rounded
                                : Icons.lock_outline_rounded,
                          ),
                          onTap: () => _openBook(context, book),
                        )
                        .animate(delay: (index * 40).ms)
                        .fadeIn(duration: 280.ms)
                        .slideX(begin: .05, end: 0);
                  }).toList(),
                );
              },
            ),
      ],
    );
  }
}
