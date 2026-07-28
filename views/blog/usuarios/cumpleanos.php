<?php $paginaVista = 'blog-usuarios-cumpleanos'; ?>
<?php
$mesesEs = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$mesesAb = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

$ahora    = new \DateTime();
$hoyMD    = $ahora->format('m-d');
$todayDoy = (int)$ahora->format('z');

// Eventos del calendario (clave mes-día, ignora el año) + lista ordenada por proximidad
$eventos = [];
$lista   = [];
foreach (($cumpleanos ?? []) as $c) {
    if (empty($c->fecha_nacimiento)) continue;
    $md = str_pad((string)$c->mes, 2, '0', STR_PAD_LEFT) . '-' . str_pad((string)$c->dia, 2, '0', STR_PAD_LEFT);
    $eventos[] = [
        'md'      => $md,
        'nombre'  => $c->nombre ?? '—',
        'inicial' => mb_strtoupper(mb_substr($c->nombre ?? 'U', 0, 1)),
        'avatar'  => $c->avatar ?? '',
        'hoy'     => ($md === $hoyMD),
    ];
    $bd  = \DateTime::createFromFormat('!Y-n-j', $ahora->format('Y') . "-{$c->mes}-{$c->dia}");
    $doy = $bd ? (int)$bd->format('z') : 999;
    $lista[] = [
        'nombre'  => $c->nombre ?? '—',
        'inicial' => mb_strtoupper(mb_substr($c->nombre ?? 'U', 0, 1)),
        'avatar'  => $c->avatar ?? '',
        'mes'     => (int)$c->mes,
        'dia'     => (int)$c->dia,
        'ord'     => ($doy - $todayDoy + 366) % 366,
        'hoy'     => ($md === $hoyMD),
    ];
}
usort($lista, fn($a, $b) => $a['ord'] <=> $b['ord']);
$cumpleHoy = array_values(array_filter($lista, fn($e) => $e['hoy']));
$total     = count($lista);
$porPagina = 5;
?>

<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">

        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">Cumpleaños del equipo</span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn">
                        <i class="fa-solid fa-right-from-bracket"></i> Salir
                    </button>
                </form>
            </div>
        </header>

        <main class="admin-content">

            <?php if (!empty($cumpleHoy)): ?>
            <div class="cb-today">
                <span class="cb-today__tile">
                    <span class="cb-today__d"><?= (int)$ahora->format('d') ?></span>
                    <span class="cb-today__mo"><?= $mesesAb[(int)$ahora->format('n') - 1] ?></span>
                </span>
                <div class="cb-today__body">
                    <strong><i class="fa-solid fa-cake-candles"></i> ¡Hoy cumple años <?= htmlspecialchars(implode(', ', array_map(fn($e) => $e['nombre'], $cumpleHoy))) ?>!</strong>
                    <span>No olvides felicitar. 🎉</span>
                </div>
            </div>
            <?php endif; ?>

            <div class="cb-wrap">
                <div class="bilbao-cal" id="cbCalendar"
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
                        <span class="bilbao-cal__leg" data-type="cumple"><i></i> Cumpleaños</span>
                    </div>
                </div>

                <div class="cb-side">
                    <div class="cb-side__head">
                        <div class="cb-side__title">
                            <i class="fa-solid fa-cake-candles"></i> <span data-cb-title>Todos los cumpleaños</span>
                        </div>
                        <div class="cb-side__sub">
                            <span data-cb-sub><?= $total ?> colaborador<?= $total !== 1 ? 'es' : '' ?> · por proximidad</span>
                            <button type="button" class="cb-back" data-cb-back hidden>
                                <i class="fa-solid fa-arrow-left"></i> Ver todos
                            </button>
                        </div>
                    </div>

                    <div class="cb-list" data-cb-list id="cbList">
                        <?php if (empty($lista)): ?>
                            <div class="cb-empty">Aún no hay fechas de nacimiento registradas.<br>Agrégalas al editar cada usuario.</div>
                        <?php else: foreach ($lista as $i => $c):
                            $mes = $mesesEs[$c['mes'] - 1] ?? '';
                        ?>
                        <div class="cb-item<?= $c['hoy'] ? ' is-today' : '' ?><?= $i >= $porPagina ? ' is-hidden' : '' ?>" data-pager-item>
                            <div class="cb-ava"><?php if (!empty($c['avatar'])): ?><img src="<?= htmlspecialchars($c['avatar']) ?>" alt=""><?php else: ?><?= htmlspecialchars($c['inicial']) ?><?php endif; ?></div>
                            <div>
                                <div class="cb-name"><?= htmlspecialchars($c['nombre']) ?></div>
                                <div class="cb-date"><?= $c['dia'] ?> de <?= $mes ?></div>
                            </div>
                            <span class="cb-chip<?= $c['hoy'] ? ' cb-chip--today' : '' ?>"><?= $c['hoy'] ? '¡Hoy!' : $c['dia'] . ' ' . $mesesAb[$c['mes'] - 1] ?></span>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>

                    <?php if ($total > $porPagina): ?>
                    <div class="cb-pager" data-pager data-pager-for="#cbList" data-pager-per="<?= $porPagina ?>" data-pager-noun="colaboradores">
                        <span class="cb-pager__info" data-pager-info></span>
                        <div class="cb-pager__btns">
                            <button type="button" class="cb-pager__btn" data-pager-prev aria-label="Anterior"><i class="fa-solid fa-chevron-left"></i></button>
                            <button type="button" class="cb-pager__btn" data-pager-next aria-label="Siguiente"><i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>
