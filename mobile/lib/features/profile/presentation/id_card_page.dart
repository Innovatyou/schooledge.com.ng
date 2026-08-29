import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/aurora_background.dart';
import '../../../core/widgets/depth_card.dart';
import '../../attendance/presentation/attendance_qr_pass.dart';
import '../data/id_card_repository.dart';

class IdCardPage extends ConsumerWidget {
  const IdCardPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final card = ref.watch(idCardProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('Digital ID card')),
      body: card.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(
          child: FilledButton.icon(
            onPressed: () => ref.invalidate(idCardProvider),
            icon: const Icon(Icons.refresh_rounded),
            label: const Text('Try again'),
          ),
        ),
        data: (data) => ListView(
          padding: const EdgeInsets.all(20),
          children: [
            _IdCardFace(data: data),
            const SizedBox(height: 20),
            const AttendanceQrPass(
              title: 'Scan to verify',
              caption:
                  'This code rotates automatically and can also be used to take attendance.',
            ),
          ],
        ),
      ),
    );
  }
}

class _IdCardFace extends StatelessWidget {
  const _IdCardFace({required this.data});
  final Map<String, dynamic> data;

  @override
  Widget build(BuildContext context) {
    final school = Map<String, dynamic>.from((data['school'] as Map?) ?? {});
    return ClipRRect(
      borderRadius: BorderRadius.circular(28),
      child: AuroraBackground.ambient(
        colors: const [AppColors.navy, AppColors.tealMid],
        baseAlpha: 1,
        child: Padding(
          padding: const EdgeInsets.all(22),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                (school['name'] ?? 'SchoolEdge').toString(),
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w900,
                  fontSize: 16,
                ),
              ),
              if ((school['address'] as String?)?.isNotEmpty ?? false)
                Padding(
                  padding: const EdgeInsets.only(top: 2),
                  child: Text(
                    school['address'].toString(),
                    style: const TextStyle(color: Colors.white70, fontSize: 11),
                  ),
                ),
              const SizedBox(height: 20),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _Photo(url: data['photo_url']?.toString()),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          (data['name'] ?? '').toString(),
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 20,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          [
                            data['class_name'],
                            data['section_name'],
                          ].where((v) => v != null).join(' · '),
                          style: const TextStyle(
                            color: Color(0xffa7f3d0),
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 12),
                        _fact('Roll', data['roll']),
                        _fact('Register no.', data['register_no']),
                        _fact('Blood group', data['blood_group']),
                        _fact('Category', data['category']),
                      ],
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _fact(String label, Object? value) {
    if (value == null || value.toString().isEmpty) {
      return const SizedBox.shrink();
    }
    return Padding(
      padding: const EdgeInsets.only(bottom: 2),
      child: RichText(
        text: TextSpan(
          style: const TextStyle(color: Colors.white, fontSize: 12),
          children: [
            TextSpan(
              text: '$label: ',
              style: const TextStyle(color: Colors.white60),
            ),
            TextSpan(
              text: value.toString(),
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
          ],
        ),
      ),
    );
  }
}

class _Photo extends StatelessWidget {
  const _Photo({this.url});
  final String? url;

  @override
  Widget build(BuildContext context) => DepthCard(
    enableTilt: false,
    padding: EdgeInsets.zero,
    color: Colors.white,
    child: ClipRRect(
      borderRadius: BorderRadius.circular(24),
      child: url == null
          ? const SizedBox(
              width: 72,
              height: 72,
              child: Icon(
                Icons.person_rounded,
                size: 40,
                color: Color(0xff829ab1),
              ),
            )
          : Image.network(
              url!,
              width: 72,
              height: 72,
              fit: BoxFit.cover,
              errorBuilder: (context, error, stack) => const SizedBox(
                width: 72,
                height: 72,
                child: Icon(
                  Icons.person_rounded,
                  size: 40,
                  color: Color(0xff829ab1),
                ),
              ),
            ),
    ),
  );
}
