/*
 * Celebration animations: confetti, balloons and fireworks on a full-screen
 * canvas, plus a pop-up card. No libraries.
 *
 *   SkoolCelebrate.play('confetti' | 'balloons' | 'fireworks')
 *   SkoolCelebrate.show(items, {once: true})  // items: [{key, theme, emoji, title, message, photo}]
 *
 * With once, each item's key is remembered on this device so the same
 * celebration pops up only the first time. People who ask their system for
 * reduced motion get the card without the animation.
 */
(function (window, document) {
	'use strict';

	var COLORS = ['#ff5d8f', '#ffd166', '#06d6a0', '#4cc9f0', '#7b5cff', '#ff8a5b', '#f72585'];
	var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var canvas, ctx, parts = [], rockets = [], running = false, until = 0, dpr = 1;

	function rand(a, b) { return a + Math.random() * (b - a); }
	function pick(list) { return list[Math.floor(Math.random() * list.length)]; }

	function setup() {
		if (canvas) return;
		canvas = document.createElement('canvas');
		canvas.className = 'cb-canvas';
		canvas.setAttribute('aria-hidden', 'true');
		document.body.appendChild(canvas);
		ctx = canvas.getContext('2d');
		resize();
		window.addEventListener('resize', resize);
	}

	function resize() {
		dpr = Math.min(window.devicePixelRatio || 1, 2);
		canvas.width = window.innerWidth * dpr;
		canvas.height = window.innerHeight * dpr;
		ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
	}

	function confetti(n) {
		var w = window.innerWidth;
		for (var i = 0; i < n; i++) {
			// two cannons from the bottom corners plus a gentle rain from the top
			var side = i % 3;
			var p = {
				kind: 'paper', color: pick(COLORS), size: rand(6, 11), rot: rand(0, 6.28), spin: rand(-0.3, 0.3),
				wob: rand(0, 6.28), life: rand(160, 260)
			};
			if (side === 2) {
				p.x = rand(0, w); p.y = rand(-200, -10); p.vx = rand(-1, 1); p.vy = rand(1, 3);
			} else {
				p.x = side ? w + 10 : -10; p.y = window.innerHeight * 0.75;
				p.vx = (side ? -1 : 1) * rand(6, 14); p.vy = rand(-17, -9);
			}
			parts.push(p);
		}
	}

	function balloons(n) {
		var w = window.innerWidth, h = window.innerHeight;
		for (var i = 0; i < n; i++) {
			parts.push({
				kind: 'balloon', color: pick(COLORS), size: rand(22, 38), x: rand(20, w - 20), y: h + rand(20, 400),
				vx: 0, vy: -rand(1.6, 3.2), wob: rand(0, 6.28), life: 600
			});
		}
		confetti(Math.round(n * 2));
	}

	// a show in waves (frames of delay at ~60fps), ending in a bigger finale
	function fireworks(waves) {
		var w = window.innerWidth, h = window.innerHeight;
		var perWave = Math.max(3, Math.min(7, Math.round(w / 260)));
		var card = !!document.querySelector('.cb-overlay') && w > 700;
		for (var wave = 0; wave < waves; wave++) {
			var finale = wave === waves - 1 && waves > 1;
			var count = finale ? perWave + 3 : perWave;
			for (var i = 0; i < count; i++) {
				// with a pop-up open, burst either side of the card so the message stays readable
				var x = card ? (i % 2 ? rand(w * 0.66, w * 0.94) : rand(w * 0.06, w * 0.34)) : rand(w * 0.1, w * 0.9);
				rockets.push({
					x: x, y: h + 10, vx: rand(-0.6, 0.6), vy: -rand(h / 62, h / 48), top: card ? rand(h * 0.1, h * 0.55) : rand(h * 0.12, h * 0.42),
					color: pick(COLORS), delay: wave * 75 + (finale ? rand(0, 25) : i * rand(8, 16)), trail: []
				});
			}
		}
	}

	function burst(x, y, color) {
		var type = pick(['peony', 'peony', 'ring', 'willow', 'glitter']);
		var second = pick(COLORS);
		var n = type === 'ring' ? 70 : 110;
		for (var i = 0; i < n; i++) {
			var a = (i / n) * Math.PI * 2 + rand(-0.05, 0.05);
			var s = type === 'ring' ? 6 : rand(1.4, 7.5);
			parts.push({
				kind: 'spark', x: x, y: y, px: x, py: y, vx: Math.cos(a) * s, vy: Math.sin(a) * s,
				color: type === 'willow' ? '#ffd166' : (i % 3 === 0 ? second : color),
				life: type === 'willow' ? rand(110, 150) : rand(55, 85), size: type === 'willow' ? 1.6 : rand(1.8, 2.8),
				drag: type === 'willow' ? 0.985 : 0.955, fall: type === 'willow' ? 0.035 : 0.06,
				twinkle: type === 'glitter' || Math.random() < 0.15
			});
		}
		parts.push({ kind: 'flash', x: x, y: y, color: color, life: 12, size: 90 });
	}

	function frame() {
		var w = window.innerWidth, h = window.innerHeight;
		ctx.clearRect(0, 0, w, h);
		ctx.globalCompositeOperation = 'lighter';
		for (var r = rockets.length - 1; r >= 0; r--) {
			var k = rockets[r];
			if (k.delay-- > 0) continue;
			k.trail.push([k.x, k.y]);
			if (k.trail.length > 10) k.trail.shift();
			k.x += k.vx; k.y += k.vy; k.vy *= 0.985;
			for (var t = 0; t < k.trail.length; t++) {
				ctx.globalAlpha = t / k.trail.length * 0.8;
				ctx.fillStyle = t > k.trail.length - 3 ? '#fff' : '#ffd166';
				ctx.beginPath(); ctx.arc(k.trail[t][0] + rand(-0.8, 0.8), k.trail[t][1], 1.4 + t / 6, 0, Math.PI * 2); ctx.fill();
			}
			ctx.globalAlpha = 1;
			if (k.y <= k.top || k.vy > -2) { burst(k.x, k.y, k.color); rockets.splice(r, 1); }
		}
		ctx.globalCompositeOperation = 'source-over';
		for (var i = parts.length - 1; i >= 0; i--) {
			var p = parts[i];
			p.life--;
			if (p.kind === 'paper') {
				p.vy += 0.32; p.vx *= 0.985; p.vy = Math.min(p.vy, 4.5);
				p.wob += 0.1; p.rot += p.spin;
				p.x += p.vx + Math.sin(p.wob) * 0.8; p.y += p.vy;
				ctx.save();
				ctx.translate(p.x, p.y); ctx.rotate(p.rot); ctx.scale(1, Math.abs(Math.cos(p.wob)) + 0.2);
				ctx.fillStyle = p.color;
				ctx.fillRect(-p.size / 2, -p.size / 4, p.size, p.size / 2);
				ctx.restore();
			} else if (p.kind === 'balloon') {
				p.wob += 0.03; p.x += Math.sin(p.wob) * 0.7; p.y += p.vy;
				ctx.strokeStyle = 'rgba(120,120,140,.6)'; ctx.lineWidth = 1;
				ctx.beginPath(); ctx.moveTo(p.x, p.y + p.size * 1.2);
				ctx.quadraticCurveTo(p.x + Math.sin(p.wob * 3) * 8, p.y + p.size * 2, p.x, p.y + p.size * 2.8); ctx.stroke();
				ctx.fillStyle = p.color;
				ctx.beginPath(); ctx.ellipse(p.x, p.y, p.size * 0.82, p.size, 0, 0, Math.PI * 2); ctx.fill();
				ctx.beginPath(); ctx.moveTo(p.x - 4, p.y + p.size + 5); ctx.lineTo(p.x + 4, p.y + p.size + 5); ctx.lineTo(p.x, p.y + p.size - 1); ctx.fill();
				ctx.fillStyle = 'rgba(255,255,255,.45)';
				ctx.beginPath(); ctx.ellipse(p.x - p.size * 0.3, p.y - p.size * 0.35, p.size * 0.16, p.size * 0.28, -0.5, 0, Math.PI * 2); ctx.fill();
				if (p.y < -p.size * 3) p.life = 0;
			} else if (p.kind === 'flash') {
				// the soft glow where a shell bursts
				var g = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.size);
				g.addColorStop(0, 'rgba(255,255,255,' + (p.life / 12 * 0.55) + ')');
				g.addColorStop(1, 'rgba(255,255,255,0)');
				ctx.fillStyle = g;
				ctx.fillRect(p.x - p.size, p.y - p.size, p.size * 2, p.size * 2);
			} else {
				p.px = p.x; p.py = p.y;
				p.vx *= p.drag; p.vy = p.vy * p.drag + p.fall; p.x += p.vx; p.y += p.vy;
				var alpha = Math.max(0, Math.min(1, p.life / 35));
				if (p.twinkle && p.life < 45) alpha *= Math.random() < 0.5 ? 0.15 : 1;
				ctx.globalCompositeOperation = 'lighter';
				ctx.globalAlpha = alpha;
				ctx.strokeStyle = p.color; ctx.lineWidth = p.size; ctx.lineCap = 'round';
				ctx.beginPath(); ctx.moveTo(p.px - p.vx * 2, p.py - p.vy * 2); ctx.lineTo(p.x, p.y); ctx.stroke();
				ctx.globalAlpha = 1;
				ctx.globalCompositeOperation = 'source-over';
			}
			if (p.life <= 0 || p.y > h + 60) parts.splice(i, 1);
		}
		if (parts.length || rockets.length || Date.now() < until) {
			window.requestAnimationFrame(frame);
		} else {
			running = false;
			ctx.clearRect(0, 0, w, h);
			canvas.classList.remove('cb-night');
		}
	}

	function play(theme) {
		if (reduced) return;
		setup();
		if (theme === 'balloons') {
			balloons(16);
			fireworks(1);
		} else if (theme === 'fireworks') {
			// dim the page so the shells glow, then a three-wave show with a finale
			if (!document.querySelector('.cb-overlay')) canvas.classList.add('cb-night'); // a pop-up already darkens the page
			fireworks(3);
			setTimeout(function () { confetti(40); }, 2600);
		} else {
			confetti(170);
		}
		until = Date.now() + 600;
		if (!running) { running = true; window.requestAnimationFrame(frame); }
	}

	function seen(key) {
		try { return window.localStorage.getItem('skoolos.celebrated.' + key) === '1'; } catch (e) { return false; }
	}
	function remember(key) {
		try { window.localStorage.setItem('skoolos.celebrated.' + key, '1'); } catch (e) { /* private mode: it may pop up again */ }
	}

	function esc(text) {
		var d = document.createElement('div');
		d.textContent = text == null ? '' : String(text);
		return d.innerHTML;
	}

	// one card at a time; "Next" moves through the queue
	function show(items, options) {
		options = options || {};
		var queue = (items || []).filter(function (item) { return !options.once || !seen(item.key); });
		if (!queue.length) return;
		var overlay = document.createElement('div');
		overlay.className = 'cb-overlay';
		overlay.setAttribute('role', 'dialog');
		overlay.setAttribute('aria-modal', 'true');
		document.body.appendChild(overlay);
		var index = 0;

		function close() {
			document.removeEventListener('keydown', onKey);
			overlay.classList.remove('cb-in');
			setTimeout(function () { overlay.remove(); }, 250);
		}
		function onKey(e) { if (e.key === 'Escape') close(); }
		function render() {
			var item = queue[index];
			if (options.once) remember(item.key);
			overlay.classList.toggle('cb-sky', item.theme === 'fireworks'); // fireworks glow on a night sky
			var last = index === queue.length - 1;
			overlay.innerHTML =
				'<div class="cb-card">' +
					'<button type="button" class="cb-x" aria-label="Close">&times;</button>' +
					(item.photo ? '<div class="cb-photo"><img src="' + esc(item.photo) + '" alt=""><span>' + esc(item.emoji) + '</span></div>' : '<div class="cb-emoji">' + esc(item.emoji) + '</div>') +
					'<h3 class="cb-title">' + esc(item.title) + '</h3>' +
					(item.message ? '<p class="cb-msg">' + esc(item.message) + '</p>' : '') +
					'<div class="cb-actions">' +
						'<button type="button" class="cb-btn cb-again">🎉 Celebrate again</button>' +
						'<button type="button" class="cb-btn cb-primary cb-next">' + (last ? 'Thank you!' : 'Next (' + (index + 2) + ' of ' + queue.length + ')') + '</button>' +
					'</div>' +
				'</div>';
			overlay.querySelector('.cb-x').onclick = close;
			overlay.querySelector('.cb-again').onclick = function () { play(item.theme); };
			overlay.querySelector('.cb-next').onclick = function () {
				if (last) { close(); } else { index++; render(); }
			};
			overlay.querySelector('.cb-next').focus();
			play(item.theme);
		}
		overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
		document.addEventListener('keydown', onKey);
		render();
		window.requestAnimationFrame(function () { overlay.classList.add('cb-in'); });
	}

	window.SkoolCelebrate = { play: play, show: show };
})(window, document);
