<?php $paginaVista = 'blog-aulas-index'; ?>
<?php
$total = count($aulas ?? []);
/* Dadas de baja: `Aula::todasConUso()` las trae todas a propósito — es aquí donde se
   ven y donde se vuelven a encender, y un catálogo que esconde sus bajas no deja
   deshacer lo que hizo una importación con un archivo incompleto. */
$bajas = 0;
foreach ($aulas ?? [] as $a) if ((int)($a->activo ?? 1) === 0) $bajas++;

// Feedback post-acción (mismo patrón de query params + toast de Alex del resto del panel)
$toast = null;
if     (isset($_GET['success']))       $toast = ['t' => '¡Aula creada!',      'm' => 'Ya puedes usarla en los horarios.',            'i' => 'fa-door-open',    'c' => '#34a853'];
elseif (isset($_GET['edited']))        $toast = ['t' => '¡Aula actualizada!', 'm' => 'Los cambios se guardaron.',                    'i' => 'fa-pen',          'c' => '#4267ac'];
elseif (isset($_GET['deleted']))       $toast = ['t' => 'Aula eliminada',     'm' => 'El catálogo está actualizado.',                'i' => 'fa-circle-check', 'c' => '#4267ac'];
elseif (isset($_GET['inhabilitado']))  $toast = ['t' => 'Aula dada de baja',  'm' => 'Deja de ofrecerse, pero no se ha borrado nada.','i' => 'fa-power-off',   'c' => '#f5b400'];
elseif (isset($_GET['reactivado']))    $toast = ['t' => 'Aula reactivada',    'm' => 'Vuelve a estar disponible en los horarios.',    'i' => 'fa-rotate-left',  'c' => '#34a853'];
?>
<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title"><i class="fa-solid fa-door-open"></i> Aulas</span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">

            <?php /* Dirección revisa pero no edita: el guard real es requireEscritura(). */
            if (blog_modulos_solo_lectura()) { $soloLecturaQue = 'el catálogo de aulas'; include __DIR__ . '/../_solo-lectura.php'; } ?>


            <?php if (isset($_GET['enuso'])): ?>
            <div class="admin-alerta admin-alerta--error">
                <i class="fa-solid fa-circle-exclamation"></i>
                No se puede eliminar: <strong><?= (int)$_GET['enuso'] ?></strong> clase(s) o cobertura(s)
                usan esa aula. Reasígnalas primero desde el horario.
            </div>
            <?php endif; ?>

            <div class="admin-panel">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title">
                        Todas las aulas
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
                    <a href="/dashboard/aulas/crear" class="admin-new-btn">
                        <i class="fa-solid fa-plus"></i> Nueva aula
                    </a>
                </div>

                <?php if (!$total): ?>
                <div class="admin-empty-state">
                    <img src="/build/assets/img/alex/alex-espera.png" alt="Alex" class="admin-empty-state__img">
                    <p class="admin-empty-state__text">Todavía no hay aulas en el catálogo.</p>
                    <a href="/dashboard/aulas/crear" class="admin-btn admin-btn--primary">
                        <i class="fa-solid fa-plus"></i> Crear la primera
                    </a>
                </div>
                <?php else: ?>
                <div class="admin-table-scroll">
                    <?php /* --cat reparte los anchos a mano: con solo tres columnas de
                             contenido corto el algoritmo automático volcaba toda la holgura
                             en la primera y la tabla se veía medio vacía. */ ?>
                    <table class="admin-table admin-table--cat" data-table data-table-per="12" data-table-noun="aulas">
                        <colgroup>
                            <col class="admin-table__col--main">
                            <col class="admin-table__col--dato">
                            <col class="admin-table__col--acts">
                        </colgroup>
                        <thead>
                            <tr>
                                <th data-sort="text">Aula</th>
                                <th data-sort="num">Clases asignadas</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($aulas as $i => $a): $uso = (int)$a->total_horarios; $off = (int)($a->activo ?? 1) === 0; ?>
                            <?php $clases = trim(($i >= 12 ? 'is-hidden ' : '') . ($off ? 'is-off' : '')); ?>
                            <tr data-pager-item<?= $clases ? ' class="' . $clases . '"' : '' ?>>
                                <td data-val="<?= s($a->nombre) ?>">
                                    <div class="cat-name">
                                        <i class="fa-solid fa-door-open"></i> <?= s($a->nombre) ?>
                                        <?php if ($off): ?><span class="cat-baja">Baja</span><?php endif; ?>
                                    </div>
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
                                        <?php /* Ver en qué horas está ocupada el aula: es lo que hace falta
                                                 para saber cuándo se puede usar por un caso especial. */ ?>
                                        <a href="/dashboard/horarios/aula?id=<?= (int)$a->id ?>" class="admin-act admin-act--horario" title="Ver horario del aula">
                                            <i class="fa-regular fa-calendar"></i>
                                        </a>
                                        <a href="/dashboard/aulas/editar?id=<?= (int)$a->id ?>" class="admin-act admin-act--edit" title="Editar aula">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <?php /* Baja lógica: la alternativa reversible a eliminar, y la
                                                 vuelta atrás de lo que hace el importador CSV con un aula
                                                 que el archivo deja de mencionar. No comprueba usos —
                                                 apagar no rompe nada—, así que está siempre disponible. */ ?>
                                        <form method="POST" action="/dashboard/aulas/activo" class="admin-act-form">
                                            <input type="hidden" name="tipo" value="aulas">
                                            <input type="hidden" name="id" value="<?= (int)$a->id ?>">
                                            <input type="hidden" name="activo" value="<?= $off ? '1' : '' ?>">
                                            <button type="submit" class="admin-act admin-act--baja<?= $off ? ' is-off' : '' ?>"
                                                    title="<?= $off ? 'Reactivar aula' : 'Dar de baja (no se borra nada)' ?>">
                                                <i class="fa-solid <?= $off ? 'fa-rotate-left' : 'fa-power-off' ?>"></i>
                                            </button>
                                        </form>
                                        <?php /* Con clases asignadas el borrado rompería el horario: se ofrece deshabilitado */ ?>
                                        <?php if ($uso): ?>
                                        <button type="button" class="admin-act admin-act--del" disabled
                                                title="No se puede eliminar: <?= $uso ?> clase(s) la usan">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="admin-act admin-act--del" title="Eliminar aula"
                                                onclick="catEliminar(<?= (int)$a->id ?>, '<?= s(addslashes($a->nombre)) ?>')">
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
$catModal = ['accion' => '/dashboard/aulas/eliminar', 'que' => 'el aula'];
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
