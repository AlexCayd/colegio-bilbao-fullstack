/* blog-_sidebar
   Migrado desde el <script> embebido de views/blog/_sidebar.php */
/* Compartido: se activa por existencia de sus elementos */
(function () {
    (function () {
        const sidebar     = document.getElementById('adminSidebar');
        const overlay     = document.getElementById('sidebarOverlay');
        const collapseBtn = document.getElementById('sidebarCollapseBtn');
        const LS_KEY      = 'bilbao_sidebar_collapsed';

        /* ⚠️ Guarda obligatoria. Los módulos de src/js/admin/ se concatenan en UN solo
           admin.min.js, así que una excepción aquí no se queda aquí: detiene la
           ejecución del archivo y se lleva por delante todos los módulos que vengan
           después. `sidebar.classList` sobre null bastaba para eso. */
        if (!sidebar || !overlay) return;

        /* .admin-main se resuelve lazy: el sidebar se incluye ANTES en el DOM. */
        function mainEl() { return document.querySelector('.admin-main'); }

        function isDesktop() { return window.innerWidth > 1024; }

        /* ── Desktop: colapsar/expandir ── */
        function applyCollapsed(collapsed) {
            sidebar.classList.toggle('admin-sidebar--collapsed', collapsed);
            const m = mainEl();
            if (m) m.classList.toggle('sidebar-collapsed', collapsed);
        }

        /* Inicializar sidebar inmediatamente (antes de que main exista) */
        if (isDesktop() && localStorage.getItem(LS_KEY) === '1') {
            sidebar.classList.add('admin-sidebar--collapsed');
            /* Sincronizar main cuando el DOM esté listo */
            document.addEventListener('DOMContentLoaded', function () {
                const m = mainEl();
                if (m) m.classList.add('sidebar-collapsed');
            });
        }

        if (collapseBtn) {
            collapseBtn.addEventListener('click', function () {
                if (!isDesktop()) return;
                const collapsed = !sidebar.classList.contains('admin-sidebar--collapsed');
                applyCollapsed(collapsed);
                localStorage.setItem(LS_KEY, collapsed ? '1' : '0');
            });
        }

        /* ── Mobile: abrir/cerrar con overlay ──
           En móvil el cajón es la ÚNICA navegación del panel, así que tiene que
           comportarse como un diálogo: anunciar su estado, atrapar el foco y cerrarse
           con Escape. Antes era un `translateX` y nada más. */
        let menuBtn = null;

        function openMobile() {
            sidebar.classList.add('is-open');
            overlay.classList.add('is-active');
            document.body.style.overflow = 'hidden';
            if (menuBtn) menuBtn.setAttribute('aria-expanded', 'true');

            /* `inert` sobre el contenido resuelve de una vez la trampa de foco y la
               accesibilidad: el lector de pantalla tampoco se va detrás del cajón.
               Recorrer los focusables a mano haría lo mismo peor. */
            const m = mainEl();
            if (m && 'inert' in HTMLElement.prototype) m.inert = true;

            const primero = sidebar.querySelector('a, button');
            if (primero) primero.focus();
        }

        function closeMobile(devolverFoco) {
            sidebar.classList.remove('is-open');
            overlay.classList.remove('is-active');
            document.body.style.overflow = '';
            if (menuBtn) menuBtn.setAttribute('aria-expanded', 'false');
            const m = mainEl();
            if (m && 'inert' in HTMLElement.prototype) m.inert = false;
            /* Solo al cerrar de forma deliberada (Escape, overlay). Con el resize no:
               ahí el usuario no estaba mirando el cajón. */
            if (devolverFoco && menuBtn) menuBtn.focus();
        }

        overlay.addEventListener('click', function () { closeMobile(true); });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('is-open')) closeMobile(true);
        });

        /* Inyectar botón hamburger — en DOMContentLoaded porque
           .admin-topbar__left aparece DESPUÉS del sidebar en el HTML */
        document.addEventListener('DOMContentLoaded', function () {
            const topbarLeft = document.querySelector('.admin-topbar__left');
            if (topbarLeft) {
                const btn = document.createElement('button');
                btn.className = 'admin-topbar__menu-btn';
                /* `type` explícito: dentro de un <form> un <button> sin tipo envía. */
                btn.type = 'button';
                btn.setAttribute('aria-label', 'Abrir menú');
                btn.setAttribute('aria-controls', 'adminSidebar');
                btn.setAttribute('aria-expanded', 'false');
                btn.innerHTML = '<i class="fa-solid fa-bars"></i>';
                btn.addEventListener('click', function () {
                    if (sidebar.classList.contains('is-open')) closeMobile(true);
                    else openMobile();
                });
                topbarLeft.insertBefore(btn, topbarLeft.firstChild);
                menuBtn = btn;

                /* Mover el breadcrumb al topbar y ocultar el título por defecto */
                const crumbs = document.getElementById('adminCrumbsSrc');
                if (crumbs) {
                    crumbs.hidden = false;
                    topbarLeft.appendChild(crumbs);
                    topbarLeft.classList.add('has-crumbs');
                }
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 1024) closeMobile(false);
        });
    })();
})();
