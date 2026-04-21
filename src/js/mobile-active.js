document.addEventListener('DOMContentLoaded', () => {
    if (window.innerWidth > 1024) return;

    const selectors = [
        '.photo-card', '.level-card', '.bento-card', '.news-item',
        '.carousel-slide', '.mascot-img', '.mascot-intro', '.mascot-happy', '.mascot-cta',
        '.organic-img-wrapper', '.social-explore-card', '.comp-card',
        '.step-item', '.explore-card', '.manifesto-img',
    ];

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) entry.target.classList.add('mobile-active');
        });
    }, { rootMargin: '-15% 0px -15% 0px', threshold: 0.2 });

    document.querySelectorAll(selectors.join(', ')).forEach(el => observer.observe(el));
});
