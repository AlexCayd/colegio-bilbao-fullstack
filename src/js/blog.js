const allPosts = Array.from(document.querySelectorAll('.post-card')).map(card => ({
    element: card.cloneNode(true),
    categories: (card.getAttribute('data-category') || '').trim().split(/\s+/)
}));

const postsGrid = document.getElementById('postsGrid');
const emptyState = document.getElementById('emptyState');
const filterButtons = document.querySelectorAll('.filter-btn');
const categoryLinks = document.querySelectorAll('[data-filter-link]');

function renderPosts(category) {
    const filtered = category === 'all'
        ? allPosts
        : allPosts.filter(post => post.categories.includes(category));

    postsGrid.innerHTML = '';

    if (filtered.length === 0) {
        emptyState.classList.add('show');
        return;
    }

    emptyState.classList.remove('show');

    filtered.forEach(post => {
        const clone = post.element.cloneNode(true);
        clone.classList.remove('visible');
        postsGrid.appendChild(clone);

        requestAnimationFrame(() => clone.classList.add('visible'));

        clone.querySelectorAll('[data-filter-link]').forEach(link => {
            link.addEventListener('click', () => {
                const filter = link.dataset.filterLink;
                setActiveFilter(filter);
                renderPosts(filter);
            });
        });
    });
}

function setActiveFilter(category) {
    filterButtons.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.filter === category);
    });
}

filterButtons.forEach(button => {
    button.addEventListener('click', () => {
        const filter = button.dataset.filter;
        setActiveFilter(filter);
        renderPosts(filter);
    });
});

categoryLinks.forEach(link => {
    link.addEventListener('click', () => {
        const filter = link.dataset.filterLink;
        setActiveFilter(filter);
        renderPosts(filter);
        document.getElementById('todos-los-articulos').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    });
});
