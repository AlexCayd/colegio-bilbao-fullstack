function normalize(str) {
    return str.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
}

const allPosts = Array.from(document.querySelectorAll('.post-card')).map(card => ({
    element: card.cloneNode(true),
    categories: (card.getAttribute('data-category') || '').trim().split(/\s+/),
    searchText: normalize((card.dataset.title || '') + ' ' + (card.dataset.excerpt || ''))
}));

const postsGrid   = document.getElementById('postsGrid');
const emptyState  = document.getElementById('emptyState');
const filterButtons  = document.querySelectorAll('.filter-btn');
const categoryLinks  = document.querySelectorAll('[data-filter-link]');
const searchInput    = document.getElementById('blogSearch');

let currentFilter = 'all';
let currentSearch = '';

function renderPosts() {
    const q = normalize(currentSearch.trim());

    const filtered = allPosts.filter(post => {
        const matchCat    = currentFilter === 'all' || post.categories.includes(currentFilter);
        const matchSearch = !q || post.searchText.includes(q);
        return matchCat && matchSearch;
    });

    postsGrid.innerHTML = '';

    if (filtered.length === 0) {
        emptyState.style.display = 'block';
        emptyState.classList.add('show');
        return;
    }

    emptyState.style.display = 'none';
    emptyState.classList.remove('show');

    filtered.forEach(post => {
        const clone = post.element.cloneNode(true);
        clone.classList.remove('visible');
        postsGrid.appendChild(clone);

        requestAnimationFrame(() => clone.classList.add('visible'));

        clone.querySelectorAll('[data-filter-link]').forEach(link => {
            link.addEventListener('click', () => {
                currentFilter = link.dataset.filterLink;
                setActiveFilter(currentFilter);
                renderPosts();
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
        currentFilter = button.dataset.filter;
        setActiveFilter(currentFilter);
        renderPosts();
    });
});

categoryLinks.forEach(link => {
    link.addEventListener('click', () => {
        currentFilter = link.dataset.filterLink;
        setActiveFilter(currentFilter);
        renderPosts();
        document.getElementById('todos-los-articulos').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    });
});

if (searchInput) {
    searchInput.addEventListener('input', () => {
        currentSearch = searchInput.value;
        renderPosts();
    });
}
