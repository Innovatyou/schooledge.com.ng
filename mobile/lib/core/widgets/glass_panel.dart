import 'dart:ui';
import 'package:flutter/material.dart';

/// Frosted-glass panel — a blurred, translucent surface meant to sit over an
/// animated gradient (AuroraBackground) or an image. Use for headers/overlays
/// where content needs to read clearly over busy motion behind it.
class GlassPanel extends StatelessWidget {
  const GlassPanel({
    required this.child,
    this.padding = const EdgeInsets.all(16),
    this.borderRadius = 24,
    this.blur = 18,
    this.tint,
    this.borderColor,
    super.key,
  });

  final Widget child;
  final EdgeInsets padding;
  final double borderRadius;
  final double blur;
  final Color? tint;
  final Color? borderColor;

  @override
  Widget build(BuildContext context) {
    final resolvedTint = tint ?? Colors.white.withValues(alpha: .12);
    final resolvedBorder = borderColor ?? Colors.white.withValues(alpha: .22);
    return ClipRRect(
      borderRadius: BorderRadius.circular(borderRadius),
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: blur, sigmaY: blur),
        child: Container(
          padding: padding,
          decoration: BoxDecoration(
            color: resolvedTint,
            borderRadius: BorderRadius.circular(borderRadius),
            border: Border.all(color: resolvedBorder),
          ),
          child: child,
        ),
      ),
    );
  }
}
