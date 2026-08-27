import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/session/current_user_provider.dart';
import '../../../core/widgets/depth_card.dart';
import '../../auth/application/auth_controller.dart';
import '../../attendance/presentation/attendance_page.dart';
import '../../fees/presentation/fees_page.dart';
import '../../homework/presentation/homework_page.dart';
import '../../learning/presentation/learning_page.dart';
import '../../library/presentation/library_page.dart';
import '../../live_classes/presentation/live_classes_page.dart';
import '../../messages/presentation/messages_page.dart';
import '../../notifications/data/notifications_repository.dart';
import '../../notifications/presentation/notifications_page.dart';
import '../../planner/presentation/planner_page.dart';
import '../../profile/presentation/profile_page.dart';
import '../../results/presentation/results_page.dart';

class HomePage extends ConsumerStatefulWidget {
  const HomePage({super.key});
  @override
  ConsumerState<HomePage> createState() => _HomePageState();
}

class _HomePageState extends ConsumerState<HomePage> {
  int tab = 0;
  @override
  Widget build(BuildContext context) {
    final user = ref.watch(currentUserProvider);
    return Scaffold(
      body: user.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) =>
            _ErrorView(onRetry: () => ref.invalidate(currentUserProvider)),
        data: (data) => tab == 0
            ? CustomScrollView(
                slivers: [
                  SliverToBoxAdapter(
                    child: _Header(
                      data: data,
                      onLogout: () =>
                          ref.read(authControllerProvider.notifier).logout(),
                    ),
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
              )
            : switch (tab) {
                1 => const PlannerPage(),
                2 => const MessagesPage(embedded: true),
                _ => ProfilePage(
                  data: data,
                  onLogout: () =>
                      ref.read(authControllerProvider.notifier).logout(),
                ),
              },
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

  String _role(Map<String, dynamic> data) =>
      ((data['membership']?['role']?['name']) ?? 'Student').toString();
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
      padding: EdgeInsets.fromLTRB(
        20,
        MediaQuery.paddingOf(context).top + 14,
        20,
        30,
      ),
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xff163a70), Color(0xff136f70)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(38)),
        boxShadow: [
          BoxShadow(
            color: Color(0x33163a70),
            blurRadius: 26,
            offset: Offset(0, 12),
          ),
        ],
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
                onPressed: () => Navigator.of(context).push(
                  MaterialPageRoute<void>(builder: (_) => const NotificationsPage()),
                ),
                icon: ref
                    .watch(unreadCountProvider)
                    .maybeWhen(
                      data: (count) => count > 0
                          ? Badge(label: Text('$count'), child: const Icon(Icons.notifications_rounded))
                          : const Icon(Icons.notifications_rounded),
                      orElse: () => const Icon(Icons.notifications_rounded),
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
    );
  }
}

class _TodayCard extends StatelessWidget {
  const _TodayCard({required this.role});
  final String role;
  @override
  Widget build(BuildContext context) => DepthCard(
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
                role.toLowerCase().contains('teacher')
                    ? '4 classes • 2 assignments to review'
                    : '5 lessons • 1 assignment due',
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

class _ModuleGrid extends StatelessWidget {
  const _ModuleGrid({required this.role});
  final String role;
  @override
  Widget build(BuildContext context) {
    final items = <({IconData icon, String label, Color color})>[
      (
        icon: Icons.menu_book_rounded,
        label: 'Learning',
        color: const Color(0xff725cff),
      ),
      (
        icon: Icons.fact_check_rounded,
        label: 'Attendance',
        color: const Color(0xff00a896),
      ),
      (
        icon: Icons.assignment_rounded,
        label: 'Homework',
        color: const Color(0xffff6b6b),
      ),
      (
        icon: Icons.workspace_premium_rounded,
        label: 'Results',
        color: const Color(0xffffa62b),
      ),
      (
        icon: Icons.account_balance_wallet_rounded,
        label: 'Fees',
        color: const Color(0xff168aad),
      ),
      (
        icon: Icons.forum_rounded,
        label: 'Messages',
        color: const Color(0xffd65db1),
      ),
      (
        icon: Icons.local_library_rounded,
        label: 'Library',
        color: const Color(0xff2a9d8f),
      ),
      (
        icon: Icons.videocam_rounded,
        label: 'Online Class',
        color: const Color(0xffe76f51),
      ),
    ];
    return GridView.builder(
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
        return DepthCard(
          onTap: () {
            final page = switch (item.label) {
              'Learning' => LearningPage(role: role),
              'Attendance' => AttendancePage(role: role),
              'Homework' => HomeworkPage(role: role),
              'Results' => ResultsPage(role: role),
              'Fees' => const FeesPage(),
              'Library' => const LibraryPage(),
              'Online Class' => const LiveClassesPage(),
              _ => const MessagesPage(),
            };
            Navigator.of(
              context,
            ).push(MaterialPageRoute<void>(builder: (_) => page));
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
      },
    );
  }
}

class _ProgressCard extends StatelessWidget {
  const _ProgressCard({required this.role});
  final String role;
  @override
  Widget build(BuildContext context) => DepthCard(
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
          child: const LinearProgressIndicator(
            value: .72,
            minHeight: 14,
            backgroundColor: Colors.white,
            color: Color(0xff16b39a),
          ),
        ),
        const SizedBox(height: 10),
        const Text(
          '72% of weekly goals completed — keep going!',
          style: TextStyle(
            color: Color(0xff35665f),
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    ),
  );
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
