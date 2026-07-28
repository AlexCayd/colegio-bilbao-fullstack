<?php $paginaVista = 'blog-suplencias-mis-coberturas'; ?>
<?php /** @var \Model\SuplenciaHora[] $horas */ ?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left"><span class="admin-topbar__title">Mis coberturas</span></div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</button>
                </form>
            </div>
        </header>

        <main class="admin-content">
            <?php if (isset($_GET['validado'])): ?>
            <div class="admin-alerta admin-alerta--exito" style="margin-bottom:16px;"><i class="fa-solid fa-circle-check"></i> ¡Gracias! Confirmaste la cobertura.</div>
            <?php endif; ?>

            <div class="admin-panel">
                <div class="admin-panel__header"><h2 class="admin-panel__title">Coberturas por validar <span class="admin-panel__count"><?= count($horas) ?></span></h2></div>
                <div class="supl-cover-body">
                    <?php if (empty($horas)): ?>
                        <div class="supl-cover-empty">
                            <img src="/build/assets/img/alex/bby-alex-feliz.png" alt="Alex">
                            <strong>Todo al día</strong>
                            <p>
                                No tienes coberturas pendientes de confirmar. Cuando prefectura te asigne una clase
                                aparecerá aquí, y podrás confirmarla en cuanto la hayas impartido.
                            </p>
                        </div>
                    <?php else: foreach ($horas as $h): ?>
                        <div class="supl-cover-card">
                            <div class="supl-cover-card__date">
                                <span class="supl-cover-card__d"><?= (int)date('d', strtotime($h->s_fecha)) ?></span>
                                <span class="supl-cover-card__mo"><?= ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'][(int)date('n', strtotime($h->s_fecha)) - 1] ?></span>
                            </div>
                            <div class="supl-cover-card__info">
                                <span class="supl-cover-card__clase"><?= s($h->materia ?: 'Clase') ?><?php if ($h->grupo_nombre): ?> · <?= s($h->grupo_nombre) ?><?php endif; ?></span>
                                <span class="supl-cover-card__meta"><?= s($h->periodo_etiqueta) ?> · <?= substr($h->periodo_inicio, 0, 5) ?>–<?= substr($h->periodo_fin, 0, 5) ?> · Cubre a <?= s($h->ausente_nombre ?: '—') ?><?php if ($h->aula_nombre): ?> · <?= s($h->aula_nombre) ?><?php endif; ?></span>
                                <?php if (!empty($h->s_notas)): ?>
                                <span class="supl-cover-card__notas"><i class="fa-regular fa-note-sticky"></i> <?= s($h->s_notas) ?></span>
                                <?php endif; ?>
                            </div>
                            <form method="POST" action="/dashboard/suplencias/validar">
                                <input type="hidden" name="hora_id" value="<?= (int)$h->id ?>">
                                <button type="submit" class="admin-btn admin-btn--primary"><i class="fa-solid fa-check"></i> Sí, la cubrí</button>
                            </form>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
