import 'dart:io';
import 'package:dio/dio.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/app_environment.dart';
import '../../../core/network/api_client.dart';
import '../../../core/session/current_user_provider.dart';
import '../../../core/widgets/module_ui.dart';

class ProfilePhotoEditor extends ConsumerStatefulWidget {
  const ProfilePhotoEditor({
    super.key,
    required this.name,
    this.initialPhotoUrl,
  });
  final String name;
  final String? initialPhotoUrl;

  @override
  ConsumerState<ProfilePhotoEditor> createState() => _ProfilePhotoEditorState();
}

class _ProfilePhotoEditorState extends ConsumerState<ProfilePhotoEditor> {
  String? photoUrl;
  bool uploading = false;

  @override
  void initState() {
    super.initState();
    photoUrl = widget.initialPhotoUrl;
  }

  @override
  Widget build(BuildContext context) {
    final url = _reachableUrl(photoUrl);
    return Stack(
      clipBehavior: Clip.none,
      children: [
        CircleAvatar(
          radius: 48,
          backgroundColor: const Color(0xff163a70),
          foregroundImage: url == null ? null : NetworkImage(url),
          child: uploading
              ? const CircularProgressIndicator(color: Colors.white)
              : Text(
                  widget.name.isEmpty ? 'S' : widget.name[0].toUpperCase(),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 34,
                    fontWeight: FontWeight.w900,
                  ),
                ),
        ),
        Positioned(
          right: -8,
          bottom: -4,
          child: IconButton.filled(
            tooltip: 'Change profile picture',
            onPressed: uploading ? null : _chooseAndUpload,
            icon: const Icon(Icons.add_a_photo_rounded, size: 20),
          ),
        ),
      ],
    );
  }

  Future<void> _chooseAndUpload() async {
    final selection = await FilePicker.pickFiles(
      type: FileType.custom,
      allowedExtensions: const ['jpg', 'jpeg', 'png', 'webp'],
    );
    if (selection.isEmpty) return;
    final selectedFile = selection.first;
    final path = selectedFile.path;
    if (path == null) return;
    final file = File(path);
    if (await file.length() > 2097152) {
      if (mounted) {
        showModuleMessage(context, 'Choose an image no larger than 2 MB.');
      }
      return;
    }
    setState(() => uploading = true);
    try {
      final response = await ref
          .read(dioProvider)
          .post(
            'profile/photo',
            data: FormData.fromMap({
              'user_photo': await MultipartFile.fromFile(
                path,
                filename: selectedFile.name,
              ),
            }),
          );
      setState(() => photoUrl = response.data['data']['photo_url']?.toString());
      ref.invalidate(currentUserProvider);
      if (mounted) {
        showModuleMessage(
          context,
          'Profile picture updated on mobile and web.',
        );
      }
    } on DioException catch (error) {
      final body = error.response?.data;
      final message = body is Map && body['error'] is Map
          ? body['error']['message']?.toString()
          : null;
      if (mounted) {
        showModuleMessage(
          context,
          message ?? 'Could not update profile picture.',
        );
      }
    } finally {
      if (mounted) setState(() => uploading = false);
    }
  }

  String? _reachableUrl(String? value) {
    if (value == null || value.isEmpty) return null;
    final image = Uri.tryParse(value);
    final api = Uri.tryParse(AppConfig.apiBaseUrl);
    if (image == null || api == null || api.host != '10.0.2.2') return value;
    return image
        .replace(
          scheme: api.scheme,
          host: api.host,
          port: api.hasPort ? api.port : null,
        )
        .toString();
  }
}
