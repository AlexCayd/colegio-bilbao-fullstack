<?php $paginaVista = 'blog-grupos-index'; ?>
<?php
$total = count($grupos ?? []);

/* El color de cada nivel sale del modelo (Materia::NIVEL_COLOR), que es también de
   donde lo leen el formulario de grupos y los chips de nivel de Eventos. */
$nivelColor = \Model\Materia::NIVEL_COLOR;

/* Dados de baja: `Grupo::todosConUso()` los trae todos a propósito — ver la nota de
   aulas/index.php. */
$bajas = 0;
foreach ($grupos ?? [] as $g) if ((int)($g->activo ?? 1) === 0) $bajas++;

$toast = null;
if     (isset($_GET['success']))      $toast = ['t' => '¡Grupo creado!',       'm' => 'Ya puedes usarlo en los horarios.',             'i' => 'fa-layer-group',  'c' => '#34a853'];
elseif (isset($_GET['edited']))       $toast = ['t' => '¡Grupo actualizado!',  'm' => 'Los cambios se guardaron.',                     'i' => 'fa-pen',          'c' => '#4267ac'];
elseif (isset($_GET['deleted']))      $toast = ['t' => 'Grupo eliminado',      'm' => 'El catálogo está actualizado.',                 'i' => 'fa-circle-check', 'c' => '#4267ac'];
elseif (isset($_GET['inhabilitado'])) $toast = ['t' => 'Grupo dado de baja',   'm' => 'Deja de ofrecerse, pero no se ha borrado nada.', 'i' => 'fa-power-off',    'c' => '#f5b400'];
elseif (isset($_GET['reactivado']))   $toast = ['t' => 'Grupo reactivado',     'm' => 'Vuelve a estar disponible en los horarios.',     'i' => 'fa-rotate-left',  'c' => '#34a853'];
?>
<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title"><i class="fa-solid fa-layer-group"></i> Grupos</span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">

            <?php /* Dirección revisa pero no edita: el guard real es requireEscritura(). */
            if (blog_modulos_solo_lectura()) { $soloLecturaQue = 'el catálogo de grupos'; include __DIR__ . '/../_solo-lectura.php'; } ?>


            <?php if (isset($_GET['enuso'])): ?>
            <div class="admin-alerta admin-alerta--error">
                <i class="fa-solid fa-circle-exclamation"></i>
                No se puede eliminar: <strong><?= (int)$_GET['enuso'] ?></strong> clase(s) o cobertura(s)
                pertenecen a ese grupo. Reasígnalas primero desde el horario.
            </div>
            <?php endif; ?>

            <?php /* Tabs de nivel a ancho completo. Filtran la tabla en cliente marcando
                     las filas descartadas con `is-filtered` y repaginando — el mismo patrón
                     que usan los buscadores del resto del panel. */ ?>
            <?php
            $porNivel = [];
            foreach ($grupos as $g) { $porNivel[$g->nivel] = ($porNivel[$g->nivel] ?? 0) + 1; }
            ?>
            <div class="cat-tabs" data-nivel-tabs>
                <button type="button" class="cat-tab is-active" data-nivel="">
                    Todos <span class="cat-tab__n"><?= $total ?></span>
                </button>
                <?php foreach (\Model\Grupo::NIVELES as $niv): $n = $porNivel[$niv] ?? 0; ?>
                <button type="button" class="cat-tab" data-nivel="<?= s($niv) ?>"
                        style="--c:<?= $nivelColor[$niv] ?? '#94a3b8' ?>;"<?= $n ? '' : ' disabled' ?>>
                    <?= s($niv) ?> <span class="cat-tab__n"><?= $n ?></span>
                </button>
                <?php endforeach; ?>
            </div>

            <div class="admin-panel">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title">
                        Todos los grupos
                        <span class="admin-panel__count"><?= $total ?></span>
                        <?php if ($bajas): ?>
                        <span class="cat-baja cat-baja--count"><?= $bajas ?> de baja</span>
                        <?php endif; ?>
                    </h2>
<?php /* La acción principal vive en la cabecera del panel sobre el que actúa, no
                             en el topbar: allí quedaba junto a la campana y el avatar, que son del
                             panel entero y no de esta pantalla. Sustituye al enlace de texto
                             «+ Nuevo» que ya había aquí — la acción existía, pero como un enlace
                             azul que no se leía como el botón que es. */ ?>
                    <a href="/dashboard/grupos/crear" class="admin-new-btn">
                        <i class="fa-solid fa-plus"></i> Nuevo grupo
                    </a>
                </div>

                <?php if (!$total): ?>
                <div class="admin-empty-state">
                    <img src="/build/assets/img/alex/alex-espera.png" alt="Alex" class="admin-empty-state__img">
                    <p class="admin-empty-state__text">Todavía no hay grupos en el catálogo.</p>
                    <a href="/dashboard/grupos/crear" class="admin-btn admin-btn--primary">
                        <i class="fa-solid fa-plus"></i> Crear el primero
                    </a>
                </div>
                <?php else: ?>
                <div class="admin-table-scroll">
                    <?php /* --cat reparte los anchos a mano: ver la nota en aulas/index.php */ ?>
                    <table class="admin-table admin-table--cat" data-table data-table-per="12" data-table-noun="grupos">
                        <colgroup>
                            <col class="admin-table__col--main">
                            <col class="admin-table__col--dato">
                            <col class="admin-table__col--dato">
                            <col class="admin-table__col--acts">
                        </colgroup>
                        <thead>
                            <tr>
                                <th data-sort="text">Grupo</th>
                                <th data-sort="text">Nivel</th>
                                <th data-sort="num">Clases asignadas</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($grupos as $i => $g): $uso = (int)$g->total_horarios; $col = $nivelColor[$g->nivel] ?? '#94a3b8'; $off = (int)($g->activo ?? 1) === 0; ?>
                            <?php $clases = trim(($i >= 12 ? 'is-hidden ' : '') . ($off ? 'is-off' : '')); ?>
                            <tr data-pager-item data-nivel="<?= s($g->nivel) ?>"<?= $clases ? ' class="' . $clases . '"' : '' ?>>
                                <td data-val="<?= s($g->nombre) ?>">
                                    <div class="cat-name">
                                        <i class="fa-solid fa-layer-group"></i> <?= s($g->nombre) ?>
                                        <?php if ($off): ?><span class="cat-baja">Baja</span><?php endif; ?>
                                    </div>
                                </td>
                                <td data-val="<?= s($g->nivel) ?>">
                                    <span class="cat-nivel" style="--c:<?= $col ?>;"><?= s($g->nivel) ?></span>
                                </td>
                                <td data-val="<?= $uso ?>">
                                    <?php if ($uso): ?>
                                        <span class="admin-badge admin-badge--published"><?= $uso ?></span>
                                    <?php else: ?>
                                        <span class="admin-badge">Sin uso</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="admin-table__actions">
                                        <a href="/dashboard/horarios/grupo?id=<?= (int)$g->id ?>" class="admin-act admin-act--horario" title="Ver horario del grupo">
                                            <i class="fa-regular fa-calendar"></i>
                                        </a>
                                        <a href="/dashboard/grupos/editar?id=<?= (int)$g->id ?>" class="admin-act admin-act--edit" title="Editar grupo">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <?php /* Baja lógica: ver la nota de aulas/index.php. */ ?>
                                        <form method="POST" action="/dashboard/grupos/activo" class="admin-act-form">
                                            <input type="hidden" name="tipo" value="grupos">
                                            <input type="hidden" name="id" value="<?= (int)$g->id ?>">
                                            <input type="hidden" name="activo" value="<?= $off ? '1' : '' ?>">
                                            <button type="submit" class="admin-act admin-act--baja<?= $off ? ' is-off' : '' ?>"
                                                    title="<?= $off ? 'Reactivar grupo' : 'Dar de baja (no se borra nada)' ?>">
                                                <i class="fa-solid <?= $off ? 'fa-rotate-left' : 'fa-power-off' ?>"></i>
                                            </button>
                                        </form>
                                        <?php if ($uso): ?>
                                        <button type="button" class="admin-act admin-act--del" disabled
                                                title="No se puede eliminar: <?= $uso ?> clase(s) pertenecen a este grupo">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="admin-act admin-act--del" title="Eliminar grupo"
                                                onclick="catEliminar(<?= (int)$g->id ?>, '<?= s(addslashes($g->nombre)) ?>')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php
$catModal = ['accion' => '/dashboard/grupos/eliminar', 'que' => 'el grupo'];
include __DIR__ . '/../_catalogo-modal.php';
?>

<?php if ($toast): ?>
<div class="at-wrap" id="alexToast">
    <span class="at-stripe" style="background:<?= $toast['c'] ?>;"></span>
    <img src="/build/assets/img/alex/alex-mano.png" alt="Alex" class="at-alex">
    <div class="at-body">
        <p class="at-title"><i class="fa-solid <?= $toast['i'] ?>" style="color:<?= $toast['c'] ?>;"></i> <?= s($toast['t']) ?></p>
        <p class="at-msg"><?= s($toast['m']) ?></p>
    </div>
    <button type="button" class="at-close" onclick="cerrarAlexToast()"><i class="fa-solid fa-xmark"></i></button>
    <span class="at-bar" style="background:<?= $toast['c'] ?>;"></span>
</div>
<?php endif; ?>
