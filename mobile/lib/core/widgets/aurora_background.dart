import 'dart:math' as math;
import 'package:flutter/material.dart';

/// Ambient animated gradient with drifting blurred orbs.
///
/// The default constructor is the full-bleed, high-saturation version used
/// by the auth flow (login/OTP). [AuroraBackground.ambient] is a much
/// subtler variant — lower alpha, theme-derived base colors — meant to sit
/// behind ordinary content (e.g. a ModulePage header) without competing
/// with it.
class AuroraBackground extends StatefulWidget {
  const AuroraBackground({required this.child, super.key})
    : _ambient = false,
      _baseColors = null,
      _baseAlphaOverride = null;

  /// [colors] lets a caller keep full brand-color saturation (e.g. a
  /// ModulePage header using its own gradient) while still getting the
  /// lower-key orb motion; pass [baseAlpha] explicitly to override the
  /// ambient default of a washed-out .55 (e.g. `baseAlpha: 1` for a bold
  /// opaque hero header, vs the default low alpha for a background wash
  /// sitting behind other content).
  const AuroraBackground.ambient({
    required this.child,
    List<Color>? colors,
    double? baseAlpha,
    super.key,
  }) : _ambient = true,
       _baseColors = colors,
       _baseAlphaOverride = baseAlpha;

  final Widget child;
  final bool _ambient;
  final List<Color>? _baseColors;
  final double? _baseAlphaOverride;

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
  Widget build(BuildContext context) {
    final ambient = widget._ambient;
    final scheme = Theme.of(context).colorScheme;
    final base =
        widget._baseColors ??
        (ambient
            ? [scheme.primary, scheme.secondaryContainer, scheme.surface]
            : const [Color(0xff071b3d), Color(0xff173b70), Color(0xff0b6e69)]);
    final orbAlpha1 = ambient ? .14 : .30;
    final orbAlpha2 = ambient ? .16 : .35;
    final orbAlpha3 = ambient ? .10 : .22;
    final baseAlpha = widget._baseAlphaOverride ?? (ambient ? .55 : 1.0);

    return AnimatedBuilder(
      animation: controller,
      builder: (context, _) {
        final angle = controller.value * math.pi * 2;
        return DecoratedBox(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: base
                  .map((c) => c.withValues(alpha: baseAlpha))
                  .toList(growable: false),
            ),
          ),
          child: Stack(
            children: [
              Positioned(
                top: -90 + math.sin(angle) * 24,
                right: -70,
                child: _Orb(
                  230,
                  const Color(0xffffc857).withValues(alpha: orbAlpha1),
                ),
              ),
              Positioned(
                bottom: -80,
                left: -70 + math.cos(angle) * 26,
                child: _Orb(
                  260,
                  const Color(0xff7c5cff).withValues(alpha: orbAlpha2),
                ),
              ),
              Positioned(
                top: 220,
                left: -55,
                child: _Orb(
                  150,
                  const Color(0xff2de2e6).withValues(alpha: orbAlpha3),
                ),
              ),
              widget.child,
            ],
          ),
        );
      },
    );
  }
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
