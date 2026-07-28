<?php $paginaVista = 'blog-home'; ?>
<?php
$now = new \DateTime();
$hour = (int)$now->format('H');
if ($hour < 12)      $saludo = 'Buenos días';
elseif ($hour < 18)  $saludo = 'Buenas tardes';
else                 $saludo = 'Buenas noches';

$nombreCompleto = $_SESSION['blog_usuario']['nombre'] ?? 'Colaborador';
$nombreCorto    = explode(' ', trim($nombreCompleto))[0];
$rolSesion      = $_SESSION['blog_usuario']['rol'] ?? '';
$esAdmin        = in_array($rolSesion, ['administrador', 'superadmin'], true);
$esSuper        = $rolSesion === 'superadmin';

// Catálogo y agrupación de módulos: fuente única compartida con el sidebar.
// El color NO se fija ahí: se asigna por posición al pintar, para que la primera
// tarjeta visible sea siempre la cyan aunque el usuario solo tenga un par de módulos.
require_once __DIR__ . '/_modulos.php';
$CAT = blog_modulos_catalogo();

$disponibles    = blog_modulos_disponibles($modulos ?? [], $esSuper);
$gruposVisibles = blog_modulos_visibles($disponibles);
// Contador global: la secuencia cromática atraviesa las categorías sin reiniciarse.
// mh-card--c1 es cyan y de ahí sigue el curso cromático (ver _admin-home.scss).
$colorIdx = 0;

$mesesEs = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$mesesLg = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$diasLg  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];

$hoyMD  = $now->format('m-d');
$todayDoy = (int)$now->format('z');

// Alex alternante en el hero (cambia en cada carga)
$heroAlexOpts = ['alex-toca', 'alex-tech', 'alex-point', 'alex-lee', 'alex-volley', 'alex-cientifico', 'alex-recicla', 'alex-medita'];
$heroAlex = $heroAlexOpts[array_rand($heroAlexOpts)];

// Marcas del calendario: los cumpleaños son recurrentes (clave MM-DD) y los eventos
// tienen fecha concreta (clave YYYY-MM-DD). El JS indexa ambas formas.
$calMarks = [];
// Lista completa ordenada por proximidad (incluye los ya pasados este año)
$bdaySorted = [];
foreach (($cumpleanosAll ?? []) as $c) {
    if (empty($c->fecha_nacimiento)) continue;
    $md = str_pad((string)$c->mes, 2, '0', STR_PAD_LEFT) . '-' . str_pad((string)$c->dia, 2, '0', STR_PAD_LEFT);
    $calMarks[] = ['md' => $md, 'tipo' => 'cumple', 'nombre' => $c->nombre ?? '—'];
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

// Eventos institucionales: se expande el rango fecha → fecha_fin a un día por marca
$tiposEvento = [];
foreach (($eventos ?? []) as $ev) {
    if (empty($ev->fecha)) continue;
    $tiposEvento[$ev->tipo] = true;
    $ini = new \DateTime($ev->fecha);
    $fin = !empty($ev->fecha_fin) ? new \DateTime($ev->fecha_fin) : clone $ini;
    if ($fin < $ini) $fin = clone $ini;
    $cursor = clone $ini;
    $guard  = 0;
    while ($cursor <= $fin && $guard++ < 90) {
        $calMarks[] = ['ymd' => $cursor->format('Y-m-d'), 'tipo' => $ev->tipo, 'nombre' => $ev->titulo];
        $cursor->modify('+1 day');
    }
}
$verCumples  = in_array('usuarios', $disponibles, true);
$verCalendar = $verCumples || !empty($eventos);
$legendLabel = \Model\Evento::TIPO_LABEL;
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
                    <?php /* Con cumpleaños hoy el tile de fecha se pone naranja: se ve desde el primer vistazo */ ?>
                    <div class="mh-datetile<?= !empty($cumpleHoy) ? ' mh-datetile--cumple' : '' ?>">
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

            <!-- MÓDULOS, agrupados por categoría -->
            <?php if (empty($gruposVisibles)): ?>
                <p class="mh-section-label"><i class="fa-solid fa-grip"></i> Tus módulos</p>
                <div class="mh-empty" style="background:#fff;border-radius:16px;border:1px solid #eef2f7;">Aún no tienes módulos asignados. Contacta a un administrador.</div>
            <?php else: foreach ($gruposVisibles as $g): ?>
            <section class="mh-cat">
                <p class="mh-section-label"><i class="fa-solid <?= $g['icon'] ?>"></i> <?= htmlspecialchars($g['label']) ?></p>
                <div class="mh-grid">
                    <?php foreach ($g['claves'] as $key): $m = $CAT[$key]; $colorIdx++; ?>
                    <a href="<?= $m['url'] ?>" class="mh-card mh-card--c<?= (($colorIdx - 1) % 10) + 1 ?>">
                        <span class="mh-card__num"><?= str_pad((string)$colorIdx, 2, '0', STR_PAD_LEFT) ?></span>
                        <div class="mh-card__icon"><i class="fa-solid <?= $m['icon'] ?>"></i></div>
                        <div class="mh-card__name"><?= htmlspecialchars($m['nombre']) ?></div>
                        <div class="mh-card__desc"><?= htmlspecialchars($m['desc']) ?></div>
                        <span class="mh-card__go">Entrar <i class="fa-solid fa-arrow-right"></i></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endforeach; endif; ?>

            <?php if ($verCalendar): ?>
            <!-- CALENDARIO (cumpleaños + eventos) + LISTA DE CUMPLEAÑOS -->
            <p class="mh-section-label"><i class="fa-regular fa-calendar-days"></i> Calendario del colegio</p>
            <div class="mh-cal-row<?= $verCumples ? '' : ' mh-cal-row--solo' ?>">
                <div class="mh-panel">
                    <div class="mh-panel__body">
                        <div class="bilbao-cal" id="mhCal" data-events='<?= htmlspecialchars(json_encode($calMarks), ENT_QUOTES) ?>'>
                            <div class="bilbao-cal__header">
                                <button type="button" class="bilbao-cal__nav" data-cal-prev aria-label="Mes anterior"><i class="fa-solid fa-chevron-left"></i></button>
                                <h3 class="bilbao-cal__month" data-cal-label>—</h3>
                                <button type="button" class="bilbao-cal__nav" data-cal-next aria-label="Mes siguiente"><i class="fa-solid fa-chevron-right"></i></button>
                            </div>
                            <div class="bilbao-cal__weekdays"><span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span><span>Dom</span></div>
                            <div class="bilbao-cal__grid" data-cal-grid></div>
                            <div class="bilbao-cal__legend">
                                <?php if ($verCumples): ?><span class="bilbao-cal__leg" data-type="cumple"><i></i> Cumpleaños</span><?php endif; ?>
                                <?php foreach ($tiposEvento as $t => $_): ?>
                                <span class="bilbao-cal__leg" data-type="<?= s($t) ?>"><i></i> <?= s($legendLabel[$t] ?? $t) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($verCumples): ?>
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
                            <div class="mh-bday-item<?= $c['hoy'] ? ' is-today' : '' ?><?= $i >= 5 ? ' is-hidden' : '' ?>" data-pager-item>
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
                    <div class="mh-pager" id="mhPager" data-pager data-pager-for="#mhBdayList" data-pager-per="5" data-pager-noun="colaboradores">
                        <span class="mh-pager__info" data-pager-info></span>
                        <div class="mh-pager__btns">
                            <button type="button" class="mh-pager__btn" data-pager-prev aria-label="Anterior"><i class="fa-solid fa-chevron-left"></i></button>
                            <button type="button" class="mh-pager__btn" data-pager-next aria-label="Siguiente"><i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>
