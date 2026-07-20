<?php
/** @var \Model\Suplencia $suplencia  @var string $action */
$esEdicion = !empty($suplencia->id);
?>

<form action="<?= $action ?>" method="POST" class="admin-form" novalidate>
    <?php if ($esEdicion): ?><input type="hidden" name="id" value="<?= (int)$suplencia->id ?>"><?php endif; ?>

    <?php if (!empty($alertas['error'])): ?>
    <div class="admin-alerta admin-alerta--error" style="margin-bottom:18px;">
        <i class="fa-solid fa-circle-exclamation"></i>
        <ul class="admin-alerta__list"><?php foreach ($alertas['error'] as $e): ?><li><?= s($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <div class="supl-form-grid">
        <div class="admin-panel">
            <div class="admin-panel__header"><h2 class="admin-panel__title">Datos de la suplencia</h2></div>
            <div class="admin-form-section">

                <div class="admin-form-row">
                    <!-- AUSENTE (autocomplete) -->
                    <div class="admin-form__group">
                        <label class="admin-form__label"><i class="fa-solid fa-user-xmark"></i> Docente ausente</label>
                        <div class="picker<?= !empty($suplencia->ausente_id) ? ' has-value' : '' ?>" data-picker>
                            <div class="admin-form__input-wrapper">
                                <input type="text" class="admin-form__input" data-picker-input autocomplete="off"
                                       placeholder="Escribe un nombre…"
                                       value="<?= s($suplencia->ausente_nombre ?? '') ?>">
                                <button type="button" class="picker__clear" data-picker-clear aria-label="Quitar"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                            <input type="hidden" name="ausente_id" data-picker-value value="<?= (int)($suplencia->ausente_id ?? 0) ?>">
                            <div class="picker__results" data-picker-results></div>
                        </div>
                    </div>
                    <!-- SUPLENTE (autocomplete) -->
                    <div class="admin-form__group">
                        <label class="admin-form__label"><i class="fa-solid fa-user-check"></i> Suplente asignado</label>
                        <div class="picker<?= !empty($suplencia->suplente_id) ? ' has-value' : '' ?>" data-picker>
                            <div class="admin-form__input-wrapper">
                                <input type="text" class="admin-form__input" data-picker-input autocomplete="off"
                                       placeholder="Sin asignar…"
                                       value="<?= s($suplencia->suplente_nombre ?? '') ?>">
                                <button type="button" class="picker__clear" data-picker-clear aria-label="Quitar"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                            <input type="hidden" name="suplente_id" data-picker-value value="<?= (int)($suplencia->suplente_id ?? 0) ?>">
                            <div class="picker__results" data-picker-results></div>
                        </div>
                    </div>
                </div>

                <div class="admin-form-row">
                    <div class="admin-form__group">
                        <label class="admin-form__label" for="fecha"><i class="fa-regular fa-calendar"></i> Fecha</label>
                        <div class="admin-form__input-wrapper">
                            <input type="date" id="fecha" name="fecha" class="admin-form__input" required
                                   value="<?= s($suplencia->fecha ?? date('Y-m-d')) ?>">
                        </div>
                    </div>
                    <div class="admin-form__group">
                        <label class="admin-form__label" for="estado"><i class="fa-solid fa-flag"></i> Estado</label>
                        <div class="admin-form__input-wrapper">
                            <select id="estado" name="estado" class="admin-form__input">
                                <?php foreach (['pendiente'=>'Pendiente','confirmada'=>'Confirmada','cancelada'=>'Cancelada'] as $v=>$l): ?>
                                <option value="<?= $v ?>" <?= ($suplencia->estado ?? 'pendiente') === $v ? 'selected' : '' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="admin-form-row">
                    <div class="admin-form__group">
                        <label class="admin-form__label" for="grupo"><i class="fa-solid fa-users"></i> Grupo</label>
                        <div class="admin-form__input-wrapper">
                            <input type="text" id="grupo" name="grupo" class="admin-form__input" placeholder="Ej. 3° Primaria A" value="<?= s($suplencia->grupo ?? '') ?>">
                        </div>
                    </div>
                    <div class="admin-form__group">
                        <label class="admin-form__label" for="materia"><i class="fa-solid fa-book"></i> Materia</label>
                        <div class="admin-form__input-wrapper">
                            <input type="text" id="materia" name="materia" class="admin-form__input" placeholder="Ej. Matemáticas" value="<?= s($suplencia->materia ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="admin-form__group">
                    <label class="admin-form__label" for="motivo"><i class="fa-solid fa-circle-info"></i> Motivo</label>
                    <div class="admin-form__input-wrapper">
                        <input type="text" id="motivo" name="motivo" class="admin-form__input" placeholder="Ej. Cita médica" value="<?= s($suplencia->motivo ?? '') ?>">
                    </div>
                </div>

                <div class="admin-form__group">
                    <label class="admin-form__label" for="notas"><i class="fa-regular fa-note-sticky"></i> Notas</label>
                    <textarea id="notas" name="notas" class="admin-form__input" rows="3" placeholder="Indicaciones para el suplente…"><?= s($suplencia->notas ?? '') ?></textarea>
                </div>
            </div>
            <div class="admin-form-footer">
                <button type="submit" class="admin-btn admin-btn--primary"><i class="fa-solid fa-floppy-disk"></i> <?= $esEdicion ? 'Guardar cambios' : 'Registrar suplencia' ?></button>
                <a href="/dashboard/suplencias" class="admin-btn admin-btn--ghost">Cancelar</a>
            </div>
        </div>

        <div class="admin-helper-card">
            <img src="/build/assets/img/alex/bby-alex-piensa.png" alt="Alex" class="admin-helper-card__alex">
            <h3 class="admin-helper-card__title">Tip de Alex</h3>
            <p class="admin-helper-card__text">Empieza a escribir el nombre y elige al colaborador de la lista. Los suplentes y ausentes se toman del directorio de usuarios.</p>
            <div class="admin-tips">
                <div class="admin-tip"><i class="fa-solid fa-user-check"></i><span>Deja el suplente vacío si aún está <strong>pendiente</strong> de asignar.</span></div>
                <div class="admin-tip"><i class="fa-solid fa-flag"></i><span>Marca <strong>confirmada</strong> cuando el suplente acepte.</span></div>
            </div>
        </div>
    </div>
</form>

