import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/aurora_background.dart';
import '../../../core/widgets/depth_card.dart';
import '../application/auth_controller.dart';

class OtpPage extends ConsumerStatefulWidget {
  const OtpPage({super.key});
  @override
  ConsumerState<OtpPage> createState() => _OtpPageState();
}

class _OtpPageState extends ConsumerState<OtpPage> {
  final code = TextEditingController();
  @override
  void dispose() {
    code.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authControllerProvider);
    final isEmail = auth.otpType == 'email';
    return Scaffold(
      body: AuroraBackground(
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 440),
                child: Column(
                  children: [
                    Align(
                      alignment: Alignment.centerLeft,
                      child: IconButton.filledTonal(
                        onPressed: auth.isBusy
                            ? null
                            : () => ref
                                  .read(authControllerProvider.notifier)
                                  .cancelOtp(),
                        icon: const Icon(Icons.arrow_back_rounded),
                      ),
                    ),
                    const SizedBox(height: 20),
                    Hero(
                      tag: 'security',
                      child: Container(
                        width: 100,
                        height: 100,
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            colors: isEmail
                                ? [
                                    const Color(0xffffd166),
                                    const Color(0xffff7b54),
                                  ]
                                : [
                                    const Color(0xff54e1c1),
                                    const Color(0xff3d7cff),
                                  ],
                          ),
                          borderRadius: BorderRadius.circular(34),
                          boxShadow: [
                            BoxShadow(
                              color:
                                  (isEmail
                                          ? const Color(0xffff9f5b)
                                          : const Color(0xff3d7cff))
                                      .withValues(alpha: .45),
                              blurRadius: 34,
                              offset: const Offset(0, 18),
                            ),
                          ],
                        ),
                        child: Icon(
                          isEmail
                              ? Icons.mark_email_unread_rounded
                              : Icons.phonelink_lock_rounded,
                          size: 52,
                          color: Colors.white,
                        ),
                      ),
                    ),
                    const SizedBox(height: 26),
                    DepthCard(
                      color: Colors.white.withValues(alpha: .95),
                      child: Column(
                        children: [
                          Text(
                            isEmail
                                ? 'Check your inbox'
                                : 'Authenticator check',
                            style: const TextStyle(
                              fontSize: 27,
                              fontWeight: FontWeight.w900,
                              color: Color(0xff102a43),
                            ),
                          ),
                          const SizedBox(height: 10),
                          Text(
                            isEmail
                                ? 'We sent a 6-digit code to ${auth.destination ?? 'your email'}'
                                : 'Enter the current 6-digit code from your authenticator app. Backup codes also work.',
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              height: 1.5,
                              color: Color(0xff627d98),
                            ),
                          ),
                          const SizedBox(height: 24),
                          TextField(
                            controller: code,
                            autofocus: true,
                            textAlign: TextAlign.center,
                            keyboardType: TextInputType.number,
                            inputFormatters: [
                              FilteringTextInputFormatter.digitsOnly,
                              LengthLimitingTextInputFormatter(12),
                            ],
                            style: const TextStyle(
                              fontSize: 30,
                              fontWeight: FontWeight.w900,
                              letterSpacing: 8,
                            ),
                            decoration: const InputDecoration(
                              hintText: '000000',
                              counterText: '',
                              prefixIcon: Icon(Icons.password_rounded),
                            ),
                            onSubmitted: (_) => _verify(),
                          ),
                          if (auth.error != null)
                            Padding(
                              padding: const EdgeInsets.only(top: 12),
                              child: Text(
                                auth.error!,
                                textAlign: TextAlign.center,
                                style: const TextStyle(
                                  color: Color(0xffc62828),
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                          const SizedBox(height: 20),
                          SizedBox(
                            width: double.infinity,
                            height: 56,
                            child: FilledButton.icon(
                              onPressed: auth.isBusy ? null : _verify,
                              icon: auth.isBusy
                                  ? const SizedBox.square(
                                      dimension: 20,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                      ),
                                    )
                                  : const Icon(Icons.verified_rounded),
                              label: const Text(
                                'Verify securely',
                                style: TextStyle(fontWeight: FontWeight.w800),
                              ),
                            ),
                          ),
                          if (isEmail)
                            TextButton.icon(
                              onPressed: auth.isBusy
                                  ? null
                                  : () => ref
                                        .read(authControllerProvider.notifier)
                                        .resendOtp(),
                              icon: const Icon(Icons.refresh_rounded),
                              label: const Text('Send a new code'),
                            ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),
                    const Text(
                      'Codes expire after 10 minutes • 5 attempts maximum',
                      style: TextStyle(color: Colors.white70, fontSize: 12),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _verify() {
    if (code.text.trim().isNotEmpty) {
      ref.read(authControllerProvider.notifier).verifyOtp(code.text.trim());
    }
  }
}
