/* SkoolOS 3D dashboards: count-up numbers, tilt, hero parallax, reveal,
   plus small Chart.js 2 / ECharts helpers shared by every role's dashboard. */
(function (window, document) {
    'use strict';

    var still = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var fine = window.matchMedia && window.matchMedia('(pointer: fine)').matches;
    var dark = function () { return document.documentElement.classList.contains('dark'); };

    // ---------- count-up ----------
    function countUp(el) {
        var end = parseFloat(el.getAttribute('data-count')) || 0;
        var dec = parseInt(el.getAttribute('data-decimals') || '0', 10);
        var pre = el.getAttribute('data-prefix') || '';
        var suf = el.getAttribute('data-suffix') || '';
        var fmt = function (v) { return pre + v.toLocaleString(undefined, { minimumFractionDigits: dec, maximumFractionDigits: dec }) + suf; };
        if (still || end === 0) { el.textContent = fmt(end); return; }
        var t0 = null, dur = 1300;
        var step = function (t) {
            if (t0 === null) t0 = t;
            var k = Math.min(1, (t - t0) / dur);
            el.textContent = fmt(end * (1 - Math.pow(1 - k, 3)));
            if (k < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
        // rAF can stall in background tabs; make sure the real value lands
        setTimeout(function () { el.textContent = fmt(end); }, dur + 200);
    }

    // ---------- reveal on scroll (also fires counters, rings and bars) ----------
    function arm(el) {
        el.classList.add('is-in');
        el.querySelectorAll('[data-count]').forEach(countUp);
        if (el.hasAttribute('data-count')) countUp(el);
        el.querySelectorAll('.d3-ring').forEach(function (r) { r.classList.add('is-in'); });
        el.querySelectorAll('.d3-kpi-bar i[data-p]').forEach(function (b) { b.style.setProperty('--p', b.getAttribute('data-p') + '%'); });
    }
    function reveal(root) {
        var items = root.querySelectorAll('.d3-reveal');
        if (!('IntersectionObserver' in window)) { items.forEach(arm); return; }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) { arm(e.target); io.unobserve(e.target); }
            });
        }, { threshold: 0.12 });
        items.forEach(function (el) { io.observe(el); });
        // never leave anything hidden if the observer is slow
        setTimeout(function () { items.forEach(function (el) { if (!el.classList.contains('is-in')) arm(el); }); }, 2500);
    }

    // ---------- tilt ----------
    function tilt(el) {
        var max = parseFloat(el.getAttribute('data-tilt')) || 8;
        el.addEventListener('mousemove', function (e) {
            var r = el.getBoundingClientRect();
            var x = (e.clientX - r.left) / r.width - 0.5;
            var y = (e.clientY - r.top) / r.height - 0.5;
            el.style.transform = 'perspective(800px) rotateY(' + (x * max).toFixed(2) + 'deg) rotateX(' + (-y * max).toFixed(2) + 'deg) translateY(-4px)';
        });
        el.addEventListener('mouseleave', function () { el.style.transform = ''; });
    }

    // ---------- hero parallax ----------
    function parallax(hero) {
        var raf = 0;
        hero.addEventListener('mousemove', function (e) {
            var r = hero.getBoundingClientRect();
            var x = (e.clientX - r.left) / r.width - 0.5;
            var y = (e.clientY - r.top) / r.height - 0.5;
            if (raf) return;
            raf = requestAnimationFrame(function () {
                raf = 0;
                hero.style.setProperty('--mx', x.toFixed(3));
                hero.style.setProperty('--my', y.toFixed(3));
            });
        });
        hero.addEventListener('mouseleave', function () {
            hero.style.setProperty('--mx', 0);
            hero.style.setProperty('--my', 0);
        });
    }

    // ---------- chart helpers ----------
    var D3 = {
        colors: { violet: '#7c5cff', blue: '#3b82f6', cyan: '#06b6d4', mint: '#10b981', amber: '#f59e0b', rose: '#f43f5e', slate: '#94a3b8' },
        palette: ['#7c5cff', '#06b6d4', '#f59e0b', '#10b981', '#f43f5e', '#3b82f6', '#a855f7', '#14b8a6', '#fb923c', '#64748b'],
        isDark: dark,
        // vertical fade of a colour for area / bar fills
        fill: function (canvas, hex, top, bottom) {
            var ctx = canvas.getContext('2d');
            var g = ctx.createLinearGradient(0, 0, 0, canvas.clientHeight || 300);
            g.addColorStop(0, D3.alpha(hex, top === undefined ? 0.45 : top));
            g.addColorStop(1, D3.alpha(hex, bottom === undefined ? 0.02 : bottom));
            return g;
        },
        alpha: function (hex, a) {
            var n = parseInt(hex.slice(1), 16);
            return 'rgba(' + ((n >> 16) & 255) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',' + a + ')';
        },
        // shared Chart.js 2 options: soft grid, rounded tooltips, no clutter
        options: function (extra) {
            var ink = dark() ? '#a4a9b8' : '#6b7290';
            var grid = dark() ? 'rgba(255,255,255,.06)' : 'rgba(20,24,60,.06)';
            var base = {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: still ? 0 : 1200, easing: 'easeOutQuart' },
                legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true, padding: 18, fontColor: ink, fontStyle: '600' } },
                tooltips: { mode: 'index', intersect: false, backgroundColor: '#11152e', titleFontStyle: '700', bodySpacing: 6, xPadding: 12, yPadding: 10, cornerRadius: 10, displayColors: true },
                hover: { mode: 'index', intersect: false },
                scales: {
                    xAxes: [{ gridLines: { display: false, drawBorder: false }, ticks: { fontColor: ink, padding: 8 } }],
                    yAxes: [{ gridLines: { color: grid, zeroLineColor: grid, drawBorder: false, borderDash: [4, 4] }, ticks: { fontColor: ink, padding: 10, beginAtZero: true, maxTicksLimit: 6 } }]
                }
            };
            return D3.merge(base, extra || {});
        },
        merge: function (a, b) {
            Object.keys(b).forEach(function (k) {
                if (b[k] && typeof b[k] === 'object' && !Array.isArray(b[k]) && a[k] && typeof a[k] === 'object' && !Array.isArray(a[k])) {
                    D3.merge(a[k], b[k]);
                } else {
                    a[k] = b[k];
                }
            });
            return a;
        },
        // ECharts doughnut with a total in the middle
        donut: function (el, data, opts) {
            if (!el || !window.echarts) return null;
            opts = opts || {};
            var chart = echarts.init(el);
            var total = data.reduce(function (s, d) { return s + (parseFloat(d.value) || 0); }, 0);
            var ink = dark() ? '#eceef4' : '#1d2440';
            var muted = dark() ? '#a4a9b8' : '#6b7290';
            chart.setOption({
                color: opts.colors || D3.palette,
                tooltip: { trigger: 'item', formatter: opts.formatter || '{b}: {c} ({d}%)', backgroundColor: '#11152e', borderWidth: 0, padding: [8, 12], textStyle: { color: '#fff' } },
                legend: { type: 'scroll', bottom: 0, itemWidth: 10, itemHeight: 10, icon: 'circle', textStyle: { color: muted }, pageTextStyle: { color: muted } },
                title: {
                    text: opts.centerText !== undefined ? opts.centerText : total.toLocaleString(),
                    subtext: opts.centerLabel || '',
                    left: 'center', top: opts.legend === false ? '38%' : '33%',
                    textStyle: { color: ink, fontSize: 24, fontWeight: 800, fontFamily: 'Sora, sans-serif' },
                    subtextStyle: { color: muted, fontSize: 12, fontWeight: 600 }
                },
                series: [{
                    type: 'pie',
                    radius: ['62%', '80%'],
                    center: ['50%', opts.legend === false ? '48%' : '43%'],
                    avoidLabelOverlap: false,
                    hoverOffset: 8,
                    itemStyle: { normal: { borderColor: dark() ? '#2a2c31' : '#fff', borderWidth: 3, shadowBlur: 14, shadowColor: 'rgba(20,24,60,.18)' } },
                    label: { normal: { show: false } },
                    labelLine: { normal: { show: false } },
                    data: data
                }]
            });
            if (opts.legend === false) chart.setOption({ legend: { show: false } });
            // ECharts keeps the size it was drawn at, so follow the card whenever it
            // changes: window resizes, the sidebar collapsing, the grid stacking on phones
            var fit = function () { chart.resize(); };
            if ('ResizeObserver' in window) {
                var raf = 0;
                new ResizeObserver(function () {
                    cancelAnimationFrame(raf);
                    raf = requestAnimationFrame(fit);
                }).observe(el);
            } else {
                window.addEventListener('resize', fit);
                document.querySelectorAll('.sidebar-toggle').forEach(function (b) {
                    b.addEventListener('click', function () { setTimeout(fit, 350); });
                });
            }
            return chart;
        }
    };
    window.D3Dash = D3;

    function init() {
        var root = document.querySelector('.d3');
        if (!root) return;
        reveal(root);
        if (!still && fine) {
            root.querySelectorAll('[data-tilt]').forEach(tilt);
            root.querySelectorAll('.d3-hero').forEach(parallax);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);
