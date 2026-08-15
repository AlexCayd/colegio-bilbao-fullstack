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
        // `desc` y `alcance` alimentan el modal de detalle del día; el calendario
        // solo usa tipo y nombre.
        $calMarks[] = [
            'ymd'    => $cursor->format('Y-m-d'),
            'tipo'   => $ev->tipo,
            'nombre' => $ev->titulo,
            'desc'   => trim(($ev->descripcion ?? '') . ($ev->niveles ? ' · ' . $ev->alcance() : '')),
        ];
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
            // ── Hero ──
            // Es la PORTADA del panel, no un tablero. Dentro del contenedor solo queda la
            // marca y el saludo: el tile de fecha, el contador de módulos y la línea de
            // cumpleaños se fueron porque competían con lo único que un hero tiene que
            // decir. La fecha ya la da el calendario de abajo, el número de módulos lo
            // dicen las tarjetas, y el cumpleaños del día bajó a su propia tira (abajo),
            // que es el único dato de los tres que no estaba en ningún otro sitio.
            $cumpleHoyL   = array_values($cumpleHoy);
            $verCumpleCta = in_array('usuarios', $disponibles, true);
            ?>

            <!-- HERO: bosque WebGL + marca y saludo en vidrio -->
            <div class="mh-hero">
                <?php /* Sin WebGL o con prefers-reduced-motion, BilbaoForest.init() devuelve
                          null y no pinta nada: el degradado de respaldo del CSS queda a la
                          vista, así que el hero nunca se ve roto. */ ?>
                <canvas id="mhForest" class="mh-hero__canvas" aria-hidden="true"></canvas>
                <div class="mh-hero__veil" aria-hidden="true"></div>

                <div class="mh-hero__top">
                    <div class="mh-hero__texto">
                        <p class="mh-hero__eyebrow">
                            <span class="mh-hero__brand">Intranet</span>
                            <span class="mh-hero__colegio">Bilbao</span>
                        </p>
                        <h1 class="mh-hero__title"><?= htmlspecialchars($saludo) ?>, <?= htmlspecialchars($nombreCorto) ?></h1>
                    </div>
                    <img src="/build/assets/img/alex/<?= $heroAlex ?>.png" alt="Alex" class="mh-hero__alex">
                </div><!-- /.mh-hero__top -->

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

            <?php if ($cumpleHoyL): $cn = count($cumpleHoyL); ?>
            <?php /* Cumpleaños de hoy. Bajó del hero, pero no se elimina: es el único de
                     los datos que había ahí arriba que no aparece en ningún otro sitio del
                     panel. Va pegado al calendario porque es su mismo tema. */ ?>
            <p class="mh-cumple-hoy">
                <i class="fa-solid fa-cake-candles"></i>
                Hoy cumple años <strong><?= htmlspecialchars($cumpleHoyL[0]['nombre']) ?></strong><?php
                    if ($cn > 1) echo ' y ' . ($cn - 1) . ($cn === 2 ? ' persona más' : ' personas más'); ?><?php
                    if ($verCumpleCta): ?> · <a href="/dashboard/usuarios/cumpleanos">Ver calendario</a><?php endif; ?>
            </p>
            <?php endif; ?>

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

<?php /* ── Detalle del día ──
          Pulsar un día del calendario abre esta ficha con lo que hay agendado.
          Antes las celdas eran botones sin acción: se veían los puntos de color pero
          no había forma de saber qué eran sin salir a Eventos. */ ?>
<div class="mh-dia" id="mhDiaModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="mhDiaTitulo">
    <div class="mh-dia__card" role="document">
        <header class="mh-dia__head">
            <div>
                <p class="mh-dia__eyebrow"><i class="fa-solid fa-calendar-day"></i> Agenda del día</p>
                <h2 class="mh-dia__titulo" id="mhDiaTitulo" data-dia-titulo></h2>
            </div>
            <button type="button" class="mh-dia__x" data-dia-cerrar aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </header>
        <ul class="mh-dia__lista" data-dia-lista></ul>
        <footer class="mh-dia__foot">
            <?php if (in_array('eventos', $disponibles, true)): ?>
            <a href="/dashboard/eventos" class="admin-btn admin-btn--ghost"><i class="fa-solid fa-calendar-days"></i> Ir a Eventos</a>
            <?php endif; ?>
            <button type="button" class="admin-btn admin-btn--primary" data-dia-cerrar>Entendido</button>
        </footer>
    </div>
</div>
