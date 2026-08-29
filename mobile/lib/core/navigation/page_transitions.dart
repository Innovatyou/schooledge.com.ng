import 'package:flutter/material.dart';

/// Fade + slight upward-slide push transition used for every in-app
/// (non-auth) navigation, replacing the platform-default MaterialPageRoute
/// transition so moving between screens feels like part of one continuous
/// app rather than a hard cut.
Route<T> moduleRoute<T>(Widget page) => PageRouteBuilder<T>(
  pageBuilder: (context, animation, secondaryAnimation) => page,
  transitionDuration: const Duration(milliseconds: 260),
  reverseTransitionDuration: const Duration(milliseconds: 220),
  transitionsBuilder: (context, animation, secondaryAnimation, child) {
    if (MediaQuery.disableAnimationsOf(context)) return child;
    final curved = CurvedAnimation(parent: animation, curve: Curves.easeOutCubic);
    return FadeTransition(
      opacity: curved,
      child: SlideTransition(
        position: Tween<Offset>(
          begin: const Offset(0, .04),
          end: Offset.zero,
        ).animate(curved),
        child: child,
      ),
    );
  },
);
