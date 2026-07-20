<?php $paginaVista = 'blog-noticias-categorias-editar'; ?>
<?php
function hexToRgba(string $hex, float $a = 0.12): string {
    $hex = ltrim($hex, '#');
    return 'rgba(' . hexdec(substr($hex,0,2)) . ',' . hexdec(substr($hex,2,2)) . ',' . hexdec(substr($hex,4,2)) . ',' . $a . ')';
}
$activeColor = $categoria->color ?? '#4267ac';
$colores     = ['#fc6722','#f5b400','#8ac926','#34a853','#46bdc6','#4285f4','#4267ac','#aa2296','#ea075a','#e51022'];
$colorTitles = ['Naranja','Amarillo','Lima','Verde','Teal','Azul','Azul marino','Morado','Rosa','Rojo'];
?>
<div class="admin-layout">

    <?php include __DIR__ . '/../../_sidebar.php'; ?>

    <div class="admin-main">

        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <div class="admin-breadcrumb">
                    <a href="/dashboard">Dashboard</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <a href="/dashboard/noticias">Noticias</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <a href="/dashboard/noticias/categorias">Categorías</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Editar categoría</span>
                </div>
                <span class="admin-topbar__title">Editar categoría de noticias</span>
            </div>
            <div class="admin-topbar__actions">
                <a href="/dashboard/noticias/categorias" class="admin-btn admin-btn--ghost">
                    <i class="fa-solid fa-xmark"></i> Cancelar
                </a>
                <button type="submit" form="form-categoria" class="admin-btn admin-btn--primary">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
                </button>
                <?php include __DIR__ . '/../../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</button>
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

            <form id="form-categoria" action="/dashboard/noticias/categorias/editar?id=<?= (int)$categoria->id ?>" method="POST" novalidate>
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
                                        <i class="fa-solid fa-folder-tree"></i> Nombre de la categoría
                                    </label>
                                    <div class="admin-form__input-wrapper">
                                        <input type="text" id="nombre" name="nombre" class="admin-form__input"
                                               placeholder="Ej. Vida Escolar" maxlength="60" required
                                               value="<?= s($categoria->nombre ?? '') ?>">
                                    </div>
                                    <span class="admin-form__hint">Máximo 60 caracteres.</span>
                                </div>

                                <div class="admin-form__group">
                                    <label class="admin-form__label" for="slug">
                                        <i class="fa-solid fa-link"></i> URL amigable (slug)
                                    </label>
                                    <div class="admin-form__input-wrapper">
                                        <input type="text" id="slug" name="slug" class="admin-form__input"
                                               placeholder="vida-escolar"
                                               value="<?= s($categoria->slug ?? '') ?>">
                                    </div>
                                    <div class="admin-form__slug-preview" id="slug-preview">
                                        /noticias/categoria/<span id="slug-display"><?= s($categoria->slug ?: 'tu-categoria') ?></span>
                                    </div>
                                    <span class="admin-form__hint">Cambiar el slug rompe enlaces existentes.</span>
                                </div>

                                <div class="admin-form__group">
                                    <label class="admin-form__label" for="descripcion">
                                        <i class="fa-regular fa-file-lines"></i> Descripción
                                        <span style="font-weight:400;color:var(--text-gray);font-size:0.78rem;">(opcional)</span>
                                    </label>
                                    <textarea id="descripcion" name="descripcion" class="admin-form__textarea"
                                              placeholder="Describe brevemente qué tipo de noticias agrupa esta categoría..."
                                              maxlength="240"
                                    ><?= s($categoria->descripcion ?? '') ?></textarea>
                                    <div style="display:flex;justify-content:flex-end;margin-top:5px;">
                                        <span id="desc-count" style="font-size:0.75rem;color:var(--text-gray);"><?= strlen($categoria->descripcion ?? '') ?> / 240</span>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="admin-panel">
                            <div class="admin-panel__header">
                                <h2 class="admin-panel__title">Color de la categoría</h2>
                            </div>
                            <div class="admin-form-section">
                                <div class="admin-form__group">
                                    <label class="admin-form__label"><i class="fa-solid fa-palette"></i> Elige un color</label>
                                    <div class="admin-color-swatches">
                                        <?php foreach ($colores as $i => $c): ?>
                                        <div class="admin-color-swatch <?= $activeColor === $c ? 'selected' : '' ?>"
                                             data-color="<?= s($c) ?>" style="background:<?= s($c) ?>;"
                                             title="<?= s($colorTitles[$i]) ?>"></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div style="margin-top:24px;">
                                    <label class="admin-form__label" style="margin-bottom:12px;"><i class="fa-regular fa-eye"></i> Vista previa</label>
                                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                                        <span id="badge-preview" class="admin-badge"
                                              style="background:<?= hexToRgba($activeColor) ?>;color:<?= s($activeColor) ?>;font-size:0.85rem;padding:6px 16px;">
                                            <?= s($categoria->nombre ?: 'Vista previa') ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="admin-form-footer">
                                <button type="submit" class="admin-btn admin-btn--primary">
                                    <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
                                </button>
                                <a href="/dashboard/noticias/categorias" class="admin-btn admin-btn--ghost">Cancelar</a>
                            </div>
                        </div>

                    </div>

                    <!-- ── COLUMNA LATERAL ── -->
                    <div>

                        <?php if (!empty($categoria->total_noticias)): ?>
                        <div class="admin-panel" style="margin-bottom:16px;">
                            <div class="admin-panel__header">
                                <h2 class="admin-panel__title">Estadísticas</h2>
                            </div>
                            <div style="padding:16px 24px;">
                                <div style="display:flex;align-items:center;gap:14px;">
                                    <div style="width:48px;height:48px;border-radius:12px;background:<?= hexToRgba($activeColor, 0.15) ?>;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:<?= s($activeColor) ?>;">
                                        <i class="fa-solid fa-newspaper"></i>
                                    </div>
                                    <div>
                                        <div style="font-size:1.8rem;font-weight:800;color:var(--col-herencia);line-height:1;"><?= (int)$categoria->total_noticias ?></div>
                                        <div style="font-size:.8rem;color:var(--text-gray);">noticia<?= $categoria->total_noticias !== 1 ? 's' : '' ?> asociada<?= $categoria->total_noticias !== 1 ? 's' : '' ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="admin-helper-card">
                            <img src="/build/assets/img/alex/alex-point.png" alt="Alex" class="admin-helper-card__alex">
                            <h3 class="admin-helper-card__title">Editar con cuidado</h3>
                            <p class="admin-helper-card__text">El slug define la URL. Cambiarlo rompe los enlaces existentes.</p>
                        </div>

                    </div>

                </div>

            </form>

        </main>
    </div>

</div>

