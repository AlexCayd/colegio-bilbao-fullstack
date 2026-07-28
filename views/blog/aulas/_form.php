<?php
/**
 * Formulario de aula, compartido por crear.php y editar.php.
 * Variables esperadas: $aula, $alertas, $accion (URL), $esNueva (bool), $usos (array|null)
 */
$usos = $usos ?? null;
?>
<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">
                    <i class="fa-solid fa-door-open"></i> <?= $esNueva ? 'Nueva aula' : 'Editar aula' ?>
                </span>
            </div>
            <div class="admin-topbar__actions">
                <button type="submit" form="formAula" class="admin-btn admin-btn--primary">
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
                Esta aula se usa en <strong><?= (int)$usos['horarios'] ?></strong> clase(s)
                y <strong><?= (int)$usos['suplencias'] ?></strong> cobertura(s).
                Al renombrarla, el cambio se verá en todas ellas.
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= s($accion) ?>" id="formAula" class="admin-panel cat-form">
                <div class="admin-form__group">
                    <label class="admin-form__label" for="nombre">Nombre del aula</label>
                    <input type="text" name="nombre" id="nombre" class="admin-form__input"
                           value="<?= s((string)$aula->nombre) ?>" maxlength="80" required
                           placeholder="Ej. Sec 2A, Laboratorio, Cedro…">
                    <small class="admin-form__hint">Debe ser único. Es el nombre que se busca al importar horarios por CSV.</small>
                </div>

                <div class="admin-form__group">
                    <label class="admin-form__label" for="descripcion">Descripción <span class="cat-opt">(opcional)</span></label>
                    <input type="text" name="descripcion" id="descripcion" class="admin-form__input"
                           value="<?= s((string)$aula->descripcion) ?>" maxlength="160"
                           placeholder="Ej. Planta alta, capacidad 30">
                </div>

                <div class="cat-form__acts">
                    <a href="/dashboard/aulas" class="admin-btn admin-btn--ghost">Cancelar</a>
                    <button type="submit" class="admin-btn admin-btn--primary">
                        <i class="fa-solid fa-floppy-disk"></i> <?= $esNueva ? 'Crear aula' : 'Guardar cambios' ?>
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>
