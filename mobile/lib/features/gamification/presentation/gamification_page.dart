import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/gamification_repository.dart';

class GamificationPage extends ConsumerWidget {
  const GamificationPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final me = ref.watch(gamificationMeProvider);
    return ModulePage(
      title: 'Rewards',
      subtitle: 'Points and badges earned from attendance and homework.',
      icon: Icons.emoji_events_rounded,
      colors: const [Color(0xff725cff), Color(0xff9d8bff)],
      children: [
        me.when(
          loading: () => const Padding(
            padding: EdgeInsets.symmetric(vertical: 60),
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (error, _) => InfoRow(
            icon: Icons.refresh_rounded,
            title: 'Could not load your rewards',
            subtitle: 'Tap to try again',
            color: const Color(0xffff6b6b),
            onTap: () => ref.invalidate(gamificationMeProvider),
          ),
          data: (data) => _MeSection(data: data),
        ),
        const SectionTitle('Class leaderboard'),
        ref
            .watch(leaderboardProvider)
            .when(
              loading: () => const Padding(
                padding: EdgeInsets.symmetric(vertical: 24),
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => InfoRow(
                icon: Icons.refresh_rounded,
                title: 'Could not load the leaderboard',
                subtitle: 'Tap to try again',
                color: const Color(0xffff6b6b),
                onTap: () => ref.invalidate(leaderboardProvider),
              ),
              data: (rows) {
                final myRank = me.maybeWhen(
                  data: (data) => data['rank_in_class'] as int?,
                  orElse: () => null,
                );
                if (rows.isEmpty) {
                  return const InfoRow(
                    icon: Icons.leaderboard_rounded,
                    title: 'No points earned yet',
                    subtitle: 'Be the first in your class to earn points!',
                    color: Color(0xff829ab1),
                    trailing: SizedBox.shrink(),
                  );
                }
                return Column(
                  children: rows.map((row) {
                    final isMe = myRank != null && row['rank'] == myRank;
                    return InfoRow(
                      icon: Icons.emoji_events_rounded,
                      title: '#${row['rank']} ${row['student_name']}',
                      subtitle:
                          '${row['points_total']} points · ${row['badge_count']} badges',
                      color: isMe
                          ? const Color(0xff725cff)
                          : const Color(0xffffa62b),
                      trailing: isMe
                          ? const Icon(
                              Icons.person_pin_rounded,
                              color: Color(0xff725cff),
                            )
                          : const SizedBox.shrink(),
                    );
                  }).toList(),
                );
              },
            ),
      ],
    );
  }
}

class _MeSection extends StatelessWidget {
  const _MeSection({required this.data});
  final Map<String, dynamic> data;

  @override
  Widget build(BuildContext context) {
    final badges = (data['badges'] as List).cast<Map<String, dynamic>>();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            StatTile(
              value: '${data['points_total']}',
              label: 'Points',
              icon: Icons.stars_rounded,
              color: const Color(0xff725cff),
            ),
            const SizedBox(width: 12),
            StatTile(
              value: data['rank_in_class'] == null
                  ? '-'
                  : '#${data['rank_in_class']}',
              label: 'Class rank',
              icon: Icons.leaderboard_rounded,
              color: const Color(0xffffa62b),
            ),
            const SizedBox(width: 12),
            StatTile(
              value: '${badges.length}',
              label: 'Badges',
              icon: Icons.military_tech_rounded,
              color: const Color(0xff00a896),
            ),
          ],
        ),
        const SectionTitle('Your badges'),
        if (badges.isEmpty)
          const InfoRow(
            icon: Icons.emoji_events_outlined,
            title: 'No badges yet',
            subtitle:
                'Keep up your attendance streak or submit homework on time!',
            color: Color(0xff829ab1),
            trailing: SizedBox.shrink(),
          )
        else
          ...badges.map(
            (badge) => InfoRow(
              icon: _iconFor(badge['icon'] as String?),
              title: badge['name']?.toString() ?? 'Badge',
              subtitle: badge['description']?.toString() ?? '',
              color: const Color(0xff725cff),
              trailing: const SizedBox.shrink(),
            ),
          ),
      ],
    );
  }

  IconData _iconFor(String? code) => switch (code) {
    'local_fire_department' => Icons.local_fire_department_rounded,
    'whatshot' => Icons.whatshot_rounded,
    'military_tech' => Icons.military_tech_rounded,
    'check_circle' => Icons.check_circle_rounded,
    _ => Icons.emoji_events_rounded,
  };
}
