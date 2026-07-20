/* blog-_sidebar
   Migrado desde el <script> embebido de views/blog/_sidebar.php */
/* Compartido: se activa por existencia de sus elementos */
(function () {
    (function () {
        const sidebar     = document.getElementById('adminSidebar');
        const overlay     = document.getElementById('sidebarOverlay');
        const collapseBtn = document.getElementById('sidebarCollapseBtn');
        const LS_KEY      = 'bilbao_sidebar_collapsed';

        function isDesktop() { return window.innerWidth > 1024; }

        /* ── Desktop: colapsar/expandir ──
           .admin-main se resuelve lazy porque el sidebar se incluye
           ANTES del div.admin-main en el DOM.                         */
        function applyCollapsed(collapsed) {
            sidebar.classList.toggle('admin-sidebar--collapsed', collapsed);
            const mainEl = document.querySelector('.admin-main');
            if (mainEl) mainEl.classList.toggle('sidebar-collapsed', collapsed);
        }

        /* Inicializar sidebar inmediatamente (antes de que main exista) */
        if (isDesktop() && localStorage.getItem(LS_KEY) === '1') {
            sidebar.classList.add('admin-sidebar--collapsed');
            /* Sincronizar main cuando el DOM esté listo */
            document.addEventListener('DOMContentLoaded', function () {
                const mainEl = document.querySelector('.admin-main');
                if (mainEl) mainEl.classList.add('sidebar-collapsed');
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

        /* ── Mobile: abrir/cerrar con overlay ── */
        function openMobile() {
            sidebar.classList.add('is-open');
            overlay.classList.add('is-active');
            document.body.style.overflow = 'hidden';
        }

        function closeMobile() {
            sidebar.classList.remove('is-open');
            overlay.classList.remove('is-active');
            document.body.style.overflow = '';
        }

        overlay.addEventListener('click', closeMobile);

        /* Inyectar botón hamburger — en DOMContentLoaded porque
           .admin-topbar__left aparece DESPUÉS del sidebar en el HTML */
        document.addEventListener('DOMContentLoaded', function () {
            const topbarLeft = document.querySelector('.admin-topbar__left');
            if (topbarLeft) {
                const btn = document.createElement('button');
                btn.className = 'admin-topbar__menu-btn';
                btn.setAttribute('aria-label', 'Abrir menú');
                btn.innerHTML = '<i class="fa-solid fa-bars"></i>';
                btn.addEventListener('click', openMobile);
                topbarLeft.insertBefore(btn, topbarLeft.firstChild);

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
            if (window.innerWidth > 1024) closeMobile();
        });
    })();
})();
