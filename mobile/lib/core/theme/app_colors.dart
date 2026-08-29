import 'package:flutter/material.dart';

/// The brand palette already used (as scattered hex literals) across every
/// screen in the app. Centralized here so the theme, ModuleColors extension,
/// and any screen that still needs a literal all draw from one source.
abstract final class AppColors {
  // Core brand
  static const navyDeep = Color(0xff071b3d);
  static const navy = Color(0xff163a70);
  static const navyMid = Color(0xff173b70);
  static const gold = Color(0xffffd166);
  static const goldBright = Color(0xffffc857);
  static const teal = Color(0xff10bfa3);
  static const tealDeep = Color(0xff0b6e69);
  static const tealMid = Color(0xff136f70);
  static const tealBright = Color(0xff16b39a);

  // Module accents
  static const learningPurple = Color(0xff725cff);
  static const attendanceTeal = Color(0xff00a896);
  static const homeworkCoral = Color(0xffff6b6b);
  static const resultsOrange = Color(0xffffa62b);
  static const feesBlue = Color(0xff168aad);
  static const messagesPink = Color(0xffd65db1);
  static const libraryTeal = Color(0xff2a9d8f);
  static const liveClassesOrangeRed = Color(0xffe76f51);
  static const adminNavy = Color(0xff163a70);

  // Status
  static const success = Color(0xff00a896);
  static const warning = Color(0xffffa62b);
  static const danger = Color(0xffff6b6b);
}
