import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:just_audio/just_audio.dart';
import '../data/library_progress_store.dart';
import '../data/library_repository.dart';

class AudiobookPlayerPage extends ConsumerStatefulWidget {
  const AudiobookPlayerPage({
    super.key,
    required this.bookId,
    required this.title,
  });
  final int bookId;
  final String title;

  @override
  ConsumerState<AudiobookPlayerPage> createState() =>
      _AudiobookPlayerPageState();
}

class _AudiobookPlayerPageState extends ConsumerState<AudiobookPlayerPage> {
  final _player = AudioPlayer();
  String? _error;
  bool _loading = true;
  File? _tempFile;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _error = null;
      _loading = true;
    });
    try {
      final bytes = await ref
          .read(libraryRepositoryProvider)
          .readAudiobook(widget.bookId);
      final tempDir = await Directory.systemTemp.createTemp('schooledge_audio');
      final file = File('${tempDir.path}/${widget.bookId}.audio');
      await file.writeAsBytes(bytes);
      await _player.setFilePath(file.path);
      final lastPosition = await LibraryProgressStore.lastAudioPosition(
        widget.bookId,
      );
      if (lastPosition != null) await _player.seek(lastPosition);
      if (!mounted) return;
      setState(() {
        _tempFile = file;
        _loading = false;
      });
    } on DioException catch (error) {
      final message = error.response?.statusCode == 404
          ? 'No audiobook is available for this book.'
          : 'Could not open this audiobook. Please try again.';
      if (mounted) setState(() => _error = message);
    } finally {
      if (mounted && _error != null) setState(() => _loading = false);
    }
  }

  @override
  void dispose() {
    LibraryProgressStore.saveLastAudioPosition(widget.bookId, _player.position);
    _player.dispose();
    _tempFile?.delete().ignore();
    super.dispose();
  }

  String _format(Duration d) {
    final minutes = d.inMinutes.remainder(60).toString().padLeft(2, '0');
    final seconds = d.inSeconds.remainder(60).toString().padLeft(2, '0');
    final hours = d.inHours;
    return hours > 0 ? '$hours:$minutes:$seconds' : '$minutes:$seconds';
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
        : _loading
        ? const Center(child: CircularProgressIndicator())
        : Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  Icons.headphones_rounded,
                  size: 96,
                  color: Theme.of(context).colorScheme.primary,
                ),
                const SizedBox(height: 32),
                StreamBuilder<Duration>(
                  stream: _player.positionStream,
                  builder: (context, snapshot) {
                    final position = snapshot.data ?? Duration.zero;
                    final duration = _player.duration ?? Duration.zero;
                    return Column(
                      children: [
                        Slider(
                          min: 0,
                          max: duration.inMilliseconds > 0
                              ? duration.inMilliseconds.toDouble()
                              : 1,
                          value: position.inMilliseconds
                              .clamp(0, duration.inMilliseconds)
                              .toDouble(),
                          onChanged: (value) => _player.seek(
                            Duration(milliseconds: value.round()),
                          ),
                        ),
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 8),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(_format(position)),
                              Text(_format(duration)),
                            ],
                          ),
                        ),
                      ],
                    );
                  },
                ),
                const SizedBox(height: 16),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    IconButton(
                      iconSize: 36,
                      icon: const Icon(Icons.replay_10_rounded),
                      onPressed: () => _player.seek(
                        _player.position - const Duration(seconds: 10),
                      ),
                    ),
                    const SizedBox(width: 16),
                    StreamBuilder<PlayerState>(
                      stream: _player.playerStateStream,
                      builder: (context, snapshot) {
                        final playing = snapshot.data?.playing ?? false;
                        return IconButton.filled(
                          iconSize: 48,
                          icon: Icon(
                            playing
                                ? Icons.pause_rounded
                                : Icons.play_arrow_rounded,
                          ),
                          onPressed: () =>
                              playing ? _player.pause() : _player.play(),
                        );
                      },
                    ),
                    const SizedBox(width: 16),
                    IconButton(
                      iconSize: 36,
                      icon: const Icon(Icons.forward_10_rounded),
                      onPressed: () => _player.seek(
                        _player.position + const Duration(seconds: 10),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
  );
}
