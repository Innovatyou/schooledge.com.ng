import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/depth_card.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/celebrations_repository.dart';
import 'celebration_effect.dart';
import 'celebration_popup.dart';

/// The school's celebrations wall: birthdays, top performers of the latest
/// exam, best teachers, star students and school celebrations.
class CelebrationsPage extends ConsumerStatefulWidget {
  const CelebrationsPage({super.key});

  @override
  ConsumerState<CelebrationsPage> createState() => _CelebrationsPageState();
}

class _CelebrationsPageState extends ConsumerState<CelebrationsPage> {
  final _effect = GlobalKey<CelebrationEffectState>();

  @override
  Widget build(BuildContext context) {
    final wall = ref.watch(celebrationsProvider);
    return Stack(
      children: [
        ModulePage(
          title: 'Celebrations',
          subtitle: 'Birthdays, top performers, best teachers and more.',
          icon: Icons.celebration_rounded,
          colors: const [Color(0xff7b5cff), Color(0xffff5d8f)],
          children: [
            wall.when(
              loading: () => const Padding(
                padding: EdgeInsets.symmetric(vertical: 60),
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => InfoRow(
                icon: Icons.refresh_rounded,
                title: 'Could not load celebrations',
                subtitle: 'Tap to try again',
                color: const Color(0xffff6b6b),
                onTap: () => ref.invalidate(celebrationsProvider),
              ),
              data: (data) => _Wall(
                data: data,
                onFireworks: () => _effect.currentState?.play('fireworks'),
              ),
            ),
          ],
        ),
        Positioned.fill(
          child: CelebrationEffect(key: _effect, theme: 'none'),
        ),
      ],
    );
  }
}

class _Wall extends StatelessWidget {
  const _Wall({required this.data, required this.onFireworks});
  final Map<String, dynamic> data;
  final VoidCallback onFireworks;

  @override
  Widget build(BuildContext context) {
    final mine = asMaps(data['for_me']);
    final birthdays = asMaps(data['birthdays']);
    final top = data['top_performers'] as Map?;
    final teachers = asMaps(data['best_teachers']);
    final stars = asMaps(data['star_students']);
    final events = asMaps(data['events']);
    final anniversaries = asMaps(data['anniversaries']);
    const medals = {1: '🥇', 2: '🥈', 3: '🥉'};

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Wrap(
          spacing: 10,
          runSpacing: 8,
          children: [
            if (mine.isNotEmpty)
              FilledButton.icon(
                onPressed: () => showCelebrations(context, mine),
                icon: const Text('🎁'),
                label: Text(
                  'You have ${mine.length} celebration${mine.length == 1 ? '' : 's'}',
                ),
              ),
            OutlinedButton.icon(
              onPressed: onFireworks,
              icon: const Text('🎆'),
              label: const Text('Launch fireworks'),
            ),
          ],
        ),
        const SectionTitle('🎂 Birthdays today'),
        if (birthdays.isEmpty)
          const _Empty('No birthdays today.')
        else
          for (final b in birthdays)
            _Person(
              photo: b['photo'],
              name: '${b['name']}',
              detail: [
                if ('${b['detail'] ?? ''}'.isNotEmpty) b['detail'],
                if (b['age'] != null) 'turns ${b['age']}',
              ].join(' · '),
              badge: '🎂',
            ),
        SectionTitle(
          '🏆 Top performers${top == null ? '' : ' · ${top['exam']}'}',
        ),
        if (top == null || asMaps(top['classes']).isEmpty)
          const _Empty(
            'The top three in each class appear here once exam results are published.',
          )
        else
          for (final c in asMaps(top['classes']))
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: DepthCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '${c['class']}',
                      style: const TextStyle(
                        fontWeight: FontWeight.w900,
                        fontSize: 16,
                      ),
                    ),
                    const SizedBox(height: 8),
                    for (final s in asMaps(c['students']))
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 4),
                        child: Row(
                          children: [
                            Text(
                              medals[s['rank']] ?? '',
                              style: const TextStyle(fontSize: 22),
                            ),
                            const SizedBox(width: 10),
                            _Avatar(s['photo'], 18),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                '${s['name']}',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                      ),
                  ],
                ),
              ),
            ),
        const SectionTitle('🍎 Best teachers'),
        if (teachers.isEmpty)
          const _Empty('No best teacher picked right now.')
        else
          for (final p in teachers) _Pick(p: p, badge: '🍎'),
        const SectionTitle('⭐ Star students'),
        if (stars.isEmpty)
          const _Empty('No star student picked right now.')
        else
          for (final p in stars) _Pick(p: p, badge: '⭐'),
        if (events.isNotEmpty || anniversaries.isNotEmpty) ...[
          const SectionTitle('🎈 School celebrations'),
          for (final p in events) _Pick(p: p, badge: '🎉'),
          for (final a in anniversaries)
            _Person(
              photo: a['photo'],
              name: '${a['name']}',
              detail:
                  '${a['years']} year${a['years'] == 1 ? '' : 's'} at our school',
              badge: '🏅',
            ),
        ],
        const SizedBox(height: 40),
      ],
    );
  }
}

class _Empty extends StatelessWidget {
  const _Empty(this.text);
  final String text;
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(bottom: 8),
    child: Text(
      text,
      style: TextStyle(
        color: Theme.of(context).colorScheme.onSurface.withValues(alpha: .6),
      ),
    ),
  );
}

class _Avatar extends StatelessWidget {
  const _Avatar(this.url, this.radius);
  final Object? url;
  final double radius;
  @override
  Widget build(BuildContext context) => CircleAvatar(
    radius: radius,
    backgroundColor: const Color(0xffeceef3),
    foregroundImage: url == null ? null : NetworkImage('$url'),
    child: Icon(Icons.person, size: radius),
  );
}

class _Person extends StatelessWidget {
  const _Person({
    required this.photo,
    required this.name,
    required this.detail,
    required this.badge,
  });
  final Object? photo;
  final String name, detail, badge;
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(bottom: 10),
    child: DepthCard(
      child: Row(
        children: [
          _Avatar(photo, 24),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontWeight: FontWeight.w800),
                ),
                if (detail.isNotEmpty)
                  Text(
                    detail,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: Theme.of(
                        context,
                      ).colorScheme.onSurface.withValues(alpha: .6),
                    ),
                  ),
              ],
            ),
          ),
          Text(badge, style: const TextStyle(fontSize: 24)),
        ],
      ),
    ),
  );
}

class _Pick extends StatelessWidget {
  const _Pick({required this.p, required this.badge});
  final Map<String, dynamic> p;
  final String badge;
  @override
  Widget build(BuildContext context) {
    final name = '${p['name'] ?? ''}';
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: DepthCard(
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (name.isNotEmpty)
              _Avatar(p['photo'], 28)
            else
              Text(badge, style: const TextStyle(fontSize: 40)),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    '${p['title']}'.toUpperCase(),
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w900,
                      letterSpacing: .6,
                      color: Color(0xff7b5cff),
                    ),
                  ),
                  if (name.isNotEmpty)
                    Text(
                      name,
                      style: const TextStyle(
                        fontWeight: FontWeight.w900,
                        fontSize: 17,
                      ),
                    ),
                  if ('${p['detail'] ?? ''}'.isNotEmpty)
                    Text(
                      '${p['detail']}',
                      style: TextStyle(
                        color: Theme.of(
                          context,
                        ).colorScheme.onSurface.withValues(alpha: .6),
                      ),
                    ),
                  if ('${p['message'] ?? ''}'.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text('${p['message']}'),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
