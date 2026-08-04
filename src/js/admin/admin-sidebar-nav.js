/**
 * Acordeón de módulos del sidebar.
 *
 * Componente compartido: sin guarda de página, se activa por existencia de
 * `[data-nav-toggle]` (mismo criterio que el resto de módulos de _sidebar, _form…).
 *
 * Patrón accesible ya usado en el panel (ver blog-autores-index.js): `aria-expanded`
 * sobre el botón y `hidden` sobre el panel, sin cálculos de max-height. El servidor
 * ya deja abierto el módulo activo, así que aquí solo se gestiona la interacción.
 */
(function () {
    'use strict';

    const toggles = document.querySelectorAll('[data-nav-toggle]');
    if (!toggles.length) return;

    const sidebar = document.getElementById('adminSidebar');
    const LS_KEY  = 'bilbao_sidebar_collapsed';

    function estaPlegado() {
        return !!sidebar && sidebar.classList.contains('admin-sidebar--collapsed');
    }

    /** Devuelve al estado expandido y lo persiste, para que la navegación sea usable. */
    function expandirSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('admin-sidebar--collapsed');
        document.documentElement.classList.remove('sidebar-boot-collapsed');
        const main = document.querySelector('.admin-main');
        if (main) main.classList.remove('sidebar-collapsed');
        try { localStorage.setItem(LS_KEY, '0'); } catch (e) { /* modo privado */ }
    }

    function abrir(toggle, abierto) {
        const sub = toggle.parentElement.querySelector('[data-nav-sub]');
        toggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        if (sub) sub.hidden = !abierto;
    }

    toggles.forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            // Plegado (72px) no hay sitio para el submenú: se expande primero, y el
            // módulo pulsado queda abierto. Así el gesto nunca acaba en nada visible.
            if (estaPlegado()) {
                expandirSidebar();
                toggles.forEach(function (t) { abrir(t, t === toggle); });
                return;
            }

            const abierto = toggle.getAttribute('aria-expanded') === 'true';
            // Uno abierto a la vez: la lista completa de módulos no cabe desplegada
            // y el objetivo es justamente que se vea dónde estás.
            toggles.forEach(function (t) { if (t !== toggle) abrir(t, false); });
            abrir(toggle, !abierto);
        });
    });
})();
