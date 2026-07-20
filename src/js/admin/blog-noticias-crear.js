/* blog-noticias-crear
   Migrado desde el <script> embebido de views/blog/noticias/crear.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-noticias-crear') return;
    (function () {

        const tituloInput   = document.getElementById('titulo');
        const slugPreview   = document.getElementById('slugPreviewText');
        const slugHidden    = document.getElementById('slugHidden');
        const slugManual    = document.getElementById('slugManual');
        const slugToggleBtn = document.getElementById('slugToggleBtn');
        const slugEditWrap  = document.getElementById('slugEditWrap');
        let slugManualMode  = false;

        function toSlug(str) {
            return str.toLowerCase()
                .normalize('NFD').replace(/[̀-ͯ]/g, '')
                .replace(/[^a-z0-9\s-]/g, '').trim()
                .replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
        }

        function actualizarSlug(slug) {
            slugPreview.textContent = slug || 'el-titulo-de-la-noticia';
            slugHidden.value        = slug;
        }

        tituloInput.addEventListener('input', function () {
            if (!slugManualMode) actualizarSlug(toSlug(this.value));
            actualizarSEO();
        });

        slugToggleBtn.addEventListener('click', function () {
            slugManualMode = !slugManualMode;
            slugEditWrap.style.display = slugManualMode ? 'block' : 'none';
            slugHidden.disabled = slugManualMode;
            if (slugManualMode) { slugManual.value = slugPreview.textContent; slugManual.focus(); }
        });

        slugManual.addEventListener('input', function () {
            slugPreview.textContent = toSlug(this.value) || 'el-titulo-de-la-noticia';
        });

        const seoHint = document.getElementById('seoTituloHint');
        function actualizarSEO() {
            const len = tituloInput.value.length;
            let label = '', cls = '';
            if (len === 0)       { label = '';                                      cls = 'seo--empty'; }
            else if (len < 20)   { label = `${len} / 40 · Título muy corto`;       cls = 'seo--warn'; }
            else if (len <= 40)  { label = `${len} / 40 · ¡Longitud perfecta!`;    cls = 'seo--good'; }
            else if (len <= 55)  { label = `${len} / 40 · Algo largo`;             cls = 'seo--warn'; }
            else                 { label = `${len} / 40 · Demasiado largo`;        cls = 'seo--bad';  }
            seoHint.textContent = label;
            seoHint.className   = 'seo-hint ' + cls;
        }

        document.getElementById('extracto').addEventListener('input', function () {
            document.getElementById('extractoCount').textContent = this.value.length;
        });

        // WYSIWYG
        const editor       = document.getElementById('wysiwyg');
        const contenidoHid = document.getElementById('contenidoHidden');

        editor.addEventListener('paste', function(e) {
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text/plain');
            document.execCommand('insertText', false, text);
        });
        const toolbar      = document.getElementById('editorToolbar');
        let savedRange     = null;

        document.execCommand('defaultParagraphSeparator', false, 'p');

        function togglePlaceholder() {
            editor.classList.toggle('is-empty', !editor.textContent.trim() && !editor.querySelector('img'));
        }
        togglePlaceholder();
        editor.addEventListener('input', togglePlaceholder);
        editor.addEventListener('mouseup', saveRange);
        editor.addEventListener('keyup', saveRange);

        function saveRange() {
            const sel = window.getSelection();
            if (sel && sel.rangeCount) savedRange = sel.getRangeAt(0).cloneRange();
        }
        function restoreRange() {
            if (!savedRange) return;
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(savedRange);
        }

        toolbar.addEventListener('mousedown', function (e) {
            const btn = e.target.closest('[data-cmd]');
            if (!btn) return;
            e.preventDefault();
            restoreRange();
            editor.focus();
            const cmd = btn.dataset.cmd;
            if (cmd === 'bold')   document.execCommand('bold');
            if (cmd === 'italic') document.execCommand('italic');
            if (cmd === 'h2')     document.execCommand('formatBlock', false, 'h2');
            if (cmd === 'h3')     document.execCommand('formatBlock', false, 'h3');
            if (cmd === 'list')   document.execCommand('insertUnorderedList');
            if (cmd === 'quote')  document.execCommand('formatBlock', false, 'blockquote');
            if (cmd === 'plain')  document.execCommand('formatBlock', false, 'p');
            if (cmd === 'link')   abrirModalEnlace();
            actualizarBotones();
            togglePlaceholder();
            calcularLectura();
        });

        function actualizarBotones() {
            toolbar.querySelectorAll('[data-cmd]').forEach(btn => {
                const cmd = btn.dataset.cmd;
                let active = false;
                try {
                    if (cmd === 'bold')   active = document.queryCommandState('bold');
                    if (cmd === 'italic') active = document.queryCommandState('italic');
                    if (cmd === 'list')   active = document.queryCommandState('insertUnorderedList');
                    const block = document.queryCommandValue('formatBlock');
                    if (cmd === 'h2')    active = block === 'h2';
                    if (cmd === 'h3')    active = block === 'h3';
                    if (cmd === 'quote') active = block === 'blockquote';
                } catch (_) {}
                btn.classList.toggle('editor-toolbar__btn--active', active);
            });
        }

        document.addEventListener('selectionchange', () => {
            if (document.activeElement === editor || editor.contains(document.activeElement)) {
                actualizarBotones(); saveRange();
            }
        });
        editor.addEventListener('input', () => {
            calcularLectura();
            togglePlaceholder();
            if (editor.classList.contains('is-empty')) {
                editor.innerHTML = '<br>';
                const r = document.createRange(), s = window.getSelection();
                r.setStart(editor, 0); r.collapse(true);
                s.removeAllRanges(); s.addRange(r);
            }
        });

        // Salir del blockquote al presionar Enter (sin Shift)
        editor.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' || e.shiftKey) return;
            const sel = window.getSelection();
            if (!sel || !sel.rangeCount) return;
            const node = sel.anchorNode;
            const block = node && (node.nodeType === 3 ? node.parentElement : node).closest('blockquote');
            if (!block) return;
            e.preventDefault();
            const p = document.createElement('p');
            p.innerHTML = '<br>';
            block.insertAdjacentElement('afterend', p);
            const range = document.createRange();
            range.setStart(p, 0);
            range.collapse(true);
            sel.removeAllRanges();
            sel.addRange(range);
            togglePlaceholder();
        });

        // Modal enlace
        const linkModal   = document.getElementById('linkModal');
        const linkUrl     = document.getElementById('linkUrl');
        const linkConfirm = document.getElementById('linkConfirmBtn');
        const linkCancel  = document.getElementById('linkCancelBtn');

        function abrirModalEnlace() {
            linkUrl.value = '';
            linkModal.classList.add('is-open');
            setTimeout(() => linkUrl.focus(), 150);
        }
        linkCancel.addEventListener('click', () => linkModal.classList.remove('is-open'));
        linkModal.addEventListener('click', e => { if (e.target === linkModal) linkModal.classList.remove('is-open'); });
        linkConfirm.addEventListener('click', function () {
            const url = linkUrl.value.trim();
            if (!url) return;
            restoreRange(); editor.focus();
            document.execCommand('createLink', false, url);
            linkModal.classList.remove('is-open');
        });
        linkUrl.addEventListener('keydown', e => {
            if (e.key === 'Enter') linkConfirm.click();
            if (e.key === 'Escape') linkCancel.click();
        });

        // Tiempo de lectura
        const tiempoInput = document.getElementById('tiempoInput');
        function calcularLectura() {
            const words = (editor.innerText || '').trim().split(/\s+/).filter(w => w.length > 0).length;
            tiempoInput.value = Math.max(1, Math.ceil(words / 200));
        }
        calcularLectura();

        // Estado → botón + fecha
        const estadoSel   = document.getElementById('estado');
        const fechaGroup  = document.getElementById('fechaPublicacionGroup');
        const fechaInput  = document.getElementById('fecha_publicacion');
        const submitBtn   = document.getElementById('submitBtn');
        const submitIcon  = document.getElementById('submitIcon');
        const submitLabel = document.getElementById('submitLabel');

        const btnCfg = {
            borrador:   { icon: 'fa-regular fa-floppy-disk',    label: 'Guardar como borrador', cls: 'admin-btn admin-btn--secondary' },
            publicado:  { icon: 'fa-solid fa-rocket',            label: 'Publicar noticia',      cls: 'admin-btn admin-btn--primary'   },
            programado: { icon: 'fa-regular fa-calendar-check',  label: 'Programar noticia',     cls: 'admin-btn admin-btn--primary'   },
        };

        function todayISO() {
            const n = new Date(), p = v => String(v).padStart(2,'0');
            return `${n.getFullYear()}-${p(n.getMonth()+1)}-${p(n.getDate())}T00:00`;
        }

        function actualizarUI() {
            const val = estadoSel.value;
            const show = val === 'programado';
            fechaGroup.style.display = show ? 'block' : 'none';
            if (show) fechaInput.min = todayISO();
            const cfg = btnCfg[val] || btnCfg.borrador;
            submitIcon.className    = cfg.icon;
            submitLabel.textContent = cfg.label;
            submitBtn.className     = cfg.cls + ' submit-full';
        }
        if (estadoSel) {
            estadoSel.addEventListener('change', actualizarUI);
            actualizarUI();
        }

        // Upload imagen
        const portadaInput  = document.getElementById('portada');
        const imagePreview  = document.getElementById('imagePreview');
        const imageContent  = document.getElementById('imageUploadContent');
        const imageZone     = document.getElementById('imageUploadZone');

        function mostrarPreview(file) {
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function (e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display  = 'block';
                imageContent.style.display  = 'none';
                imageZone.classList.add('image-upload-zone--has-image');
            };
            reader.readAsDataURL(file);
        }

        portadaInput.addEventListener('change', () => mostrarPreview(portadaInput.files[0]));
        imageZone.addEventListener('dragover', e => { e.preventDefault(); imageZone.classList.add('image-upload-zone--over'); });
        imageZone.addEventListener('dragleave', () => imageZone.classList.remove('image-upload-zone--over'));
        imageZone.addEventListener('drop', function (e) {
            e.preventDefault();
            imageZone.classList.remove('image-upload-zone--over');
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) { portadaInput.files = e.dataTransfer.files; mostrarPreview(file); }
        });

        // Submit
        document.getElementById('noticiaForm').addEventListener('submit', function () {
            contenidoHid.value = editor.innerHTML;
            if (slugManualMode) slugHidden.disabled = true;
            else slugManual.disabled = true;
        });

        actualizarSEO();

        /* ── Modal confirmar envío a revisión ── */
        const revModal      = document.getElementById('revisionModal');
        const btnEnvRevCr   = document.getElementById('btnEnviarRevisionCrear');
        const btnRevCancel  = document.getElementById('btnRevCancelar');
        const btnRevConfirm = document.getElementById('btnRevConfirmar');
        const accionInput   = document.getElementById('_accionHidden');

        if (btnEnvRevCr) {
            btnEnvRevCr.addEventListener('click', function () {
                revModal.classList.add('is-open');
            });
        }
        if (btnRevCancel) {
            btnRevCancel.addEventListener('click', function () {
                revModal.classList.remove('is-open');
            });
        }
        if (btnRevConfirm) {
            btnRevConfirm.addEventListener('click', function () {
                accionInput.value  = 'enviar_revision';
                contenidoHid.value = editor.innerHTML;
                if (slugManualMode) slugHidden.disabled = true;
                else slugManual.disabled = true;
                document.getElementById('noticiaForm').submit();
            });
        }
        revModal && revModal.addEventListener('click', function (e) {
            if (e.target === revModal) revModal.classList.remove('is-open');
        });

    })();
})();
