import 'dart:math' as math;
import 'package:flutter/material.dart';

class AuroraBackground extends StatefulWidget {
  const AuroraBackground({required this.child, super.key});
  final Widget child;
  @override
  State<AuroraBackground> createState() => _AuroraBackgroundState();
}

class _AuroraBackgroundState extends State<AuroraBackground>
    with SingleTickerProviderStateMixin {
  late final AnimationController controller = AnimationController(
    vsync: this,
    duration: const Duration(seconds: 14),
  )..repeat();
  @override
  void dispose() {
    controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => AnimatedBuilder(
    animation: controller,
    builder: (context, _) {
      final angle = controller.value * math.pi * 2;
      return DecoratedBox(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xff071b3d), Color(0xff173b70), Color(0xff0b6e69)],
          ),
        ),
        child: Stack(
          children: [
            Positioned(
              top: -90 + math.sin(angle) * 24,
              right: -70,
              child: _Orb(230, const Color(0xffffc857).withValues(alpha: .30)),
            ),
            Positioned(
              bottom: -80,
              left: -70 + math.cos(angle) * 26,
              child: _Orb(260, const Color(0xff7c5cff).withValues(alpha: .35)),
            ),
            Positioned(
              top: 220,
              left: -55,
              child: _Orb(150, const Color(0xff2de2e6).withValues(alpha: .22)),
            ),
            widget.child,
          ],
        ),
      );
    },
  );
}

class _Orb extends StatelessWidget {
  const _Orb(this.size, this.color);
  final double size;
  final Color color;
  @override
  Widget build(BuildContext context) => Container(
    width: size,
    height: size,
    decoration: BoxDecoration(
      shape: BoxShape.circle,
      gradient: RadialGradient(colors: [color, color.withValues(alpha: 0)]),
    ),
  );
}
