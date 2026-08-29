import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:shared_preferences/shared_preferences.dart';

class WelcomePage extends StatefulWidget {
  const WelcomePage({super.key});
  @override
  State<WelcomePage> createState() => _WelcomePageState();
}

class _WelcomePageState extends State<WelcomePage> {
  final _controller = PageController();
  int _page = 0;
  static const _slides = [
    (
      'assets/branding/welcome_learn.png',
      'Learn without limits',
      'Classes, homework, library resources and results—all in one colourful learning space.',
    ),
    (
      'assets/branding/welcome_connect.png',
      'Everyone stays connected',
      'Students, teachers and parents share attendance, messages and school moments instantly.',
    ),
    (
      'assets/branding/welcome_grow.png',
      'Grow with confidence',
      'Follow progress, celebrate achievement and take the next step toward a brighter future.',
    ),
  ];
  Future<void> _finish() async {
    final preferences = await SharedPreferences.getInstance();
    await preferences.setBool('welcome_completed', true);
    if (mounted) context.go('/login');
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: const Color(0xff071b3d),
    body: SafeArea(
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 10, 10, 0),
            child: Row(
              children: [
                Image.asset(
                  'assets/branding/schooledge_logo.png',
                  width: 48,
                  height: 48,
                ),
                const SizedBox(width: 10),
                const Text(
                  'SchoolEdge',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 22,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const Spacer(),
                TextButton(onPressed: _finish, child: const Text('Skip')),
              ],
            ),
          ),
          Expanded(
            child: PageView.builder(
              controller: _controller,
              itemCount: _slides.length,
              onPageChanged: (value) => setState(() => _page = value),
              itemBuilder: (context, index) {
                final slide = _slides[index];
                return Padding(
                  padding: const EdgeInsets.fromLTRB(22, 14, 22, 8),
                  child: Column(
                    children: [
                      Expanded(
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(32),
                          child: Image.asset(
                            slide.$1,
                            width: double.infinity,
                            fit: BoxFit.cover,
                          ),
                        ),
                      ),
                      const SizedBox(height: 24),
                      Text(
                        slide.$2,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 29,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      const SizedBox(height: 10),
                      Text(
                        slide.$3,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          color: Color(0xffc9d8ef),
                          fontSize: 16,
                          height: 1.45,
                        ),
                      ),
                    ],
                  ),
                );
              },
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(22, 12, 22, 22),
            child: Row(
              children: [
                Row(
                  children: List.generate(
                    _slides.length,
                    (index) => AnimatedContainer(
                      duration: const Duration(milliseconds: 250),
                      margin: const EdgeInsets.only(right: 7),
                      width: index == _page ? 26 : 8,
                      height: 8,
                      decoration: BoxDecoration(
                        color: index == _page
                            ? const Color(0xffffd166)
                            : const Color(0xff526986),
                        borderRadius: BorderRadius.circular(20),
                      ),
                    ),
                  ),
                ),
                const Spacer(),
                FilledButton.icon(
                  style: FilledButton.styleFrom(
                    backgroundColor: const Color(0xff10bfa3),
                    foregroundColor: const Color(0xff071b3d),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 22,
                      vertical: 16,
                    ),
                  ),
                  onPressed: () {
                    if (_page == _slides.length - 1) {
                      _finish();
                    } else {
                      _controller.nextPage(
                        duration: const Duration(milliseconds: 380),
                        curve: Curves.easeOutCubic,
                      );
                    }
                  },
                  icon: Icon(
                    _page == _slides.length - 1
                        ? Icons.rocket_launch_rounded
                        : Icons.arrow_forward_rounded,
                  ),
                  label: Text(
                    _page == _slides.length - 1
                        ? 'Enter SchoolEdge'
                        : 'Continue',
                    style: const TextStyle(fontWeight: FontWeight.w900),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    ),
  );
}
