<?php $paginaVista = 'blog-usuarios-detalle'; ?>
<?php
/**
 * Ficha de lectura de un colaborador.
 *
 * @var \Model\UsuarioBlog $u           registro completo (+ total_articulos)
 * @var string[]           $tipos       tipo_personal explotado
 * @var bool               $esDocente
 * @var array              $contenido   artículos + noticias
 * @var array              $ausencias   Suplencia[]  (sus propias ausencias)
 * @var array              $conteos     por estado
 * @var array              $coberturas  SuplenciaHora[]  (lo que ha cubierto)
 * @var array              $porEstadoHora
 * @var array              $swaps
 * @var array              $porEstadoSwap
 * @var bool               $acotado     el alcance por nivel recorta lo que se ve
 * @var array|null         $horario     claves de datosHorarioProfesor()
 * @var bool               $esPropio    true en /dashboard/perfil (es uno mismo)
 *
 * SECCIONES APILADAS, no pestañas: cuáles existen depende del tipo de personal (un
 * administrativo no tiene horario ni suplencias), y unas pestañas cuyo número cambia
 * según a quién mires obligan a aprender la excepción. Además así funciona Ctrl-F,
 * se puede imprimir y no hace falta JS.
 *
 * ── DOS RUTAS, UNA PLANTILLA ──
 * `/dashboard/usuarios/detalle?id=N`  → ficha de OTRA persona (solo lectura)
 * `/dashboard/perfil`                 → la de uno mismo, con «Mi cuenta» editable arriba
 *
 * Antes «Mi perfil» era un formulario suelto que no decía nada de lo que esa persona
 * hace, y la ficha —que sí— solo la abría quien coordina: un profesor no tenía dónde ver
 * su propio histórico. Los datos salen de `BlogController::datosFicha()` en los dos
 * casos, así que las dos pantallas no pueden divergir.
 *
 * ⚠️ `$paginaVista` es el MISMO en las dos rutas, y tiene que serlo: `blog-usuarios-detalle`
 * está en la lista de `body[data-page]` de `src/scss/admin/_admin-horarios.scss`, sin la
 * cual la rejilla del horario sale como tabla desnuda.
 */
$esPropio    = $esPropio ?? false;
$puedeEditar = ($_SESSION['blog_usuario']['rol'] ?? '') === 'administrador';

$avatarColors = ['#4D8ABB', '#374C69', '#38A169', '#E67E22', '#9B59B6', '#319795'];
$color        = $avatarColors[$u->id % count($avatarColors)];
$inicial      = strtoupper(mb_substr($u->nombre, 0, 1));

$nivColor = ['Maternal' => '#fc6722', 'Kinder' => '#f5b400', 'Primaria' => '#8ac926',
             'Secundaria' => '#46bdc6', 'Bachillerato' => '#4267ac'];
$nivelesU = array_filter(array_map('trim', explode(',', (string)$u->niveles)));

// `usuarios.niveles` significa DOS cosas según el puesto, y decirlo mal inventa un
// dato: en un profesor son los niveles que IMPARTE (vacío = se deducen de sus clases),
// en un directivo los que GESTIONA (vacío = todo el colegio). En prefectura y
// administrativos la columna es NULL por normalizarNiveles(), así que ni se pinta.
$esDirectivo = in_array('directivo', $tipos, true);
$mostrarNiv  = $esDocente || $esDirectivo;

$modulosU = ($u->rol === 'administrador')
    ? []
    : array_filter(array_map('trim', explode(',', (string)$u->modulos)));

// Rótulos de los módulos. `true` porque describen los permisos de la persona MIRADA, no
// los de quien mira. Nombre propio y no `$_catMods`: esa la define `_sidebar.php` —que se
// incluye más abajo— con el catálogo adaptado al usuario en sesión, y se pisarían.
require_once __DIR__ . '/../_modulos.php';
$modCat = blog_modulos_catalogo(true);

// El horario puede venir a null (no docente) o con `tramos` vacío (docente sin clases).
$tramos        = $horario['tramos']        ?? [];
$rejilla       = $horario['rejilla']       ?? [];
$discrepantes  = $horario['discrepantes']  ?? [];
$nivelesHor    = $horario['niveles']       ?? [];
$ocupadoPorDia = $horario['ocupadoPorDia'] ?? [];
$totalClases   = $horario['totalClases']   ?? 0;
$vista         = 'profesor';   // contrato de _grid.php

$minOcupados  = array_sum($ocupadoPorDia);
$horasEnteras = intdiv($minOcupados, 60);
$restoMin     = $minOcupados % 60;

$hayHorario = $esDocente && !empty($tramos);
$haySupl    = $esDocente && (!empty($ausencias) || !empty($coberturas));
$haySwaps   = $esDocente && !empty($swaps);
$hayCont    = !empty($contenido);

// Coberturas que no se cumplieron: el dato existe desde que «no se cubrió» dejó de
// borrar al suplente, y es justo lo que no se ve en ninguna otra pantalla por persona.
$noCubiertas = (int)($porEstadoHora['no_cubierta'] ?? 0);
$validadas   = (int)($porEstadoHora['validada'] ?? 0);
$agendadas   = (int)($porEstadoHora['agendada'] ?? 0);

$fmt = fn($f) => $f ? date('d/m/Y', strtotime((string)$f)) : '—';
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title"><?= $esPropio ? 'Mi perfil' : 'Ficha del colaborador' ?></span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">

            <!-- ── Identidad ────────────────────────────────────────────────── -->
            <div class="ufi-hero">
                <div class="ufi-hero__ava" style="background:<?= s($color) ?>;">
                    <?php if ($u->avatar): ?>
                        <img src="<?= s($u->avatar) ?>" alt="" onerror="this.parentElement.textContent='<?= s($inicial) ?>'">
                    <?php else: ?><?= s($inicial) ?><?php endif; ?>
                </div>
                <div class="ufi-hero__body">
                    <h1 class="ufi-hero__name"><?= s($u->nombre) ?></h1>
                    <p class="ufi-hero__mail">
                        <a href="mailto:<?= s($u->email) ?>"><i class="fa-regular fa-envelope"></i> <?= s($u->email) ?></a>
                    </p>
                    <div class="ufi-hero__chips">
                        <span class="ufi-rol<?= $u->rol === 'administrador' ? ' ufi-rol--admin' : '' ?>">
                            <i class="fa-solid <?= $u->rol === 'administrador' ? 'fa-shield-halved' : 'fa-user' ?>"></i>
                            <?= $u->rol === 'administrador' ? 'Admin' : 'Usuario' ?>
                        </span>
                        <?php foreach ($tipos as $t): ?>
                        <span class="admin-badge"><?= s(\Model\UsuarioBlog::TIPO_LABEL[$t] ?? ucfirst($t)) ?></span>
                        <?php endforeach; ?>
                        <?php if (!$tipos): ?>
                        <span class="ufi-nil">Sin tipo de personal</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($puedeEditar): ?>
                <?php /* Del COLABORADOR, no del panel: la acción vive junto a la identidad
                         sobre la que actúa, no arriba a la derecha con la campana. */ ?>
                <div class="ufi-hero__acts">
                    <a href="/dashboard/usuarios/editar?id=<?= (int)$u->id ?>" class="admin-btn admin-btn--ghost admin-btn--sm">
                        <i class="fa-solid fa-pen"></i> Editar
                    </a>
                </div>
                <?php endif; ?>
                <dl class="ufi-hero__meta">
                    <div><dt>Último acceso</dt><dd><?= $fmt($u->ultimo_acceso ?? null) ?></dd></div>
                    <?php if ($esDocente): ?>
                    <div><dt>Suplencias</dt><dd><?= $esDocente ? (int)($conteos['total'] ?? 0) : 0 ?></dd></div>
                    <div><dt>Coberturas</dt><dd><?= count($coberturas) ?></dd></div>
                    <?php endif; ?>
                </dl>
            </div>

            <?php if ($acotado): ?>
            <?php /* Una dirección de nivel ve solo lo suyo. Un listado filtrado que no lo
                      dice se lee como el dato completo, que es peor que no enseñarlo. */ ?>
            <p class="ufi-alcance">
                <i class="fa-solid fa-filter"></i>
                Las suplencias de esta ficha están acotadas a los niveles que gestionas.
            </p>
            <?php endif; ?>

            <!-- ── Subnav de anclas ─────────────────────────────────────────── -->
            <?php /* Enlaces, no pestañas: el conjunto varía según el puesto y así el
                      salto funciona sin JS. */ ?>
            <nav class="ufi-nav" aria-label="Secciones de la ficha">
                <?php if ($esPropio): ?><a href="#ufi-cuenta"><i class="fa-solid fa-user-pen"></i> Mi cuenta</a><?php endif; ?>
                <a href="#ufi-perfil"><i class="fa-solid fa-id-card"></i> Perfil</a>
                <?php if ($hayHorario): ?><a href="#ufi-horario"><i class="fa-regular fa-calendar"></i> Horario</a><?php endif; ?>
                <?php if ($esDocente): ?><a href="#ufi-suplencias"><i class="fa-solid fa-user-clock"></i> Suplencias</a><?php endif; ?>
                <?php if ($haySwaps): ?><a href="#ufi-swaps"><i class="fa-solid fa-right-left"></i> Intercambios</a><?php endif; ?>
                <?php if ($hayCont): ?><a href="#ufi-contenido"><i class="fa-regular fa-newspaper"></i> Redacción</a><?php endif; ?>
            </nav>

            <?php /* Los campos editables van PRIMERO y solo sobre uno mismo: es lo único
                      accionable de la pantalla, y debajo empieza lo que solo se consulta. */ ?>
            <?php if ($esPropio) include __DIR__ . '/_perfil-cuenta.php'; ?>

            <!-- ── Perfil: niveles, módulos, disponibilidad ─────────────────── -->
            <section class="admin-panel" id="ufi-perfil">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title"><i class="fa-solid fa-id-card"></i> Perfil y accesos</h2>
                </div>
                <div class="ufi-cols">

                    <?php if ($mostrarNiv): ?>
                    <div class="ufi-field">
                        <h3 class="ufi-field__t">
                            <?= $esDirectivo ? 'Niveles que gestiona' : 'Niveles que imparte' ?>
                        </h3>
                        <?php if ($nivelesU): ?>
                        <div class="ufi-chips">
                            <?php foreach ($nivelesU as $n): ?>
                            <span class="per-nivel" style="--c:<?= s($nivColor[$n] ?? '#94a3b8') ?>;"><?= s($n) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <?php /* Vacío NO significa lo mismo en los dos puestos, y confundirlos
                                  es grave del lado de dirección: ahí es alcance total. */ ?>
                        <p class="ufi-field__hint">
                            <?php if ($esDirectivo): ?>
                            <strong>Todo el colegio.</strong> Sin niveles declarados, una dirección ve los datos
                            de los cinco niveles.
                            <?php else: ?>
                            Sin declarar: se deducen de las clases que tiene cargadas.
                            <?php endif; ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($esDocente): ?>
                    <div class="ufi-field">
                        <h3 class="ufi-field__t">Suplencias</h3>
                        <?php $suple = (int)($u->puede_suplir ?? 1) === 1; ?>
                        <span class="ufi-flag<?= $suple ? ' ufi-flag--ok' : ' ufi-flag--off' ?>">
                            <i class="fa-solid <?= $suple ? 'fa-circle-check' : 'fa-ban' ?>"></i>
                            <?= $suple ? 'Puede cubrir a otros profesores' : 'Excluido de suplencias' ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <div class="ufi-field ufi-field--wide">
                        <h3 class="ufi-field__t">Módulos</h3>
                        <?php if ($u->rol === 'administrador'): ?>
                        <p class="ufi-field__hint">Es administrador: accede a <strong>todos</strong> los módulos, así
                           que no tiene lista asignada.</p>
                        <?php elseif ($modulosU): ?>
                        <?php /* ⚠️ El rótulo sale del catálogo compartido, no de un
                                  `ucfirst($clave)`: eso pintaba la clave cruda y decía
                                  «Swaps» donde el resto del panel dice «Intercambios».
                                  El fallback cubre una clave huérfana en un CSV viejo. */ ?>
                        <div class="ufi-chips">
                            <?php foreach ($modulosU as $m): ?>
                            <span class="ufi-mod"><?= s($modCat[$m]['nombre'] ?? ucfirst(str_replace('_', ' ', $m))) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="ufi-field__hint">Sin módulos asignados: solo ve el inicio y el soporte técnico.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <?php if ($hayHorario): ?>
            <!-- ── Horario ──────────────────────────────────────────────────── -->
            <section class="admin-panel" id="ufi-horario">
                <div class="admin-panel__header hor-head">
                    <h2 class="admin-panel__title"><i class="fa-regular fa-calendar"></i> Horario semanal</h2>
                    <div class="hor-head__meta">
                        <?php if (count($nivelesHor) > 1): ?>
                        <span class="hor-niveles" title="Su horario cruza estas jornadas">
                            <i class="fa-solid fa-layer-group"></i> <?= s(implode(' · ', $nivelesHor)) ?>
                        </span>
                        <?php endif; ?>
                        <span class="ufi-carga">
                            <strong><?= $horasEnteras ?><small>h<?= $restoMin ? ' ' . sprintf('%02d', $restoMin) : '' ?></small></strong>
                            <?= (int)$totalClases ?> <?= $totalClases === 1 ? 'clase' : 'clases' ?> a la semana
                        </span>
                        <?php /* Fuera del `$puedeEditar`: eso es permiso de ESCRITURA, y una
                                 dirección en solo lectura también necesita poder llevarse el
                                 horario en papel. El bloque ya está dentro de `if ($hayHorario)`. */ ?>
                        <a href="/dashboard/usuarios/horario.pdf?id=<?= (int)$u->id ?>" class="admin-btn admin-btn--ghost admin-btn--sm">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </a>
                        <?php if ($puedeEditar): ?>
                        <a href="/dashboard/usuarios/horario?id=<?= (int)$u->id ?>" class="admin-btn admin-btn--ghost admin-btn--sm">
                            <i class="fa-regular fa-calendar-plus"></i> Editar horario
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php /* El mismo partial que "Mi horario" y el módulo Horarios: si la ficha
                          montara su propia rejilla podrían acabar pintando semanas distintas. */ ?>
                <?php $niveles = $nivelesHor; include __DIR__ . '/../horarios/_grid.php'; ?>
            </section>
            <?php endif; ?>

            <?php if ($esDocente): ?>
            <!-- ── Suplencias ───────────────────────────────────────────────── -->
            <section class="admin-panel" id="ufi-suplencias">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title"><i class="fa-solid fa-user-clock"></i> Suplencias</h2>
                </div>

                <div class="ufi-stats">
                    <div class="ufi-stat"><span class="ufi-stat__n"><?= count($ausencias) ?></span><span class="ufi-stat__l">Ausencias</span></div>
                    <div class="ufi-stat"><span class="ufi-stat__n"><?= count($coberturas) ?></span><span class="ufi-stat__l">Horas cubiertas</span></div>
                    <div class="ufi-stat ufi-stat--ok"><span class="ufi-stat__n"><?= $validadas ?></span><span class="ufi-stat__l">Validadas</span></div>
                    <?php if ($agendadas): ?>
                    <div class="ufi-stat ufi-stat--warn"><span class="ufi-stat__n"><?= $agendadas ?></span><span class="ufi-stat__l">Por confirmar</span></div>
                    <?php endif; ?>
                    <?php if ($noCubiertas): ?>
                    <?php /* Se muestra solo si existe: un cero en rojo permanente convierte una
                              tarjeta informativa en una acusación de fondo. */ ?>
                    <div class="ufi-stat ufi-stat--bad" title="Coberturas que aceptó y no se cumplieron">
                        <span class="ufi-stat__n"><?= $noCubiertas ?></span><span class="ufi-stat__l">No cubiertas</span>
                    </div>
                    <?php endif; ?>
                </div>

                <h3 class="ufi-sub">Sus ausencias</h3>
                <?php if (!$ausencias): ?>
                <p class="ufi-vacio">No ha registrado ninguna ausencia.</p>
                <?php else: ?>
                <div class="admin-table-scroll">
                    <table class="admin-table" data-table data-table-per="8">
                        <thead>
                            <tr>
                                <th data-sort="date">Fecha</th>
                                <th data-sort="text">Motivo</th>
                                <th data-sort="text">Origen</th>
                                <th data-sort="num">Horas</th>
                                <th data-sort="text">Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ausencias as $i => $sup): ?>
                            <tr data-pager-item<?= $i >= 8 ? ' class="is-hidden"' : '' ?>>
                                <td data-label="Fecha" data-val="<?= s($sup->fecha) ?>"><?= $fmt($sup->fecha) ?></td>
                                <td data-label="Motivo"><?= s($sup->motivo ?: '—') ?></td>
                                <td data-label="Origen">
                                    <span class="admin-badge"><?= $sup->origen === 'sin_aviso' ? 'Sin aviso' : 'Anticipada' ?></span>
                                </td>
                                <td data-label="Horas" data-val="<?= (int)($sup->total_horas ?? 0) ?>"><?= (int)($sup->total_horas ?? 0) ?></td>
                                <td data-label="Estado">
                                    <span class="admin-badge"><?= s(\Model\Suplencia::ESTADO_LABEL[$sup->estado] ?? $sup->estado) ?></span>
                                </td>
                                <td>
                                    <a href="/dashboard/suplencias/agendar?id=<?= (int)$sup->id ?>" class="admin-act admin-act--ghost" title="Abrir suplencia">
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <h3 class="ufi-sub">Horas que ha cubierto</h3>
                <?php if (!$coberturas): ?>
                <p class="ufi-vacio">Todavía no ha cubierto ninguna hora.</p>
                <?php else: ?>
                <div class="admin-table-scroll">
                    <table class="admin-table" data-table data-table-per="8">
                        <thead>
                            <tr>
                                <th data-sort="date">Fecha</th>
                                <th data-sort="text">Cubrió a</th>
                                <th data-sort="text">Hora</th>
                                <th data-sort="text">Clase</th>
                                <th data-sort="text">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($coberturas as $i => $h): ?>
                            <?php
                            $esGuardia = ($h->tipo ?? 'clase') === 'guardia';
                            $etqEstado = ['agendada' => 'Por confirmar', 'validada' => 'Validada',
                                          'no_cubierta' => 'No se cubrió', 'pendiente' => 'Pendiente'];
                            ?>
                            <tr data-pager-item<?= $i >= 8 ? ' class="is-hidden"' : '' ?>>
                                <td data-label="Fecha" data-val="<?= s($h->s_fecha) ?>"><?= $fmt($h->s_fecha) ?></td>
                                <td data-label="Cubrió a"><?= s($h->ausente_nombre ?: '—') ?></td>
                                <td data-label="Hora"><?= s(substr((string)$h->periodo_inicio, 0, 5)) ?>–<?= s(substr((string)$h->periodo_fin, 0, 5)) ?></td>
                                <td data-label="Clase">
                                    <?php if ($esGuardia): ?>
                                        <span class="admin-badge">Guardia</span>
                                    <?php else: ?>
                                        <?= s($h->materia ?: 'Clase') ?><?= $h->grupo_nombre ? ' · ' . s($h->grupo_nombre) : '' ?>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Estado">
                                    <span class="ufi-eh ufi-eh--<?= s((string)$h->estado_hora) ?>">
                                        <?= s($etqEstado[$h->estado_hora] ?? $h->estado_hora) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>

            <?php if ($haySwaps): ?>
            <!-- ── Swaps ────────────────────────────────────────────────────── -->
            <?php /* Tabla compacta y NO `.swp-card`: esa clase vive dentro del scope
                      body[data-page="blog-swaps-index"], y su partial es una superficie
                      de decisión que aquí saldría sin ninguna acción. */ ?>
            <section class="admin-panel" id="ufi-swaps">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title"><i class="fa-solid fa-right-left"></i> Intercambios de clase</h2>
                </div>
                <div class="admin-table-scroll">
                    <table class="admin-table" data-table data-table-per="8">
                        <thead>
                            <tr>
                                <th data-sort="date">Fecha</th>
                                <th data-sort="text">Con</th>
                                <th data-sort="text">Cede</th>
                                <th data-sort="text">Recibe</th>
                                <th data-sort="text">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($swaps as $i => $sw): ?>
                            <?php
                            $esSolicitante = (int)$sw->solicitante_id === (int)$u->id;
                            $otro = $esSolicitante ? $sw->destinatario_nombre : $sw->solicitante_nombre;
                            // "Cede" y "Recibe" se leen desde la persona de la ficha, no desde
                            // quien abrió el swap: si no, la mitad de las filas se leen al revés.
                            $cede    = $esSolicitante
                                ? ($sw->origen_materia  . ' · ' . $sw->origen_grupo)
                                : ($sw->destino_materia . ' · ' . $sw->destino_grupo);
                            $recibe  = $esSolicitante
                                ? ($sw->destino_materia . ' · ' . $sw->destino_grupo)
                                : ($sw->origen_materia  . ' · ' . $sw->origen_grupo);
                            $col = \Model\Swap::ESTADO_COLOR[$sw->estado] ?? 'nil';
                            ?>
                            <tr data-pager-item<?= $i >= 8 ? ' class="is-hidden"' : '' ?>>
                                <td data-label="Fecha" data-val="<?= s($sw->fecha_origen) ?>"><?= $fmt($sw->fecha_origen) ?></td>
                                <td data-label="Con"><?= s($otro ?: '—') ?></td>
                                <td data-label="Cede"><?= s(trim($cede, ' ·') ?: '—') ?></td>
                                <td data-label="Recibe"><?= s(trim($recibe, ' ·') ?: '—') ?></td>
                                <td data-label="Estado">
                                    <span class="ufi-eh ufi-eh--<?= s($col) ?>">
                                        <?= s(\Model\Swap::ESTADO_LABEL[$sw->estado] ?? $sw->estado) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($hayCont): ?>
            <!-- ── Redacción ────────────────────────────────────────────────── -->
            <section class="admin-panel" id="ufi-contenido">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title"><i class="fa-regular fa-newspaper"></i> Redacción</h2>
                </div>
                <div class="admin-table-scroll">
                    <table class="admin-table" data-table data-table-per="6">
                        <thead>
                            <tr>
                                <th data-sort="text">Título</th>
                                <th data-sort="text">Tipo</th>
                                <th data-sort="text">Estado</th>
                                <th data-sort="num">Vistas</th>
                                <th data-sort="date">Creado</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($contenido as $i => $c): ?>
                            <tr data-pager-item<?= $i >= 6 ? ' class="is-hidden"' : '' ?>>
                                <td data-label="Título"><div class="admin-table__title"><?= s($c['titulo']) ?></div></td>
                                <td data-label="Tipo"><span class="admin-badge"><?= $c['tipo'] === 'noticia' ? 'Noticia' : 'Artículo' ?></span></td>
                                <td data-label="Estado"><span class="admin-badge"><?= s(ucfirst((string)$c['estado'])) ?></span></td>
                                <td data-label="Vistas" data-val="<?= (int)$c['vistas'] ?>"><?= (int)$c['vistas'] ?></td>
                                <td data-label="Creado" data-val="<?= s($c['creado_en']) ?>"><?= $fmt($c['creado_en']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>

            <?php if (!$esDocente && !$hayCont): ?>
            <?php /* Un administrativo o un prefecto no tiene horario, ni suplencias, ni
                      swaps. Es poco, y está bien que lo sea: no hay más que el
                      sistema sepa de esa persona. Inventar secciones de relleno haría
                      pensar que faltan datos. */ ?>
            <p class="ufi-vacio ufi-vacio--big">
                <i class="fa-regular fa-folder-open"></i>
                Este puesto no imparte clase, así que no tiene horario, suplencias ni intercambios.
            </p>
            <?php endif; ?>

        </main>
    </div>
</div>
