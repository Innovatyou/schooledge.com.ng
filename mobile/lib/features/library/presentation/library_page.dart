import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/library_repository.dart';
import 'book_reader_page.dart';

class LibraryPage extends ConsumerWidget {
  const LibraryPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) => ModulePage(
    title: 'Library',
    subtitle: 'Browse the school catalogue and read available e-books.',
    icon: Icons.local_library_rounded,
    colors: const [Color(0xff1d7874), Color(0xff2a9d8f)],
    children: [
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
            data: (books) {
              if (books.isEmpty) {
                return const InfoRow(
                  icon: Icons.menu_book_rounded,
                  title: 'No books yet',
                  subtitle: 'Your school has not added any books yet.',
                  color: Color(0xff829ab1),
                  trailing: SizedBox.shrink(),
                );
              }
              return Column(
                children: books.map((book) {
                  final hasEbook = book['has_ebook'] == true;
                  return InfoRow(
                    icon: Icons.menu_book_rounded,
                    title: book['title']?.toString() ?? 'Book',
                    subtitle:
                        '${book['author'] ?? 'Unknown author'}'
                        '${book['category'] != null ? ' · ${book['category']}' : ''}'
                        '${hasEbook ? ' · E-book available' : ' · Physical copy only'}',
                    color: hasEbook
                        ? const Color(0xff2a9d8f)
                        : const Color(0xff829ab1),
                    trailing: Icon(
                      hasEbook
                          ? Icons.menu_book_rounded
                          : Icons.lock_outline_rounded,
                    ),
                    onTap: hasEbook
                        ? () => Navigator.of(context).push(
                            MaterialPageRoute<void>(
                              builder: (_) => BookReaderPage(
                                bookId: book['id'] as int,
                                title: book['title']?.toString() ?? 'Book',
                              ),
                            ),
                          )
                        : () => showModuleMessage(
                            context,
                            'This book only has physical copies available at the school library.',
                          ),
                  );
                }).toList(),
              );
            },
          ),
    ],
  );
}
