document.addEventListener('DOMContentLoaded', () => {
    // Si el CDN de GSAP falla, los elementos quedan visibles (sin bloqueo)
    if (typeof gsap === 'undefined') {
        document.querySelectorAll('.reveal, .fade-up').forEach(el => {
            el.style.opacity = '1';
            el.style.transform = 'none';
        });
        return;
    }

    gsap.registerPlugin(ScrollTrigger);

    // Scroll reveal — entrada desde abajo
    gsap.utils.toArray('.reveal').forEach(el => {
        gsap.from(el, {
            opacity: 0,
            y: 40,
            duration: 0.9,
            ease: 'expo.out',
            scrollTrigger: {
                trigger: el,
                start: 'top 88%',
                toggleActions: 'play none none none',
            }
        });
    });

    // Fade-up — con stagger escalonado por posición en fila
    gsap.utils.toArray('.fade-up').forEach((el, i) => {
        gsap.from(el, {
            opacity: 0,
            y: 24,
            duration: 0.7,
            delay: (i % 4) * 0.07,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: el,
                start: 'top 90%',
                toggleActions: 'play none none none',
            }
        });
    });

    // Parallax en hero de artículo
    const parallaxImg = document.getElementById('parallax-img');
    if (parallaxImg) {
        gsap.to(parallaxImg, {
            y: '20%',
            ease: 'none',
            scrollTrigger: {
                trigger: parallaxImg.closest('.article-hero'),
                start: 'top top',
                end: 'bottom top',
                scrub: true,
            }
        });
    }
});
