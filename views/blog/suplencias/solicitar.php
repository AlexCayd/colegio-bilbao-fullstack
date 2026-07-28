<?php $paginaVista = 'blog-suplencias-solicitar'; ?>
<?php
/** @var \Model\Periodo[] $periodos  @var array $matriz  @var array $alertas */
$dias   = \Model\Horario::DIAS;
$sesion = $_SESSION['blog_usuario'] ?? [];
$inicial = mb_strtoupper(mb_substr($sesion['nombre'] ?? 'U', 0, 1));
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left"><span class="admin-topbar__title">Solicitar suplencia</span></div>
            <div class="admin-topbar__actions">
                <button type="submit" form="form-solicitar" class="admin-btn admin-btn--primary"><i class="fa-solid fa-paper-plane"></i> Enviar solicitud</button>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</button>
                </form>
            </div>
        </header>

        <main class="admin-content" data-profesor="<?= (int)($sesion['id'] ?? 0) ?>">
            <?php if (!empty($alertas['error'])): ?>
            <div class="admin-alerta admin-alerta--error" style="margin-bottom:16px;">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul class="admin-alerta__list"><?php foreach ($alertas['error'] as $e): ?><li><?= s($e) ?></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>

            <form id="form-solicitar" method="POST" action="/dashboard/suplencias/solicitar" enctype="multipart/form-data" class="supl-form">
                <div class="supl-form-grid">
                    <div class="admin-panel">
                        <div class="admin-panel__header"><h2 class="admin-panel__title">Datos de la ausencia</h2></div>
                        <div class="admin-form-section">

                            <!-- El profesor ausente es quien solicita: se lee de la sesión -->
                            <div class="admin-form__group">
                                <label class="admin-form__label"><i class="fa-solid fa-user-xmark"></i> Profesor ausente</label>
                                <div class="supl-self">
                                    <span class="supl-person__ava supl-self__ava">
                                        <?php if (!empty($sesion['avatar'])): ?><img src="<?= s($sesion['avatar']) ?>" alt=""><?php else: ?><?= s($inicial) ?><?php endif; ?>
                                    </span>
                                    <div class="supl-self__info">
                                        <span class="supl-self__name"><?= s($sesion['nombre'] ?? 'Colaborador') ?></span>
                                        <span class="supl-self__meta">Tu solicitud se registra como ausencia anticipada</span>
                                    </div>
                                    <span class="supl-badge supl-badge--info">Anticipada</span>
                                </div>
                            </div>

                            <div class="admin-form-row">
                                <?php
                                $fechaMin   = date('Y-m-d');
                                $fechaLabel = 'Fecha de la ausencia';
                                include __DIR__ . '/../_campo-fecha.php';
                                ?>
                                <?php include __DIR__ . '/_campo-motivo.php'; ?>
                            </div>

                            <!-- Horario interactivo: se marcan las clases del día elegido -->
                            <div class="admin-form__group">
                                <label class="admin-form__label">
                                    <i class="fa-regular fa-clock"></i> Horas a cubrir
                                    <span class="supl-picked-count" data-picked-count></span>
                                </label>
                                <div data-week></div>
                                <div data-week-inputs hidden></div>
                            </div>

                            <div class="admin-form__group">
                                <label class="admin-form__label" for="notas"><i class="fa-regular fa-note-sticky"></i> Notas para el profesor suplente</label>
                                <textarea id="notas" name="notas" class="admin-form__input" rows="3" placeholder="Indicaciones, material, en qué página va el grupo…"></textarea>
                                <span class="admin-form__hint">Las lee quien cubra tus clases.</span>
                            </div>

                            <div class="admin-form__group">
                                <label class="admin-form__label"><i class="fa-solid fa-paperclip"></i> Justificante <span style="font-weight:400;color:var(--text-gray);">(opcional)</span></label>
                                <label class="admin-file" data-file data-file-max="4">
                                    <input type="file" name="justificante" accept="application/pdf,image/jpeg,image/png,image/webp">
                                    <span class="admin-file__ico"><i class="fa-solid fa-paperclip"></i></span>
                                    <span class="admin-file__text">
                                        <span class="admin-file__title" data-file-title>Elige un archivo o arrástralo aquí</span>
                                        <span class="admin-file__hint" data-file-hint>PDF o imagen (JPG, PNG, WebP) · máximo 4 MB</span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <?php /* El submit también al cierre del formulario: dejarlo solo en el topbar confunde */ ?>
                        <div class="supl-form-actions">
                            <a href="/dashboard/suplencias" class="admin-btn admin-btn--ghost"><i class="fa-solid fa-xmark"></i> Cancelar</a>
                            <button type="submit" class="admin-btn admin-btn--primary"><i class="fa-solid fa-paper-plane"></i> Enviar solicitud</button>
                        </div>
                    </div>

                    <div class="admin-helper-card">
                        <img src="/build/assets/img/alex/bby-alex-piensa.png" alt="Alex" class="admin-helper-card__alex">
                        <h3 class="admin-helper-card__title">¿Cómo funciona?</h3>
                        <p class="admin-helper-card__text">Elige la fecha en que faltarás y toca en tu horario las clases que deben cubrirse. Prefectura asignará un suplente disponible y él confirmará la cobertura.</p>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>
