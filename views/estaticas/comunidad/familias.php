<?php $paginaVista = 'estaticas-comunidad-familias'; ?>
<?php
// ── EVENTOS DINÁMICOS (desde el módulo Eventos del panel) ─────────
// El controlador pasa $eventosCal = [{fecha, tipo, titulo, desc}]. Si no hay, se usa un
// pequeño conjunto de muestra para no dejar el calendario vacío.
$tipoMeta = [
    'junta'      => ['Reunión',   '#4285f4', 'fa-people-group'],
    'evento'     => ['Evento',    '#aa2296', 'fa-palette'],
    'suspension' => ['Aviso',     '#e51022', 'fa-calendar-xmark'],
    'festivo'    => ['Festivo',   '#fc6722', 'fa-star'],
    'entrega'    => ['Académico', '#46bdc6', 'fa-file-lines'],
];
$fuente = $eventosCal ?? [];
if (empty($fuente)) {
    $fuente = [
        ['fecha' => '2026-07-28', 'tipo' => 'junta',      'titulo' => 'Junta de padres · Primaria', 'desc' => 'Auditorio principal, 18:00 h.'],
        ['fecha' => '2026-08-08', 'tipo' => 'evento',     'titulo' => 'Festival de arte y talento', 'desc' => 'Música, teatro y exposición de arte.'],
        ['fecha' => '2026-08-14', 'tipo' => 'suspension', 'titulo' => 'Suspensión de clases',        'desc' => 'Consejo técnico escolar.'],
        ['fecha' => '2026-08-21', 'tipo' => 'entrega',    'titulo' => 'Entrega de boletas',          'desc' => 'Consulta el horario con el titular.'],
    ];
}
// Calendario + tarjetas de avisos derivan de la misma fuente
$eventos = [];
$avisos  = [];
foreach ($fuente as $e) {
    $meta = $tipoMeta[$e['tipo']] ?? ['Aviso', '#4d8abb', 'fa-calendar-day'];
    $eventos[] = ['fecha' => $e['fecha'], 'tipo' => $e['tipo'], 'titulo' => $e['titulo']];
    $avisos[]  = [
        'titulo'    => $e['titulo'],
        'fecha'     => $e['fecha'],
        'categoria' => $meta[0],
        'color'     => $meta[1],
        'icono'     => $meta[2],
        'cuerpo'    => $e['desc'] ?? '',
    ];
}
$mesesEs = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
?>
<main id="main-content" class="fam">

    <!-- ── HERO ─────────────────────────────────── -->
    <section class="fam__hero">
        <?php $bg_scene = 'bosque'; $bg_colores = ['#46bdc6', '#4D8ABB', '#7DC6E5', '#374C69', '#F1C400']; include __DIR__ . '/_bg.php'; ?>
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

