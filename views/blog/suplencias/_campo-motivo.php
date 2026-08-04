<?php
/**
 * Campo "Motivo" del módulo Suplencias: catálogo sugerido + opción "Otro" que
 * exige describirlo. La columna sigue siendo texto libre (ver Suplencia::MOTIVOS).
 * Variable opcional: $motivoValor (valor actual, para repintar tras un error).
 */
$motivoValor = trim((string)($motivoValor ?? ''));
$esCatalogo  = $motivoValor !== '' && in_array($motivoValor, \Model\Suplencia::MOTIVOS, true);
$esOtro      = $motivoValor !== '' && !$esCatalogo;
?>
<div class="admin-form__group" data-motivo>
    <label class="admin-form__label" for="motivo"><i class="fa-solid fa-circle-info"></i> Motivo</label>

    <div class="hor-select-wrap">
        <i class="fa-solid fa-circle-info"></i>
        <select id="motivo" name="motivo" class="hor-select supl-motivo__select" data-motivo-select required>
            <option value="">Elige un motivo…</option>
            <?php foreach (\Model\Suplencia::MOTIVOS as $m): ?>
            <option value="<?= s($m) ?>" <?= $motivoValor === $m ? 'selected' : '' ?>><?= s($m) ?></option>
            <?php endforeach; ?>
            <option value="Otro" <?= $esOtro ? 'selected' : '' ?>>Otro (describir)</option>
        </select>
    </div>

    <div class="admin-form__input-wrapper supl-motivo__otro" data-motivo-otro <?= $esOtro ? '' : 'hidden' ?>>
        <input type="text" name="motivo_otro" class="admin-form__input" maxlength="150"
               placeholder="Describe el motivo…" value="<?= $esOtro ? s($motivoValor) : '' ?>" data-motivo-otro-input>
    </div>
</div>
