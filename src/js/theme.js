/* theme.js — standalone dark mode, se incluye en todas las páginas via bundle */
(function () {
    'use strict';

    /* Aplicar tema guardado antes del primer paint */
    var saved = localStorage.getItem('bilbao_theme');
    if (saved === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.body && document.body.setAttribute('data-theme', 'dark');
    }

    document.addEventListener('DOMContentLoaded', function () {
        var btn      = document.getElementById('theme-toggle');
        var icon     = document.getElementById('theme-icon');
        var sunIcon  = icon ? icon.querySelector('.icon-sun')  : null;
        var moonIcon = icon ? icon.querySelector('.icon-moon') : null;

        function applyThemeIcon(isDark) {
            if (sunIcon)  sunIcon.style.display  = isDark ? 'none' : '';
            if (moonIcon) moonIcon.style.display = isDark ? ''     : 'none';
        }

        /* Sincronizar ícono con tema actual */
        var current = document.documentElement.getAttribute('data-theme');
        applyThemeIcon(current === 'dark');

        if (btn) {
            btn.addEventListener('click', function () {
                var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                var next   = isDark ? 'light' : 'dark';

                document.documentElement.setAttribute('data-theme', next);
                document.body.setAttribute('data-theme', next);
                applyThemeIcon(next === 'dark');
                localStorage.setItem('bilbao_theme', next);

                /* Notificar a landing.js para recolorear el bosque */
                document.dispatchEvent(new CustomEvent('bilbao:theme', { detail: next }));
            });
        }

        /* ES | EN — traducción in-place via Google Translate Widget */
        function triggerGoogleTranslate() {
            var sel = document.querySelector('.goog-te-combo');
            if (sel) {
                sel.value = 'en';
                sel.dispatchEvent(new Event('change'));
                return;
            }
            setTimeout(function() {
                var sel2 = document.querySelector('.goog-te-combo');
                if (sel2) { sel2.value = 'en'; sel2.dispatchEvent(new Event('change')); }
            }, 800);
        }
        var langBtn = document.getElementById('lang-en-btn');
        if (langBtn) langBtn.addEventListener('click', function(e) { e.preventDefault(); triggerGoogleTranslate(); });
        document.querySelectorAll('.lang-en-overlay-btn').forEach(function(el) {
            el.addEventListener('click', function(e) { e.preventDefault(); triggerGoogleTranslate(); });
        });

        /* Sombra en el header al hacer scroll */
        var headerBar = document.querySelector('.header-bar');
        if (headerBar) {
            window.addEventListener('scroll', function () {
                headerBar.classList.toggle('scrolled', window.scrollY > 10);
            }, { passive: true });
        }

        /* Marcar enlace activo en la nav desktop */
        var navLinks = document.querySelectorAll('.header-nav__link');
        var path = window.location.pathname;
        navLinks.forEach(function (a) {
            var href = a.getAttribute('href');
            if (href && href !== '/' && path.startsWith(href)) {
                a.style.color = 'var(--col-bilbao)';
            }
        });
    });
})();
