<?php $paginaVista = 'blog-categorias-editar'; ?>
<?php
function hexToRgba(string $hex, float $a = 0.12): string {
    $hex = ltrim($hex, '#');
    return 'rgba(' . hexdec(substr($hex,0,2)) . ',' . hexdec(substr($hex,2,2)) . ',' . hexdec(substr($hex,4,2)) . ',' . $a . ')';
}
$activeColor = $categoria->color ?? '#4267ac';
$colores = ['#fc6722','#f5b400','#8ac926','#34a853','#46bdc6','#4285f4','#4267ac','#aa2296','#ea075a','#e51022'];
$colorTitles = ['Naranja','Amarillo','Lima','Verde','Teal','Azul','Azul marino','Morado','Rosa','Rojo'];
?>
<div class="admin-layout">

    <!-- ===================== SIDEBAR ===================== -->
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <!-- ===================== MAIN ===================== -->
    <div class="admin-main">

        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <div class="admin-breadcrumb">
                    <a href="/dashboard">Dashboard</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <a href="/dashboard/categorias">Categorías</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Editar categoría</span>
                </div>
                <span class="admin-topbar__title">Editar categoría</span>
            </div>
            <div class="admin-topbar__actions">
                <a href="/dashboard/categorias" class="admin-btn admin-btn--ghost">
                    <i class="fa-solid fa-xmark"></i> Cancelar
                </a>
                <button type="submit" form="form-editar-categoria" class="admin-btn admin-btn--primary">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
                </button>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn">
                        <i class="fa-solid fa-right-from-bracket"></i> Salir
                    </button>
                </form>
            </div>
        </header>

        <main class="admin-content">

            <?php if (!empty($alertas['error'])): ?>
            <div class="admin-alerta admin-alerta--error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul class="admin-alerta__list">
                    <?php foreach ($alertas['error'] as $error): ?>
                        <li><?= s($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form id="form-editar-categoria" action="/dashboard/categorias/editar" method="POST" novalidate>
                <input type="hidden" name="id"    value="<?= (int)$categoria->id ?>">
                <input type="hidden" id="color-value" name="color" value="<?= s($activeColor) ?>">

                <div class="admin-form-grid">

                    <!-- ── COLUMNA PRINCIPAL ── -->
                    <div>

                        <div class="admin-panel" style="margin-bottom:20px;">
                            <div class="admin-panel__header">
                                <h2 class="admin-panel__title">Información de la categoría</h2>
                            </div>
                            <div class="admin-form-section">

                                <div class="admin-form__group">
                                    <label class="admin-form__label" for="nombre">
                                        <i class="fa-solid fa-tag"></i> Nombre de la categoría
                                    </label>
                                    <div class="admin-form__input-wrapper">
                                        <input
                                            type="text"
                                            id="nombre"
                                            name="nombre"
                                            class="admin-form__input"
                                            value="<?= s($categoria->nombre ?? '') ?>"
                                            placeholder="Ej. Vida Escolar"
                                            required
                                            maxlength="60"
                                        >
                                    </div>
                                    <span class="admin-form__hint">Máximo 60 caracteres.</span>
                                </div>

                                <div class="admin-form__group">
                                    <label class="admin-form__label" for="slug">
                                        <i class="fa-solid fa-link"></i> URL amigable (slug)
                                    </label>
                                    <div class="admin-form__input-wrapper">
                                        <input
                                            type="text"
                                            id="slug"
                                            name="slug"
                                            class="admin-form__input"
                                            value="<?= s($categoria->slug ?? '') ?>"
                                            placeholder="vida-escolar"
                                        >
                                    </div>
                                    <div class="admin-form__slug-preview">
                                        /voces-bilbao/<span id="slug-display"><?= s($categoria->slug ?? 'tu-categoria') ?></span>
                                    </div>
                                    <span class="admin-form__hint">
                                        <i class="fa-solid fa-triangle-exclamation" style="color:#E67E22;margin-right:3px;"></i>
                                        Cambiar el slug romperá los enlaces existentes que apunten a esta categoría.
                                    </span>
                                </div>

                                <div class="admin-form__group">
                                    <label class="admin-form__label" for="descripcion">
                                        <i class="fa-regular fa-file-lines"></i> Descripción
                                        <span style="font-weight:400;color:var(--text-gray);font-size:0.78rem;">(opcional)</span>
                                    </label>
                                    <textarea
                                        id="descripcion"
                                        name="descripcion"
                                        class="admin-form__textarea"
                                        placeholder="Describe qué tipo de contenido agrupa esta categoría..."
                                        maxlength="240"
                                    ><?= s($categoria->descripcion ?? '') ?></textarea>
                                    <div style="display:flex;justify-content:flex-end;margin-top:5px;">
                                        <span id="desc-count" style="font-size:0.75rem;color:var(--text-gray);">0 / 240</span>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- COLOR -->
                        <div class="admin-panel">
                            <div class="admin-panel__header">
                                <h2 class="admin-panel__title">Color de la categoría</h2>
                            </div>
                            <div class="admin-form-section">

                                <div class="admin-form__group">
                                    <label class="admin-form__label">
                                        <i class="fa-solid fa-palette"></i> Color activo
                                    </label>
                                    <div class="admin-color-swatches">
                                        <?php foreach ($colores as $i => $c): ?>
                                        <div class="admin-color-swatch <?= $activeColor === $c ? 'selected' : '' ?>"
                                             data-color="<?= s($c) ?>"
                                             style="background:<?= s($c) ?>;"
                                             title="<?= s($colorTitles[$i]) ?>"></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div style="margin-top:24px;">
                                    <label class="admin-form__label" style="margin-bottom:12px;">
                                        <i class="fa-regular fa-eye"></i> Vista previa
                                    </label>
                                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                                        <span id="badge-preview" class="admin-badge"
                                              style="background:<?= hexToRgba($activeColor) ?>;color:<?= s($activeColor) ?>;font-size:0.85rem;padding:6px 16px;">
                                            <?= s($categoria->nombre ?? 'Vista previa') ?>
                                        </span>
                                        <span style="font-size:0.82rem;color:var(--text-gray);">Así se verá en el blog</span>
                                    </div>
                                </div>

                            </div>

                            <div class="admin-form-footer">
                                <button type="submit" class="admin-btn admin-btn--primary">
                                    <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
                                </button>
                                <a href="/dashboard/categorias" class="admin-btn admin-btn--ghost">Cancelar</a>
                            </div>
                        </div>

                        <!-- ZONA DE PELIGRO -->
                        <div class="admin-danger-zone">
                            <div class="admin-danger-zone__header">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span class="admin-danger-zone__title">Zona de peligro</span>
                            </div>
                            <div class="admin-danger-zone__body">
                                <div>
                                    <p style="font-size:0.9rem;font-weight:700;color:var(--col-herencia);margin-bottom:4px;">Eliminar esta categoría</p>
                                    <p class="admin-danger-zone__desc">
                                        Los artículos asignados quedarán sin clasificar. Esta acción no se puede deshacer.
                                    </p>
                                </div>
                                <button type="button" class="admin-btn admin-btn--danger"
                                    onclick="abrirModalEliminar(<?= (int)$categoria->id ?>, '<?= s(addslashes($categoria->nombre)) ?>')">
                                    <i class="fa-regular fa-trash-can"></i> Eliminar categoría
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- ── COLUMNA LATERAL ── -->
                    <div>

                        <div class="admin-helper-card">
                            <img src="/build/assets/img/alex/alex-point.png" alt="Alex" class="admin-helper-card__alex">
                            <h3 class="admin-helper-card__title">Editar categoría</h3>
                            <p class="admin-helper-card__text">
                                Puedes cambiar el nombre, la descripción y el color. Ten cuidado al modificar el slug.
                            </p>
                            <div class="admin-tips">
                                <div class="admin-tip">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    <span>Cambiar el slug romperá los URLs existentes del blog.</span>
                                </div>
                                <div class="admin-tip">
                                    <i class="fa-solid fa-palette"></i>
                                    <span>Cambiar el color se refleja de inmediato en todas las etiquetas del blog.</span>
                                </div>
                                <div class="admin-tip">
                                    <i class="fa-regular fa-newspaper"></i>
                                    <span>Los artículos asociados no se afectan al editar la categoría.</span>
                                </div>
                            </div>
                        </div>

                        <!-- ESTADÍSTICAS -->
                        <div class="admin-panel">
                            <div class="admin-panel__header">
                                <h2 class="admin-panel__title">Estadísticas</h2>
                            </div>
                            <div style="padding:20px 24px;display:flex;flex-direction:column;gap:14px;">
                                <div>
                                    <span style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-gray);">Artículos totales</span>
                                    <p style="font-size:1.6rem;font-weight:900;color:var(--col-herencia);letter-spacing:-0.03em;margin-top:3px;line-height:1;">
                                        <?= (int)$categoria->total_articulos ?>
                                    </p>
                                </div>
                                <?php if ($categoria->creado_en): ?>
                                <div>
                                    <span style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-gray);">Fecha de creación</span>
                                    <p style="font-size:0.9rem;color:var(--col-herencia);font-weight:600;margin-top:3px;">
                                        <?= s(date('d M Y', strtotime($categoria->creado_en))) ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <span style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-gray);">Color asignado</span>
                                    <div style="display:flex;align-items:center;gap:8px;margin-top:5px;">
                                        <span style="width:20px;height:20px;border-radius:5px;background:<?= s($activeColor) ?>;display:inline-block;"></span>
                                        <span style="font-size:0.85rem;font-weight:600;color:var(--col-herencia);"><?= s($activeColor) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            </form>

        </main>
    </div>

</div>

<!-- MODAL ELIMINAR -->
<div id="deleteModal" class="ubm-backdrop" aria-modal="true" role="dialog" aria-labelledby="ubm-title">
    <div class="ubm-card">
        <div class="ubm-icon"><i class="fa-solid fa-tag"></i></div>
        <h2 class="ubm-title" id="ubm-title">¿Eliminar categoría?</h2>
        <p class="ubm-text">Estás a punto de eliminar <strong id="ubm-name"></strong>. Los artículos asociados quedarán sin clasificar. Esta acción no se puede deshacer.</p>
        <div class="ubm-field">
            <label class="ubm-field__label" for="ubm-input">Escribe el nombre para confirmar</label>
            <div class="ubm-field__wrap">
                <input type="text" id="ubm-input" class="ubm-field__input" placeholder="Nombre de la categoría" autocomplete="off" spellcheck="false">
                <i class="fa-solid fa-check ubm-field__check" id="ubm-check"></i>
            </div>
        </div>
        <form method="POST" action="/dashboard/categorias/eliminar" id="ubm-form">
            <input type="hidden" name="id" id="ubm-id">
            <div class="ubm-actions">
                <button type="button" class="admin-btn admin-btn--ghost" onclick="cerrarModalEliminar()">Cancelar</button>
                <button type="submit" class="admin-btn ubm-confirm" id="ubm-submit" disabled>
                    <i class="fa-solid fa-trash-can"></i> Sí, eliminar
                </button>
            </div>
        </form>
    </div>
</div>


