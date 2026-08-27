import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pdfx/pdfx.dart';
import '../../../core/network/api_client.dart';

class BookReaderPage extends ConsumerStatefulWidget {
  const BookReaderPage({super.key, required this.bookId, required this.title});
  final int bookId;
  final String title;

  @override
  ConsumerState<BookReaderPage> createState() => _BookReaderPageState();
}

class _BookReaderPageState extends ConsumerState<BookReaderPage> {
  PdfControllerPinch? _controller;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final response = await ref
          .read(dioProvider)
          .get<List<int>>(
            'library/books/${widget.bookId}/read',
            options: Options(responseType: ResponseType.bytes),
          );
      final bytes = Uint8List.fromList(response.data!);
      final controller = PdfControllerPinch(
        document: PdfDocument.openData(bytes),
      );
      if (!mounted) return;
      setState(() => _controller = controller);
    } on DioException catch (error) {
      final message = error.response?.statusCode == 404
          ? 'No digital copy is available for this book.'
          : 'Could not open this book. Please try again.';
      if (mounted) setState(() => _error = message);
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: Text(widget.title)),
    body: _error != null
        ? Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(_error!, textAlign: TextAlign.center),
                  const SizedBox(height: 16),
                  FilledButton.icon(
                    onPressed: _load,
                    icon: const Icon(Icons.refresh),
                    label: const Text('Try again'),
                  ),
                ],
              ),
            ),
          )
        : _controller == null
        ? const Center(child: CircularProgressIndicator())
        : PdfViewPinch(controller: _controller!),
  );
}
