function toggleNextElement(trigger) {
    const content = trigger?.nextElementSibling;
    if (!content) return;

    const expanded = trigger.getAttribute('aria-expanded') === 'true';
    trigger.setAttribute('aria-expanded', !expanded);
    content.style.display = expanded ? 'none' : 'block';
}

document.querySelectorAll('.nav-accordion-trigger').forEach(trigger => {
    trigger.addEventListener('click', () => toggleNextElement(trigger));
});

document.querySelectorAll('.footer-col-title').forEach(toggle => {
    toggle.addEventListener('click', () => {
        if (window.innerWidth < 1024) toggleNextElement(toggle);
    });
});

function handleFooterResize() {
    if (window.innerWidth >= 1024) {
        document.querySelectorAll('.footer-links').forEach(el => el.style.display = 'block');
        document.querySelectorAll('.footer-col-title .chevron').forEach(el => el.style.transform = '');
    } else {
        document.querySelectorAll('.footer-links').forEach(el => el.style.display = 'none');
    }
}

window.addEventListener('resize', handleFooterResize);
handleFooterResize();
