<?php $paginaVista = 'blog-actualizaciones-form'; ?>
<?php
/**
 * Alta y edición de un anuncio. Una sola plantilla: la única diferencia es si hay `?id=`,
 * y tener dos ficheros casi idénticos garantiza que se desincronicen.
 *
 * ⚠️ El estado NO se elige aquí. Se guarda siempre como borrador y se publica con su
 * propio botón desde el listado: publicar bloquea a todo el claustro, y eso no puede ser
 * un `<select>` que se marca sin querer al guardar una corrección de ortografía.
 *
 * @var \Model\Actualizacion $act  @var bool $esNuevo  @var array $alertas
 */
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title"><?= $esNuevo ? 'Nueva actualización' : 'Editar actualización' ?></span>
            </div>
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

            <?php if (!$esNuevo && $act->estado === 'publicada'): ?>
            <div class="admin-alerta admin-alerta--warn" style="margin-bottom:16px;">
                <i class="fa-solid fa-bullhorn"></i>
                <span>
                    Este anuncio <strong>ya está publicado</strong>. Los cambios se verán al instante,
                    pero quien ya lo marcó como visto no volverá a recibirlo.
                </span>
            </div>
            <?php endif; ?>

            <form method="POST" action="/dashboard/actualizaciones/crear" enctype="multipart/form-data">
                <?php if (!$esNuevo): ?>
                <input type="hidden" name="id" value="<?= (int)$act->id ?>">
                <input type="hidden" name="estado" value="<?= s($act->estado) ?>">
                <?php endif; ?>

                <section class="admin-panel">
                    <div class="admin-panel__header"><h2 class="admin-panel__title">Contenido del anuncio</h2></div>

                    <div class="admin-form-section">
                        <div class="admin-form-row">
                            <div class="admin-form__group">
                                <label class="admin-form__label" for="titulo"><i class="fa-solid fa-heading"></i> Título</label>
                                <input type="text" id="titulo" name="titulo" class="admin-form__input" maxlength="160"
                                       value="<?= s((string)$act->titulo) ?>" placeholder="Ej. Ya puedes cancelar una suplencia" required>
                            </div>

                            <div class="admin-form__group">
                                <label class="admin-form__label" for="version"><i class="fa-solid fa-tag"></i> Versión <span style="font-weight:400;color:var(--text-gray);">(opcional)</span></label>
                                <input type="text" id="version" name="version" class="admin-form__input" maxlength="20"
                                       value="<?= s((string)$act->version) ?>" placeholder="v2.4">
                                <span class="admin-form__hint">Etiqueta libre: «v2.4», «Septiembre 2026»…</span>
                            </div>
                        </div>

                        <div class="admin-form__group">
                            <label class="admin-form__label" for="cuerpo"><i class="fa-regular fa-note-sticky"></i> Qué cambió</label>
                            <textarea id="cuerpo" name="cuerpo" class="admin-form__input" rows="8" required
                                      placeholder="Cuéntalo como se lo contarías a un profesor: qué puede hacer ahora que antes no."><?= s((string)$act->cuerpo) ?></textarea>
                            <span class="admin-form__hint">Los saltos de línea se respetan. Lo leerá todo el claustro antes de poder seguir trabajando, así que conviene que sea corto.</span>
                        </div>

                        <div class="admin-form__group">
                            <label class="admin-form__label"><i class="fa-regular fa-image"></i> Captura <span style="font-weight:400;color:var(--text-gray);">(opcional)</span></label>
                            <label class="admin-file" data-file data-file-max="4">
                                <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp">
                                <span class="admin-file__ico"><i class="fa-regular fa-image"></i></span>
                                <span class="admin-file__text">
                                    <span class="admin-file__title" data-file-title>Elige una imagen</span>
                                    <span class="admin-file__hint" data-file-hint>JPG, PNG o WebP · máx. 4 MB</span>
                                </span>
                            </label>
                            <?php if ($act->imagen): ?>
                            <img src="<?= s($act->imagen) ?>" alt="" class="act-form__previa">
                            <span class="admin-form__hint">Subir otra reemplaza esta.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="admin-form-footer">
                        <a href="/dashboard/actualizaciones" class="admin-btn admin-btn--ghost"><i class="fa-solid fa-xmark"></i> Cancelar</a>
                        <button type="submit" class="admin-btn admin-btn--primary">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <?= $esNuevo ? 'Guardar como borrador' : 'Guardar cambios' ?>
                        </button>
                    </div>
                </section>
            </form>

            <?php if ($esNuevo): ?>
            <p class="admin-form__hint" style="margin-top:12px;">
                <i class="fa-solid fa-circle-info"></i>
                Se guarda en borrador. Desde el listado lo publicas cuando quieras: ese es el momento
                en que empieza a bloquear.
            </p>
            <?php endif; ?>
        </main>
    </div>
</div>
