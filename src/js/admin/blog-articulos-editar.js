/* blog-articulos-editar
   Migrado desde el <script> embebido de views/blog/articulos/editar.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-articulos-editar') return;
    (function () {

        /* ─────────────────────────────────────────────────────
           1. SLUG
        ───────────────────────────────────────────────────── */
        const tituloInput   = document.getElementById('titulo');
        const slugPreview   = document.getElementById('slugPreviewText');
        const slugHidden    = document.getElementById('slugHidden');
        const slugManual    = document.getElementById('slugManual');
        const slugToggleBtn = document.getElementById('slugToggleBtn');
        const slugEditWrap  = document.getElementById('slugEditWrap');
        let   slugManualMode = false;

        function toSlug(str) {
            return str.toLowerCase()
                .normalize('NFD').replace(/[̀-ͯ]/g, '')
                .replace(/[^a-z0-9\s-]/g, '').trim()
                .replace(/[\s]+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
        }

        tituloInput.addEventListener('input', function () {
            if (!slugManualMode) {
                const slug = toSlug(this.value);
                slugPreview.textContent = slug || 'el-titulo-del-articulo';
                slugHidden.value = slug;
            }
            actualizarSEO();
        });

        slugToggleBtn.addEventListener('click', function () {
            slugManualMode = !slugManualMode;
            slugEditWrap.style.display = slugManualMode ? 'block' : 'none';
            slugHidden.disabled = slugManualMode;
            if (slugManualMode) { slugManual.value = slugPreview.textContent; slugManual.focus(); }
        });

        slugManual.addEventListener('input', function () {
            const clean = toSlug(this.value);
            slugPreview.textContent = clean || 'el-titulo-del-articulo';
        });


        /* ─────────────────────────────────────────────────────
           2. SEO INDICATOR
        ───────────────────────────────────────────────────── */
        const seoHint = document.getElementById('seoTituloHint');

        function actualizarSEO() {
            const len = tituloInput.value.length;
            let label = '', cls = '';
            if (!len)           { label = '';                                               cls = 'seo--empty'; }
            else if (len < 20)  { label = `${len} / 40 · Título muy corto para SEO`;       cls = 'seo--warn';  }
            else if (len <= 40) { label = `${len} / 40 · ¡Longitud perfecta!`;             cls = 'seo--good';  }
            else if (len <= 55) { label = `${len} / 40 · Algo largo, considera recortarlo`; cls = 'seo--warn';  }
            else                { label = `${len} / 40 · Demasiado largo para Google`;      cls = 'seo--bad';  }
            seoHint.textContent = label;
            seoHint.className   = 'seo-hint ' + cls;
        }
        actualizarSEO();


        /* ─────────────────────────────────────────────────────
           3. CONTADOR EXTRACTO
        ───────────────────────────────────────────────────── */
        const extracto      = document.getElementById('extracto');
        const extractoCount = document.getElementById('extractoCount');
        extracto.addEventListener('input', function () { extractoCount.textContent = this.value.length; });


        /* ─────────────────────────────────────────────────────
           4. WYSIWYG EDITOR
        ───────────────────────────────────────────────────── */
        const editor       = document.getElementById('wysiwyg');
        const contenidoHid = document.getElementById('contenidoHidden');
        const toolbar      = document.getElementById('editorToolbar');

        editor.addEventListener('paste', function(e) {
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text/plain');
            document.execCommand('insertText', false, text);
        });
        let   savedRange   = null;

        function togglePlaceholder() {
            const empty = !editor.textContent.trim() && !editor.querySelector('img');
            editor.classList.toggle('is-empty', empty);
        }
        togglePlaceholder();
        editor.addEventListener('input', togglePlaceholder);
        editor.addEventListener('focus', togglePlaceholder);

        editor.addEventListener('mouseup', saveRange);
        editor.addEventListener('keyup',   saveRange);

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

        document.addEventListener('selectionchange', function () {
            if (editor.contains(document.activeElement) || document.activeElement === editor) {
                actualizarBotones();
                saveRange();
            }
        });

        editor.addEventListener('input', function () {
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


        /* ─────────────────────────────────────────────────────
           5. MODAL DE ENLACE
        ───────────────────────────────────────────────────── */
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


        /* ─────────────────────────────────────────────────────
           6. TIEMPO DE LECTURA — DINÁMICO Y EDITABLE
        ───────────────────────────────────────────────────── */
        const tiempoInput = document.getElementById('tiempoInput');

        function calcularLectura() {
            const text  = (editor.innerText || editor.textContent || '').replace(/\s+/g, ' ').trim();
            const words = text ? text.split(' ').filter(w => w.length > 0).length : 0;
            tiempoInput.value = Math.max(1, Math.ceil(words / 200));
        }
        calcularLectura();


        /* ─────────────────────────────────────────────────────
           7. TAGS CON PILLS
        ───────────────────────────────────────────────────── */
        const tagWrap    = document.getElementById('tagWrap');
        const tagInput   = document.getElementById('tagInputField');
        const tagsHidden = document.getElementById('tagsHidden');

        const tags = tagsHidden.value
            ? tagsHidden.value.split(',').map(t => t.trim()).filter(Boolean)
            : [];

        function escHtml(s) {
            return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        function renderTags() {
            tagWrap.querySelectorAll('.tag-pill').forEach(p => p.remove());
            tags.forEach((tag, i) => {
                const pill = document.createElement('span');
                pill.className = 'tag-pill';
                pill.innerHTML = escHtml(tag) +
                    '<button type="button" class="tag-pill__remove" data-i="' + i + '" title="Quitar">' +
                    '<i class="fa-solid fa-xmark"></i></button>';
                tagWrap.insertBefore(pill, tagInput);
            });
            tagsHidden.value = tags.join(',');
        }

        function agregarTag(val) {
            val = val.replace(/,/g, '').trim();
            if (val && !tags.includes(val)) { tags.push(val); renderTags(); }
        }

        tagWrap.addEventListener('click', e => {
            if (e.target.closest('.tag-pill__remove')) {
                tags.splice(parseInt(e.target.closest('[data-i]').dataset.i), 1);
                renderTags();
            } else { tagInput.focus(); }
        });

        tagInput.addEventListener('keydown', function (e) {
            if (e.key === ',' || e.key === 'Enter') {
                e.preventDefault(); agregarTag(this.value); this.value = '';
            } else if (e.key === 'Backspace' && !this.value && tags.length) {
                tags.pop(); renderTags();
            }
        });

        tagInput.addEventListener('input', function () {
            if (this.value.includes(',')) {
                this.value.split(',').slice(0, -1).forEach(p => agregarTag(p));
                this.value = this.value.split(',').pop();
            }
        });

        renderTags();


        /* ─────────────────────────────────────────────────────
           8. ESTADO — FECHA + BOTÓN DINÁMICO
        ───────────────────────────────────────────────────── */
        const estadoSel   = document.getElementById('estado');
        const fechaGroup  = document.getElementById('fechaPublicacionGroup');
        const fechaInput  = document.getElementById('fecha_publicacion');
        const submitBtn   = document.getElementById('submitBtn');
        const submitIcon  = document.getElementById('submitIcon');
        const submitLabel = document.getElementById('submitLabel');

        const btnCfg = {
            borrador:   { icon: 'fa-regular fa-floppy-disk',   label: 'Guardar cambios',    cls: 'admin-btn admin-btn--secondary' },
            publicado:  { icon: 'fa-solid fa-rocket',           label: 'Publicar artículo',  cls: 'admin-btn admin-btn--primary'   },
            programado: { icon: 'fa-regular fa-calendar-check', label: 'Programar artículo', cls: 'admin-btn admin-btn--primary'   },
        };

        function todayISO() {
            const n = new Date(), pad = v => String(v).padStart(2, '0');
            return `${n.getFullYear()}-${pad(n.getMonth()+1)}-${pad(n.getDate())}T00:00`;
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


        /* ─────────────────────────────────────────────────────
           9. UPLOAD DE IMAGEN
        ───────────────────────────────────────────────────── */
        const imagenInput    = document.getElementById('imagen');
        const imagePreview   = document.getElementById('imagePreview');
        const imageContent   = document.getElementById('imageUploadContent');
        const imageZone      = document.getElementById('imageUploadZone');
        const imageChangeTrigger = document.getElementById('imageChangeTrigger');

        if (imagenInput) {
            imagenInput.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = e => {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    if (imageContent) imageContent.style.display = 'none';
                    imageZone.classList.add('image-upload-zone--has-image');
                    if (imageChangeTrigger) {
                        imageChangeTrigger.innerHTML = '<i class="fa-solid fa-check"></i> ' + file.name;
                    }
                };
                reader.readAsDataURL(file);
            });
        }

        if (imageZone) {
            imageZone.addEventListener('dragover', e => { e.preventDefault(); imageZone.classList.add('image-upload-zone--over'); });
            imageZone.addEventListener('dragleave', () => imageZone.classList.remove('image-upload-zone--over'));
            imageZone.addEventListener('drop', function (e) {
                e.preventDefault();
                imageZone.classList.remove('image-upload-zone--over');
                const file = e.dataTransfer.files[0];
                if (file && file.type.startsWith('image/') && imagenInput) {
                    imagenInput.files = e.dataTransfer.files;
                    imagenInput.dispatchEvent(new Event('change'));
                }
            });
        }


        /* ─────────────────────────────────────────────────────
           10. MODAL ELIMINAR
        ───────────────────────────────────────────────────── */
        const deleteModal = document.getElementById('deleteModal');
        const ubmInput    = document.getElementById('ubm-input');
        const ubmSubmit   = document.getElementById('ubm-submit');
        const tituloRef   = (document.getElementById('articuloForm') || {}).dataset?.tituloRef || '';

        window.abrirModalEliminar = function () {
            ubmInput.value = '';
            ubmInput.classList.remove('is-valid');
            ubmSubmit.disabled = true;
            deleteModal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
            setTimeout(() => ubmInput.focus(), 300);
        };

        window.cerrarModalEliminar = function () {
            deleteModal.classList.remove('is-open');
            document.body.style.overflow = '';
        };

        ubmInput.addEventListener('input', function () {
            const match = this.value === tituloRef;
            this.classList.toggle('is-valid', match);
            ubmSubmit.disabled = !match;
        });

        document.getElementById('ubm-form').addEventListener('submit', function (e) {
            e.preventDefault();
            deleteModal.classList.remove('is-open');
            document.body.style.overflow = '';
            const main = document.querySelector('.admin-main');
            if (main) { main.style.transition = 'opacity .35s ease,transform .35s ease'; main.style.opacity = '0'; main.style.transform = 'translateY(10px)'; }
            setTimeout(() => this.submit(), 380);
        });

        deleteModal.addEventListener('click', e => { if (e.target === deleteModal) cerrarModalEliminar(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') { cerrarModalEliminar(); linkModal.classList.remove('is-open'); } });


        /* ─────────────────────────────────────────────────────
           11. SUBMIT — SINCRONIZAR WYSIWYG
        ───────────────────────────────────────────────────── */
        document.getElementById('articuloForm').addEventListener('submit', function () {
            contenidoHid.value = editor.innerHTML;
            if (slugManualMode) { slugHidden.disabled = true; }
            else               { slugManual.disabled  = true; }
        });

    })();

    /* ── Panel revisión ── */
    (function () {
        document.querySelectorAll('.rev-rechazar-toggle').forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                const panel = document.getElementById(this.dataset.target);
                if (!panel) return;
                const open = panel.style.display !== 'none';
                panel.style.display = open ? 'none' : 'block';
                this.innerHTML = open
                    ? '<i class="fa-solid fa-comment-exclamation"></i> Devolver feedback'
                    : '<i class="fa-solid fa-chevron-up"></i> Cancelar';
            });
        });
        // Contador de caracteres
        document.querySelectorAll('.rev-textarea').forEach(function (ta) {
            const counter = ta.parentElement ? ta.parentElement.querySelector('.rev-char-count') : null;
            if (counter) ta.addEventListener('input', function () { counter.textContent = this.value.length + '/600'; });
        });
        const selRev   = document.getElementById('revEstadoArt');
        const fechaRev = document.getElementById('revFechaArt');
        if (selRev && fechaRev) {
            selRev.addEventListener('change', function () {
                const prog = this.value === 'programado';
                fechaRev.style.display = prog ? 'block' : 'none';
                fechaRev.required = prog;
            });
        }

        /* ─── Modal confirmar envío a revisión ─── */
        const revModal      = document.getElementById('revisionModal');
        const btnEnvRev     = document.getElementById('btnEnviarRevision');
        const btnRevCancel  = document.getElementById('btnRevCancelar');
        const btnRevConfirm = document.getElementById('btnRevConfirmar');
        let pendingReenviar = false;

        if (btnEnvRev && revModal) {
            btnEnvRev.addEventListener('click', function () {
                pendingReenviar = false;
                revModal.classList.add('is-open');
            });
        }
        if (btnRevCancel) {
            btnRevCancel.addEventListener('click', function () {
                pendingReenviar = false;
                revModal.classList.remove('is-open');
            });
        }
        if (btnRevConfirm) {
            btnRevConfirm.addEventListener('click', function () {
                // Sync WYSIWYG via getElementById (form.submit() no dispara el evento submit)
                var _ed = document.getElementById('wysiwyg');
                var _hid = document.getElementById('contenidoHidden');
                if (_ed && _hid) { _hid.value = _ed.innerHTML; }
                if (pendingReenviar) {
                    pendingReenviar = false;
                    const form = document.getElementById('articuloForm');
                    let accionInput = form.querySelector('input[name="_accion"]');
                    if (!accionInput) {
                        accionInput = document.createElement('input');
                        accionInput.type = 'hidden';
                        accionInput.name = '_accion';
                        form.appendChild(accionInput);
                    }
                    accionInput.value = 'reenviar_revision';
                    form.submit();
                } else {
                    const revForm = document.getElementById('enviarRevisionForm');
                    if (revForm) {
                        revForm.submit();
                    } else {
                        document.getElementById('articuloForm').submit();
                    }
                }
            });
        }
        revModal && revModal.addEventListener('click', function (e) {
            if (e.target === revModal) { pendingReenviar = false; revModal.classList.remove('is-open'); }
        });

        // 4th state: save + re-send for rejected articles
        const btnGuardarReenviar = document.getElementById('btnGuardarReenviar');
        if (btnGuardarReenviar && revModal) {
            btnGuardarReenviar.addEventListener('click', function () {
                pendingReenviar = true;
                revModal.classList.add('is-open');
            });
        }

    })();
})();
