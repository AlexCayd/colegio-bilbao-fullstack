/* blog-suplencias-agendar — panel de asignación: hora seleccionada → candidatos → preview del horario.
   El preview se abre como acordeón bajo el candidato pulsado (no al final de la lista) y los
   candidatos bloqueados no ofrecen asignación: el sistema decide y no se puede forzar. */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-suplencias-agendar') return;

    var main = document.querySelector('.admin-content[data-fecha]');
    if (!main) return;

    var fecha        = main.dataset.fecha;
    var ausente      = main.dataset.ausente || '0';
    var puedeAgendar = main.dataset.puedeAgendar === '1';

    var PAGINA = 5;   // candidatos por página

    var asignForm = document.getElementById('supl-asignar-form');
    var horaInput = document.getElementById('supl-asignar-hora');
    var supInput  = document.getElementById('supl-asignar-suplente');

    var assign      = main.querySelector('[data-assign]');
    var assignTile  = main.querySelector('[data-assign-tile]');
    var assignTitle = main.querySelector('[data-assign-title]');
    var assignSub   = main.querySelector('[data-assign-sub]');
    var assignBody  = main.querySelector('[data-assign-body]');
    if (!assign) return;

    var cards = Array.prototype.slice.call(main.querySelectorAll('[data-hora-card]'));
    var horaActual = null;      // tarjeta seleccionada
    var cacheCand  = {};        // periodo_id → candidatos

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function avatarHtml(u) {
        return u.avatar ? '<img src="' + esc(u.avatar) + '" alt="">' : esc((u.nombre || '?').charAt(0).toUpperCase());
    }

    /* ── Una fila de candidato + su hueco de preview (acordeón) ── */
    function filaHtml(u, ok) {
        var chips = '<span class="supl-cand__chip">' + u.horas_libres + ' h libres</span>' +
                    '<span class="supl-cand__chip">' + u.coberturas + ' cob.</span>' +
                    (u.es_administrativo ? '<span class="supl-cand__chip supl-cand__chip--warn">Administrativo</span>' : '');

        var fila = '<button type="button" class="supl-cand' + (ok ? ' supl-cand--ok' : ' supl-cand--off') + '"' +
                       ' data-cand="' + u.id + '" data-nombre="' + esc(u.nombre) + '"' +
                       ' data-avatar="' + esc(u.avatar || '') + '" data-ok="' + (ok ? '1' : '0') + '"' +
                       ' data-motivo="' + esc(u.motivo || '') + '"' +
                       (ok ? '' : ' aria-disabled="true"') + '>' +
                   '<span class="supl-person__ava supl-cand__ava">' + avatarHtml(u) + '</span>' +
                   '<span class="supl-cand__body">' +
                       '<span class="supl-cand__name">' + esc(u.nombre) + '</span>' +
                       '<span class="supl-cand__chips">' +
                           (ok ? chips : '<span class="supl-cand__motivo"><i class="fa-solid fa-ban"></i> ' + esc(u.motivo || 'No disponible') + '</span>') +
                       '</span>' +
                   '</span>' +
                   '<i class="fa-solid fa-chevron-down supl-cand__go"></i>' +
                   '</button>';

        // El preview vive pegado a su candidato para no obligar a scrollear toda la lista
        return '<div class="supl-cand-wrap" data-cand-wrap data-pager-item>' + fila +
               '<div class="supl-preview" data-preview hidden></div></div>';
    }

    function grupoHtml(titulo, icono, lista, ok, idPager) {
        if (!lista.length) return '';
        var html = '<p class="supl-assign__group"><i class="fa-solid ' + icono + '"></i> ' + titulo +
                   ' <span>' + lista.length + '</span></p>';
        html += '<div class="supl-cand-list" id="' + idPager + '">' +
                lista.map(function (u) { return filaHtml(u, ok); }).join('') +
                '</div>';
        if (lista.length > PAGINA) {
            html += '<div class="supl-pager" data-pager data-pager-for="#' + idPager + '"' +
                    ' data-pager-per="' + PAGINA + '" data-pager-noun="profesores">' +
                    '<span class="supl-pager__info" data-pager-info></span>' +
                    '<div class="supl-pager__btns">' +
                        '<button type="button" class="supl-pager__btn" data-pager-prev aria-label="Anterior"><i class="fa-solid fa-chevron-left"></i></button>' +
                        '<button type="button" class="supl-pager__btn" data-pager-next aria-label="Siguiente"><i class="fa-solid fa-chevron-right"></i></button>' +
                    '</div></div>';
        }
        return html;
    }

    /* ── Lista de candidatos ── */
    function pintarCandidatos(data, card) {
        if (!data.length) {
            assignBody.innerHTML = '<p class="supl-week-empty">No hay candidatos para esta hora.</p>';
            return;
        }
        var elegibles  = data.filter(function (u) { return u.elegible; });
        var bloqueados = data.filter(function (u) { return !u.elegible; });

        var html = '';
        if (elegibles.length) {
            html += grupoHtml('Sugeridos', 'fa-star', elegibles, true, 'suplCandOk');
        } else {
            html += '<p class="supl-week-empty">Ningún profesor cumple las reglas para esta hora. ' +
                    'Revisa los bloqueados para ver por qué.</p>';
        }
        if (bloqueados.length) {
            html += '<details class="supl-blocked"><summary>Ver bloqueados (' + bloqueados.length + ')</summary>' +
                    '<div class="supl-blocked__body">' +
                    grupoHtml('No disponibles', 'fa-ban', bloqueados, false, 'suplCandOff') +
                    '</div></details>';
        }
        assignBody.innerHTML = html;

        // Paginación 5/5 de cada grupo (módulo compartido admin-pager.js)
        if (window.AdminPager) window.AdminPager.init(assignBody);

        assignBody.querySelectorAll('[data-cand]').forEach(function (btn) {
            btn.addEventListener('click', function () { alternarPreview(btn, card); });
        });
    }

    /* ── Preview del horario del candidato, con la hora de cobertura resaltada ── */
    function alternarPreview(btn, card) {
        var wrap = btn.closest('[data-cand-wrap]');
        var box  = wrap ? wrap.querySelector('[data-preview]') : null;
        if (!box) return;

        // Un solo preview abierto a la vez
        if (btn.classList.contains('is-active')) {
            btn.classList.remove('is-active');
            box.hidden = true;
            box.innerHTML = '';
            return;
        }
        assignBody.querySelectorAll('[data-cand].is-active').forEach(function (b) { b.classList.remove('is-active'); });
        assignBody.querySelectorAll('[data-preview]').forEach(function (p) { p.hidden = true; p.innerHTML = ''; });
        btn.classList.add('is-active');

        var id     = btn.dataset.cand;
        var nombre = btn.dataset.nombre;
        var avatar = btn.dataset.avatar;
        var ok     = btn.dataset.ok === '1';

        box.hidden = false;
        box.innerHTML = '<p class="supl-week-empty"><i class="fa-solid fa-spinner fa-spin"></i> Cargando el horario de ' + esc(nombre) + '…</p>';

        window.SuplWeek.fetch(id, fecha)
            .then(function (data) {
                var cta = '';
                if (!ok) {
                    // Bloqueado por las reglas: no se ofrece asignación, ni forzada
                    cta = '<div class="supl-preview__blocked">' +
                              '<i class="fa-solid fa-lock"></i> ' +
                              '<span>No se puede asignar: <strong>' + esc(btn.dataset.motivo || 'no cumple las reglas de suplencia') + '</strong></span>' +
                          '</div>';
                } else if (puedeAgendar) {
                    cta = '<div class="supl-confirm">' +
                              '<span class="supl-person__ava supl-confirm__ava">' +
                                  (avatar ? '<img src="' + esc(avatar) + '" alt="">' : esc(nombre.charAt(0).toUpperCase())) +
                              '</span>' +
                              '<div class="supl-confirm__text">' +
                                  '<strong>' + esc(nombre) + '</strong>' +
                                  '<small>Cubrirá ' + esc(card.dataset.etiqueta) + ' · ' + esc(card.dataset.rango) + ' · ' + esc(card.dataset.clase) + '</small>' +
                              '</div>' +
                              '<button type="button" class="supl-confirm__btn" data-confirmar>' +
                                  '<i class="fa-solid fa-user-check"></i> Confirmar asignación' +
                              '</button>' +
                          '</div>';
                }

                box.innerHTML =
                    '<div class="supl-preview__head">' +
                        '<span class="supl-preview__name"><i class="fa-regular fa-calendar-days"></i> Horario de ' + esc(nombre) + '</span>' +
                    '</div>' +
                    '<div data-preview-week></div>' + cta;

                window.SuplWeek.render(box.querySelector('[data-preview-week]'), data, {
                    mode: 'preview',
                    target: card.dataset.periodo
                });

                var confirmar = box.querySelector('[data-confirmar]');
                if (confirmar) {
                    confirmar.addEventListener('click', function () {
                        confirmar.disabled = true;
                        horaInput.value = card.dataset.hora;
                        supInput.value  = id;
                        asignForm.submit();
                    });
                }
            })
            .catch(function () {
                box.innerHTML = '<p class="supl-week-empty">No se pudo cargar el horario de ' + esc(nombre) + '.</p>';
            });
    }

    /* ── Selección de hora ── */
    function seleccionar(card) {
        cards.forEach(function (c) { c.classList.remove('is-active'); });
        card.classList.add('is-active');
        horaActual = card;

        assignTile.querySelector('strong').textContent = card.dataset.etiqueta;
        assignTile.querySelector('small').textContent  = card.dataset.rango;
        assignTitle.textContent = card.dataset.rango;
        assignSub.textContent   = card.dataset.clase;
        assign.classList.add('is-active');

        var periodo = card.dataset.periodo;
        if (cacheCand[periodo]) { pintarCandidatos(cacheCand[periodo], card); return; }

        assignBody.innerHTML = '<p class="supl-week-empty"><i class="fa-solid fa-spinner fa-spin"></i> Buscando suplentes disponibles…</p>';
        fetch('/dashboard/suplencias/sugerir?fecha=' + encodeURIComponent(fecha) +
              '&periodo=' + encodeURIComponent(periodo) +
              '&ausente=' + encodeURIComponent(ausente))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                cacheCand[periodo] = data || [];
                if (horaActual === card) pintarCandidatos(cacheCand[periodo], card);
            })
            .catch(function () {
                assignBody.innerHTML = '<p class="supl-week-empty">Error al cargar las sugerencias.</p>';
            });
    }

    if (!puedeAgendar || !window.SuplWeek) return;

    var abiertas = cards.filter(function (c) { return c.classList.contains('is-open'); });
    abiertas.forEach(function (card) {
        card.addEventListener('click', function (e) {
            if (e.target.closest('form')) return;   // no robar el submit de desasignar
            seleccionar(card);
        });
        card.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); seleccionar(card); }
        });
    });

    // Arranca en la primera hora pendiente para no dejar el panel vacío
    if (abiertas.length) seleccionar(abiertas[0]);
})();
