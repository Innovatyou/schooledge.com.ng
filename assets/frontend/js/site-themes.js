/* =========================================================
   School website templates: interactions
   Scroll reveal, count-up stats, 3D tilt cards and a hero scene that
   leans toward the cursor. Honours prefers-reduced-motion.
   ========================================================= */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var finePointer = window.matchMedia('(pointer: fine)').matches;

    /* ---------- reveal on scroll ---------- */
    var reveals = document.querySelectorAll('.st-reveal');
    if ('IntersectionObserver' in window && !reduceMotion) {
        var revealObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        reveals.forEach(function (el) { revealObserver.observe(el); });
    } else {
        reveals.forEach(function (el) { el.classList.add('in'); });
    }

    /* ---------- count-up numbers ---------- */
    function countUp(el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        if (reduceMotion || target === 0) { el.textContent = target.toLocaleString(); return; }
        var start = null, done = false;
        function step(ts) {
            if (done) return;
            if (!start) start = ts;
            var p = Math.min((ts - start) / 1500, 1);
            el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString();
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
        // animation frames are paused in background tabs; always land on the real number
        setTimeout(function () { done = true; el.textContent = target.toLocaleString(); }, 1700);
    }
    var counters = document.querySelectorAll('.st-count');
    if ('IntersectionObserver' in window) {
        var countObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    countUp(entry.target);
                    countObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.4 });
        counters.forEach(function (el) { countObserver.observe(el); });
    } else {
        counters.forEach(countUp);
    }

    if (!finePointer || reduceMotion) return;

    /* ---------- 3D tilt cards ---------- */
    document.querySelectorAll('.st-tilt').forEach(function (card) {
        var max = card.classList.contains('st-frame') ? 6 : 9;
        card.addEventListener('mousemove', function (e) {
            var r = card.getBoundingClientRect();
            var x = (e.clientX - r.left) / r.width - 0.5;
            var y = (e.clientY - r.top) / r.height - 0.5;
            card.style.transform = 'perspective(900px) rotateX(' + (-y * max) + 'deg) rotateY(' + (x * max) + 'deg) translateY(-4px)';
        });
        card.addEventListener('mouseleave', function () { card.style.transform = ''; });
    });

    /* ---------- hero scene leans toward the cursor ---------- */
    var hero = document.querySelector('.st-hero');
    var scene = hero && hero.querySelector('.st-stack, .st-layers, .st-arches');
    if (hero && scene) {
        var target = { x: 0, y: 0 }, current = { x: 0, y: 0 }, running = false;
        function frame() {
            current.x += (target.x - current.x) * 0.08;
            current.y += (target.y - current.y) * 0.08;
            scene.style.setProperty('--ry', (current.x * 14).toFixed(2) + 'deg');
            scene.style.setProperty('--rx', (-current.y * 10).toFixed(2) + 'deg');
            if (Math.abs(target.x - current.x) > 0.001 || Math.abs(target.y - current.y) > 0.001) {
                requestAnimationFrame(frame);
            } else {
                running = false;
            }
        }
        function kick() {
            if (!running) { running = true; requestAnimationFrame(frame); }
        }
        hero.addEventListener('mousemove', function (e) {
            var r = hero.getBoundingClientRect();
            target.x = (e.clientX - r.left) / r.width - 0.5;
            target.y = (e.clientY - r.top) / r.height - 0.5;
            kick();
        });
        hero.addEventListener('mouseleave', function () { target.x = 0; target.y = 0; kick(); });
    }
})();
