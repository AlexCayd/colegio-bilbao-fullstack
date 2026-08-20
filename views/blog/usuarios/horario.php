<?php $paginaVista = 'blog-usuarios-horario'; ?>
<?php
/**
 * Editor del horario de un profesor. Es el ÚNICO punto del panel que escribe horario:
 * el módulo Horarios es de solo lectura y el CSV reemplaza semanas enteras.
 *
 * Se edita un nivel a la vez porque una casilla tiene que ser un `(dia, periodo_id)`
 * escribible, y eso solo pasa en la jornada de un nivel. Debajo va la semana
 * consolidada (el partial de Horarios, sin tocar): el editor muestra un nivel, esa
 * rejilla muestra la verdad.
 *
 * @var \Model\UsuarioBlog $profesor  @var string $nivel  @var string[] $niveles
 * @var string[] $declarados  @var array $porNivel  @var \Model\Periodo[] $periodos
 * @var array $celdas  @var array $grupos  @var array $materias  @var \Model\Aula[] $aulas
 * @var string[] $paleta  @var ?array $flash
 * @var array $tramos  @var array $rejilla  @var string[] $consNiveles  @var string[] $discrepantes
 */
$NIVEL_CORTO = ['Maternal' => 'Mat', 'Kinder' => 'Kín', 'Primaria' => 'Prim',
                'Secundaria' => 'Sec', 'Bachillerato' => 'Bach'];
$totalBloques = array_sum($porNivel);

// La vista consolidada comparte el partial de Horarios, que espera $niveles con los
// niveles del ámbito. Aquí $niveles son las pestañas, así que se renombra al incluirlo.
$vista = 'profesor';
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">Horario de <?= s($profesor->nombre) ?></span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">

            <?php /* ── Avisos ── */ ?>
            <?php if (!empty($flash['errores'])): ?>
            <div class="hed-alertas hed-alertas--error">
                <p class="hed-alertas__t"><i class="fa-solid fa-circle-exclamation"></i> No se guardó nada</p>
                <ul>
                    <?php foreach ($flash['errores'] as $e): ?><li><?= s($e) ?></li><?php endforeach; ?>
                </ul>
                <p class="hed-alertas__pie">Corrige lo de arriba y vuelve a guardar; el bloque sigue abierto con lo que habías puesto.</p>
            </div>
            <?php elseif (!empty($flash['avisos'])): ?>
            <div class="hed-alertas hed-alertas--warn" data-hed-confirmar>
                <p class="hed-alertas__t"><i class="fa-solid fa-triangle-exclamation"></i> Revisa antes de guardar</p>
                <ul>
                    <?php foreach ($flash['avisos'] as $a): ?><li><?= s($a) ?></li><?php endforeach; ?>
                </ul>
                <p class="hed-alertas__pie">
                    Un aula compartida no siempre es un error —el patio o el salón de usos múltiples
                    reciben a dos grupos a la vez—, así que no se bloquea: confírmalo tú.
                </p>
            </div>
            <?php elseif (!empty($flash['nivelAjeno'])): ?>
            <div class="hed-alertas hed-alertas--info">
                <p class="hed-alertas__t"><i class="fa-solid fa-circle-info"></i> Guardado</p>
                <ul><li>
                    <strong><?= s($flash['nivelAjeno']) ?></strong> no consta entre sus niveles declarados.
                    No impide nada, pero acota su rejilla y prioriza sus suplencias:
                    <a href="/dashboard/usuarios/editar?id=<?= (int)$profesor->id ?>">añádelo a su ficha</a>.
                </li></ul>
            </div>
            <?php elseif (isset($_GET['ok'])): ?>
            <div class="admin-alerta admin-alerta--exito" style="margin-bottom:16px;">
                <i class="fa-solid fa-circle-check"></i>
                <?= (int)$_GET['ok'] === 1 ? 'Bloque guardado.' : 'Bloque guardado con ' . ((int)$_GET['ok'] - 1) . ' acompañante' . ((int)$_GET['ok'] === 2 ? '' : 's') . '.' ?>
            </div>
            <?php elseif (isset($_GET['deleted'])): ?>
            <div class="admin-alerta admin-alerta--exito" style="margin-bottom:16px;">
                <i class="fa-solid fa-circle-check"></i> Bloque eliminado.
            </div>
            <?php endif; ?>

            <?php /* ── Pestañas de nivel ──
                     Son los CINCO niveles, no los del ámbito del profesor: si salieran del
                     ámbito, no habría dónde crear su primera clase de un nivel nuevo. Son
                     enlaces, no JS: cada rejilla necesita su overlay calculado en servidor. */ ?>
            <div class="hed-tabs">
                <?php foreach ($niveles as $n):
                    $esDeclarado = in_array($n, $declarados, true);
                    $tiene       = (int)($porNivel[$n] ?? 0);
                    $ajeno       = $tiene > 0 && $declarados && !$esDeclarado;
                ?>
                <a class="hed-tab<?= $n === $nivel ? ' is-active' : '' ?><?= $esDeclarado ? ' hed-tab--declarado' : '' ?><?= $ajeno ? ' hed-tab--ajeno' : '' ?>"
                   href="?id=<?= (int)$profesor->id ?>&nivel=<?= urlencode($n) ?>"
                   title="<?= $esDeclarado ? 'Nivel declarado en su ficha' : ($ajeno ? 'Da clase aquí, pero no consta en su ficha' : 'No consta que imparta este nivel') ?>">
                    <span class="hed-tab__n"><?= s($n) ?></span>
                    <?php if ($tiene): ?><span class="hed-tab__b"><?= $tiene ?></span><?php endif; ?>
                    <?php if ($ajeno): ?><i class="fa-solid fa-circle-question hed-tab__q"></i><?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>

            <?php /* ── Editor ── */ ?>
            <div class="admin-panel hed-panel">
                <div class="admin-panel__header hed-head">
                    <h2 class="admin-panel__title">
                        <i class="fa-solid fa-table-cells"></i> Jornada de <?= s($nivel) ?>
                        <span class="admin-panel__count"><?= (int)($porNivel[$nivel] ?? 0) ?></span>
                    </h2>
                    <p class="hed-head__help">
                        Pulsa una casilla libre para darle clase, o un <strong>receso</strong> para
                        asignar una guardia. Se carga de una en una.
                    </p>
                </div>

                <?php if (empty($periodos)): ?>
                <div class="admin-empty-state" style="padding:40px;">
                    <p class="admin-empty-state__text">
                        No hay jornada configurada para <?= s($nivel) ?>. Añade sus periodos antes de cargar horario.
                    </p>
                </div>
                <?php else: ?>
                <?php include __DIR__ . '/_horario-grid.php'; ?>

                <div class="hed-legend">
                    <span><i class="hed-legend__sw hed-legend__sw--libre"></i> Libre (pulsa para dar clase)</span>
                    <span><i class="hed-legend__sw hed-legend__sw--clase"></i> Clase de <?= s($nivel) ?> (pulsa para editar)</span>
                    <span><i class="hed-legend__sw hed-legend__sw--rec"></i> Receso (pulsa para poner guardia)</span>
                    <span><i class="hed-legend__sw hed-legend__sw--guardia"></i> Guardia asignada</span>
                    <span><i class="hed-legend__sw hed-legend__sw--ocup"></i> Ocupada por otro nivel</span>
                </div>
                <?php endif; ?>
            </div>

            <?php /* ── Semana consolidada ──
                     El partial de Horarios, tal cual y por el camino canónico. Es la red de
                     seguridad contra "me falta una clase": arriba se ve un nivel, aquí la
                     semana entera con las jornadas cruzadas. */ ?>
            <div class="admin-panel">
                <div class="admin-panel__header hor-head">
                    <h2 class="admin-panel__title"><i class="fa-regular fa-calendar-check"></i> Semana completa</h2>
                    <?php if (count($consNiveles) > 1): ?>
                    <div class="hor-head__meta">
                        <span class="hor-niveles" title="Su horario cruza estas jornadas">
                            <i class="fa-solid fa-layer-group"></i> <?= s(implode(' · ', $consNiveles)) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if (empty($tramos)): ?>
                <div class="admin-empty-state" style="padding:40px;">
                    <p class="admin-empty-state__text">Todavía no tiene ninguna clase cargada.</p>
                </div>
                <?php else: ?>
                <?php $niveles = $consNiveles; include __DIR__ . '/../horarios/_grid.php'; ?>
                <?php endif; ?>
            </div>
        </main>

    </div>
</div>

<?php include __DIR__ . '/_horario-modal.php'; ?>

<?php /* Reapertura tras un error o un aviso: el JS repuebla el modal con lo enviado en
         vez de obligar a rellenarlo otra vez. Isla JSON, nunca PHP dentro de JS. */ ?>
<?php if (!empty($flash['reabrir'])): ?>
<?php /* `tipo` y `lugar` son obligatorios aquí: sin ellos, un error al guardar una
         guardia reabría el modal en modo «Clase» y sin lugar, o sea pidiendo justo lo
         que la casilla no admite. Los acompañantes viajan con sus nombres porque el
         picker pinta chips, no ids. */
    $_rb = $flash['reabrir'];
    $_rbAcomp = [];
    foreach (array_values((array)($_rb['acompanantes'] ?? [])) as $_aid) {
        $_u = \Model\UsuarioBlog::find((int)$_aid);
        if ($_u) $_rbAcomp[] = ['id' => (int)$_u->id, 'nombre' => $_u->nombre];
    }
?>
<script type="application/json" id="hedReabrir"><?= json_encode([
    'id'      => (string)($_rb['id'] ?? ''),
    'celdas'  => array_values((array)($_rb['celdas'] ?? [])),
    'tipo'    => ($_rb['tipo'] ?? 'clase') === 'guardia' ? 'guardia' : 'clase',
    'lugar'   => (string)($_rb['lugar_id'] ?? ''),
    'grupo'   => (string)($_rb['grupo_id'] ?? ''),
    'materia' => (string)($_rb['materia_id'] ?? ''),
    'aula'    => (string)($_rb['aula_id'] ?? ''),
    'color'   => (string)($_rb['color'] ?? ''),
    'acomp'   => $_rbAcomp,
    'forzar'  => !empty($flash['avisos']),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif; ?>
