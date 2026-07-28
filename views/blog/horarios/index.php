<?php $paginaVista = 'blog-horarios-index'; ?>
<?php
/** @var string $vista  @var array $entidades  @var int $entidadId  @var \Model\Periodo[] $periodos  @var array $matriz */
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
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</button>
                </form>
            </div>
        </header>

        <main class="admin-content">
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
                    <?php endif; ?>
                </div>

                <?php if (empty($entidades)): ?>
                <div class="admin-empty-state" style="padding:40px;">
                    <p class="admin-empty-state__text">No hay <?= s($vistaMeta[$vista]['label']) ?>es registrados todavía.</p>
                </div>
                <?php else: ?>
                <?php include __DIR__ . '/_grid.php'; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
