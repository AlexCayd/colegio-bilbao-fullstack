<?php $paginaVista = 'blog-horarios-mi-horario'; ?>
<?php
/** @var \Model\UsuarioBlog $profesor  @var \Model\Periodo[] $periodos  @var array $matriz */
$vista = 'profesor';

// Horas libres por día: es lo que el sistema usa para proponer suplencias
$librePorDia = [];
foreach (\Model\Horario::DIAS as $d) {
    $n = 0;
    foreach ($periodos as $p) {
        if ((int)$p->es_receso === 1) continue;
        if (!isset($matriz[$d][(int)$p->id])) $n++;
    }
    $librePorDia[$d] = $n;
}
$totalLibres = array_sum($librePorDia);
$puedeSuplir = (int)($profesor->puede_suplir ?? 1) === 1;
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">Mi horario</span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</button>
                </form>
            </div>
        </header>

        <main class="admin-content">

            <div class="mih-intro">
                <img src="/build/assets/img/alex/bby-alex-saluda.png" alt="Alex" class="mih-intro__alex">
                <div class="mih-intro__body">
                    <p class="mih-intro__text">
                        Este es tu horario tal como lo tiene cargado dirección. Es de <strong>solo lectura</strong>:
                        si algo no cuadra, avisa a prefectura.
                        <?php if ($puedeSuplir): ?>
                        Tus <strong>horas libres</strong> son las que el sistema puede proponer para cubrir una
                        suplencia, respetando siempre al menos una hora de descanso al día.
                        <?php else: ?>
                        Estás marcado como <strong>no disponible para suplir</strong>, así que no aparecerás entre
                        los candidatos a cubrir clases.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="mih-intro__stat">
                    <span class="mih-intro__stat-n"><?= (int)$totalLibres ?></span>
                    <span class="mih-intro__stat-l">hora<?= $totalLibres === 1 ? '' : 's' ?> libre<?= $totalLibres === 1 ? '' : 's' ?> a la semana</span>
                </div>
            </div>

            <div class="admin-panel">
                <div class="admin-panel__header hor-head">
                    <h2 class="admin-panel__title"><i class="fa-regular fa-calendar-check"></i> <?= s($profesor->nombre) ?></h2>
                    <span class="mih-badge<?= $puedeSuplir ? '' : ' mih-badge--off' ?>">
                        <i class="fa-solid <?= $puedeSuplir ? 'fa-circle-check' : 'fa-ban' ?>"></i>
                        <?= $puedeSuplir ? 'Disponible para suplir' : 'Excluido de suplencias' ?>
                    </span>
                </div>

                <?php if (empty($periodos)): ?>
                <div class="admin-empty-state" style="padding:40px;">
                    <p class="admin-empty-state__text">Todavía no hay una jornada configurada.</p>
                </div>
                <?php else: ?>
                <?php include __DIR__ . '/_grid.php'; ?>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>
