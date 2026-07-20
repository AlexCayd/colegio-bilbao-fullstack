<?php $paginaVista = 'estaticas-comunidad-familias'; ?>
<?php
// ── AVISOS DE PRUEBA ──────────────────────────────────────────────
// TODO: volver dinámico desde el panel de administración.
$avisos = [
    [
        'titulo'    => 'Junta de padres · Primaria',
        'fecha'     => '2026-07-24',
        'categoria' => 'Reunión',
        'color'     => '#4285f4',
        'icono'     => 'fa-people-group',
        'cuerpo'    => 'Los invitamos a la junta informativa del ciclo escolar. Auditorio principal, 9:00 h.',
    ],
    [
        'titulo'    => 'Festival de arte y talento',
        'fecha'     => '2026-08-08',
        'categoria' => 'Evento',
        'color'     => '#aa2296',
        'icono'     => 'fa-palette',
        'cuerpo'    => '¡Ven a disfrutar del talento de nuestros estudiantes! Música, teatro y exposición de arte.',
    ],
    [
        'titulo'    => 'Suspensión de clases',
        'fecha'     => '2026-08-14',
        'categoria' => 'Aviso',
        'color'     => '#e51022',
        'icono'     => 'fa-calendar-xmark',
        'cuerpo'    => 'No habrá clases por consejo técnico escolar. Reanudamos actividades el lunes siguiente.',
    ],
    [
        'titulo'    => 'Entrega de boletas',
        'fecha'     => '2026-08-21',
        'categoria' => 'Académico',
        'color'     => '#46bdc6',
        'icono'     => 'fa-file-lines',
        'cuerpo'    => 'Consulta el avance de tus hijos con los profesores titulares en el horario asignado.',
    ],
    [
        'titulo'    => 'Semana de la lectura',
        'fecha'     => '2026-09-01',
        'categoria' => 'Evento',
        'color'     => '#fc6722',
        'icono'     => 'fa-book-open',
        'cuerpo'    => 'Cuentacuentos, intercambio de libros y visitas a la biblioteca durante toda la semana.',
    ],
    [
        'titulo'    => 'Campaña de reciclaje',
        'fecha'     => '2026-09-05',
        'categoria' => 'Comunidad',
        'color'     => '#2e6fc7',
        'icono'     => 'fa-recycle',
        'cuerpo'    => 'Trae tus materiales reciclables y súmate al cuidado de nuestro planeta. ¡Cada familia cuenta!',
    ],
];

// ── EVENTOS DEL CALENDARIO ────────────────────────────────────────
// TODO: volver dinámico. tipo: festivo | evento | junta | suspension | entrega
$eventos = [
    ['fecha' => '2026-07-24', 'tipo' => 'junta',      'titulo' => 'Junta de padres · Primaria'],
    ['fecha' => '2026-08-08', 'tipo' => 'evento',     'titulo' => 'Festival de arte y talento'],
    ['fecha' => '2026-08-14', 'tipo' => 'suspension', 'titulo' => 'Suspensión de clases'],
    ['fecha' => '2026-08-17', 'tipo' => 'festivo',    'titulo' => 'Regreso a clases'],
    ['fecha' => '2026-08-21', 'tipo' => 'entrega',    'titulo' => 'Entrega de boletas'],
    ['fecha' => '2026-09-01', 'tipo' => 'evento',     'titulo' => 'Semana de la lectura'],
    ['fecha' => '2026-09-05', 'tipo' => 'evento',     'titulo' => 'Campaña de reciclaje'],
    ['fecha' => '2026-09-16', 'tipo' => 'festivo',    'titulo' => 'Día de la Independencia'],
    ['fecha' => '2026-09-28', 'tipo' => 'junta',      'titulo' => 'Junta de padres · Secundaria'],
];
$mesesEs   = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
?>
<main id="main-content" class="fam">

    <!-- ── HERO ─────────────────────────────────── -->
    <section class="fam__hero">
        <?php $bg_colores = ['#46bdc6', '#4d8abb', '#2e6fc7', '#6fb1d8', '#4267ac']; include __DIR__ . '/_bg.php'; ?>
        <div class="fam__hero-inner">
            <div class="fam__hero-text" data-fam-reveal>
                <span class="fam__eyebrow"><i class="fa-solid fa-people-roof"></i> Familias Bilbao</span>
                <h1 class="fam__title">¡Bienvenidas,<br>familias!</h1>
                <p class="fam__lead">
                    Aquí encontrarás los avisos importantes y el calendario del colegio,
                    para que nunca te pierdas de nada.
                </p>
                <div class="fam__hero-chips">
                    <span class="fam__chip"><i class="fa-solid fa-bullhorn"></i> Avisos</span>
                    <span class="fam__chip"><i class="fa-solid fa-calendar-days"></i> Calendario</span>
                </div>
            </div>
            <div class="fam__hero-art" data-fam-reveal>
                <span class="fam__hero-halo"></span>
                <img src="/build/assets/img/alex/fam-alex-surp.png" alt="Familia Alex" class="fam__hero-img" loading="lazy">
            </div>
        </div>
    </section>

    <!-- ── AVISOS ───────────────────────────────── -->
    <section class="fam__section">
        <div class="fam__section-head">
            <h2 class="fam__section-title"><i class="fa-solid fa-bullhorn"></i> Avisos del colegio</h2>
            <p class="fam__section-sub">Lo último que necesitas saber esta temporada.</p>
        </div>

        <div class="fam__avisos">
            <?php foreach ($avisos as $a):
                $ts  = strtotime($a['fecha']);
                $dia = date('d', $ts);
                $mes = $mesesEs[(int)date('n', $ts) - 1];
            ?>
            <article class="fam-aviso" style="--aviso-color: <?= htmlspecialchars($a['color']) ?>;">
                <div class="fam-aviso__date">
                    <span class="fam-aviso__day"><?= $dia ?></span>
                    <span class="fam-aviso__month"><?= mb_substr($mes, 0, 3) ?></span>
                </div>
                <div class="fam-aviso__body">
                    <span class="fam-aviso__pill"><i class="fa-solid <?= htmlspecialchars($a['icono']) ?>"></i> <?= htmlspecialchars($a['categoria']) ?></span>
                    <h3 class="fam-aviso__title"><?= htmlspecialchars($a['titulo']) ?></h3>
                    <p class="fam-aviso__text"><?= htmlspecialchars($a['cuerpo']) ?></p>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ── CALENDARIO ───────────────────────────── -->
    <section class="fam__section fam__section--cal">
        <div class="fam__section-head">
            <h2 class="fam__section-title"><i class="fa-solid fa-calendar-days"></i> Calendario escolar</h2>
            <p class="fam__section-sub">Haz clic en un día marcado para ver los detalles.</p>
        </div>

        <div class="fam__cal-wrap">
            <div class="bilbao-cal" id="famCalendar"
                 data-events='<?= htmlspecialchars(json_encode($eventos), ENT_QUOTES) ?>'>
                <div class="bilbao-cal__header">
                    <button type="button" class="bilbao-cal__nav" data-cal-prev aria-label="Mes anterior"><i class="fa-solid fa-chevron-left"></i></button>
                    <h3 class="bilbao-cal__month" data-cal-label>—</h3>
                    <button type="button" class="bilbao-cal__nav" data-cal-next aria-label="Mes siguiente"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
                <div class="bilbao-cal__weekdays">
                    <span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span><span>Dom</span>
                </div>
                <div class="bilbao-cal__grid" data-cal-grid></div>
                <div class="bilbao-cal__legend">
                    <span class="bilbao-cal__leg" data-type="festivo"><i></i> Festivo</span>
                    <span class="bilbao-cal__leg" data-type="evento"><i></i> Evento</span>
                    <span class="bilbao-cal__leg" data-type="junta"><i></i> Junta</span>
                    <span class="bilbao-cal__leg" data-type="entrega"><i></i> Entrega</span>
                    <span class="bilbao-cal__leg" data-type="suspension"><i></i> Suspensión</span>
                </div>
            </div>

            <aside class="fam__cal-detail" data-cal-detail>
                <div class="fam__cal-detail-head">
                    <h4 class="fam__cal-detail-title">Próximos eventos</h4>
                    <button type="button" class="fam__cal-reset" data-cal-reset hidden>
                        <i class="fa-solid fa-arrow-left"></i> Próximos
                    </button>
                </div>
                <ul class="fam__cal-detail-list" data-cal-list></ul>
            </aside>
        </div>
    </section>

</main>

