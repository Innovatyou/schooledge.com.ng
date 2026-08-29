import 'package:flutter/material.dart';

class DepthCard extends StatefulWidget {
  const DepthCard({
    required this.child,
    this.onTap,
    this.color,
    this.padding = const EdgeInsets.all(20),
    this.enableTilt = true,
    super.key,
  });
  final Widget child;
  final VoidCallback? onTap;
  final Color? color;
  final EdgeInsets padding;
  /// Subtle perspective tilt toward the touch point while pressed.
  final bool enableTilt;
  @override
  State<DepthCard> createState() => _DepthCardState();
}

class _DepthCardState extends State<DepthCard> {
  bool pressed = false;
  Offset? _localPosition;
  Size? _size;

  Matrix4 get _tiltTransform {
    final base = Matrix4.translationValues(0, pressed ? 5 : 0, 0);
    if (!widget.enableTilt || !pressed || _localPosition == null || _size == null || _size!.isEmpty) {
      return base;
    }
    // -0.5..0.5 across each axis, scaled to a gentle rotation.
    final dx = (_localPosition!.dx / _size!.width) - 0.5;
    final dy = (_localPosition!.dy / _size!.height) - 0.5;
    const tiltFactor = 0.006;
    return base
      ..setEntry(3, 2, 0.0012)
      ..rotateX(dy * -tiltFactor * 10)
      ..rotateY(dx * tiltFactor * 10);
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return GestureDetector(
      onPanDown: (details) =>
          setState(() => _localPosition = details.localPosition),
      onPanUpdate: (details) =>
          setState(() => _localPosition = details.localPosition),
      child: AnimatedScale(
        scale: pressed ? .97 : 1,
        duration: const Duration(milliseconds: 120),
        child: LayoutBuilder(
          builder: (context, constraints) {
            _size = constraints.biggest;
            return AnimatedContainer(
              duration: const Duration(milliseconds: 120),
              transform: _tiltTransform,
              transformAlignment: Alignment.center,
              decoration: BoxDecoration(
                color: widget.color ?? scheme.surfaceContainerHigh,
                borderRadius: BorderRadius.circular(28),
                border: Border.all(
                  color: scheme.outlineVariant.withValues(alpha: .4),
                ),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: pressed ? .10 : .20),
                    blurRadius: pressed ? 10 : 24,
                    offset: Offset(0, pressed ? 5 : 12),
                  ),
                ],
              ),
              child: Material(
                color: Colors.transparent,
                child: InkWell(
                  borderRadius: BorderRadius.circular(28),
                  onTap: widget.onTap,
                  onHighlightChanged: (value) =>
                      setState(() => pressed = value),
                  child: Padding(padding: widget.padding, child: widget.child),
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}
