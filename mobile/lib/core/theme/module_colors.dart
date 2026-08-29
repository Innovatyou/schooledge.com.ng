import 'package:flutter/material.dart';
import 'app_colors.dart';

/// Per-module accent colors, exposed as a ThemeExtension so screens read
/// `Theme.of(context).extension<ModuleColors>()!` instead of hardcoding hex.
@immutable
class ModuleColors extends ThemeExtension<ModuleColors> {
  const ModuleColors({
    required this.learning,
    required this.attendance,
    required this.homework,
    required this.results,
    required this.fees,
    required this.messages,
    required this.library,
    required this.liveClasses,
    required this.admin,
    required this.rewards,
  });

  final Color learning;
  final Color attendance;
  final Color homework;
  final Color results;
  final Color fees;
  final Color messages;
  final Color library;
  final Color liveClasses;
  final Color admin;
  final Color rewards;

  static const light = ModuleColors(
    learning: AppColors.learningPurple,
    attendance: AppColors.attendanceTeal,
    homework: AppColors.homeworkCoral,
    results: AppColors.resultsOrange,
    fees: AppColors.feesBlue,
    messages: AppColors.messagesPink,
    library: AppColors.libraryTeal,
    liveClasses: AppColors.liveClassesOrangeRed,
    admin: AppColors.adminNavy,
    rewards: AppColors.gold,
  );

  // Same hues, slightly lifted for dark-surface contrast.
  static const dark = ModuleColors(
    learning: Color(0xff9d8bff),
    attendance: Color(0xff33c7b4),
    homework: Color(0xffff8f8f),
    results: Color(0xffffc266),
    fees: Color(0xff4db4d6),
    messages: Color(0xffe595cb),
    library: Color(0xff5cc2b4),
    liveClasses: Color(0xffef9377),
    admin: Color(0xff5c85c2),
    rewards: Color(0xffffda85),
  );

  @override
  ModuleColors copyWith({
    Color? learning,
    Color? attendance,
    Color? homework,
    Color? results,
    Color? fees,
    Color? messages,
    Color? library,
    Color? liveClasses,
    Color? admin,
    Color? rewards,
  }) => ModuleColors(
    learning: learning ?? this.learning,
    attendance: attendance ?? this.attendance,
    homework: homework ?? this.homework,
    results: results ?? this.results,
    fees: fees ?? this.fees,
    messages: messages ?? this.messages,
    library: library ?? this.library,
    liveClasses: liveClasses ?? this.liveClasses,
    admin: admin ?? this.admin,
    rewards: rewards ?? this.rewards,
  );

  @override
  ModuleColors lerp(ThemeExtension<ModuleColors>? other, double t) {
    if (other is! ModuleColors) return this;
    return ModuleColors(
      learning: Color.lerp(learning, other.learning, t)!,
      attendance: Color.lerp(attendance, other.attendance, t)!,
      homework: Color.lerp(homework, other.homework, t)!,
      results: Color.lerp(results, other.results, t)!,
      fees: Color.lerp(fees, other.fees, t)!,
      messages: Color.lerp(messages, other.messages, t)!,
      library: Color.lerp(library, other.library, t)!,
      liveClasses: Color.lerp(liveClasses, other.liveClasses, t)!,
      admin: Color.lerp(admin, other.admin, t)!,
      rewards: Color.lerp(rewards, other.rewards, t)!,
    );
  }
}
