<?php
/**
 * Formulario de grupo, compartido por crear.php y editar.php.
 * Variables esperadas: $grupo, $alertas, $accion (URL), $esNueva (bool), $usos (array|null)
 */
$usos = $usos ?? null;
?>
<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">
                    <i class="fa-solid fa-layer-group"></i> <?= $esNueva ? 'Nuevo grupo' : 'Editar grupo' ?>
                </span>
            </div>
            <div class="admin-topbar__actions">
                <button type="submit" form="formGrupo" class="admin-btn admin-btn--primary">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar
                </button>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">
            <?php if (!empty($alertas['error'])): ?>
            <div class="admin-alerta admin-alerta--error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul class="admin-alerta__list">
                    <?php foreach ($alertas['error'] as $e): ?><li><?= s($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($usos && array_sum($usos)): ?>
            <div class="admin-alerta admin-alerta--success cat-aviso">
                <i class="fa-solid fa-circle-info"></i>
                Este grupo tiene <strong><?= (int)$usos['horarios'] ?></strong> clase(s)
                y <strong><?= (int)$usos['suplencias'] ?></strong> cobertura(s).
                Al renombrarlo, el cambio se verá en todas ellas.
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= s($accion) ?>" id="formGrupo" class="admin-panel cat-form">
                <div class="admin-form__group">
                    <label class="admin-form__label" for="nombre">Nombre del grupo</label>
                    <input type="text" name="nombre" id="nombre" class="admin-form__input"
                           value="<?= s((string)$grupo->nombre) ?>" maxlength="80" required
                           placeholder="Ej. 2A Secundaria, Kinder 1…">
                    <small class="admin-form__hint">Debe ser único. Es el nombre que se busca al importar horarios por CSV.</small>
                </div>

                <div class="cat-form__row">
                    <div class="admin-form__group">
                        <label class="admin-form__label" for="nivel">Nivel académico</label>
                        <select name="nivel" id="nivel" class="hor-select" required>
                            <option value="">Elige un nivel…</option>
                            <?php foreach (\Model\Grupo::NIVELES as $n): ?>
                            <option value="<?= s($n) ?>" <?= $grupo->nivel === $n ? 'selected' : '' ?>><?= s($n) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="admin-form__group">
                        <label class="admin-form__label" for="orden">Orden</label>
                        <input type="number" name="orden" id="orden" class="admin-form__input"
                               value="<?= (int)$grupo->orden ?>" min="1" max="999" required>
                        <small class="admin-form__hint">Posición en la secuencia académica, de menor a mayor.</small>
                    </div>
                </div>

                <div class="cat-form__acts">
                    <a href="/dashboard/grupos" class="admin-btn admin-btn--ghost">Cancelar</a>
                    <button type="submit" class="admin-btn admin-btn--primary">
                        <i class="fa-solid fa-floppy-disk"></i> <?= $esNueva ? 'Crear grupo' : 'Guardar cambios' ?>
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>
