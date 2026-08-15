/* admin-horario-celda — detalle de una casilla del horario.
 *
 * La celda de .hor-grid recorta con ellipsis: materia, grupo, aula y docentes
 * comparten una línea y lo que no cabe desaparece. Antes el dato completo solo estaba
 * en el atributo `title`, que en táctil no existe. Ahora un clic abre la ficha entera.
 *
 * SIN guarda de data-page a propósito: se activa por la existencia del modal, así que
 * sirve a las TRES vistas que incluyen `_grid.php` (horarios/index, mi-horario y la
 * semana consolidada del editor) sin repetir la lista de páginas en dos sitios.
 *
 * El contenido se arma con createElement y textContent, nunca innerHTML: son nombres
 * de personas y de aulas que vienen de la BD.
 */
(function () {
    var modal = document.getElementById('horCeldaModal');
    if (!modal) return;

    var elTitulo  = modal.querySelector('[data-hcd-titulo]') || modal.querySelector('#hcdTitulo');
    var elEyebrow = modal.querySelector('[data-hcd-eyebrow]');
    var elLista   = modal.querySelector('[data-hcd-lista]');
    var ultimoFoco = null;

    function fila(termino, valor) {
        if (!valor || (Array.isArray(valor) && !valor.length)) return;
        var dt = document.createElement('dt');
        dt.textContent = termino;
        var dd = document.createElement('dd');
        dd.textContent = Array.isArray(valor) ? valor.join(' · ') : valor;
        elLista.appendChild(dt);
        elLista.appendChild(dd);
    }

    function abrir(btn) {
        var d;
        try { d = JSON.parse(btn.getAttribute('data-hor-cell') || '{}'); } catch (e) { return; }

        elLista.textContent = '';
        elTitulo.textContent = d.materia || 'Clase';
        // El color de la materia es el mismo que pinta la casilla: BlogController::
        // colorMateria() lo calcula una vez y viaja en la ficha, no se recalcula aquí.
        if (d.color) modal.querySelector('.hcd-modal__card').style.setProperty('--c', d.color);

        if (elEyebrow) {
            elEyebrow.textContent = [d.inicio && d.fin ? d.inicio + '–' + d.fin : '', d.periodo, d.nivel]
                .filter(Boolean).join(' · ');
        }

        if (d.guardia) {
            fila('Lugar', d.lugar);
        } else {
            fila(d.grupos && d.grupos.length > 1 ? 'Grupos' : 'Grupo', d.grupos);
            fila('Aula', d.aula);
            fila(d.docentes && d.docentes.length > 1 ? 'Docentes' : 'Docente', d.docentes);
        }

        // Materia dividida: el grupo se reparte entre dos o más opciones simultáneas,
        // y en la casilla se ven apiladas y recortadas. Aquí cada una va completa.
        (d.opciones || []).forEach(function (op) {
            fila('También a la vez',
                 [op.materia, (op.grupos || []).join(' · '), op.aula, (op.docentes || []).join(' · ')]
                     .filter(Boolean).join(' — '));
        });

        // Dos clases que se pisan en el reloj. La casilla solo muestra un badge rojo
        // con el número; el nombre de la clase perdida estaba únicamente en el title.
        (d.conflicto || []).forEach(function (cf) {
            fila('Se solapa con', [cf.materia, cf.inicio, cf.nivel].filter(Boolean).join(' · '));
        });

        if (d.ajeno && d.nivel) {
            fila('Atención', 'Imparte ' + d.nivel + ', que no consta entre sus niveles declarados.');
        }

        ultimoFoco = document.activeElement;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        var cerrar = modal.querySelector('[data-hcd-cerrar]');
        if (cerrar) cerrar.focus();
    }

    function cerrar() {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        // Devolver el foco a la casilla: si no, el tabulador reempieza arriba del todo
        // y se pierde el sitio en una rejilla de treinta celdas.
        if (ultimoFoco && ultimoFoco.focus) ultimoFoco.focus();
        ultimoFoco = null;
    }

    // Delegado en el documento: las rejillas se pintan en servidor, pero el editor
    // repinta la suya al guardar y así no hay que reenganchar listeners.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('[data-hor-cell]') : null;
        if (btn) { abrir(btn); return; }
        if (e.target === modal || (e.target.closest && e.target.closest('[data-hcd-cerrar]'))) cerrar();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) cerrar();
    });
})();
