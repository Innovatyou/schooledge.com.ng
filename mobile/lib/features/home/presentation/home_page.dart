import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../core/push/push_service.dart';
import '../../../core/session/current_user_provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/module_colors.dart';
import '../../../core/widgets/aurora_background.dart';
import '../../../core/widgets/depth_card.dart';
import '../../admin/presentation/admin_dashboard_page.dart';
import '../../auth/application/auth_controller.dart';
import '../../attendance/data/attendance_repository.dart';
import '../../attendance/presentation/attendance_page.dart';
import '../../fees/presentation/fees_page.dart';
import '../../gamification/presentation/gamification_page.dart';
import '../../homework/data/homework_repository.dart';
import '../../planner/data/timetable_repository.dart';
import '../../homework/presentation/homework_page.dart';
import '../../safety/presentation/sos_button.dart';
import '../../learning/presentation/learning_page.dart';
import '../../library/presentation/library_page.dart';
import '../../live_classes/presentation/live_classes_page.dart';
import '../../messages/presentation/messages_page.dart';
import '../../notifications/data/notifications_repository.dart';
import '../../notifications/presentation/notifications_page.dart';
import '../../planner/presentation/planner_page.dart';
import '../../profile/presentation/profile_page.dart';
import '../../results/presentation/results_page.dart';
import '../../../core/navigation/page_transitions.dart';

class HomePage extends ConsumerStatefulWidget {
  const HomePage({super.key});
  @override
  ConsumerState<HomePage> createState() => _HomePageState();
}

class _HomePageState extends ConsumerState<HomePage> {
  int tab = 0;

  @override
  void initState() {
    super.initState();
    // Covers an already-signed-in cold start (login/OTP-verify already
    // registered the token for a fresh sign-in) - fire-and-forget, never
    // blocks rendering the home screen.
    ref.read(pushServiceProvider).registerForCurrentSession();
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(currentUserProvider);
    return Scaffold(
      body: user.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) =>
            _ErrorView(onRetry: () => ref.invalidate(currentUserProvider)),
        data: (data) => Stack(
          children: [
            IndexedStack(
              index: tab,
              children: [
                _HomeDashboard(
                  data: data,
                  onLogout: () =>
                      ref.read(authControllerProvider.notifier).logout(),
                ),
                const PlannerPage(),
                const MessagesPage(embedded: true),
                ProfilePage(
                  data: data,
                  onLogout: () =>
                      ref.read(authControllerProvider.notifier).logout(),
                ),
              ],
            ),
            // Persistent across all 4 tabs (not just Home) - a panic button
            // needs to be reachable no matter where the student/teacher is
            // in the app.
            const SafetyQuickActions(),
          ],
        ),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: tab,
        onDestinationSelected: (value) => setState(() => tab = value),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.space_dashboard_rounded),
            label: 'Home',
          ),
          NavigationDestination(
            icon: Icon(Icons.calendar_month_rounded),
            label: 'Planner',
          ),
          NavigationDestination(
            icon: Badge(child: Icon(Icons.chat_bubble_rounded)),
            label: 'Messages',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_rounded),
            label: 'Profile',
          ),
        ],
      ),
    );
  }
}

String _role(Map<String, dynamic> data) =>
    ((data['membership']?['role']?['name']) ?? 'Student').toString();

class _HomeDashboard extends StatelessWidget {
  const _HomeDashboard({required this.data, required this.onLogout});
  final Map<String, dynamic> data;
  final VoidCallback onLogout;

  @override
  Widget build(BuildContext context) => CustomScrollView(
    slivers: [
      SliverToBoxAdapter(
        child: _Header(data: data, onLogout: onLogout),
      ),
      SliverPadding(
        padding: const EdgeInsets.fromLTRB(20, 0, 20, 28),
        sliver: SliverList.list(
          children: [
            const SizedBox(height: 20),
            _TodayCard(role: _role(data)),
            const SizedBox(height: 24),
            Text(
              'Explore your school',
              style: TextStyle(
                fontSize: 21,
                fontWeight: FontWeight.w900,
                color: Theme.of(context).colorScheme.onSurface,
              ),
            ),
            const SizedBox(height: 14),
            _ModuleGrid(role: _role(data)),
            const SizedBox(height: 26),
            _ProgressCard(role: _role(data)),
            const SizedBox(height: 100),
          ],
        ),
      ),
    ],
  );
}

class _Header extends ConsumerWidget {
  const _Header({required this.data, required this.onLogout});
  final Map<String, dynamic> data;
  final VoidCallback onLogout;
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final school =
        (data['membership']?['school']?['school_name'] ?? 'My School')
            .toString();
    return Container(
      decoration: const BoxDecoration(
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(38)),
        boxShadow: [
          BoxShadow(
            color: Color(0x33163a70),
            blurRadius: 26,
            offset: Offset(0, 12),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: const BorderRadius.vertical(bottom: Radius.circular(38)),
        child: AuroraBackground.ambient(
          colors: const [AppColors.navy, AppColors.tealMid],
          baseAlpha: 1,
          child: Padding(
            padding: EdgeInsets.fromLTRB(
              20,
              MediaQuery.paddingOf(context).top + 14,
              20,
              30,
            ),
            child: Column(
              children: [
                Row(
                  children: [
                    Container(
                      width: 52,
                      height: 52,
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: .16),
                        borderRadius: BorderRadius.circular(18),
                      ),
                      child: const Icon(
                        Icons.school_rounded,
                        color: Color(0xffffd166),
                        size: 30,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            school,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.w800,
                              fontSize: 16,
                            ),
                          ),
                          Text(
                            (data['membership']?['role']?['name'] ?? 'Member')
                                .toString(),
                            style: const TextStyle(
                              color: Color(0xffa7f3d0),
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                    ),
                    IconButton.filledTonal(
                      onPressed: () => Navigator.of(
                        context,
                      ).push(moduleRoute<void>(const NotificationsPage())),
                      icon: ref
                          .watch(unreadCountProvider)
                          .maybeWhen(
                            data: (count) => count > 0
                                ? Badge(
                                    label: Text('$count'),
                                    child: const Icon(
                                      Icons.notifications_rounded,
                                    ),
                                  )
                                : const Icon(Icons.notifications_rounded),
                            orElse: () =>
                                const Icon(Icons.notifications_rounded),
                          ),
                    ),
                    const SizedBox(width: 8),
                    IconButton.filledTonal(
                      onPressed: onLogout,
                      icon: const Icon(Icons.logout_rounded),
                    ),
                  ],
                ),
                const SizedBox(height: 24),
                Align(
                  alignment: Alignment.centerLeft,
                  child: Text(
                    'Hello, ${(data['name'] ?? 'Scholar').toString().split(' ').first}! 👋',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 30,
                      fontWeight: FontWeight.w900,
                      letterSpacing: -.7,
                    ),
                  ),
                ),
                const SizedBox(height: 5),
                const Align(
                  alignment: Alignment.centerLeft,
                  child: Text(
                    'Ready to make today amazing?',
                    style: TextStyle(color: Colors.white70, fontSize: 15),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _TodayCard extends ConsumerWidget {
  const _TodayCard({required this.role});
  final String role;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isTeacher = role.toLowerCase().contains('teacher');
    final todayWeekday = DateFormat(
      'EEEE',
    ).format(DateTime.now()).toLowerCase();
    final subtitle = isTeacher
        ? ref
              .watch(teacherClassesProvider)
              .maybeWhen(
                data: (classes) =>
                    '${classes.length} ${classes.length == 1 ? 'class' : 'classes'} assigned',
                orElse: () => 'Loading your schedule…',
              )
        : _studentSubtitle(ref, todayWeekday);
    return DepthCard(
      color: const Color(0xfffff4d6),
      child: Row(
        children: [
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xffffd166), Color(0xffff8a5b)],
              ),
              borderRadius: BorderRadius.circular(22),
            ),
            child: const Icon(
              Icons.auto_awesome_rounded,
              color: Colors.white,
              size: 34,
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Today at a glance',
                  style: TextStyle(fontSize: 17, fontWeight: FontWeight.w900),
                ),
                const SizedBox(height: 5),
                Text(
                  subtitle,
                  style: const TextStyle(color: Color(0xff7b6131)),
                ),
              ],
            ),
          ),
          const Icon(Icons.arrow_forward_ios_rounded, size: 18),
        ],
      ),
    );
  }

  String _studentSubtitle(WidgetRef ref, String todayWeekday) {
    final lessons = ref
        .watch(timetableProvider(todayWeekday))
        .maybeWhen(
          data: (data) => (data['periods'] as List)
              .cast<Map<String, dynamic>>()
              .where((period) => period['is_break'] != true)
              .length,
          orElse: () => null,
        );
    final today = DateFormat('yyyy-MM-dd').format(DateTime.now());
    final dueToday = ref
        .watch(homeworkListProvider)
        .maybeWhen(
          data: (items) => items
              .where(
                (item) =>
                    item['due_date'] == today && item['submitted'] != true,
              )
              .length,
          orElse: () => null,
        );
    if (lessons == null && dueToday == null) return 'Loading your day…';
    final lessonsText = lessons == null
        ? ''
        : '$lessons ${lessons == 1 ? 'lesson' : 'lessons'}';
    final dueText = dueToday == null
        ? ''
        : '$dueToday ${dueToday == 1 ? 'assignment' : 'assignments'} due';
    return [lessonsText, dueText].where((s) => s.isNotEmpty).join(' • ');
  }
}

typedef _ModuleItem = ({IconData icon, String label, Color color});

/// Drag-to-reorder module grid. Order is persisted on-device (by label, not
/// index) so it survives app restarts and gracefully absorbs role changes -
/// a label no longer relevant (e.g. Admin, after a role switch) is just
/// skipped, and any label never seen before is appended at the end in its
/// default position, instead of the saved order silently going stale.
class _ModuleGrid extends StatefulWidget {
  const _ModuleGrid({required this.role});
  final String role;

  @override
  State<_ModuleGrid> createState() => _ModuleGridState();
}

class _ModuleGridState extends State<_ModuleGrid> {
  static const _prefsKey = 'schooledge.home_module_order';
  List<String>? _savedOrder;
  bool _hasAnimatedIn = false;

  @override
  void initState() {
    super.initState();
    _restoreOrder();
  }

  Future<void> _restoreOrder() async {
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getStringList(_prefsKey);
    if (mounted && saved != null) setState(() => _savedOrder = saved);
  }

  Future<void> _persistOrder(List<String> labels) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setStringList(_prefsKey, labels);
  }

  List<_ModuleItem> _baseItems(ModuleColors module) {
    final role = widget.role;
    return [
      (
        icon: Icons.menu_book_rounded,
        label: 'Learning',
        color: module.learning,
      ),
      (
        icon: Icons.fact_check_rounded,
        label: 'Attendance',
        color: module.attendance,
      ),
      (
        icon: Icons.assignment_rounded,
        label: 'Homework',
        color: module.homework,
      ),
      (
        icon: Icons.workspace_premium_rounded,
        label: 'Results',
        color: module.results,
      ),
      (
        icon: Icons.account_balance_wallet_rounded,
        label: 'Fees',
        color: module.fees,
      ),
      (icon: Icons.forum_rounded, label: 'Messages', color: module.messages),
      (
        icon: Icons.local_library_rounded,
        label: 'Library',
        color: module.library,
      ),
      (
        icon: Icons.videocam_rounded,
        label: 'Online Class',
        color: module.liveClasses,
      ),
      if (role.toLowerCase().contains('student') ||
          role.toLowerCase().contains('parent'))
        (
          icon: Icons.emoji_events_rounded,
          label: 'Rewards',
          color: module.rewards,
        ),
      if (role.toLowerCase().contains('admin'))
        (
          icon: Icons.admin_panel_settings_rounded,
          label: 'Admin',
          color: module.admin,
        ),
    ];
  }

  List<_ModuleItem> _applySavedOrder(List<_ModuleItem> base) {
    final order = _savedOrder;
    if (order == null) return base;
    final byLabel = {for (final item in base) item.label: item};
    final ordered = <_ModuleItem>[
      for (final label in order) ?byLabel.remove(label),
    ];
    ordered.addAll(byLabel.values); // new labels the saved order predates
    return ordered;
  }

  void _reorder(List<_ModuleItem> items, int fromIndex, int toIndex) {
    if (fromIndex == toIndex) return;
    final updated = [...items];
    final moved = updated.removeAt(fromIndex);
    updated.insert(toIndex, moved);
    final labels = updated.map((item) => item.label).toList();
    setState(() => _savedOrder = labels);
    _persistOrder(labels);
  }

  @override
  Widget build(BuildContext context) {
    final module = Theme.of(context).extension<ModuleColors>()!;
    final items = _applySavedOrder(_baseItems(module));
    final grid = GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: 14,
        mainAxisSpacing: 14,
        childAspectRatio: 1.18,
      ),
      itemCount: items.length,
      itemBuilder: (context, index) {
        final item = items[index];
        final card = DepthCard(
          onTap: () {
            final role = widget.role;
            final page = switch (item.label) {
              'Learning' => LearningPage(role: role),
              'Attendance' => AttendancePage(role: role),
              'Homework' => HomeworkPage(role: role),
              'Results' => ResultsPage(role: role),
              'Fees' => const FeesPage(),
              'Library' => const LibraryPage(),
              'Online Class' => const LiveClassesPage(),
              'Rewards' => const GamificationPage(),
              'Admin' => const AdminDashboardPage(),
              _ => const MessagesPage(),
            };
            Navigator.of(context).push(moduleRoute<void>(page));
          },
          padding: const EdgeInsets.all(17),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: item.color.withValues(alpha: .13),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Icon(item.icon, color: item.color, size: 27),
              ),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      item.label,
                      style: const TextStyle(
                        fontWeight: FontWeight.w900,
                        fontSize: 16,
                      ),
                    ),
                  ),
                  Icon(Icons.north_east_rounded, color: item.color, size: 19),
                ],
              ),
            ],
          ),
        );

        final tile = LayoutBuilder(
          key: ValueKey(item.label),
          builder: (context, constraints) => DragTarget<int>(
            onWillAcceptWithDetails: (details) => details.data != index,
            onAcceptWithDetails: (details) =>
                _reorder(items, details.data, index),
            builder: (context, candidateData, rejectedData) => AnimatedScale(
              scale: candidateData.isNotEmpty ? 1.04 : 1.0,
              duration: const Duration(milliseconds: 120),
              child: LongPressDraggable<int>(
                data: index,
                dragAnchorStrategy: pointerDragAnchorStrategy,
                feedback: SizedBox(
                  width: constraints.maxWidth,
                  height: constraints.maxHeight,
                  child: Opacity(opacity: .85, child: card),
                ),
                childWhenDragging: Opacity(opacity: .25, child: card),
                child: card,
              ),
            ),
          ),
        );

        if (_hasAnimatedIn) return tile;
        return tile
            .animate(delay: (index * 45).ms)
            .fadeIn(duration: 320.ms)
            .slideY(begin: .12, end: 0, curve: Curves.easeOutCubic);
      },
    );
    // Only the very first build (per grid instance) plays the staggered
    // entrance - a reorder shouldn't replay it on every tile.
    _hasAnimatedIn = true;
    return grid;
  }
}

class _ProgressCard extends ConsumerWidget {
  const _ProgressCard({required this.role});
  final String role;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final hasStudentContext =
        ref.watch(studentContextProvider) != null ||
        role.toLowerCase() == 'student';
    final summary = hasStudentContext
        ? ref.watch(attendanceSummaryProvider)
        : null;
    final percent = summary?.maybeWhen(
      data: (data) => (data['present_percent'] as num?)?.toDouble(),
      orElse: () => null,
    );

    return DepthCard(
      color: const Color(0xffe9f9f5),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.insights_rounded, color: Color(0xff00897b)),
              SizedBox(width: 8),
              Text(
                'This week',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900),
              ),
            ],
          ),
          const SizedBox(height: 18),
          ClipRRect(
            borderRadius: BorderRadius.circular(20),
            child: LinearProgressIndicator(
              value: percent == null ? null : (percent / 100).clamp(0, 1),
              minHeight: 14,
              backgroundColor: Colors.white,
              color: const Color(0xff16b39a),
            ),
          ),
          const SizedBox(height: 10),
          Text(
            percent == null
                ? 'Attendance overview will appear here once available.'
                : '${percent.toStringAsFixed(0)}% attendance this term — keep going!',
            style: const TextStyle(
              color: Color(0xff35665f),
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  const _ErrorView({required this.onRetry});
  final VoidCallback onRetry;
  @override
  Widget build(BuildContext context) => Center(
    child: FilledButton.icon(
      onPressed: onRetry,
      icon: const Icon(Icons.refresh),
      label: const Text('Try again'),
    ),
  );
}
