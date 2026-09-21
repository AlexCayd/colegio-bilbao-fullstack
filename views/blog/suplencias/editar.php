<?php $paginaVista = 'blog-suplencias-editar'; ?>
<?php
/**
 * Editar una ausencia ya abierta.
 *
 * @var \Model\Suplencia $suplencia  @var array $alertas
 * @var bool $veJustif  @var array|null $justifInfo
 *
 * SOLO lo blando: motivo, notas y justificante. La fecha y el profesor ausente se
 * muestran como dato, no como campo — las horas de cobertura se fijaron leyendo el
 * horario de ESA persona en ESE día, y hay suplentes ya asignados y notificados sobre
 * ellas. Cambiar cualquiera de los dos dejaría unas horas que no corresponden a nada.
 * Para eso está cancelar y volver a abrir.
 *
 * Reutiliza los componentes del alta (`.supl-form`, `.admin-file`, `_campo-motivo.php`)
 * y `.admin-danger-zone` para la cancelación, así que no estrena SCSS propio.
 */
$motivoValor = (string)($suplencia->motivo ?? '');
$estadoLabel = \Model\Suplencia::ESTADO_LABEL;
$cancelada   = $suplencia->estado === 'cancelada';
$estadoJ     = $suplencia->estadoJustificante();
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left"><span class="admin-topbar__title">Editar suplencia</span></div>
            <div class="admin-topbar__actions">
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

            <?php if ($cancelada): ?>
            <div class="admin-alerta admin-alerta--warn" style="margin-bottom:16px;">
                <i class="fa-solid fa-ban"></i>
                <span>Esta suplencia está <strong>cancelada</strong>. Se conserva como registro, pero ya no se le asignan coberturas.</span>
            </div>
            <?php endif; ?>

            <form id="form-editar-supl" method="POST" action="/dashboard/suplencias/editar" enctype="multipart/form-data" class="supl-form">
                <input type="hidden" name="id" value="<?= (int)$suplencia->id ?>">

                <div class="admin-panel">
                    <div class="admin-panel__header"><h2 class="admin-panel__title">Ausencia</h2></div>

                    <div class="admin-form-section">

                        <?php /* Lo que NO se edita, como dato y con su porqué a la vista: un
                                 campo deshabilitado sin explicación se lee como un fallo. */ ?>
                        <div class="admin-form__group">
                            <label class="admin-form__label"><i class="fa-solid fa-lock"></i> Datos fijos</label>
                            <div class="supl-facts">
                                <span class="supl-fact">
                                    <i class="fa-solid fa-user-xmark"></i>
                                    <b><?= s($suplencia->ausente_nombre ?: 'Profesor') ?></b>
                                    <small>Profesor ausente</small>
                                </span>
                                <span class="supl-fact">
                                    <i class="fa-regular fa-calendar"></i>
                                    <b><?= s(fecha_larga($suplencia->fecha)) ?></b>
                                    <small><?= date('d/m/Y', strtotime($suplencia->fecha)) ?></small>
                                </span>
                                <span class="supl-fact">
                                    <i class="fa-solid fa-circle-half-stroke"></i>
                                    <b><?= s($estadoLabel[$suplencia->estado] ?? $suplencia->estado) ?></b>
                                    <small><?= $suplencia->origen === 'sin_aviso' ? 'Sin aviso' : 'Anticipada' ?></small>
                                </span>
                                <span class="supl-fact">
                                    <i class="fa-regular fa-clock"></i>
                                    <b><?= (int)$suplencia->total_horas ?> h</b>
                                    <small>a cubrir</small>
                                </span>
                            </div>
                            <span class="admin-form__hint">
                                La fecha y el profesor no se cambian: las horas de cobertura salen de su
                                horario de ese día y ya hay suplentes avisados. Si la ausencia era otra,
                                cancela esta y abre una nueva.
                            </span>
                        </div>

                        <div class="admin-form-row">
                            <?php include __DIR__ . '/_campo-motivo.php'; ?>

                            <div class="admin-form__group">
                                <label class="admin-form__label" for="notas"><i class="fa-regular fa-note-sticky"></i> Notas para el profesor suplente</label>
                                <textarea id="notas" name="notas" class="admin-form__input" rows="3" placeholder="Indicaciones, material…"><?= s((string)($suplencia->notas ?? '')) ?></textarea>
                                <span class="admin-form__hint">Las lee quien cubra la clase.</span>
                            </div>
                        </div>

                        <div class="admin-form__group">
                            <label class="admin-form__label">
                                <i class="fa-solid fa-paperclip"></i> Justificante
                                <?php if ($suplencia->origen === 'sin_aviso'): ?>
                                <span style="font-weight:400;color:var(--pal-rojo);">(obligatorio: ausencia sin aviso)</span>
                                <?php else: ?>
                                <span style="font-weight:400;color:var(--text-gray);">(opcional)</span>
                                <?php endif; ?>
                            </label>

                            <?php /* Los cuatro estados de estadoJustificante(), no un booleano:
                                     'resuelto' pone `justificante` a NULL, así que mirar solo si el
                                     campo está vacío le decía "falta el justificante" a quien
                                     acababa de tener el suyo aprobado. */ ?>
                            <?php if ($estadoJ === 'resuelto'): ?>
                            <p class="admin-form__hint" style="margin-bottom:8px;">
                                <i class="fa-solid fa-circle-check" style="color:var(--pal-verde);"></i>
                                Ya se revisó y resolvió<?= $suplencia->resolutor_nombre ? ' (' . s($suplencia->resolutor_nombre) . ')' : '' ?>.
                                El archivo se eliminó del servidor. Puedes adjuntar otro si hace falta.
                            </p>
                            <?php elseif ($veJustif && $justifInfo): ?>
                            <p class="admin-form__hint" style="margin-bottom:8px;">
                                <i class="fa-solid <?= s($justifInfo['icono']) ?>"></i>
                                Hay uno cargado: <strong><?= s($justifInfo['nombre']) ?></strong> · <?= s($justifInfo['peso']) ?>
                                — <a href="/dashboard/suplencias/justificante?id=<?= (int)$suplencia->id ?>">Descargar</a>.
                                Subir otro <strong>reemplaza</strong> el actual.
                            </p>
                            <?php elseif ($veJustif && !empty($suplencia->justificante)): ?>
                            <p class="admin-form__hint" style="margin-bottom:8px;">
                                <i class="fa-solid fa-triangle-exclamation" style="color:var(--pal-ambar);"></i>
                                Consta un justificante, pero el archivo ya no está en el servidor. Vuelve a subirlo.
                            </p>
                            <?php endif; ?>

                            <label class="admin-file" data-file data-file-max="<?= \Model\Suplencia::MAX_JUSTIFICANTE_MB ?>">
                                <input type="file" name="justificante" accept="application/pdf,image/jpeg,image/png,image/webp,text/plain">
                                <span class="admin-file__ico"><i class="fa-solid fa-paperclip"></i></span>
                                <span class="admin-file__text">
                                    <span class="admin-file__title" data-file-title>Elige un archivo</span>
                                    <span class="admin-file__hint" data-file-hint>PDF, imagen o texto · máx. <?= \Model\Suplencia::MAX_JUSTIFICANTE_MB ?> MB</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="supl-form-actions">
                        <a href="/dashboard/suplencias/agendar?id=<?= (int)$suplencia->id ?>" class="admin-btn admin-btn--ghost"><i class="fa-solid fa-arrow-left"></i> Volver</a>
                        <button type="submit" class="admin-btn admin-btn--primary"><i class="fa-solid fa-floppy-disk"></i> Guardar cambios</button>
                    </div>
                </div>
            </form>

            <?php /* Cancelar va fuera del <form> de edición: es otra acción, con otro destino
                     y consecuencias distintas. Meterla dentro invitaría a pulsarla creyendo
                     que guarda. */ ?>
            <?php if (!$cancelada): ?>
            <section class="admin-panel admin-danger-zone" data-danger style="margin-top:18px;">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title"><i class="fa-solid fa-ban"></i> Cancelar la suplencia</h2>
                </div>
                <div class="admin-form-section">
                    <p class="admin-form__hint" style="margin-bottom:12px;">
                        Si la ausencia ya no va a ocurrir. <strong>No borra el registro</strong>: queda
                        marcada como cancelada y deja de aparecer como trabajo pendiente. Se avisa al
                        profesor, a quien tuviera una cobertura asignada y a dirección.
                    </p>
                    <form method="POST" action="/dashboard/suplencias/cancelar">
                        <input type="hidden" name="id" value="<?= (int)$suplencia->id ?>">
                        <div class="admin-form__group">
                            <label class="admin-form__label" for="motivo_cancelacion"><i class="fa-solid fa-comment"></i> ¿Por qué se cancela?</label>
                            <input type="text" id="motivo_cancelacion" name="motivo_cancelacion" class="admin-form__input"
                                   maxlength="150" required placeholder="Ej. El profesor sí va a asistir">
                            <span class="admin-form__hint">Viaja dentro del aviso, para que nadie tenga que preguntar.</span>
                        </div>
                        <button type="submit" class="admin-btn admin-btn--danger"><i class="fa-solid fa-ban"></i> Cancelar suplencia</button>
                    </form>
                </div>
            </section>
            <?php endif; ?>

        </main>
    </div>
</div>
