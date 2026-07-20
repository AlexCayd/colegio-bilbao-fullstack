/* blog-articulo
   Migrado desde el <script> embebido de views/blog/articulo.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-articulo') return;
        (function () {
            /* Progreso de lectura — trackea el artículo completo */
            var bar  = document.getElementById('readProgress');
            var body = document.querySelector('.article-body-wrapper');

            function onScroll() {
                if (!bar || !body) return;
                var st         = window.pageYOffset;
                var top        = body.getBoundingClientRect().top + st;
                var h          = body.offsetHeight;
                var wh         = window.innerHeight;
                var scrollable = h - wh;
                var p          = scrollable > 0 ? (st - top) / scrollable : (st >= top ? 1 : 0);
                bar.style.width = Math.max(0, Math.min(1, p)) * 100 + '%';
            }

            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();

            /* Copiar enlace */
            var btn = document.getElementById('copyLinkArticulo');
            if (btn) {
                btn.addEventListener('click', function () {
                    var orig = btn.innerHTML;
                    btn.innerHTML = '¡Copiado!';
                    setTimeout(function () { btn.innerHTML = orig; }, 2200);
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(window.location.href);
                    } else {
                        var ta = document.createElement('textarea');
                        ta.value = window.location.href;
                        Object.assign(ta.style, { position: 'fixed', opacity: '0' });
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        document.body.removeChild(ta);
                    }
                });
            }
        })();
    

        /* ── Share buttons ── */
        (function () {
            const copyBtn = document.getElementById('artCopyLink');
            const copyTxt = document.getElementById('artCopyTxt');
            if (copyBtn) {
                copyBtn.addEventListener('click', function () {
                    navigator.clipboard.writeText(location.href).then(function () {
                        copyTxt.textContent = '¡Copiado!';
                        copyBtn.style.background = '#dcfce7';
                        copyBtn.style.color = '#166534';
                        setTimeout(function () {
                            copyTxt.textContent = 'Copiar enlace';
                            copyBtn.style.background = '';
                            copyBtn.style.color = '';
                        }, 2000);
                    }).catch(function () {
                        const ta = document.createElement('textarea');
                        ta.value = location.href;
                        ta.style.position = 'absolute'; ta.style.left = '-9999px';
                        document.body.appendChild(ta); ta.select();
                        document.execCommand('copy'); document.body.removeChild(ta);
                        copyTxt.textContent = '¡Copiado!';
                        setTimeout(function () { copyTxt.textContent = 'Copiar enlace'; }, 2000);
                    });
                });
            }
            const shareBtn = document.getElementById('artShareBtn');
            if (shareBtn) {
                if (navigator.share) {
                    shareBtn.addEventListener('click', function () {
                        navigator.share({ title: document.title, url: location.href });
                    });
                } else {
                    shareBtn.style.display = 'none';
                }
            }
        })();
    
})();
