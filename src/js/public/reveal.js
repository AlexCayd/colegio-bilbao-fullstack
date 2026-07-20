// Las animaciones .reveal y .fade-up las maneja GSAP — ver gsap-animations.js
// Este archivo solo ejecuta el contador numérico al entrar en viewport

function runCounter(el) {
    const target = +el.getAttribute('data-target');
    const suffix = el.getAttribute('data-suffix') || '';
    const plus = el.getAttribute('data-plus') ? '+' : '';

    const steps = 2000 / 20;
    const increment = target / steps;
    let current = 0;

    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            el.textContent = plus + target + suffix;
            clearInterval(timer);
        } else {
            el.textContent = plus + Math.ceil(current) + suffix;
        }
    }, 20);
}

document.addEventListener('DOMContentLoaded', () => {
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const counter = entry.target.querySelector('h3[data-target]');
            if (counter && !counter.classList.contains('counted')) {
                runCounter(counter);
                counter.classList.add('counted');
            }
        });
    }, { threshold: 0.2 });

    document.querySelectorAll('.stat-item').forEach(el => counterObserver.observe(el));
});
