<?php $paginaVista = 'blog-suplencias-crear'; ?>
<?php
/** @var \Model\Suplencia $suplencia  @var array $grupos  @var array $aulas
 *  @var array $materias  @var array $alertas
 *  Esta vista es solo para quien puede agendar (prefectura/admin): el profesor entra por /solicitar,
 *  que fuerza origen 'anticipada'. Aquí sí se puede registrar una ausencia "sin aviso". */
$rolSesion   = $_SESSION['blog_usuario']['rol'] ?? '';
$tiposSesion = array_filter(array_map('trim', explode(',', (string)($_SESSION['blog_usuario']['tipo_personal'] ?? ''))));
// Solo prefectura/admin pueden registrar una ausencia "sin aviso"
$puedeSinAviso = $rolSesion === 'administrador' || in_array('prefecto', $tiposSesion, true);

$motivoValor = (string)($suplencia->motivo ?? '');
$fechaValor  = trim((string)($suplencia->fecha ?? '')) ?: date('Y-m-d');
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left"><span class="admin-topbar__title">Abrir suplencia</span></div>
            <div class="admin-topbar__actions">
                <button type="submit" form="form-crear-supl" class="admin-btn admin-btn--primary"><i class="fa-solid fa-floppy-disk"></i> Crear y agendar</button>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">
            <?php if (!empty($alertas['error'])): ?>
            <div class="admin-alerta admin-alerta--error" style="margin-bottom:16px;">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul class="admin-alerta__list"><?php foreach ($alertas['error'] as $e): ?><li><?= s($e) ?></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>

            <form id="form-crear-supl" method="POST" action="/dashboard/suplencias/crear" enctype="multipart/form-data" class="supl-form">
                <div class="supl-form-grid">
                    <div class="admin-panel">
                        <div class="admin-panel__header"><h2 class="admin-panel__title">Ausencia</h2></div>
                        <div class="admin-form-section">
                            <div class="admin-form-row">
                                <div class="admin-form__group">
                                    <label class="admin-form__label"><i class="fa-solid fa-user-xmark"></i> Profesor ausente</label>
                                    <div class="picker" data-picker>
                                        <div class="admin-form__input-wrapper">
                                            <input type="text" class="admin-form__input" data-picker-input autocomplete="off" placeholder="Escribe un nombre…">
                                            <button type="button" class="picker__clear" data-picker-clear aria-label="Quitar"><i class="fa-solid fa-xmark"></i></button>
                                        </div>
                                        <input type="hidden" name="profesor_ausente_id" data-picker-value value="<?= (int)($suplencia->profesor_ausente_id ?? 0) ?: '' ?>">
                                        <div class="picker__results" data-picker-results></div>
                                    </div>
                                </div>

                                <?php /* Fin de semana bloqueado (el default de $fechaHabiles): no hay
                                         clases que cubrir en sábado ni domingo. El calendario del
                                         listado sí puede prefijar la fecha vía ?fecha=. */ ?>
                                <?php
                                $fechaLabel = 'Fecha de la ausencia';
                                $fechaValor = $fechaPrefijada ?? $fechaValor;
                                include __DIR__ . '/../_campo-fecha.php';
                                ?>
                            </div>

                            <div class="admin-form-row">
                                <?php if ($puedeSinAviso): ?>
                                <div class="admin-form__group">
                                    <label class="admin-form__label"><i class="fa-solid fa-bolt"></i> Origen</label>
                                    <div class="supl-origen">
                                        <label class="supl-origen__opt"><input type="radio" name="origen" value="anticipada" <?= ($suplencia->origen ?? 'anticipada') !== 'sin_aviso' ? 'checked' : '' ?>> <span>Anticipada</span></label>
                                        <label class="supl-origen__opt"><input type="radio" name="origen" value="sin_aviso" <?= ($suplencia->origen ?? '') === 'sin_aviso' ? 'checked' : '' ?>> <span>Sin aviso</span></label>
                                    </div>
                                </div>
                                <?php else: ?>
                                <?php /* Un profesor solo puede abrir ausencias anticipadas: sin selector */ ?>
                                <div class="admin-form__group">
                                    <label class="admin-form__label"><i class="fa-solid fa-bolt"></i> Origen</label>
                                    <input type="hidden" name="origen" value="anticipada">
                                    <div class="supl-origen supl-origen--fijo">
                                        <span class="supl-origen__opt is-fixed"><i class="fa-solid fa-circle-check"></i> <span>Anticipada</span></span>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php include __DIR__ . '/_campo-motivo.php'; ?>
                            </div>

                            <!-- Horario del profesor elegido: se marcan las clases a cubrir -->
                            <div class="admin-form__group">
                                <label class="admin-form__label">
                                    <i class="fa-regular fa-clock"></i> Horas a cubrir
                                    <span class="supl-picked-count" data-picked-count></span>
                                </label>
                                <div data-week><p class="supl-week-empty">Elige al profesor ausente para ver su horario.</p></div>
                                <div data-week-inputs hidden></div>
                            </div>

                            <div class="admin-form-row">
                                <div class="admin-form__group">
                                    <label class="admin-form__label" for="notas"><i class="fa-regular fa-note-sticky"></i> Notas para el profesor suplente</label>
                                    <textarea id="notas" name="notas" class="admin-form__input" rows="2" placeholder="Indicaciones, material…"><?= s((string)($suplencia->notas ?? '')) ?></textarea>
                                    <span class="admin-form__hint">Las lee quien cubra la clase.</span>
                                </div>
                                <div class="admin-form__group">
                                    <label class="admin-form__label"><i class="fa-solid fa-paperclip"></i> Justificante <span style="font-weight:400;color:var(--text-gray);">(opcional)</span></label>
                                    <label class="admin-file" data-file data-file-max="<?= \Model\Suplencia::MAX_JUSTIFICANTE_MB ?>">
                                        <input type="file" name="justificante" accept="application/pdf,image/jpeg,image/png,image/webp">
                                        <span class="admin-file__ico"><i class="fa-solid fa-paperclip"></i></span>
                                        <span class="admin-file__text">
                                            <span class="admin-file__title" data-file-title>Elige un archivo</span>
                                            <span class="admin-file__hint" data-file-hint>PDF o imagen · máx. <?= \Model\Suplencia::MAX_JUSTIFICANTE_MB ?> MB</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <?php /* El submit también al cierre del formulario: dejarlo solo en el topbar confunde */ ?>
                        <div class="supl-form-actions">
                            <a href="/dashboard/suplencias" class="admin-btn admin-btn--ghost"><i class="fa-solid fa-xmark"></i> Cancelar</a>
                            <button type="submit" class="admin-btn admin-btn--primary"><i class="fa-solid fa-floppy-disk"></i> Crear y agendar</button>
                        </div>
                    </div>

                    <?php /* Un solo tip. Antes había dos bloques seguidos diciendo casi lo
                             mismo: esta tarjeta y la leyenda que el JS inyectaba aparte.
                             Ahora admin-supl-week.js escribe dentro de [data-week-legend],
                             que vive en esta misma tarjeta: sin fecha se lee el flujo
                             completo; con fecha, el día concreto que hay que tocar. */ ?>
                    <div class="admin-helper-card">
                        <img src="/build/assets/img/alex/bby-alex-piensa.png" alt="Alex" class="admin-helper-card__alex">
                        <h3 class="admin-helper-card__title">Tip de Alex</h3>
                        <p class="admin-helper-card__text">Al elegir al profesor y la fecha aparece su horario: toca las clases que hay que cubrir. En la siguiente pantalla asignarás a los suplentes sugeridos por el sistema.</p>
                        <p class="admin-helper-card__extra" data-week-legend></p>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>
