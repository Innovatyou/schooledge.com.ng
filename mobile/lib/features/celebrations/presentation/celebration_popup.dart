import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'celebration_effect.dart';

const _seenKey = 'schooledge.celebrated_keys';

/// Pops up [items] one at a time with their animation. With [once], each
/// item's key is remembered on the device so it only pops up the first time.
Future<void> showCelebrations(
  BuildContext context,
  List<Map<String, dynamic>> items, {
  bool once = false,
}) async {
  var queue = items;
  SharedPreferences? prefs;
  if (once) {
    prefs = await SharedPreferences.getInstance();
    final seen = prefs.getStringList(_seenKey) ?? const <String>[];
    queue = items.where((item) => !seen.contains(item['key'])).toList();
    if (queue.isEmpty) return;
    // keep the list short: birthday keys carry the date, so old ones never match again
    final updated = [...seen, ...queue.map((item) => '${item['key']}')];
    await prefs.setStringList(
      _seenKey,
      updated.length > 200 ? updated.sublist(updated.length - 200) : updated,
    );
  }
  if (!context.mounted) return;
  await showGeneralDialog<void>(
    context: context,
    barrierDismissible: true,
    barrierLabel: 'Close',
    barrierColor: Colors.transparent,
    transitionDuration: const Duration(milliseconds: 280),
    pageBuilder: (_, _, _) => _CelebrationDialog(items: queue),
    transitionBuilder: (_, animation, _, child) => FadeTransition(
      opacity: animation,
      child: ScaleTransition(
        scale: Tween(begin: .94, end: 1.0).animate(
          CurvedAnimation(parent: animation, curve: Curves.easeOutBack),
        ),
        child: child,
      ),
    ),
  );
}

class _CelebrationDialog extends StatefulWidget {
  const _CelebrationDialog({required this.items});
  final List<Map<String, dynamic>> items;

  @override
  State<_CelebrationDialog> createState() => _CelebrationDialogState();
}

class _CelebrationDialogState extends State<_CelebrationDialog> {
  int index = 0;
  var effectKey = GlobalKey<CelebrationEffectState>();

  Map<String, dynamic> get item => widget.items[index];
  String get theme => (item['theme'] ?? 'fireworks').toString();

  @override
  Widget build(BuildContext context) {
    final last = index == widget.items.length - 1;
    final photo = item['photo']?.toString();
    final emoji = (item['emoji'] ?? '🎉').toString();
    return Stack(
      children: [
        // fireworks glow on a night sky; the others on a dimmed screen
        Positioned.fill(
          child: GestureDetector(
            onTap: () => Navigator.of(context).pop(),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 400),
              decoration: BoxDecoration(
                gradient: theme == 'fireworks'
                    ? const RadialGradient(
                        center: Alignment(0, -.5),
                        radius: 1.2,
                        colors: [Color(0xe61e185a), Color(0xf004050f)],
                      )
                    : null,
                color: theme == 'fireworks' ? null : const Color(0x8c0f121e),
              ),
            ),
          ),
        ),
        Center(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Material(
              color: Theme.of(context).colorScheme.surface,
              borderRadius: BorderRadius.circular(28),
              elevation: 24,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(24, 30, 24, 20),
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 380),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (photo != null && photo.isNotEmpty)
                        SizedBox(
                          width: 108,
                          height: 108,
                          child: Stack(
                            clipBehavior: Clip.none,
                            children: [
                              Container(
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  border: Border.all(
                                    color: const Color(0xfff5b301),
                                    width: 5,
                                  ),
                                ),
                                child: ClipOval(
                                  child: Image.network(
                                    photo,
                                    width: 108,
                                    height: 108,
                                    fit: BoxFit.cover,
                                    errorBuilder: (_, _, _) => const ColoredBox(
                                      color: Color(0xffeceef3),
                                      child: Center(
                                        child: Icon(Icons.person, size: 48),
                                      ),
                                    ),
                                  ),
                                ),
                              ),
                              Positioned(
                                right: -10,
                                bottom: -6,
                                child: Text(
                                  emoji,
                                  style: const TextStyle(fontSize: 40),
                                ),
                              ),
                            ],
                          ),
                        )
                      else
                        Text(emoji, style: const TextStyle(fontSize: 68)),
                      const SizedBox(height: 16),
                      Text(
                        (item['title'] ?? '').toString(),
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          fontSize: 23,
                          fontWeight: FontWeight.w900,
                          height: 1.2,
                        ),
                      ),
                      if ((item['message'] ?? '').toString().isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Text(
                          item['message'].toString(),
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 15,
                            height: 1.5,
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurface.withValues(alpha: .65),
                          ),
                        ),
                      ],
                      const SizedBox(height: 20),
                      Wrap(
                        spacing: 10,
                        runSpacing: 8,
                        alignment: WrapAlignment.center,
                        children: [
                          OutlinedButton(
                            onPressed: () =>
                                effectKey.currentState?.play(theme),
                            child: const Text('🎆 Celebrate again'),
                          ),
                          FilledButton(
                            onPressed: () {
                              if (last) {
                                Navigator.of(context).pop();
                              } else {
                                setState(() {
                                  index++;
                                  effectKey =
                                      GlobalKey<CelebrationEffectState>();
                                });
                              }
                            },
                            child: Text(
                              last
                                  ? 'Thank you!'
                                  : 'Next (${index + 2} of ${widget.items.length})',
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
        Positioned.fill(
          child: CelebrationEffect(key: effectKey, theme: theme),
        ),
      ],
    );
  }
}
