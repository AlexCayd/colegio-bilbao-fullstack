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
$esAdmin        = $rolSesion === 'administrador';

// Catálogo y agrupación de módulos: fuente única compartida con el sidebar.
// El color NO se fija ahí: se asigna por posición al pintar, para que la primera
// tarjeta visible sea siempre la cyan aunque el usuario solo tenga un par de módulos.
require_once __DIR__ . '/_modulos.php';
$CAT = blog_modulos_catalogo();

$disponibles    = $modulos ?? [];
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
// Próximos eventos: una entrada por evento (no por día), de hoy en adelante.
$hoyYmd = $now->format('Y-m-d');
$proxEventos = [];
foreach (($eventos ?? []) as $ev) {
    if (empty($ev->fecha)) continue;
    // Un evento sigue siendo "próximo" mientras no haya terminado su rango
    $finYmd = !empty($ev->fecha_fin) && $ev->fecha_fin >= $ev->fecha ? $ev->fecha_fin : $ev->fecha;
    if ($finYmd < $hoyYmd) continue;
    $ini = new \DateTime($ev->fecha);
    $proxEventos[] = [
        'titulo'  => $ev->titulo,
        'tipo'    => $ev->tipo,
        'ymd'     => $ev->fecha,
        'dia'     => (int)$ini->format('j'),
        'mes'     => $mesesEs[(int)$ini->format('n') - 1],
        'rango'   => $finYmd !== $ev->fecha,
        'finDia'  => $finYmd !== $ev->fecha ? (int)(new \DateTime($finYmd))->format('j') : null,
        'finMes'  => $finYmd !== $ev->fecha ? $mesesEs[(int)(new \DateTime($finYmd))->format('n') - 1] : null,
        'hoy'     => $ev->fecha <= $hoyYmd && $finYmd >= $hoyYmd,
    ];
}
usort($proxEventos, fn($a, $b) => strcmp($a['ymd'], $b['ymd']));

// Cumpleaños y eventos los ve TODO el usuario autenticado: son información de
// convivencia, no datos sensibles. El módulo `usuarios` sigue controlando quién
// puede *editar* colaboradores, que es distinto.
$verCumples  = !empty($bdaySorted);
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
            </div>
        </header>

        <main class="admin-content">

            <?php
            // ── Estado del día ──
            // El hero dejó de ser un saludo decorativo: es el panel donde se ve de un
            // vistazo qué pasa hoy y qué está pendiente. Las tarjetas a cero no se
            // pintan, así que un día tranquilo el hero vuelve a ser solo el saludo.
            $pend       = $pendientes ?? [];
            $evHoy      = array_values(array_filter($proxEventos, fn($e) => $e['hoy']));
            $cumpleHoyL = array_values($cumpleHoy);
            $verCumpleCta = in_array('usuarios', $disponibles, true);
            $hayEstado  = $cumpleHoyL || $evHoy
                          || ($pend['notificaciones'] ?? 0) || ($pend['coberturas'] ?? 0) || ($pend['sinSuplente'] ?? 0);
            ?>

            <!-- HERO: saludo + estado del día -->
            <div class="mh-hero<?= $hayEstado ? ' has-estado' : '' ?>">
                <div class="mh-hero__top">
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
                </div><!-- /.mh-hero__top -->

                <?php if ($hayEstado): ?>
                <div class="mh-estado">

                    <?php if ($cumpleHoyL): ?>
                    <?php $cn = count($cumpleHoyL); ?>
                    <a class="mh-est mh-est--cumple<?= $verCumpleCta ? '' : ' is-static' ?>"
                       <?= $verCumpleCta ? 'href="/dashboard/usuarios/cumpleanos"' : '' ?>>
                        <span class="mh-est__avas">
                            <?php foreach (array_slice($cumpleHoyL, 0, 3) as $c): ?>
                            <span class="mh-cumple__ava" title="<?= htmlspecialchars($c['nombre']) ?>">
                                <?php if (!empty($c['avatar'])): ?><img src="<?= htmlspecialchars($c['avatar']) ?>" alt=""><?php else: ?><?= htmlspecialchars($c['inicial']) ?><?php endif; ?>
                            </span>
                            <?php endforeach; ?>
                        </span>
                        <span class="mh-est__txt">
                            <span class="mh-est__label"><i class="fa-solid fa-cake-candles"></i> Cumpleaños</span>
                            <span class="mh-est__dato"><?= htmlspecialchars($cumpleHoyL[0]['nombre']) ?><?= $cn > 1 ? ' y ' . ($cn - 1) . ' más' : '' ?></span>
                        </span>
                    </a>
                    <?php endif; ?>

                    <?php if (!empty($pend['notificaciones'])): ?>
                    <a class="mh-est mh-est--notif" href="/dashboard/notificaciones">
                        <span class="mh-est__ico"><i class="fa-regular fa-bell"></i></span>
                        <span class="mh-est__txt">
                            <span class="mh-est__label">Sin leer</span>
                            <span class="mh-est__dato"><?= (int)$pend['notificaciones'] ?> notificación<?= $pend['notificaciones'] == 1 ? '' : 'es' ?></span>
                        </span>
                    </a>
                    <?php endif; ?>

                    <?php if (!empty($pend['coberturas'])): ?>
                    <a class="mh-est mh-est--cobertura" href="/dashboard/suplencias/mis-coberturas">
                        <span class="mh-est__ico"><i class="fa-solid fa-clipboard-check"></i></span>
                        <span class="mh-est__txt">
                            <span class="mh-est__label">Por confirmar</span>
                            <span class="mh-est__dato"><?= (int)$pend['coberturas'] ?> cobertura<?= $pend['coberturas'] == 1 ? '' : 's' ?></span>
                        </span>
                    </a>
                    <?php endif; ?>

                    <?php if (!empty($pend['sinSuplente'])): ?>
                    <a class="mh-est mh-est--suplente" href="/dashboard/suplencias">
                        <span class="mh-est__ico"><i class="fa-solid fa-user-clock"></i></span>
                        <span class="mh-est__txt">
                            <span class="mh-est__label">Sin suplente</span>
                            <span class="mh-est__dato"><?= (int)$pend['sinSuplente'] ?> ausencia<?= $pend['sinSuplente'] == 1 ? '' : 's' ?></span>
                        </span>
                    </a>
                    <?php endif; ?>

                    <?php if ($evHoy): ?>
                    <a class="mh-est mh-est--evento" href="/dashboard/eventos">
                        <span class="mh-est__ico"><i class="fa-solid fa-calendar-day"></i></span>
                        <span class="mh-est__txt">
                            <span class="mh-est__label">Hoy</span>
                            <span class="mh-est__dato"><?= htmlspecialchars($evHoy[0]['titulo']) ?><?= count($evHoy) > 1 ? ' y ' . (count($evHoy) - 1) . ' más' : '' ?></span>
                        </span>
                    </a>
                    <?php endif; ?>

                </div>
                <?php endif; ?>
            </div>

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
                            <div class="bilbao-cal__weekdays"><span>Dom</span><span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span></div>
                            <div class="bilbao-cal__grid" data-cal-grid></div>
                            <div class="bilbao-cal__legend">
                                <?php if ($verCumples): ?><span class="bilbao-cal__leg" data-type="cumple"><i></i> Cumpleaños</span><?php endif; ?>
                                <?php foreach ($tiposEvento as $t => $_): ?>
                                <span class="bilbao-cal__leg" data-type="<?= s($t) ?>"><i></i> <?= s($legendLabel[$t] ?? $t) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php /* Próximos eventos, bajo el calendario. El calendario marca los
                                 días pero no dice qué pasa en ellos sin pasar el ratón. */ ?>
                        <?php if ($proxEventos): ?>
                        <div class="mh-next">
                            <div class="mh-next__label"><i class="fa-regular fa-clock"></i> Próximos eventos</div>
                            <div class="mh-next__list" id="mhNextList">
                                <?php foreach ($proxEventos as $i => $e): ?>
                                <div class="mh-next__item<?= $e['hoy'] ? ' is-today' : '' ?><?= $i >= 8 ? ' is-hidden' : '' ?>" data-pager-item>
                                    <span class="mh-next__date" data-type="<?= s($e['tipo']) ?>">
                                        <strong><?= $e['dia'] ?></strong>
                                        <small><?= s($e['mes']) ?></small>
                                    </span>
                                    <div class="mh-next__body">
                                        <div class="mh-next__title"><?= s($e['titulo']) ?></div>
                                        <div class="mh-next__meta">
                                            <?= s($legendLabel[$e['tipo']] ?? $e['tipo']) ?>
                                            <?php if ($e['rango']): ?> · hasta el <?= $e['finDia'] ?> <?= s($e['finMes']) ?><?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($e['hoy']): ?><span class="mh-next__chip">Hoy</span><?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($proxEventos) > 8): ?>
                            <div class="mh-pager" data-pager data-pager-for="#mhNextList" data-pager-per="8" data-pager-noun="eventos">
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
                            <div class="mh-bday-item<?= $c['hoy'] ? ' is-today' : '' ?><?= $i >= 10 ? ' is-hidden' : '' ?>" data-pager-item>
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
                    <?php /* El paginador se pinta siempre: cuántos caben lo decide el JS
                             midiendo la columna (blog-home.js), así que el servidor no
                             puede saber si hará falta. Con una sola página se oculta solo. */ ?>
                    <div class="mh-pager" id="mhPager" data-pager data-pager-for="#mhBdayList" data-pager-per="10" data-pager-noun="colaboradores">
                        <span class="mh-pager__info" data-pager-info></span>
                        <div class="mh-pager__btns">
                            <button type="button" class="mh-pager__btn" data-pager-prev aria-label="Anterior"><i class="fa-solid fa-chevron-left"></i></button>
                            <button type="button" class="mh-pager__btn" data-pager-next aria-label="Siguiente"><i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php /* Rebote por permisos: si una notificación apunta a un módulo que el usuario
         ya no tiene, requireModulo() lo trae aquí. Antes el rebote era mudo y
         parecía que el enlace estaba roto. */ ?>
<?php if (isset($_GET['sinacceso'])):
    $_modSin = blog_modulos_catalogo()[$_GET['sinacceso']]['nombre'] ?? null;
?>
<div class="at-wrap" id="alexToast">
    <span class="at-stripe" style="background:#f5b400;"></span>
    <img src="/build/assets/img/alex/alex-point.png" alt="Alex" class="at-alex">
    <div class="at-body">
        <p class="at-title"><i class="fa-solid fa-lock" style="color:#f5b400;"></i> Sin acceso a ese módulo</p>
        <p class="at-msg">
            <?php if ($_modSin): ?>
                Esa página pertenece a <strong><?= s($_modSin) ?></strong> y tu cuenta no tiene ese módulo.
            <?php else: ?>
                Esa página pertenece a un módulo al que tu cuenta no tiene acceso.
            <?php endif; ?>
            Si crees que deberías entrar, pídeselo a un administrador.
        </p>
    </div>
    <button type="button" class="at-close" onclick="cerrarAlexToast()"><i class="fa-solid fa-xmark"></i></button>
    <span class="at-bar" style="background:#f5b400;"></span>
</div>
<?php endif; ?>
