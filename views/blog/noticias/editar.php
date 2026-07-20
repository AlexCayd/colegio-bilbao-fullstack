<?php $paginaVista = 'blog-noticias-editar'; ?>
<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">

        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <div class="admin-breadcrumb">
                    <a href="/dashboard">Dashboard</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <a href="/dashboard/noticias">Noticias</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Editar noticia</span>
                </div>
                <span class="admin-topbar__title">Editar noticia</span>
            </div>
            <div class="admin-topbar__actions">
                <?php if (!empty($noticia->slug) && $noticia->estado === 'publicado'): ?>
                <a href="/noticias/<?= s($noticia->slug) ?>" target="_blank" class="admin-btn admin-btn--ghost" style="font-size:.8rem;">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Ver publicada
                </a>
                <?php endif; ?>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</button>
                </form>
            </div>
        </header>

        <main class="admin-content">

            <?php if (!empty($alertas['error'])): ?>
            <div class="admin-alerta admin-alerta--error" id="alertaError">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul class="admin-alerta__list">
                    <?php foreach ($alertas['error'] as $msg): ?>
                        <li><?= s($msg) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="admin-alerta__close" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <?php endif; ?>

            <form action="/dashboard/noticias/editar?id=<?= (int)$noticia->id ?>" method="POST" enctype="multipart/form-data" class="article-editor-layout" id="noticiaForm">

                <!-- ===== COLUMNA PRINCIPAL ===== -->
                <div class="article-editor-main">

                    <!-- Título -->
                    <div class="article-title-field">
                        <input type="text" name="titulo" id="titulo" placeholder="Escribe el título de la noticia..."
                               class="article-title-input" autocomplete="off" value="<?= s($noticia->titulo ?? '') ?>">
                        <div class="article-slug-preview">
                            <span class="article-slug-label">URL:</span>
                            <span class="article-slug-value">noticias/<em id="slugPreviewText"><?= s($noticia->slug ?? 'el-titulo-de-la-noticia') ?></em></span>
                            <button type="button" class="article-slug-edit-btn" id="slugToggleBtn" title="Editar slug manualmente">
                                <i class="fa-solid fa-pen"></i> Editar slug
                            </button>
                        </div>
                        <div id="slugEditWrap" style="display:none;margin-top:10px;">
                            <input type="text" name="slug" id="slugManual" class="admin-input" style="font-size:.82rem;"
                                   placeholder="slug-de-la-noticia" value="<?= s($noticia->slug ?? '') ?>">
                        </div>
                        <input type="hidden" name="slug" id="slugHidden" value="<?= s($noticia->slug ?? '') ?>">
                        <div style="margin-top:8px;">
                            <span class="seo-hint" id="seoTituloHint"></span>
                        </div>
                    </div>

                    <!-- Extracto -->
                    <div class="admin-field-group">
                        <label class="admin-field-label" for="extracto">
                            Extracto
                            <span class="admin-field-hint">Descripción breve para listados y SEO. Máximo 200 caracteres.</span>
                        </label>
                        <textarea name="extracto" id="extracto" rows="3" class="admin-textarea"
                                  placeholder="Una o dos frases que resuman la noticia..." maxlength="200"
                        ><?= s($noticia->extracto ?? '') ?></textarea>
                        <div class="admin-char-count"><span id="extractoCount"><?= strlen($noticia->extracto ?? '') ?></span> / 200</div>
                    </div>

                    <!-- Contenido WYSIWYG -->
                    <div class="admin-field-group">
                        <label class="admin-field-label">Contenido</label>
                        <div class="editor-toolbar" id="editorToolbar">
                            <button type="button" class="editor-toolbar__btn" data-cmd="bold"><i class="fa-solid fa-bold"></i></button>
                            <button type="button" class="editor-toolbar__btn" data-cmd="italic"><i class="fa-solid fa-italic"></i></button>
                            <div class="editor-toolbar__sep"></div>
                            <button type="button" class="editor-toolbar__btn" data-cmd="h2"><i class="fa-solid fa-heading"></i><span style="font-size:.65rem;font-weight:800;margin-left:1px;">2</span></button>
                            <button type="button" class="editor-toolbar__btn" data-cmd="h3"><i class="fa-solid fa-heading"></i><span style="font-size:.65rem;font-weight:800;margin-left:1px;">3</span></button>
                            <div class="editor-toolbar__sep"></div>
                            <button type="button" class="editor-toolbar__btn" data-cmd="list"><i class="fa-solid fa-list-ul"></i></button>
                            <button type="button" class="editor-toolbar__btn" data-cmd="quote" title="Cita"><i class="fa-solid fa-quote-left"></i></button>
                            <button type="button" class="editor-toolbar__btn" data-cmd="plain" title="Texto normal"><i class="fa-solid fa-paragraph"></i></button>
                            <button type="button" class="editor-toolbar__btn" data-cmd="link"><i class="fa-solid fa-link"></i></button>
                        </div>
                        <div id="wysiwyg" class="wysiwyg-editor" contenteditable="true"
                             data-placeholder="Escribe aquí el cuerpo de la noticia..."
                             role="textbox" aria-multiline="true"
                        ><?= $noticia->contenido ?? '' ?></div>
                        <input type="hidden" name="contenido" id="contenidoHidden" value="<?= s($noticia->contenido ?? '') ?>">
                    </div>

                    <!-- Imagen de portada -->
                    <div class="admin-field-group">
                        <label class="admin-field-label">
                            Imagen de portada
                            <span class="admin-field-hint">Deja vacío para mantener la imagen actual. JPG, PNG o WebP · Máx. 20 MB.</span>
                        </label>
                        <div class="image-upload-zone <?= !empty($noticia->portada) ? 'image-upload-zone--has-image' : '' ?>" id="imageUploadZone">
                            <div class="image-upload-zone__content" id="imageUploadContent"
                                 style="<?= !empty($noticia->portada) ? 'display:none;' : '' ?>">
                                <div class="image-upload-zone__icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                                <p class="image-upload-zone__text">Arrastra una imagen aquí o</p>
                                <label for="portada" class="image-upload-zone__btn">Seleccionar archivo</label>
                                <p class="image-upload-zone__hint">JPG, PNG o WebP · Máx. 20 MB</p>
                            </div>
                            <img src="<?= !empty($noticia->portada) ? s($noticia->portada) : '' ?>"
                                 alt="<?= s($noticia->portada_alt ?? '') ?>"
                                 class="image-upload-zone__preview" id="imagePreview"
                                 style="<?= !empty($noticia->portada) ? '' : 'display:none;' ?>">
                            <input type="file" name="portada" id="portada" accept="image/jpeg,image/png,image/webp" style="display:none;">
                        </div>
                        <?php if (!empty($noticia->portada)): ?>
                        <div style="margin-top:8px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <label for="portada" class="admin-btn admin-btn--ghost" style="font-size:.8rem;cursor:pointer;">
                                <i class="fa-solid fa-arrows-rotate"></i> Cambiar imagen
                            </label>
                            <a href="<?= s($noticia->portada) ?>" download class="admin-btn admin-btn--ghost" style="font-size:.8rem;">
                                <i class="fa-solid fa-download"></i> Descargar portada
                            </a>
                            <span style="font-size:.78rem;color:#94A3B8;">Imagen actual: <?= basename($noticia->portada) ?></span>
                        </div>
                        <?php else: ?>
                        <p class="admin-field-hint" style="margin-top:8px;">Esta noticia no tiene portada aún, agrégala cuanto antes.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Alt de portada -->
                    <div class="admin-field-group">
                        <label class="admin-field-label" for="portada_alt">
                            Texto alternativo de la imagen
                            <span class="admin-field-hint">Describe la imagen para accesibilidad y SEO.</span>
                        </label>
                        <input type="text" name="portada_alt" id="portada_alt" class="admin-input"
                               placeholder="Ej. Alumnos del Colegio Bilbao en el evento de..."
                               value="<?= s($noticia->portada_alt ?? '') ?>">
                    </div>

                    <!-- Zona de peligro -->
                    <?php if (($_SESSION['blog_usuario']['rol'] ?? '') === 'administrador'): ?>
                    <div class="admin-danger-zone">
                        <div class="admin-danger-zone__header">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Zona de peligro
                        </div>
                        <div class="admin-danger-zone__body">
                            <div>
                                <p class="admin-danger-zone__label">Eliminar esta noticia</p>
                                <p class="admin-danger-zone__desc">Esta acción es permanente y no se puede deshacer.</p>
                            </div>
                            <button type="button" class="admin-btn admin-btn--danger"
                                    onclick="confirmarEliminar(<?= (int)$noticia->id ?>, '<?= s(addslashes($noticia->titulo ?? '')) ?>')">
                                <i class="fa-regular fa-trash-can"></i> Eliminar noticia
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

                <!-- ===== PANEL LATERAL ===== -->
                <aside class="article-editor-sidebar">

                    <!-- Revisión pendiente (solo admins) -->
                    <?php if (!empty($noticia->envio_revision) && ($_SESSION['blog_usuario']['rol'] ?? '') === 'administrador'): ?>
                    <div class="editor-panel editor-panel--review">
                        <div class="editor-panel__header rev-panel-header">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            En revisión
                            <span class="rev-panel-autor">· <?= s($noticia->autor_nombre ?? 'editor') ?></span>
                        </div>
                        <div class="editor-panel__body">
                            <?php
                            $vpN = null;
                            if (!empty($noticia->version_pendiente)) {
                                $vpN = json_decode($noticia->version_pendiente, true);
                            }
                            ?>
                            <?php if (is_array($vpN)): ?>
                            <div class="rev-pending-diff" style="margin-bottom:12px;">
                                <p class="rev-pending-diff__label">
                                    <i class="fa-solid fa-code-compare"></i> Cambios propuestos
                                </p>
                                <?php if (!empty($vpN['titulo']) && $vpN['titulo'] !== $noticia->titulo): ?>
                                <div class="rev-diff-row">
                                    <span class="rev-diff-row__key">Título:</span>
                                    <span class="rev-diff-row__val"><?= s($vpN['titulo']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($vpN['extracto']) && $vpN['extracto'] !== $noticia->extracto): ?>
                                <div class="rev-diff-row">
                                    <span class="rev-diff-row__key">Extracto:</span>
                                    <span class="rev-diff-row__val"><?= s($vpN['extracto']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($vpN['contenido']) && $vpN['contenido'] !== $noticia->contenido): ?>
                                <div class="rev-diff-row">
                                    <span class="rev-diff-row__key">Contenido:</span>
                                    <span class="rev-diff-row__val rev-diff-row__val--modified">Modificado</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <!-- Aprobar -->
                            <form method="POST" action="/dashboard/noticias/aprobar" class="rev-aprobar-form" style="margin-bottom:10px;">
                                <input type="hidden" name="id" value="<?= (int)$noticia->id ?>">
                                <select name="estado" class="admin-select rev-select" id="revEstadoNot" style="width:100%;margin-bottom:8px;font-size:.82rem;">
                                    <option value="publicado">Publicar ahora</option>
                                    <option value="programado">Programar fecha</option>
                                </select>
                                <input type="datetime-local" name="fecha_publicacion" class="admin-input" id="revFechaNot" style="display:none;width:100%;font-size:.82rem;margin-bottom:8px;">
                                <button type="submit" class="admin-btn admin-btn--primary" style="width:100%;font-size:.82rem;">
                                    <i class="fa-solid fa-check"></i> Aprobar
                                </button>
                            </form>
                            <!-- Rechazar -->
                            <button type="button" class="rev-rechazar-toggle rev-rechazar-toggle--v2" data-target="revRechazarNot">
                                <i class="fa-solid fa-comment-exclamation"></i> Devolver feedback
                            </button>
                            <div id="revRechazarNot" class="rev-feedback-panel" style="display:none;">
                                <p class="rev-feedback-para">
                                    <i class="fa-solid fa-user-pen"></i>
                                    Enviando feedback a <strong><?= s($noticia->autor_nombre ?? 'editor') ?></strong>
                                </p>
                                <form method="POST" action="/dashboard/noticias/rechazar">
                                    <input type="hidden" name="id" value="<?= (int)$noticia->id ?>">
                                    <div style="position:relative;">
                                        <textarea name="comentario" class="rev-textarea" rows="4" maxlength="600"
                                                  placeholder="Explica qué debe corregir antes de publicar…"
                                                  required></textarea>
                                        <span class="rev-char-count">0/600</span>
                                    </div>
                                    <button type="submit" class="admin-btn rev-send-btn">
                                        <i class="fa-solid fa-paper-plane"></i> Enviar feedback
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Feedback del admin (visible para editores cuando existe) -->
                    <?php if (($_SESSION['blog_usuario']['rol'] ?? '') === 'editor' && !empty($noticia->comentario_revision)): ?>
                    <div class="editor-feedback-banner">
                        <div class="editor-feedback-banner__head">
                            <i class="fa-solid fa-comment-exclamation"></i>
                            Retroalimentación del administrador
                        </div>
                        <p class="editor-feedback-banner__body">
                            <?= nl2br(htmlspecialchars($noticia->comentario_revision)) ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <!-- Publicación -->
                    <div class="editor-panel">
                        <div class="editor-panel__header"><i class="fa-solid fa-rocket"></i> Publicación</div>
                        <div class="editor-panel__body">
                            <?php $esEditor = ($_SESSION['blog_usuario']['rol'] ?? '') === 'editor'; ?>
                            <?php if ($esEditor): ?>
                                <input type="hidden" name="estado" value="borrador">
                                <div class="editor-estado-badge"><i class="fa-regular fa-file-lines"></i> Borrador</div>
                            <?php else: ?>
                            <div class="admin-field-group">
                                <label class="admin-field-label" for="estado">Estado</label>
                                <select name="estado" id="estado" class="admin-select">
                                    <option value="borrador"   <?= ($noticia->estado ?? 'borrador') === 'borrador'   ? 'selected' : '' ?>>Borrador</option>
                                    <option value="publicado"  <?= ($noticia->estado ?? '') === 'publicado'  ? 'selected' : '' ?>>Publicado</option>
                                    <option value="programado" <?= ($noticia->estado ?? '') === 'programado' ? 'selected' : '' ?>>Programado</option>
                                </select>
                            </div>
                            <div class="admin-field-group" id="fechaPublicacionGroup"
                                 style="display:<?= ($noticia->estado ?? '') === 'programado' ? 'block' : 'none' ?>;">
                                <label class="admin-field-label" for="fecha_publicacion">Fecha de publicación</label>
                                <input type="datetime-local" name="fecha_publicacion" id="fecha_publicacion" class="admin-input"
                                       value="<?= s($noticia->fecha_publicacion ?? '') ?>">
                            </div>
                            <?php endif; ?>
                            <div class="editor-panel__divider"></div>
                            <div class="admin-field-group" style="margin-bottom:0;">
                                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:.85rem;font-weight:600;color:#374C69;">
                                    <input type="checkbox" name="destacada" id="destacada" value="1"
                                           <?= !empty($noticia->destacada) ? 'checked' : '' ?>
                                           style="width:16px;height:16px;accent-color:#D69E2E;">
                                    <span><i class="fa-solid fa-star" style="color:#D69E2E;margin-right:4px;"></i> Noticia destacada</span>
                                </label>
                            </div>
                            <div class="editor-panel__divider"></div>
                            <div class="editor-panel__actions">
                                <button type="submit" id="submitBtn" class="admin-btn admin-btn--secondary submit-full">
                                    <i id="submitIcon" class="fa-regular fa-floppy-disk"></i>
                                    <span id="submitLabel">Guardar cambios</span>
                                </button>
                            </div>
                            <?php if ($esEditor): ?>
                            <div class="editor-panel__divider"></div>
                            <?php
                            $yaEnRevisionN = !empty($noticia->envio_revision);
                            $esPublicadoN  = in_array($noticia->estado ?? '', ['publicado','programado'], true);
                            ?>
                            <?php
                            $tieneComentarioN = !$yaEnRevisionN && !$esPublicadoN && !empty($noticia->comentario_revision);
                            ?>
                            <?php if ($yaEnRevisionN): ?>
                            <p class="editor-panel__hint" style="background:#fef9c3;border:1px solid #fde047;border-radius:6px;padding:8px 10px;color:#713f12;font-size:.8rem;text-align:center;">
                                <i class="fa-solid fa-lock"></i> Noticia en revisión. No puedes editarla hasta que el administrador la revise.
                            </p>
                            <?php elseif ($esPublicadoN): ?>
                            <button type="button" class="admin-btn admin-btn--primary submit-full" id="btnEnviarRevisionEditar">
                                <i class="fa-solid fa-paper-plane"></i> Guardar y enviar a revisión
                            </button>
                            <p class="editor-panel__hint" style="margin-top:6px;font-size:.75rem;color:#94a3b8;text-align:center;">
                                Los cambios no se publicarán hasta que el admin los apruebe
                            </p>
                            <?php elseif ($tieneComentarioN): ?>
                            <button type="button" class="admin-btn admin-btn--primary submit-full" id="btnGuardarReenviarNoticia">
                                <i class="fa-solid fa-rotate-right"></i> Reenviar a revisión
                            </button>
                            <p class="editor-panel__hint" style="margin-top:6px;font-size:.75rem;color:#94a3b8;text-align:center;">
                                Los cambios se guardan y la noticia vuelve a revisión del admin
                            </p>
                            <?php else: ?>
                            <form method="POST" action="/dashboard/noticias/enviar-revision" style="margin:0;" id="enviarRevisionNoticiaForm">
                                <input type="hidden" name="id" value="<?= (int)$noticia->id ?>">
                                <button type="button" class="admin-btn admin-btn--primary" style="width:100%;justify-content:center;" id="btnEnviarRevisionEditar">
                                    <i class="fa-solid fa-paper-plane"></i> Enviar a revisión
                                </button>
                            </form>
                            <p class="editor-panel__hint" style="margin-top:6px;font-size:.75rem;color:#94a3b8;text-align:center;">
                                Guarda los cambios antes de enviar
                            </p>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Categoría -->
                    <div class="editor-panel">
                        <div class="editor-panel__header"><i class="fa-solid fa-folder-tree"></i> Categoría</div>
                        <div class="editor-panel__body">
                            <select name="categoria_id" class="admin-select">
                                <option value="">— Selecciona una categoría —</option>
                                <?php foreach ($categorias as $cat): ?>
                                <option value="<?= (int)$cat->id ?>" <?= (int)($noticia->categoria_id ?? 0) === (int)$cat->id ? 'selected' : '' ?>>
                                    <?= s($cat->nombre) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <a href="/dashboard/noticias/categorias/crear" class="editor-panel__link">
                                <i class="fa-solid fa-plus"></i> Nueva categoría
                            </a>
                        </div>
                    </div>

                    <!-- Autor -->
                    <div class="editor-panel">
                        <div class="editor-panel__header"><i class="fa-solid fa-user-pen"></i> Autor</div>
                        <div class="editor-panel__body">
                            <select name="autor_id" class="admin-select">
                                <option value="">— Selecciona un autor —</option>
                                <?php foreach ($usuarios as $u): ?>
                                <option value="<?= (int)$u->id ?>" <?= (int)($noticia->autor_id ?? 0) === (int)$u->id ? 'selected' : '' ?>>
                                    <?= s($u->nombre) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Tiempo de lectura -->
                    <div class="editor-panel">
                        <div class="editor-panel__header"><i class="fa-regular fa-clock"></i> Tiempo de lectura</div>
                        <div class="editor-panel__body">
                            <div class="reading-time-display">
                                <input type="number" name="tiempo_lectura" id="tiempoInput" min="1" max="999"
                                       class="reading-time-input"
                                       value="<?= (int)($noticia->tiempo_lectura ?? 0) ?: '' ?>" placeholder="—">
                                <span class="reading-time-unit">min</span>
                            </div>
                            <p class="editor-panel__hint" style="margin-top:6px;"><i class="fa-solid fa-wand-magic-sparkles"></i> Calculado automáticamente.</p>
                        </div>
                    </div>

                </aside>

            </form>

        </main>
    </div>

</div>

<!-- MODAL ELIMINAR -->
<div id="deleteModal" class="ubm-backdrop" aria-modal="true" role="dialog">
    <div class="ubm-card">
        <div class="ubm-icon"><i class="fa-solid fa-bullhorn"></i></div>
        <h2 class="ubm-title">¿Eliminar noticia?</h2>
        <p class="ubm-text">Estás a punto de eliminar <strong id="ubm-name"></strong>. Esta acción no se puede deshacer.</p>
        <div class="ubm-field">
            <label class="ubm-field__label" for="ubm-input">Escribe el título para confirmar</label>
            <div class="ubm-field__wrap">
                <input type="text" id="ubm-input" class="ubm-field__input" placeholder="Título de la noticia" autocomplete="off" spellcheck="false">
                <i class="fa-solid fa-check ubm-field__check"></i>
            </div>
        </div>
        <form method="POST" action="/dashboard/noticias/eliminar" id="ubm-form">
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

<!-- MODAL REVISIÓN -->
<div id="revisionModal" class="revision-confirm-modal" role="dialog" aria-modal="true" aria-label="Confirmar envío a revisión">
    <div class="revision-confirm-modal__card">
        <div class="revision-confirm-modal__icon"><i class="fa-solid fa-paper-plane"></i></div>
        <p class="revision-confirm-modal__title">¿Enviar a revisión?</p>
        <p class="revision-confirm-modal__text">Una vez enviada, no podrás editar esta noticia hasta que el administrador la revise.</p>
        <div class="revision-confirm-modal__actions">
            <button type="button" class="admin-btn admin-btn--ghost" id="revisionModalCancel">Cancelar</button>
            <button type="button" class="admin-btn admin-btn--primary" id="revisionModalConfirm">
                <i class="fa-solid fa-paper-plane"></i> Enviar a revisión
            </button>
        </div>
    </div>
</div>

<!-- MODAL ENLACE -->
<div id="linkModal" class="link-modal-backdrop" role="dialog" aria-modal="true" aria-label="Insertar enlace">
    <div class="link-modal-card">
        <p class="link-modal-title"><i class="fa-solid fa-link"></i> Insertar enlace</p>
        <input type="text" id="linkUrl" class="admin-input" placeholder="https://ejemplo.com" style="font-size:.9rem;">
        <div class="link-modal-actions">
            <button type="button" class="admin-btn admin-btn--secondary" id="linkCancelBtn">Cancelar</button>
            <button type="button" class="admin-btn admin-btn--primary" id="linkConfirmBtn">Insertar</button>
        </div>
    </div>
</div>


