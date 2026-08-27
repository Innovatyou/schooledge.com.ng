import 'package:flutter/material.dart';

class DepthCard extends StatefulWidget {
  const DepthCard({
    required this.child,
    this.onTap,
    this.color,
    this.padding = const EdgeInsets.all(20),
    super.key,
  });
  final Widget child;
  final VoidCallback? onTap;
  final Color? color;
  final EdgeInsets padding;
  @override
  State<DepthCard> createState() => _DepthCardState();
}

class _DepthCardState extends State<DepthCard> {
  bool pressed = false;
  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return AnimatedScale(
      scale: pressed ? .97 : 1,
      duration: const Duration(milliseconds: 120),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 120),
        transform: Matrix4.translationValues(0, pressed ? 5 : 0, 0),
        decoration: BoxDecoration(
          color: widget.color ?? scheme.surfaceContainerHigh,
          borderRadius: BorderRadius.circular(28),
          border: Border.all(color: scheme.outlineVariant.withValues(alpha: .4)),
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
            onHighlightChanged: (value) => setState(() => pressed = value),
            child: Padding(padding: widget.padding, child: widget.child),
          ),
        ),
      ),
    );
  }
}
