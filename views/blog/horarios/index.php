<?php $paginaVista = 'blog-horarios-index'; ?>
<?php
/** @var string $vista  @var array $entidades  @var int $entidadId
 *  @var array $tramos  @var array $rejilla  @var string[] $niveles */
$vistaMeta = [
    'profesor' => ['label' => 'Profesor', 'icon' => 'fa-chalkboard-user', 'url' => '/dashboard/horarios/profesor'],
    'aula'     => ['label' => 'Aula',     'icon' => 'fa-door-open',       'url' => '/dashboard/horarios/aula'],
    'grupo'    => ['label' => 'Grupo',    'icon' => 'fa-users-rectangle', 'url' => '/dashboard/horarios/grupo'],
];
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">Horarios</span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">

            <?php /* Dirección revisa pero no edita: el guard real es requireEscritura(). */
            if (blog_modulos_solo_lectura()) { $soloLecturaQue = 'los horarios'; include __DIR__ . '/../_solo-lectura.php'; } ?>

            <!-- Pestañas de vista -->
            <div class="hor-tabs">
                <?php foreach ($vistaMeta as $k => $vm): ?>
                <a href="<?= $vm['url'] ?>" class="hor-tab<?= $vista === $k ? ' is-active' : '' ?>">
                    <i class="fa-solid <?= $vm['icon'] ?>"></i> <?= $vm['label'] ?>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="admin-panel">
                <div class="admin-panel__header hor-head">
                    <h2 class="admin-panel__title"><i class="fa-solid <?= $vistaMeta[$vista]['icon'] ?>"></i> Ver por <?= $vistaMeta[$vista]['label'] ?></h2>
                    <?php if (!empty($entidades)): ?>
                    <div class="hor-head__meta">
                    <?php if (!empty($tramos)): ?>
                    <?php /* La semana que se está viendo, en papel. Sirve a las tres vistas
                             —profesor, aula y grupo— con la misma plantilla que «Mi horario»:
                             de su sujeto solo necesita el nombre. Sale del PDF de vacío si no
                             hay clases, así que solo aparece cuando hay algo que imprimir. */ ?>
                    <a href="/dashboard/horarios/pdf?vista=<?= s($vista) ?>&amp;id=<?= (int)$entidadId ?>"
                       class="admin-btn admin-btn--ghost admin-btn--sm">
                        <i class="fa-solid fa-file-pdf"></i> PDF
                    </a>
                    <?php endif; ?>
                    <div class="hor-select-wrap">
                        <i class="fa-solid <?= $vistaMeta[$vista]['icon'] ?>"></i>
                        <select class="hor-select" data-hor-select data-url="<?= $vistaMeta[$vista]['url'] ?>" aria-label="Elegir <?= s($vistaMeta[$vista]['label']) ?>">
                            <?php if ($vista === 'grupo'): // agrupado por nivel académico
                                $porNivel = [];
                                foreach ($entidades as $e) $porNivel[$e->nivel][] = $e;
                                foreach ($porNivel as $nivel => $lista): ?>
                            <optgroup label="<?= s($nivel) ?>">
                                <?php foreach ($lista as $e): ?>
                                <option value="<?= (int)$e->id ?>" <?= (int)$e->id === $entidadId ? 'selected' : '' ?>><?= s($e->nombre) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endforeach; else: ?>
                                <?php foreach ($entidades as $e): ?>
                                <option value="<?= (int)$e->id ?>" <?= (int)$e->id === $entidadId ? 'selected' : '' ?>><?= s($e->nombre) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($entidades)): ?>
                <div class="admin-empty-state" style="padding:40px;">
                    <p class="admin-empty-state__text">No hay <?= s($vistaMeta[$vista]['label']) ?>es registrados todavía.</p>
                </div>
                <?php elseif (empty($tramos)): ?>
                <?php /* Sin clases y sin jornada que enmarcarlas: pintar la rejilla de los
                          cinco niveles a la vez daba 16 filas fragmentadas y una columna de
                          recesos apilados. Decir que no hay horario es más útil. */ ?>
                <div class="admin-empty-state" style="padding:40px;">
                    <p class="admin-empty-state__text">
                        <?php if ($vista === 'profesor'): ?>
                        Este profesor todavía no tiene horario cargado.
                        <?php if (empty($niveles)): ?>
                        Puedes declarar sus niveles en <a href="/dashboard/usuarios/editar?id=<?= (int)$entidadId ?>">su ficha</a>
                        o cargar sus clases desde <a href="/dashboard/horarios/importar">Importar CSV</a>.
                        <?php endif; ?>
                        <?php else: ?>
                        Todavía no hay clases asignadas a est<?= $vista === 'aula' ? 'a aula' : 'e grupo' ?>.
                        <?php endif; ?>
                    </p>
                </div>
                <?php else: ?>
                <?php include __DIR__ . '/_grid.php'; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
