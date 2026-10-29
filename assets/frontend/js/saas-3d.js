/* =========================================================
   SaaS landing page: 3D interactions
   Hero WebGL scene, module orbit ring, tilt cards, portal tabs,
   counters and scroll reveal. Everything degrades gracefully
   without WebGL and honours prefers-reduced-motion.
   ========================================================= */
(function () {
    'use strict';

    var root = document.documentElement;
    root.classList.remove('no-js');

    /* ---------- light / dark theme ----------
       The initial theme is set before paint by the inline script in _head.php;
       this wires up the toggle and follows OS changes until the visitor picks one. */
    var THEME_KEY = 'skoolos-theme';
    var themeToggles = document.querySelectorAll('.theme-toggle');
    function savedTheme() {
        try { return localStorage.getItem(THEME_KEY); } catch (e) { return null; }
    }
    function applyTheme(theme, persist) {
        root.setAttribute('data-theme', theme);
        themeToggles.forEach(function (btn) {
            btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
            btn.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
        });
        if (persist) {
            try { localStorage.setItem(THEME_KEY, theme); } catch (e) {}
        }
    }
    applyTheme(root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light', false);
    themeToggles.forEach(function (btn) {
        btn.addEventListener('click', function () {
            applyTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark', true);
        });
    });
    if (window.matchMedia) {
        var systemDark = window.matchMedia('(prefers-color-scheme: dark)');
        var onSystemChange = function (e) {
            var s = savedTheme();
            if (s !== 'light' && s !== 'dark') applyTheme(e.matches ? 'dark' : 'light', false);
        };
        if (systemDark.addEventListener) systemDark.addEventListener('change', onSystemChange);
        else if (systemDark.addListener) systemDark.addListener(onSystemChange);
    }
    // enable colour transitions only after first paint so the page doesn't fade in
    requestAnimationFrame(function () { root.classList.add('theme-ready'); });

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var finePointer = window.matchMedia('(pointer: fine)').matches;

    /* ---------- scroll reveal ---------- */
    var revealEls = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && !reduceMotion) {
        var revealObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        revealEls.forEach(function (el) { revealObserver.observe(el); });
    } else {
        revealEls.forEach(function (el) { el.classList.add('in'); });
    }

    /* ---------- animated counters ---------- */
    var counters = document.querySelectorAll('[data-count]');
    function runCounter(el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        var suffix = el.getAttribute('data-suffix') || '';
        if (reduceMotion) { el.textContent = target + suffix; return; }
        var start = null;
        function step(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / 1400, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * eased) + suffix;
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }
    if ('IntersectionObserver' in window) {
        var counterObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    runCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        counters.forEach(function (el) { counterObserver.observe(el); });
    } else {
        counters.forEach(runCounter);
    }

    /* ---------- tilt cards with glare ---------- */
    if (finePointer && !reduceMotion) {
        document.querySelectorAll('.tilt-card').forEach(function (card) {
            var max = card.classList.contains('price-card') ? 6 : 10;
            card.addEventListener('mousemove', function (e) {
                var r = card.getBoundingClientRect();
                var x = (e.clientX - r.left) / r.width;
                var y = (e.clientY - r.top) / r.height;
                card.style.transform = 'perspective(1000px) rotateX(' + ((0.5 - y) * max) + 'deg) rotateY(' + ((x - 0.5) * max) + 'deg) translateY(-6px)';
                card.style.setProperty('--gx', (x * 100) + '%');
                card.style.setProperty('--gy', (y * 100) + '%');
            });
            card.addEventListener('mouseleave', function () {
                card.style.transform = '';
            });
        });
    }

    /* ---------- portal tabs ---------- */
    var tabs = document.querySelectorAll('.portal-tab');
    var views = document.querySelectorAll('.portal-view');
    var device = document.getElementById('portalDevice');
    var portalUrl = document.getElementById('portalUrl');
    var portalNames = ['admin dashboard', 'teacher portal', 'parent portal', 'student portal'];
    var portalIndex = 0, portalTimer = null;

    function showPortal(i) {
        portalIndex = i;
        tabs.forEach(function (t, n) {
            t.classList.toggle('active', n === i);
            t.setAttribute('aria-selected', n === i ? 'true' : 'false');
        });
        views.forEach(function (v, n) { v.classList.toggle('active', n === i); });
        if (portalUrl) portalUrl.textContent = portalNames[i];
        if (device && !reduceMotion) {
            device.classList.remove('flip');
            void device.offsetWidth;
            device.classList.add('flip');
        }
    }
    function autoPortal() {
        clearInterval(portalTimer);
        if (reduceMotion) return;
        portalTimer = setInterval(function () { showPortal((portalIndex + 1) % tabs.length); }, 5000);
    }
    tabs.forEach(function (tab, i) {
        tab.addEventListener('click', function () { showPortal(i); autoPortal(); });
    });
    if (tabs.length) autoPortal();

    // device follows the cursor slightly
    var deviceWrap = document.querySelector('.device-wrap');
    if (deviceWrap && device && finePointer && !reduceMotion) {
        deviceWrap.addEventListener('mousemove', function (e) {
            var r = deviceWrap.getBoundingClientRect();
            var x = (e.clientX - r.left) / r.width - 0.5;
            var y = (e.clientY - r.top) / r.height - 0.5;
            device.style.transform = 'rotateY(' + (-14 + x * 16) + 'deg) rotateX(' + (8 - y * 12) + 'deg)';
        });
        deviceWrap.addEventListener('mouseleave', function () { device.style.transform = ''; });
    }

    /* ---------- module orbit system ----------
       Modules revolve around the core on three tilted, counter-rotating rings.
       Each planet's screen position is projected from its angle on the ring, and
       its depth (front/back of the ellipse) drives scale, opacity and z-index so
       it passes in front of and behind the core. The cursor tilts the whole plane. */
    var solar = document.getElementById('solar');
    if (solar) {
        var planets = Array.prototype.slice.call(solar.querySelectorAll('.planet'));
        var rings = solar.querySelectorAll('.solar-ring');
        var RADII = [0.25, 0.365, 0.475];   // fraction of the stage size
        var SPEEDS = [0.2, -0.13, 0.085];    // radians per second, alternating direction
        var BASE_TILT = 0.5;                 // ellipse squash (cos of the view inclination)
        var BASE_ROT = -0.2;                 // plane rotation in radians

        var perRing = [0, 0, 0], ringCounts = [0, 0, 0];
        planets.forEach(function (p) { ringCounts[+p.getAttribute('data-ring')]++; });
        var bodies = planets.map(function (p) {
            var r = +p.getAttribute('data-ring');
            // spread evenly with a per-ring phase offset so rings don't line up
            var theta = (perRing[r]++ / ringCounts[r]) * Math.PI * 2 + r * 0.7;
            return { el: p, ring: r, theta: theta };
        });

        var size = 0, stageH = 0, tilt = BASE_TILT, rot = BASE_ROT;
        var tiltTarget = { t: BASE_TILT, r: BASE_ROT };
        var speedFactor = reduceMotion ? 0 : 1, speedTarget = speedFactor;
        var activeIndex = -1, hovering = false;

        var detail = document.getElementById('solarDetail');
        var sdIcon = document.getElementById('sdIcon');
        var sdName = document.getElementById('sdName');
        var sdDesc = document.getElementById('sdDesc');
        var sdCount = document.getElementById('sdCount');
        var total = planets.length;
        function pad(n) { return (n < 10 ? '0' : '') + n; }

        function setActive(i) {
            if (i === activeIndex) return;
            if (activeIndex > -1) planets[activeIndex].classList.remove('active');
            activeIndex = i;
            var p = planets[i];
            p.classList.add('active');
            sdIcon.className = 'fas ' + p.getAttribute('data-icon');
            sdName.textContent = p.getAttribute('data-name');
            sdDesc.textContent = p.getAttribute('data-desc');
            sdCount.textContent = pad(i + 1) + ' / ' + pad(total);
            detail.classList.remove('swap');
            void detail.offsetWidth;
            detail.classList.add('swap');
        }

        // radii scale with the stage width; the stage is wider than tall because the rings are flattened
        function measure() { size = solar.clientWidth; stageH = solar.clientHeight; }

        function place() {
            var cx = size / 2, cy = stageH / 2;
            var cosR = Math.cos(rot), sinR = Math.sin(rot);
            for (var i = 0; i < rings.length; i++) {
                var rr = RADII[i] * size;
                rings[i].style.width = (rr * 2) + 'px';
                rings[i].style.height = (rr * 2 * tilt) + 'px';
                rings[i].style.transform = 'translate(-50%,-50%) rotate(' + rot + 'rad)';
            }
            for (var b = 0; b < bodies.length; b++) {
                var o = bodies[b];
                var R = RADII[o.ring] * size;
                var ex = R * Math.cos(o.theta), ey = R * tilt * Math.sin(o.theta);
                var x = cx + ex * cosR - ey * sinR;
                var y = cy + ex * sinR + ey * cosR;
                var depth = (Math.sin(o.theta) + 1) / 2;      // 0 = far side, 1 = near side
                var scale = 0.62 + depth * 0.5;
                o.el.style.transform = 'translate3d(' + x.toFixed(1) + 'px,' + y.toFixed(1) + 'px,0) scale(' + scale.toFixed(3) + ')';
                o.el.style.opacity = (0.35 + depth * 0.65).toFixed(3);
                // the core sits at z-index 50: far-side planets go behind it, near-side in front
                o.el.style.zIndex = o.el.classList.contains('active') ? 120 : Math.round(10 + depth * 80);
            }
        }

        var last = 0, solarVisible = true, solarRunning = false;
        function loop(ts) {
            var dt = last ? Math.min((ts - last) / 1000, 0.05) : 0;
            last = ts;
            speedFactor += (speedTarget - speedFactor) * 0.06;
            tilt += (tiltTarget.t - tilt) * 0.06;
            rot += (tiltTarget.r - rot) * 0.06;
            for (var b = 0; b < bodies.length; b++) {
                bodies[b].theta += SPEEDS[bodies[b].ring] * speedFactor * dt;
            }
            place();
            if (solarVisible) requestAnimationFrame(loop);
            else { solarRunning = false; last = 0; }
        }
        function startLoop() {
            if (!solarRunning) { solarRunning = true; requestAnimationFrame(loop); }
        }

        planets.forEach(function (p, i) {
            p.addEventListener('mouseenter', function () { hovering = true; speedTarget = 0; setActive(i); });
            p.addEventListener('mouseleave', function () { hovering = false; speedTarget = reduceMotion ? 0 : 1; });
            p.addEventListener('focus', function () { setActive(i); });
            p.addEventListener('click', function () { setActive(i); });
        });

        // cursor tilts the orbital plane for a sense of depth
        if (finePointer && !reduceMotion) {
            solar.addEventListener('mousemove', function (e) {
                var r = solar.getBoundingClientRect();
                var mx = (e.clientX - r.left) / r.width - 0.5;
                var my = (e.clientY - r.top) / r.height - 0.5;
                tiltTarget.t = BASE_TILT + my * 0.22;
                tiltTarget.r = BASE_ROT + mx * 0.25;
            });
            solar.addEventListener('mouseleave', function () {
                tiltTarget.t = BASE_TILT;
                tiltTarget.r = BASE_ROT;
            });
        }

        // auto-tour: highlight the next module every few seconds unless the visitor is exploring
        setActive(0);
        if (!reduceMotion) {
            setInterval(function () {
                if (!hovering && solarVisible) setActive((activeIndex + 1) % total);
            }, 3200);
        }

        measure();
        place();
        window.addEventListener('resize', function () { measure(); place(); });

        if (reduceMotion) {
            // static layout; nothing to animate
        } else if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                solarVisible = entries[0].isIntersecting;
                if (solarVisible) startLoop();
            }).observe(solar);
        } else {
            startLoop();
        }
    }

    /* ---------- hero glass cards parallax ---------- */
    var hero = document.getElementById('home');
    var floatCards = document.querySelectorAll('.glass-card[data-depth]');
    var mouse = { x: 0, y: 0 };
    if (hero && finePointer && !reduceMotion) {
        hero.addEventListener('mousemove', function (e) {
            var r = hero.getBoundingClientRect();
            mouse.x = (e.clientX - r.left) / r.width - 0.5;
            mouse.y = (e.clientY - r.top) / r.height - 0.5;
            floatCards.forEach(function (c) {
                var d = parseFloat(c.getAttribute('data-depth'));
                c.style.transform = 'translate3d(' + (mouse.x * -40 * d) + 'px,' + (mouse.y * -30 * d) + 'px,0) rotateY(' + (mouse.x * 10) + 'deg) rotateX(' + (-mouse.y * 10) + 'deg)';
            });
        });
    }

    /* ---------- hero WebGL scene ---------- */
    var canvas = document.getElementById('heroCanvas');
    if (canvas && window.THREE && hasWebGL()) {
        hero.classList.add('has-webgl');
        initHeroScene(canvas);
    }

    function hasWebGL() {
        try {
            var c = document.createElement('canvas');
            return !!(window.WebGLRenderingContext && (c.getContext('webgl') || c.getContext('experimental-webgl')));
        } catch (e) {
            return false;
        }
    }

    function initHeroScene(canvas) {
        var THREE = window.THREE;
        var renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));

        var scene = new THREE.Scene();
        var camera = new THREE.PerspectiveCamera(45, 1, 0.1, 200);
        camera.position.set(0, 0, 14);

        var group = new THREE.Group();
        scene.add(group);

        // soft round sprite so points render as glowing dots instead of squares
        var dotCanvas = document.createElement('canvas');
        dotCanvas.width = dotCanvas.height = 64;
        var dctx = dotCanvas.getContext('2d');
        var dgrad = dctx.createRadialGradient(32, 32, 0, 32, 32, 32);
        dgrad.addColorStop(0, 'rgba(255,255,255,1)');
        dgrad.addColorStop(0.35, 'rgba(255,255,255,.8)');
        dgrad.addColorStop(1, 'rgba(255,255,255,0)');
        dctx.fillStyle = dgrad;
        dctx.fillRect(0, 0, 64, 64);
        var dotTexture = new THREE.CanvasTexture(dotCanvas);

        // lights
        scene.add(new THREE.AmbientLight(0x6f6aff, 0.6));
        var key = new THREE.PointLight(0x7c5cff, 2.2, 60);
        key.position.set(6, 6, 10);
        scene.add(key);
        var rim = new THREE.PointLight(0x22d3ee, 2, 60);
        rim.position.set(-8, -4, 6);
        scene.add(rim);

        // core: faceted glass crystal with a wireframe shell and glowing heart
        var coreGeo = new THREE.IcosahedronGeometry(2.2, 1);
        var core = new THREE.Mesh(coreGeo, new THREE.MeshStandardMaterial({
            color: 0x2b2d7a, metalness: 0.55, roughness: 0.38, flatShading: true,
            emissive: 0x1b1b5e, emissiveIntensity: 0.6, transparent: true, opacity: 0.92
        }));
        group.add(core);

        var shell = new THREE.Mesh(new THREE.IcosahedronGeometry(2.9, 1), new THREE.MeshBasicMaterial({
            color: 0x8b7bff, wireframe: true, transparent: true, opacity: 0.22
        }));
        group.add(shell);

        var heart = new THREE.Mesh(new THREE.SphereGeometry(1.2, 32, 32), new THREE.MeshBasicMaterial({
            color: 0x4f8cff, transparent: true, opacity: 0.35, blending: THREE.AdditiveBlending
        }));
        group.add(heart);

        // shell vertices as glowing nodes
        var nodeGeo = new THREE.BufferGeometry();
        nodeGeo.setAttribute('position', shell.geometry.getAttribute('position').clone());
        var nodes = new THREE.Points(nodeGeo, new THREE.PointsMaterial({
            color: 0x9ff3ff, size: 0.22, map: dotTexture, depthWrite: false, transparent: true, opacity: 0.95, blending: THREE.AdditiveBlending
        }));
        shell.add(nodes);

        // orbit rings with satellites: one per portal
        var satColors = [0x34d399, 0xa78bfa, 0xfbbf24, 0x22d3ee];
        var orbits = [];
        satColors.forEach(function (color, i) {
            var r = 4 + i * 0.75;
            var pivot = new THREE.Group();
            pivot.rotation.x = Math.PI / 2 + (i - 1.5) * 0.35;
            pivot.rotation.y = i * 0.6;
            group.add(pivot);

            var ringMesh = new THREE.Mesh(new THREE.TorusGeometry(r, 0.012, 8, 160), new THREE.MeshBasicMaterial({
                color: 0x8b7bff, transparent: true, opacity: 0.28
            }));
            pivot.add(ringMesh);

            var sat = new THREE.Mesh(new THREE.SphereGeometry(0.17, 20, 20), new THREE.MeshBasicMaterial({ color: color }));
            var glow = new THREE.Mesh(new THREE.SphereGeometry(0.42, 20, 20), new THREE.MeshBasicMaterial({
                color: color, transparent: true, opacity: 0.22, blending: THREE.AdditiveBlending
            }));
            sat.add(glow);
            pivot.add(sat);
            orbits.push({ sat: sat, r: r, speed: 0.35 + i * 0.12, phase: i * 1.7 });
        });

        // starfield
        var starCount = window.innerWidth < 768 ? 600 : 1400;
        var starPos = new Float32Array(starCount * 3);
        for (var s = 0; s < starCount; s++) {
            var rr = 12 + Math.random() * 40;
            var th = Math.random() * Math.PI * 2;
            var ph = Math.acos(2 * Math.random() - 1);
            starPos[s * 3] = rr * Math.sin(ph) * Math.cos(th);
            starPos[s * 3 + 1] = rr * Math.sin(ph) * Math.sin(th);
            starPos[s * 3 + 2] = rr * Math.cos(ph) - 10;
        }
        var starGeo = new THREE.BufferGeometry();
        starGeo.setAttribute('position', new THREE.BufferAttribute(starPos, 3));
        var stars = new THREE.Points(starGeo, new THREE.PointsMaterial({
            color: 0xb9c2ff, size: 0.14, map: dotTexture, depthWrite: false, transparent: true, opacity: 0.75
        }));
        scene.add(stars);

        // layout: core sits on the right on desktop, centred behind the copy on mobile
        var isWide = true;
        function resize() {
            var w = canvas.clientWidth, h = canvas.clientHeight;
            renderer.setSize(w, h, false);
            camera.aspect = w / h;
            camera.updateProjectionMatrix();
            isWide = w >= 992;
            // on narrow screens drop the core below the headline so it never sits behind body copy
            group.position.x = isWide ? 4.6 : 0;
            group.position.y = isWide ? 0 : -4.2;
            group.scale.setScalar(isWide ? 1 : 0.62);
            core.material.opacity = isWide ? 0.92 : 0.55;
            shell.material.opacity = isWide ? 0.22 : 0.12;
        }
        resize();
        window.addEventListener('resize', resize);

        var target = { x: 0, y: 0 }, current = { x: 0, y: 0 };
        if (finePointer) {
            window.addEventListener('mousemove', function (e) {
                target.x = e.clientX / window.innerWidth - 0.5;
                target.y = e.clientY / window.innerHeight - 0.5;
            });
        }

        var clock = new THREE.Clock();
        var visible = true, running = false;

        function frame() {
            var t = clock.getElapsedTime();
            current.x += (target.x - current.x) * 0.05;
            current.y += (target.y - current.y) * 0.05;

            var scrollP = Math.min(window.scrollY / window.innerHeight, 1);

            core.rotation.y = t * 0.25;
            core.rotation.x = t * 0.12;
            shell.rotation.y = -t * 0.12;
            shell.rotation.z = t * 0.06;
            heart.scale.setScalar(1 + Math.sin(t * 2) * 0.08);

            orbits.forEach(function (o) {
                var a = t * o.speed + o.phase;
                o.sat.position.set(Math.cos(a) * o.r, Math.sin(a) * o.r, 0);
            });

            group.rotation.y = current.x * 0.6 + scrollP * 1.2;
            group.rotation.x = current.y * 0.4;
            group.position.z = -scrollP * 4;
            stars.rotation.y = t * 0.01 + current.x * 0.1;
            stars.rotation.x = current.y * 0.05;

            renderer.render(scene, camera);
            if (visible && !reduceMotion) requestAnimationFrame(frame);
            else running = false;
        }
        function start() {
            if (!running) { running = true; requestAnimationFrame(frame); }
        }

        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                visible = entries[0].isIntersecting;
                if (visible) start();
            }).observe(canvas);
        }
        start();
    }

    /* ---------- CTA panel: drifting connected particles (2D) ---------- */
    var cta = document.getElementById('ctaCanvas');
    if (cta && cta.getContext && !reduceMotion) {
        var ctx = cta.getContext('2d');
        var pts = [], ctaVisible = false, ctaRunning = false, cw = 0, ch = 0;
        function sizeCta() {
            var dpr = Math.min(window.devicePixelRatio || 1, 2);
            cw = cta.clientWidth; ch = cta.clientHeight;
            cta.width = cw * dpr; cta.height = ch * dpr;
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            var n = Math.round(cw * ch / 9000);
            pts = [];
            for (var i = 0; i < n; i++) {
                pts.push({ x: Math.random() * cw, y: Math.random() * ch, vx: (Math.random() - 0.5) * 0.4, vy: (Math.random() - 0.5) * 0.4 });
            }
        }
        function drawCta() {
            ctx.clearRect(0, 0, cw, ch);
            for (var i = 0; i < pts.length; i++) {
                var p = pts[i];
                p.x += p.vx; p.y += p.vy;
                if (p.x < 0 || p.x > cw) p.vx *= -1;
                if (p.y < 0 || p.y > ch) p.vy *= -1;
                for (var j = i + 1; j < pts.length; j++) {
                    var q = pts[j], dx = p.x - q.x, dy = p.y - q.y, d = dx * dx + dy * dy;
                    if (d < 12000) {
                        ctx.strokeStyle = 'rgba(255,255,255,' + (0.18 * (1 - d / 12000)) + ')';
                        ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(q.x, q.y); ctx.stroke();
                    }
                }
                ctx.fillStyle = 'rgba(255,255,255,.7)';
                ctx.beginPath(); ctx.arc(p.x, p.y, 1.6, 0, Math.PI * 2); ctx.fill();
            }
            if (ctaVisible) requestAnimationFrame(drawCta);
            else ctaRunning = false;
        }
        sizeCta();
        window.addEventListener('resize', sizeCta);
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                ctaVisible = entries[0].isIntersecting;
                if (ctaVisible && !ctaRunning) { ctaRunning = true; requestAnimationFrame(drawCta); }
            }).observe(cta);
        }
    }
})();
