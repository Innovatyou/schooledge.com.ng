/* SkoolOS guides: the "Guide" drawer, ticking off steps, and search on the Guide Center.
   Ticked steps are remembered per browser (localStorage) under skoolos-guide:<audience>:<key>. */
(function (window, document) {
    'use strict';

    var store = {
        get: function (k) { try { return JSON.parse(localStorage.getItem(k) || 'null'); } catch (e) { return null; } },
        set: function (k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
    };

    // ---------- tickable step lists (drawer + guide page share the same storage) ----------
    function wireSteps(list) {
        var key = list.getAttribute('data-guide-store');
        var items = list.querySelectorAll(':scope > li');
        var bar = document.querySelectorAll('[data-guide-progress="' + key + '"]');
        var done = store.get(key) || [];
        var paint = function () {
            items.forEach(function (li, i) {
                var on = done.indexOf(i) !== -1;
                li.classList.toggle('is-done', on);
                var btn = li.querySelector('.gd-num');
                if (btn) {
                    btn.setAttribute('aria-pressed', on ? 'true' : 'false');
                    btn.setAttribute('aria-label', 'Step ' + (i + 1) + (on ? ', done. Mark as not done' : '. Mark as done'));
                }
            });
            var pct = items.length ? Math.round(done.length / items.length * 100) : 0;
            bar.forEach(function (b) {
                var fill = b.querySelector('.gd-meter i');
                var label = b.querySelector('[data-label]');
                if (fill) fill.style.width = pct + '%';
                if (label) label.textContent = done.length + ' of ' + items.length + ' done';
            });
        };
        items.forEach(function (li, i) {
            var btn = li.querySelector('.gd-num');
            if (!btn) return;
            btn.addEventListener('click', function () {
                var at = done.indexOf(i);
                if (at === -1) done.push(i); else done.splice(at, 1);
                store.set(key, done);
                // keep any other copy of this list on the page in step
                document.querySelectorAll('[data-guide-store="' + key + '"]').forEach(function (other) {
                    if (other !== list && other._gdRepaint) other._gdRepaint(done);
                });
                paint();
            });
        });
        list._gdRepaint = function (d) { done = d.slice(); paint(); };
        paint();
    }

    // ---------- drawer ----------
    function wireDrawer() {
        var fab = document.querySelector('.gd-fab');
        var drawer = document.querySelector('.gd-drawer');
        if (!fab || !drawer) return;
        var root = document.documentElement;
        var scrim = document.querySelector('.gd-scrim');
        var seenKey = 'skoolos-guide-seen:' + fab.getAttribute('data-guide');
        var last = null;

        if (!store.get(seenKey)) fab.classList.add('is-new');

        var open = function () {
            last = document.activeElement;
            root.classList.add('gd-open-drawer');
            drawer.setAttribute('aria-hidden', 'false');
            fab.setAttribute('aria-expanded', 'true');
            fab.classList.remove('is-new');
            store.set(seenKey, 1);
            setTimeout(function () { drawer.querySelector('.gd-close').focus(); }, 50);
        };
        var close = function () {
            root.classList.remove('gd-open-drawer');
            drawer.setAttribute('aria-hidden', 'true');
            fab.setAttribute('aria-expanded', 'false');
            if (last && last.focus) last.focus();
        };
        fab.addEventListener('click', open);
        scrim.addEventListener('click', close);
        drawer.querySelector('.gd-close').addEventListener('click', close);
        document.addEventListener('keydown', function (e) {
            if (!root.classList.contains('gd-open-drawer')) return;
            if (e.key === 'Escape') { close(); return; }
            if (e.key !== 'Tab') return;
            // keep focus inside the drawer while it's open
            var f = drawer.querySelectorAll('a[href], button:not([disabled]), summary');
            if (!f.length) return;
            var first = f[0], end = f[f.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); end.focus(); }
            else if (!e.shiftKey && document.activeElement === end) { e.preventDefault(); first.focus(); }
        });
        // "?" opens the guide from anywhere that isn't a text field
        document.addEventListener('keydown', function (e) {
            var t = e.target.tagName;
            if (e.key === '?' && t !== 'INPUT' && t !== 'TEXTAREA' && t !== 'SELECT' && !e.target.isContentEditable) open();
        });
    }

    // ---------- Guide Center: search, filter, per-card progress ----------
    function wireCenter() {
        var center = document.querySelector('.gd-center');
        if (!center) return;
        var input = center.querySelector('#gdSearch');
        var chips = center.querySelectorAll('.gd-filter button');
        var cards = center.querySelectorAll('.gd-card');
        var groups = center.querySelectorAll('.gd-group');
        var empty = center.querySelector('.gd-empty');
        var group = 'all';

        cards.forEach(function (card) {
            var total = parseInt(card.getAttribute('data-steps'), 10) || 0;
            var done = (store.get(card.getAttribute('data-store')) || []).length;
            var fill = card.querySelector('.gd-meter i');
            if (fill && total) fill.style.width = Math.round(done / total * 100) + '%';
        });

        var apply = function () {
            var q = (input.value || '').trim().toLowerCase();
            var shown = 0;
            cards.forEach(function (card) {
                var hit = (group === 'all' || card.getAttribute('data-group') === group) &&
                    (!q || card.getAttribute('data-text').indexOf(q) !== -1);
                card.hidden = !hit;
                if (hit) shown++;
            });
            groups.forEach(function (g) { g.hidden = !g.querySelector('.gd-card:not([hidden])'); });
            empty.style.display = shown ? 'none' : 'block';
        };
        input.addEventListener('input', apply);
        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                group = chip.getAttribute('data-group');
                chips.forEach(function (c) { c.classList.toggle('is-on', c === chip); c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); });
                apply();
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === '/' && document.activeElement !== input && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                input.focus();
            }
        });
    }

    function init() {
        document.querySelectorAll('[data-guide-store]').forEach(wireSteps);
        wireDrawer();
        wireCenter();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})(window, document);
