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

            <form method="POST" action="<?= s($accion) ?>" id="formGrupo" class="admin-panel cat-form cat-form--wide">
                <div class="admin-form__group">
                    <label class="admin-form__label" for="nombre">Nombre del grupo</label>
                    <input type="text" name="nombre" id="nombre" class="admin-form__input"
                           value="<?= s((string)$grupo->nombre) ?>" maxlength="80" required
                           placeholder="Ej. 2A Secundaria, Kinder 1…">
                    <small class="admin-form__hint">Debe ser único. Es el nombre que se busca al importar horarios por CSV.</small>
                </div>

                <?php /* Nivel como tabs (radios estilizados), mismo componente visual que el
                         filtro del listado. El orden de los grupos ya no se configura: se
                         deduce del nivel y, dentro de él, del nombre alfabéticamente. */ ?>
                <?php
                $nivelColorForm = [
                    'Maternal'     => '#fc6722',
                    'Kinder'       => '#f5b400',
                    'Primaria'     => '#8ac926',
                    'Secundaria'   => '#46bdc6',
                    'Bachillerato' => '#4267ac',
                ];
                ?>
                <div class="admin-form__group">
                    <label class="admin-form__label">Nivel académico</label>
                    <div class="cat-tabs cat-tabs--radio">
                        <?php foreach (\Model\Grupo::NIVELES as $n): ?>
                        <label class="cat-tab" style="--c:<?= $nivelColorForm[$n] ?? '#94a3b8' ?>;">
                            <input type="radio" name="nivel" value="<?= s($n) ?>" <?= $grupo->nivel === $n ? 'checked' : '' ?> required>
                            <span><?= s($n) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <small class="admin-form__hint">Fija la posición del grupo en los listados: primero por nivel, después alfabéticamente.</small>
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
