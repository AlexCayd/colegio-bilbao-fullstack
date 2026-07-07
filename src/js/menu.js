const menuTrigger = document.querySelector('.menu-trigger');
const closeMenuBtn = document.getElementById('close-menu-btn');
const menuOverlay = document.getElementById('menu-overlay');

function toggleMenu(show) {
    if (!menuOverlay || !menuTrigger) return;

    menuOverlay.setAttribute('aria-hidden', !show);
    menuTrigger.setAttribute('aria-expanded', show);
    document.body.classList.toggle('no-scroll', show);

    if (show) closeMenuBtn?.focus();
    else menuTrigger.focus();
}

menuTrigger?.addEventListener('click', () => toggleMenu(true));
closeMenuBtn?.addEventListener('click', () => toggleMenu(false));
