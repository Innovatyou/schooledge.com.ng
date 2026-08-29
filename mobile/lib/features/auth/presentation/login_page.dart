import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/aurora_background.dart';
import '../../../core/widgets/depth_card.dart';
import '../application/auth_controller.dart';

class LoginPage extends ConsumerStatefulWidget {
  const LoginPage({super.key});
  @override
  ConsumerState<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends ConsumerState<LoginPage> {
  final username = TextEditingController();
  final password = TextEditingController();
  bool hidden = true;
  @override
  void dispose() {
    username.dispose();
    password.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authControllerProvider);
    return Scaffold(
      body: AuroraBackground(
        child: SafeArea(
          child: LayoutBuilder(
            builder: (context, bounds) => SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: ConstrainedBox(
                constraints: BoxConstraints(minHeight: bounds.maxHeight - 48),
                child: Center(
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 460),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        TweenAnimationBuilder<double>(
                          tween: Tween(begin: 0, end: 1),
                          duration: const Duration(milliseconds: 700),
                          builder: (context, value, child) =>
                              Transform.translate(
                                offset: Offset(0, 24 * (1 - value)),
                                child: Opacity(opacity: value, child: child),
                              ),
                          child: const _BrandHero(),
                        ),
                        const SizedBox(height: 28),
                        DepthCard(
                          color: Colors.white.withValues(alpha: .94),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              const Text(
                                'Welcome back',
                                style: TextStyle(
                                  fontSize: 28,
                                  fontWeight: FontWeight.w900,
                                  color: Color(0xff102a43),
                                ),
                              ),
                              const SizedBox(height: 6),
                              const Text(
                                'Your entire school day, beautifully connected.',
                                style: TextStyle(color: Color(0xff627d98)),
                              ),
                              const SizedBox(height: 24),
                              TextField(
                                controller: username,
                                textInputAction: TextInputAction.next,
                                decoration: const InputDecoration(
                                  labelText: 'Username or email',
                                  prefixIcon: Icon(Icons.person_rounded),
                                ),
                              ),
                              const SizedBox(height: 14),
                              TextField(
                                controller: password,
                                obscureText: hidden,
                                onSubmitted: (_) => _login(),
                                decoration: InputDecoration(
                                  labelText: 'Password',
                                  prefixIcon: const Icon(Icons.lock_rounded),
                                  suffixIcon: IconButton(
                                    onPressed: () =>
                                        setState(() => hidden = !hidden),
                                    icon: Icon(
                                      hidden
                                          ? Icons.visibility_rounded
                                          : Icons.visibility_off_rounded,
                                    ),
                                  ),
                                ),
                              ),
                              if (auth.error != null)
                                Padding(
                                  padding: const EdgeInsets.only(top: 12),
                                  child: Text(
                                    auth.error!,
                                    style: const TextStyle(
                                      color: Color(0xffc62828),
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ),
                              const SizedBox(height: 20),
                              SizedBox(
                                height: 56,
                                child: FilledButton.icon(
                                  onPressed: auth.isBusy ? null : _login,
                                  icon: auth.isBusy
                                      ? const SizedBox.square(
                                          dimension: 20,
                                          child: CircularProgressIndicator(
                                            strokeWidth: 2,
                                          ),
                                        )
                                      : const Icon(Icons.arrow_forward_rounded),
                                  label: const Text(
                                    'Enter SchoolEdge',
                                    style: TextStyle(
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 22),
                        const Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.verified_user_rounded,
                              color: Color(0xff80deea),
                              size: 18,
                            ),
                            SizedBox(width: 8),
                            Text(
                              'Protected by secure sign-in and 2-step verification',
                              style: TextStyle(
                                color: Colors.white70,
                                fontSize: 12,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _login() {
    if (username.text.trim().isNotEmpty && password.text.isNotEmpty) {
      ref
          .read(authControllerProvider.notifier)
          .login(username.text.trim(), password.text);
    }
  }
}

class _BrandHero extends StatelessWidget {
  const _BrandHero();
  @override
  Widget build(BuildContext context) => Column(
    children: [
      Hero(
        tag: 'security',
        child: Container(
          width: 86,
          height: 86,
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xffffd166), Color(0xffff8a5b)],
            ),
            borderRadius: BorderRadius.circular(28),
            boxShadow: [
              BoxShadow(
                color: const Color(0xffffb15b).withValues(alpha: .45),
                blurRadius: 28,
                offset: const Offset(0, 14),
              ),
            ],
          ),
          child: const Icon(
            Icons.school_rounded,
            size: 48,
            color: Color(0xff17324d),
          ),
        ),
      ),
      const SizedBox(height: 18),
      const Text(
        'SchoolEdge',
        style: TextStyle(
          color: Colors.white,
          fontSize: 38,
          fontWeight: FontWeight.w900,
          letterSpacing: -1,
        ),
      ),
      const Text(
        'LEARN  •  CONNECT  •  GROW',
        style: TextStyle(
          color: Color(0xffa7f3d0),
          fontSize: 12,
          fontWeight: FontWeight.w800,
          letterSpacing: 2,
        ),
      ),
    ],
  );
}
