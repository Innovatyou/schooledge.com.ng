import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../core/theme/app_theme.dart';
import '../core/theme/theme_mode_controller.dart';
import '../features/auth/application/auth_controller.dart';
import '../features/auth/presentation/login_page.dart';
import '../features/auth/presentation/otp_page.dart';
import '../features/home/presentation/home_page.dart';
import '../l10n/app_strings.dart';
import '../features/onboarding/presentation/welcome_page.dart';
import 'package:shared_preferences/shared_preferences.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final auth = ref.watch(authControllerProvider);
  return GoRouter(
    initialLocation: '/splash',
    redirect: (context, state) {
      if (auth.stage == AuthStage.restoring) {
        return state.matchedLocation == '/splash' ? null : '/splash';
      }
      if (auth.stage == AuthStage.otp) {
        return state.matchedLocation == '/otp' ? null : '/otp';
      }
      if (auth.stage == AuthStage.signedIn) {
        return state.matchedLocation == '/' ? null : '/';
      }
      if (state.matchedLocation == '/splash' ||
          state.matchedLocation == '/welcome') {
        return null;
      }
      return state.matchedLocation == '/login' ? null : '/login';
    },
    routes: [
      GoRoute(path: '/login', builder: (_, _) => const LoginPage()),
      GoRoute(path: '/otp', builder: (_, _) => const OtpPage()),
      GoRoute(path: '/splash', builder: (_, _) => const _SplashPage()),
      GoRoute(path: '/welcome', builder: (_, _) => const WelcomePage()),
      GoRoute(path: '/', builder: (_, _) => const HomePage()),
    ],
  );
});

class SchoolEdgeApp extends ConsumerWidget {
  const SchoolEdgeApp({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) => MaterialApp.router(
    title: 'SchoolEdge',
    theme: AppTheme.light(),
    darkTheme: AppTheme.dark(),
    themeMode: ref.watch(themeModeControllerProvider),
    routerConfig: ref.watch(routerProvider),
    supportedLocales: AppStrings.supportedLocales,
  );
}

class _SplashPage extends StatefulWidget {
  const _SplashPage();
  @override
  State<_SplashPage> createState() => _SplashPageState();
}

class _SplashPageState extends State<_SplashPage> {
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _timer = Timer(const Duration(milliseconds: 1400), _continue);
  }

  Future<void> _continue() async {
    final preferences = await SharedPreferences.getInstance();
    if (mounted) {
      context.go(
        preferences.getBool('welcome_completed') == true
            ? '/login'
            : '/welcome',
      );
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: Color(0xff071b3d),
    body: Stack(
      fit: StackFit.expand,
      children: [
        Image.asset('assets/branding/splash_art.png', fit: BoxFit.cover),
        const DecoratedBox(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [Colors.transparent, Color(0xcc071b3d)],
            ),
          ),
        ),
        const SafeArea(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              Text(
                'SchoolEdge',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 38,
                  fontWeight: FontWeight.w900,
                ),
              ),
              SizedBox(height: 6),
              Text(
                'LEARN  •  CONNECT  •  GROW',
                style: TextStyle(
                  color: Color(0xff54e1c1),
                  fontWeight: FontWeight.w800,
                  letterSpacing: 2,
                ),
              ),
              SizedBox(height: 38),
              CircularProgressIndicator(color: Color(0xffffd166)),
              SizedBox(height: 42),
            ],
          ),
        ),
      ],
    ),
  );
}
