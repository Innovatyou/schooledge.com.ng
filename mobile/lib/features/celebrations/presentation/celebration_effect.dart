import 'dart:math';
import 'package:flutter/material.dart';
import 'package:flutter/scheduler.dart';

/// Full-screen fireworks, confetti or balloons drawn on a canvas - the same
/// effects as the web (assets/js/celebrate.js). Call [CelebrationEffectState.play]
/// through a GlobalKey to replay. Ignores touches; respects reduced motion.
class CelebrationEffect extends StatefulWidget {
  const CelebrationEffect({super.key, required this.theme});
  final String theme;

  @override
  State<CelebrationEffect> createState() => CelebrationEffectState();
}

const _colors = [
  Color(0xffff5d8f),
  Color(0xffffd166),
  Color(0xff06d6a0),
  Color(0xff4cc9f0),
  Color(0xff7b5cff),
  Color(0xffff8a5b),
  Color(0xfff72585),
];

class _Rocket {
  _Rocket(this.x, this.y, this.vx, this.vy, this.top, this.color, this.delay);
  double x, y, vx, vy, top;
  final Color color;
  int delay;
  final trail = <Offset>[];
}

class _Particle {
  _Particle({
    required this.kind,
    required this.x,
    required this.y,
    this.vx = 0,
    this.vy = 0,
    required this.color,
    required this.life,
    this.size = 2,
    this.drag = .955,
    this.fall = .06,
    this.twinkle = false,
    this.rot = 0,
    this.spin = 0,
    this.wob = 0,
  });
  final String kind; // spark | flash | paper | balloon
  double x, y, vx, vy, life, size, drag, fall, rot, spin, wob;
  final Color color;
  final bool twinkle;
  double px = 0, py = 0;
}

class CelebrationEffectState extends State<CelebrationEffect>
    with SingleTickerProviderStateMixin {
  final _rand = Random();
  final _rockets = <_Rocket>[];
  final _parts = <_Particle>[];
  late final Ticker _ticker;
  Size _size = Size.zero;
  int _frame = 0;

  double _r(double a, double b) => a + _rand.nextDouble() * (b - a);
  Color _pick() => _colors[_rand.nextInt(_colors.length)];

  @override
  void initState() {
    super.initState();
    _ticker = createTicker((_) => _tick());
    WidgetsBinding.instance.addPostFrameCallback((_) => play(widget.theme));
  }

  @override
  void dispose() {
    _ticker.dispose();
    super.dispose();
  }

  void play(String theme) {
    if (!mounted || _size == Size.zero || theme == 'none') return;
    if (MediaQuery.of(context).disableAnimations) return;
    switch (theme) {
      case 'balloons':
        _balloons(12);
        _fireworks(1);
      case 'confetti':
        _confetti(140);
      default:
        _fireworks(3);
    }
    if (!_ticker.isActive) _ticker.start();
  }

  // a show in waves, ending in a bigger finale; bursts either side of the card
  void _fireworks(int waves) {
    final w = _size.width, h = _size.height;
    final perWave = max(3, min(6, (w / 120).round()));
    for (var wave = 0; wave < waves; wave++) {
      final finale = wave == waves - 1 && waves > 1;
      final count = finale ? perWave + 2 : perWave;
      for (var i = 0; i < count; i++) {
        final x = i.isOdd ? _r(w * .62, w * .95) : _r(w * .05, w * .38);
        _rockets.add(
          _Rocket(
            x,
            h + 10,
            _r(-.5, .5),
            -_r(h / 62, h / 48),
            _r(h * .08, h * .3),
            _pick(),
            wave * 75 + (finale ? _r(0, 25).round() : (i * _r(8, 16)).round()),
          ),
        );
      }
    }
  }

  void _burst(double x, double y, Color color) {
    final type = [
      'peony',
      'peony',
      'ring',
      'willow',
      'glitter',
    ][_rand.nextInt(5)];
    final second = _pick();
    final n = type == 'ring' ? 56 : 84;
    for (var i = 0; i < n; i++) {
      final a = i / n * pi * 2 + _r(-.05, .05);
      final s = type == 'ring' ? 5.0 : _r(1.2, 6.2);
      final willow = type == 'willow';
      _parts.add(
        _Particle(
          kind: 'spark',
          x: x,
          y: y,
          vx: cos(a) * s,
          vy: sin(a) * s,
          color: willow
              ? const Color(0xffffd166)
              : (i % 3 == 0 ? second : color),
          life: willow ? _r(110, 150) : _r(55, 85),
          size: willow ? 1.6 : _r(1.8, 2.8),
          drag: willow ? .985 : .955,
          fall: willow ? .035 : .06,
          twinkle: type == 'glitter' || _rand.nextDouble() < .15,
        ),
      );
    }
    _parts.add(
      _Particle(kind: 'flash', x: x, y: y, color: color, life: 12, size: 80),
    );
  }

  void _confetti(int n) {
    final w = _size.width, h = _size.height;
    for (var i = 0; i < n; i++) {
      final side = i % 3;
      _parts.add(
        side == 2
            ? _Particle(
                kind: 'paper',
                x: _r(0, w),
                y: _r(-200, -10),
                vx: _r(-1, 1),
                vy: _r(1, 3),
                color: _pick(),
                life: _r(160, 260),
                size: _r(6, 10),
                rot: _r(0, 6.28),
                spin: _r(-.3, .3),
                wob: _r(0, 6.28),
              )
            : _Particle(
                kind: 'paper',
                x: side == 1 ? w + 10 : -10,
                y: h * .75,
                vx: (side == 1 ? -1 : 1) * _r(5, 11),
                vy: _r(-16, -9),
                color: _pick(),
                life: _r(160, 260),
                size: _r(6, 10),
                rot: _r(0, 6.28),
                spin: _r(-.3, .3),
                wob: _r(0, 6.28),
              ),
      );
    }
  }

  void _balloons(int n) {
    final w = _size.width, h = _size.height;
    for (var i = 0; i < n; i++) {
      _parts.add(
        _Particle(
          kind: 'balloon',
          x: _r(20, w - 20),
          y: h + _r(20, 400),
          vy: -_r(1.6, 3.2),
          color: _pick(),
          life: 600,
          size: _r(20, 32),
          wob: _r(0, 6.28),
        ),
      );
    }
    _confetti(n * 2);
  }

  void _tick() {
    _frame++;
    for (var r = _rockets.length - 1; r >= 0; r--) {
      final k = _rockets[r];
      if (k.delay-- > 0) continue;
      k.trail.add(Offset(k.x, k.y));
      if (k.trail.length > 10) k.trail.removeAt(0);
      k.x += k.vx;
      k.y += k.vy;
      k.vy *= .985;
      if (k.y <= k.top || k.vy > -2) {
        _burst(k.x, k.y, k.color);
        _rockets.removeAt(r);
      }
    }
    for (var i = _parts.length - 1; i >= 0; i--) {
      final p = _parts[i];
      p.life--;
      switch (p.kind) {
        case 'paper':
          p.vy = min(p.vy + .32, 4.5);
          p.vx *= .985;
          p.wob += .1;
          p.rot += p.spin;
          p.x += p.vx + sin(p.wob) * .8;
          p.y += p.vy;
        case 'balloon':
          p.wob += .03;
          p.x += sin(p.wob) * .7;
          p.y += p.vy;
          if (p.y < -p.size * 3) p.life = 0;
        case 'spark':
          p.px = p.x;
          p.py = p.y;
          p.vx *= p.drag;
          p.vy = p.vy * p.drag + p.fall;
          p.x += p.vx;
          p.y += p.vy;
      }
      if (p.life <= 0 || p.y > _size.height + 60) _parts.removeAt(i);
    }
    if (_parts.isEmpty && _rockets.isEmpty) _ticker.stop();
    setState(() {});
  }

  @override
  Widget build(BuildContext context) => IgnorePointer(
    child: LayoutBuilder(
      builder: (context, constraints) {
        _size = constraints.biggest;
        return CustomPaint(
          size: Size.infinite,
          painter: _EffectPainter(_rockets, _parts, _frame, _rand),
        );
      },
    ),
  );
}

class _EffectPainter extends CustomPainter {
  _EffectPainter(this.rockets, this.parts, this.frame, this.rand);
  final List<_Rocket> rockets;
  final List<_Particle> parts;
  final int frame;
  final Random rand;

  @override
  void paint(Canvas canvas, Size size) {
    final glow = Paint()..blendMode = BlendMode.plus;
    for (final k in rockets) {
      if (k.delay > 0) continue;
      for (var t = 0; t < k.trail.length; t++) {
        glow.color =
            (t > k.trail.length - 3 ? Colors.white : const Color(0xffffd166))
                .withValues(alpha: t / k.trail.length * .8);
        canvas.drawCircle(k.trail[t], 1.4 + t / 6, glow);
      }
    }
    for (final p in parts) {
      switch (p.kind) {
        case 'flash':
          final a = p.life / 12 * .5;
          canvas.drawCircle(
            Offset(p.x, p.y),
            p.size,
            Paint()
              ..shader =
                  RadialGradient(
                    colors: [
                      Colors.white.withValues(alpha: a),
                      Colors.white.withValues(alpha: 0),
                    ],
                  ).createShader(
                    Rect.fromCircle(center: Offset(p.x, p.y), radius: p.size),
                  ),
          );
        case 'spark':
          var alpha = (p.life / 35).clamp(0.0, 1.0);
          if (p.twinkle && p.life < 45 && rand.nextBool()) alpha *= .15;
          canvas.drawLine(
            Offset(p.px - p.vx * 2, p.py - p.vy * 2),
            Offset(p.x, p.y),
            Paint()
              ..blendMode = BlendMode.plus
              ..color = p.color.withValues(alpha: alpha)
              ..strokeWidth = p.size
              ..strokeCap = StrokeCap.round,
          );
        case 'paper':
          canvas.save();
          canvas.translate(p.x, p.y);
          canvas.rotate(p.rot);
          canvas.scale(1, cos(p.wob).abs() + .2);
          canvas.drawRect(
            Rect.fromCenter(
              center: Offset.zero,
              width: p.size,
              height: p.size / 2,
            ),
            Paint()..color = p.color,
          );
          canvas.restore();
        case 'balloon':
          final string = Path()
            ..moveTo(p.x, p.y + p.size * 1.2)
            ..quadraticBezierTo(
              p.x + sin(p.wob * 3) * 8,
              p.y + p.size * 2,
              p.x,
              p.y + p.size * 2.8,
            );
          canvas.drawPath(
            string,
            Paint()
              ..style = PaintingStyle.stroke
              ..color = const Color(0x99787890),
          );
          canvas.drawOval(
            Rect.fromCenter(
              center: Offset(p.x, p.y),
              width: p.size * 1.64,
              height: p.size * 2,
            ),
            Paint()..color = p.color,
          );
          canvas.drawOval(
            Rect.fromCenter(
              center: Offset(p.x - p.size * .3, p.y - p.size * .35),
              width: p.size * .32,
              height: p.size * .56,
            ),
            Paint()..color = Colors.white.withValues(alpha: .45),
          );
      }
    }
  }

  @override
  bool shouldRepaint(covariant _EffectPainter old) => true;
}
