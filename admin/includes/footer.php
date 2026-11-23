<?php
/**
 * Admin Footer
 */
$lang = getCurrentLang();
?>
            </main>
        </div>
    </div>
    
    <script src="<?php echo asset('js/main.js'); ?>"></script>
    <script>
    // Admin sidebar (off-canvas on phones/tablets)
    (function () {
        var sidebar = document.getElementById('admin-sidebar');
        var overlay = document.getElementById('admin-sidebar-overlay');
        var toggle  = document.getElementById('admin-menu-toggle');
        var closeBt = document.getElementById('admin-sidebar-close');
        if (!sidebar || !toggle) return;
        function setOpen(open) {
            sidebar.classList.toggle('active', open);
            if (overlay) overlay.classList.toggle('active', open);
            document.body.classList.toggle('admin-nav-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        toggle.addEventListener('click', function () { setOpen(!sidebar.classList.contains('active')); });
        if (closeBt) closeBt.addEventListener('click', function () { setOpen(false); });
        if (overlay) overlay.addEventListener('click', function () { setOpen(false); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
        window.matchMedia('(min-width: 1101px)').addEventListener('change', function (mq) { if (mq.matches) setOpen(false); });
        // keep the active item in view inside a long sidebar
        var active = sidebar.querySelector('.admin-sidebar-link.active');
        if (active && active.scrollIntoView) active.scrollIntoView({ block: 'nearest' });

        // Wrap every admin table in a horizontal scroller with a hint on small screens
        document.querySelectorAll('.admin-table').forEach(function (t) {
            var wrap = t.parentElement;
            if (!wrap.classList.contains('admin-table-wrap')) {
                if (wrap.style && wrap.style.overflowX === 'auto') { wrap.classList.add('admin-table-wrap'); }
                else { var w = document.createElement('div'); w.className = 'admin-table-wrap'; t.parentNode.insertBefore(w, t); w.appendChild(t); wrap = w; }
            }
            function upd() { wrap.classList.toggle('is-scrollable', wrap.scrollWidth > wrap.clientWidth + 2); }
            upd(); window.addEventListener('resize', upd);
        });
    })();
    </script>
</body>
</html>
