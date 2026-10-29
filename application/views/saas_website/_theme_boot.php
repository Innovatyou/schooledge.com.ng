    <!-- Theme: runs before first paint so dark-mode visitors never see a light flash.
         A saved choice wins; otherwise follow the operating system setting. -->
    <script type="text/javascript">
        (function () {
            var t;
            try { t = localStorage.getItem('skoolos-theme'); } catch (e) {}
            if (t !== 'light' && t !== 'dark') {
                t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
