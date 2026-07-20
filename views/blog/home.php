<?php $paginaVista = 'blog-home'; ?>
<?php
$now = new \DateTime();
$hour = (int)$now->format('H');
if ($hour < 12)      $saludo = 'Buenos días';
elseif ($hour < 18)  $saludo = 'Buenas tardes';
else                 $saludo = 'Buenas noches';

$nombreCompleto = $_SESSION['blog_usuario']['nombre'] ?? 'Colaborador';
$nombreCorto    = explode(' ', trim($nombreCompleto))[0];
$esAdmin        = ($_SESSION['blog_usuario']['rol'] ?? '') === 'administrador';

$CAT = [
    'redaccion'  => ['nombre' => 'Redacción',  'desc' => 'Blog, noticias y contenido editorial del colegio.', 'icon' => 'fa-pen-nib',    'clase' => 'mh-card--blue',   'url' => '/dashboard/redaccion', 'n' => '01'],
    'suplencias' => ['nombre' => 'Suplencias', 'desc' => 'Organiza y consulta las suplencias del personal.',   'icon' => 'fa-user-clock', 'clase' => 'mh-card--amber',  'url' => '/dashboard/suplencias', 'n' => '02'],
    'usuarios'   => ['nombre' => 'Usuarios',   'desc' => 'Colaboradores, permisos y calendario de cumpleaños.','icon' => 'fa-users-gear', 'clase' => 'mh-card--purple', 'url' => '/dashboard/usuarios',   'n' => '03'],
];
$disponibles = $modulos ?? [];

$mesesEs = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$mesesLg = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$diasLg  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];

$hoyMD  = $now->format('m-d');
$todayDoy = (int)$now->format('z');

// Alex alternante en el hero (cambia en cada carga)
$heroAlexOpts = ['alex-toca', 'alex-tech', 'alex-point', 'alex-lee', 'alex-volley', 'alex-cientifico', 'alex-recicla', 'alex-medita'];
$heroAlex = $heroAlexOpts[array_rand($heroAlexOpts)];

// Eventos de cumpleaños para el calendario (clave MM-DD)
$bdayEvents = [];
// Lista completa ordenada por proximidad (incluye los ya pasados este año)
$bdaySorted = [];
foreach (($cumpleanosAll ?? []) as $c) {
    if (empty($c->fecha_nacimiento)) continue;
    $md = str_pad((string)$c->mes, 2, '0', STR_PAD_LEFT) . '-' . str_pad((string)$c->dia, 2, '0', STR_PAD_LEFT);
    $bdayEvents[] = ['md' => $md, 'nombre' => $c->nombre ?? '—', 'inicial' => mb_strtoupper(mb_substr($c->nombre ?? 'U', 0, 1)), 'avatar' => $c->avatar ?? ''];
    $bd  = \DateTime::createFromFormat('!Y-n-j', $now->format('Y') . "-{$c->mes}-{$c->dia}");
    $doy = $bd ? (int)$bd->format('z') : 999;
    $bdaySorted[] = [
        'nombre'  => $c->nombre ?? '—',
        'inicial' => mb_strtoupper(mb_substr($c->nombre ?? 'U', 0, 1)),
        'avatar'  => $c->avatar ?? '',
        'mes'     => (int)$c->mes,
        'dia'     => (int)$c->dia,
        'ord'     => ($doy - $todayDoy + 366) % 366,
        'hoy'     => ($md === $hoyMD),
    ];
}
usort($bdaySorted, fn($a, $b) => $a['ord'] <=> $b['ord']);
$cumpleHoy = array_filter($bdaySorted, fn($e) => $e['hoy']);
?>

<div class="admin-layout">

    <?php include __DIR__ . '/_sidebar.php'; ?>

    <div class="admin-main">

        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">Inicio</span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</button>
                </form>
            </div>
        </header>

        <main class="admin-content">

            <!-- HERO claro con tile de fecha -->
            <div class="mh-hero">
                <div class="mh-hero__left">
                    <div class="mh-datetile">
                        <span class="mh-datetile__dow"><?= mb_substr($diasLg[(int)$now->format('w')], 0, 3) ?></span>
                        <span class="mh-datetile__d"><?= $now->format('d') ?></span>
                        <span class="mh-datetile__mo"><?= $mesesEs[(int)$now->format('n') - 1] ?></span>
                    </div>
                    <div>
                        <p class="mh-hero__eyebrow"><i class="fa-solid fa-wand-magic-sparkles"></i> Intranet · Colegio Bilbao</p>
                        <h1 class="mh-hero__title"><?= htmlspecialchars($saludo) ?>, <?= htmlspecialchars($nombreCorto) ?></h1>
                        <p class="mh-hero__sub"><?= $diasLg[(int)$now->format('w')] ?>, <?= (int)$now->format('d') ?> de <?= $mesesLg[(int)$now->format('n') - 1] ?> · Tienes <strong><?= count($disponibles) ?> módulo<?= count($disponibles) !== 1 ? 's' : '' ?></strong></p>
                    </div>
                </div>
                <img src="/build/assets/img/alex/<?= $heroAlex ?>.png" alt="Alex" class="mh-hero__alex">
            </div>

            <?php if (!empty($cumpleHoy)): ?>
            <div class="mh-today-banner">
                <i class="fa-solid fa-cake-candles"></i>
                <p>¡Hoy cumple años <?= htmlspecialchars(implode(', ', array_map(fn($e) => $e['nombre'], $cumpleHoy))) ?>! No olvides felicitar. 🎉</p>
            </div>
            <?php endif; ?>

            <!-- MÓDULOS -->
            <p class="mh-section-label"><i class="fa-solid fa-grip"></i> Tus módulos</p>
            <?php if (empty($disponibles)): ?>
                <div class="mh-empty" style="background:#fff;border-radius:16px;border:1px solid #eef2f7;">Aún no tienes módulos asignados. Contacta a un administrador.</div>
            <?php else: ?>
            <div class="mh-grid">
                <?php foreach ($disponibles as $key): if (!isset($CAT[$key])) continue; $m = $CAT[$key]; ?>
                <a href="<?= $m['url'] ?>" class="mh-card <?= $m['clase'] ?>">
                    <span class="mh-card__num"><?= $m['n'] ?></span>
                    <div class="mh-card__icon"><i class="fa-solid <?= $m['icon'] ?>"></i></div>
                    <div class="mh-card__name"><?= htmlspecialchars($m['nombre']) ?></div>
                    <div class="mh-card__desc"><?= htmlspecialchars($m['desc']) ?></div>
                    <span class="mh-card__go">Entrar <i class="fa-solid fa-arrow-right"></i></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (in_array('usuarios', $disponibles, true)): ?>
            <!-- CALENDARIO + CUMPLEAÑOS -->
            <p class="mh-section-label"><i class="fa-solid fa-cake-candles"></i> Cumpleaños del equipo</p>
            <div class="mh-cal-row">
                <div class="mh-panel">
                    <div class="mh-panel__body">
                        <div class="bilbao-cal" id="mhCal" data-events='<?= htmlspecialchars(json_encode($bdayEvents), ENT_QUOTES) ?>'>
                            <div class="bilbao-cal__header">
                                <button type="button" class="bilbao-cal__nav" data-cal-prev aria-label="Mes anterior"><i class="fa-solid fa-chevron-left"></i></button>
                                <h3 class="bilbao-cal__month" data-cal-label>—</h3>
                                <button type="button" class="bilbao-cal__nav" data-cal-next aria-label="Mes siguiente"><i class="fa-solid fa-chevron-right"></i></button>
                            </div>
                            <div class="bilbao-cal__weekdays"><span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span><span>Dom</span></div>
                            <div class="bilbao-cal__grid" data-cal-grid></div>
                            <div class="bilbao-cal__legend"><span class="bilbao-cal__leg" data-type="cumple"><i></i> Cumpleaños</span></div>
                        </div>
                    </div>
                </div>

                <div class="mh-panel">
                    <div class="mh-panel__head">
                        <div class="mh-panel__title"><i class="fa-solid fa-gift"></i> Todos los cumpleaños</div>
                        <div class="mh-panel__sub">Ordenados por proximidad</div>
                    </div>
                    <div class="mh-panel__body" style="padding-top:12px;">
                        <div class="mh-bday-list" id="mhBdayList">
                            <?php if (empty($bdaySorted)): ?>
                                <div class="mh-empty">Aún no hay fechas de nacimiento registradas.</div>
                            <?php else: foreach ($bdaySorted as $i => $c):
                                $mes = $mesesLg[$c['mes'] - 1] ?? '';
                            ?>
                            <div class="mh-bday-item<?= $c['hoy'] ? ' is-today' : '' ?><?= $i >= 5 ? ' is-hidden' : '' ?>" data-bday>
                                <div class="mh-bday-ava"><?php if (!empty($c['avatar'])): ?><img src="<?= htmlspecialchars($c['avatar']) ?>" alt=""><?php else: ?><?= htmlspecialchars($c['inicial']) ?><?php endif; ?></div>
                                <div>
                                    <div class="mh-bday-name"><?= htmlspecialchars($c['nombre']) ?></div>
                                    <div class="mh-bday-date"><?= $c['dia'] ?> de <?= $mes ?></div>
                                </div>
                                <span class="mh-bday-chip<?= $c['hoy'] ? ' mh-bday-chip--today' : '' ?>"><?= $c['hoy'] ? '¡Hoy!' : $c['dia'] . ' ' . $mesesEs[$c['mes'] - 1] ?></span>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                    <?php if (count($bdaySorted) > 5): ?>
                    <div class="mh-pager" id="mhPager">
                        <span class="mh-pager__info" data-pager-info></span>
                        <div class="mh-pager__btns">
                            <button type="button" class="mh-pager__btn" data-pager-prev aria-label="Anterior"><i class="fa-solid fa-chevron-left"></i></button>
                            <button type="button" class="mh-pager__btn" data-pager-next aria-label="Siguiente"><i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php if (in_array('usuarios', $disponibles, true)): ?>
<?php endif; ?>
