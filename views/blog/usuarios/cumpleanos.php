<?php $paginaVista = 'blog-usuarios-cumpleanos'; ?>
<?php
$mesesEs = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

// Preparar eventos para el calendario (clave mes-día, ignora el año)
$eventos = [];
foreach (($cumpleanos ?? []) as $c) {
    if (empty($c->fecha_nacimiento)) continue;
    $md = str_pad((string)$c->mes, 2, '0', STR_PAD_LEFT) . '-' . str_pad((string)$c->dia, 2, '0', STR_PAD_LEFT);
    $eventos[] = [
        'md'      => $md,
        'nombre'  => $c->nombre ?? '—',
        'inicial' => mb_strtoupper(mb_substr($c->nombre ?? 'U', 0, 1)),
        'avatar'  => $c->avatar ?? '',
    ];
}
?>

<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">

        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <div class="admin-breadcrumb">
                    <a href="/dashboard/usuarios">Usuarios</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Cumpleaños</span>
                </div>
                <span class="admin-topbar__title">Cumpleaños del equipo</span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn">
                        <i class="fa-solid fa-right-from-bracket"></i> Salir
                    </button>
                </form>
            </div>
        </header>

        <main class="admin-content">

            <div class="cb-wrap">
                <div class="bilbao-cal" id="cbCalendar"
                     data-events='<?= htmlspecialchars(json_encode($eventos), ENT_QUOTES) ?>'>
                    <div class="bilbao-cal__header">
                        <button type="button" class="bilbao-cal__nav" data-cal-prev aria-label="Mes anterior"><i class="fa-solid fa-chevron-left"></i></button>
                        <h3 class="bilbao-cal__month" data-cal-label>—</h3>
                        <button type="button" class="bilbao-cal__nav" data-cal-next aria-label="Mes siguiente"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                    <div class="bilbao-cal__weekdays">
                        <span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span><span>Dom</span>
                    </div>
                    <div class="bilbao-cal__grid" data-cal-grid></div>
                    <div class="bilbao-cal__legend">
                        <span class="bilbao-cal__leg" data-type="cumple"><i></i> Cumpleaños</span>
                    </div>
                </div>

                <div class="cb-side">
                    <div class="cb-side__head">
                        <div class="cb-side__title"><i class="fa-solid fa-cake-candles"></i> <span data-cb-title>Todos los cumpleaños</span></div>
                        <div class="cb-side__sub"><?= count($eventos) ?> colaborador<?= count($eventos) !== 1 ? 'es' : '' ?> con fecha registrada</div>
                    </div>
                    <div class="cb-list" data-cb-list>
                        <?php if (empty($eventos)): ?>
                            <div class="cb-empty">Aún no hay fechas de nacimiento registradas.<br>Agrégalas al editar cada usuario.</div>
                        <?php else: foreach ($cumpleanos as $c):
                            $ini = mb_strtoupper(mb_substr($c->nombre ?? 'U', 0, 1));
                            $dia = (int)$c->dia; $mes = $mesesEs[((int)$c->mes) - 1] ?? '';
                        ?>
                        <div class="cb-item">
                            <div class="cb-ava"><?php if (!empty($c->avatar)): ?><img src="<?= htmlspecialchars($c->avatar) ?>" alt=""><?php else: ?><?= htmlspecialchars($ini) ?><?php endif; ?></div>
                            <div>
                                <div class="cb-name"><?= htmlspecialchars($c->nombre ?? '—') ?></div>
                                <div class="cb-date"><?= $dia ?> de <?= $mes ?></div>
                            </div>
                            <span class="cb-chip"><?= $dia ?> <?= mb_substr($mes, 0, 3) ?></span>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

