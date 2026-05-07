// Sticky scroll — Aprendizaje Integral (switches visual panel on scroll)
document.addEventListener('DOMContentLoaded', () => {
    const sections = document.querySelectorAll('.content-section[data-target]');
    if (!sections.length) return;

    const allVisuals = document.querySelectorAll('.visual-image, .visual-mascot');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const target = entry.target.getAttribute('data-target');

            allVisuals.forEach(el => el.classList.remove('active'));

            const img    = document.getElementById('img-'    + target);
            const mascot = document.getElementById('mascot-' + target);
            if (img)    img.classList.add('active');
            if (mascot) mascot.classList.add('active');
        });
    }, { threshold: 0.5 });

    sections.forEach(section => observer.observe(section));
});
