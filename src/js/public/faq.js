// FAQ accordion — .faq-question / .faq-answer (max-height toggle)
document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', () => {
        const expanded = question.getAttribute('aria-expanded') === 'true';
        question.setAttribute('aria-expanded', String(!expanded));
        const answer = question.nextElementSibling;
        answer.style.maxHeight = expanded ? null : answer.scrollHeight + 'px';
    });
});

// FAQ sidebar active state on scroll (preguntas-frecuentes)
const faqNavLinks = document.querySelectorAll('.faq-nav-list a');
const mobileCatLinks = document.querySelectorAll('.mobile-cat-link');

if (faqNavLinks.length) {
    window.addEventListener('scroll', () => {
        const fromTop = window.scrollY + 180;
        faqNavLinks.forEach(link => {
            const section = document.querySelector(link.hash);
            if (!section) return;
            const inView = section.offsetTop <= fromTop && section.offsetTop + section.offsetHeight > fromTop;
            link.classList.toggle('active', inView);
            const mobileLink = document.querySelector(`.mobile-cat-link[href="${link.hash}"]`);
            if (mobileLink) mobileLink.classList.toggle('active', inView);
        });
    });
}
