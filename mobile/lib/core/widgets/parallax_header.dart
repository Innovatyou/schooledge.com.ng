import 'package:flutter/material.dart';

/// Makes [child] drift more slowly than the enclosing scroll view's content,
/// creating a parallax depth effect. Meant for a purely decorative
/// background element inside a SliverAppBar's FlexibleSpaceBar (a watermark
/// icon, gradient layer) — never for interactive content, since it only
/// translates visually and doesn't move hit-test bounds in step.
class ParallaxLayer extends StatelessWidget {
  const ParallaxLayer({required this.child, this.factor = 0.4, super.key});

  final Widget child;

  /// 0 = appears to lag completely behind the scroll; 1 = scrolls in lockstep
  /// with content (no parallax). 0.3–0.5 reads as a natural depth cue.
  final double factor;

  @override
  Widget build(BuildContext context) {
    final scrollable = Scrollable.maybeOf(context);
    if (scrollable == null) return child;
    return AnimatedBuilder(
      animation: scrollable.position,
      builder: (context, _) {
        final position = scrollable.position;
        final pixels = position.hasPixels ? position.pixels : 0.0;
        // Only react to downward scroll (positive pixels) - ignore overscroll.
        final drift = pixels <= 0 ? 0.0 : pixels * (1 - factor);
        return Transform.translate(offset: Offset(0, drift), child: child);
      },
    );
  }
}
