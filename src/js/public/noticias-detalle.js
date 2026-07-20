/* noticias-detalle
   Migrado desde el <script> embebido de views/noticias/detalle.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'noticias-detalle') return;
        (function () {
            var bar    = document.getElementById('readProgress');
            var bodyEl = document.getElementById('articleBody');

            function onScroll() {
                if (!bar || !bodyEl) return;
                var st         = window.pageYOffset;
                var top        = bodyEl.getBoundingClientRect().top + st;
                var h          = bodyEl.offsetHeight;
                var wh         = window.innerHeight;
                var scrollable = h - wh;
                var p          = scrollable > 0 ? (st - top) / scrollable : (st >= top ? 1 : 0);
                bar.style.width = (Math.max(0, Math.min(1, p)) * 100).toFixed(1) + '%';
            }

            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();

            var copyBtn = document.getElementById('copyLinkHero');
            var copyTxt = document.getElementById('ndCopyTxt');
            if (copyBtn) {
                copyBtn.addEventListener('click', function () {
                    var write = function () {
                        copyTxt.textContent = '¡Copiado!';
                        copyBtn.style.background = '#dcfce7';
                        copyBtn.style.color = '#166534';
                        setTimeout(function () {
                            copyTxt.textContent = 'Copiar enlace';
                            copyBtn.style.background = '';
                            copyBtn.style.color = '';
                        }, 2000);
                    };
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(location.href).then(write).catch(write);
                    } else {
                        var ta = document.createElement('textarea');
                        ta.value = location.href;
                        Object.assign(ta.style, { position: 'fixed', opacity: '0' });
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        document.body.removeChild(ta);
                        write();
                    }
                });
            }

            var shareBtn = document.getElementById('ndShareBtn');
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
