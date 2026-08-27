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
      return state.matchedLocation == '/login' ? null : '/login';
    },
    routes: [
      GoRoute(path: '/login', builder: (_, _) => const LoginPage()),
      GoRoute(path: '/otp', builder: (_, _) => const OtpPage()),
      GoRoute(path: '/splash', builder: (_, _) => const _SplashPage()),
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

class _SplashPage extends StatelessWidget {
  const _SplashPage();
  @override
  Widget build(BuildContext context) => const Scaffold(
    backgroundColor: Color(0xff071b3d),
    body: Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.school_rounded, color: Color(0xffffd166), size: 72),
          SizedBox(height: 20),
          Text(
            'SchoolEdge',
            style: TextStyle(
              color: Colors.white,
              fontSize: 32,
              fontWeight: FontWeight.w900,
            ),
          ),
          SizedBox(height: 24),
          CircularProgressIndicator(color: Color(0xff54e1c1)),
        ],
      ),
    ),
  );
}
