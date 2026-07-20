<?php $paginaVista = 'blog-articulos-editar'; ?>
<?php
// Variables disponibles: $articulo, $categorias, $usuarios, $tagsActuales, $alertas

// Formato de fecha para datetime-local
$fechaLocal = '';
if (!empty($articulo->fecha_publicacion)) {
    try {
        $dt = new DateTime($articulo->fecha_publicacion);
        $fechaLocal = $dt->format('Y-m-d\TH:i');
    } catch (Exception $e) {}
}

$badgeMap = [
    'publicado'  => ['class' => 'admin-badge--published',  'label' => 'Publicado'],
    'borrador'   => ['class' => 'admin-badge--draft',       'label' => 'Borrador'],
    'programado' => ['class' => 'admin-badge--scheduled',   'label' => 'Programado'],
];
$estadoBadge = $badgeMap[$articulo->estado ?? 'borrador'] ?? $badgeMap['borrador'];
?>
<div class="admin-layout">

    <!-- ===================== SIDEBAR ===================== -->
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <!-- ===================== MAIN ===================== -->
    <div class="admin-main">

        <!-- TOP BAR -->
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <div class="admin-breadcrumb">
                    <a href="/dashboard">Dashboard</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <a href="/dashboard/articulos">Artículos</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Editar artículo</span>
                </div>
                <span class="admin-topbar__title">Editar artículo</span>
            </div>
            <div class="admin-topbar__actions">
                <?php if (($articulo->estado ?? '') === 'publicado' && $articulo->slug): ?>
                <a href="/blog/<?= s($articulo->slug) ?>" class="admin-topbar__preview-btn" target="_blank">
                    <i class="fa-solid fa-eye"></i> Ver en el sitio
                </a>
                <?php endif; ?>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</button>
                </form>
            </div>
        </header>

        <!-- CONTENIDO -->
        <main class="admin-content">

            <!-- Aviso de estado -->
            <div class="admin-edit-notice">
                <i class="fa-solid fa-circle-info"></i>
                Última modificación:
                <strong>
                    <?= $articulo->actualizado_en
                        ? date('d \d\e F \d\e Y \a \l\a\s H:i', strtotime($articulo->actualizado_en))
                        : date('d \d\e F \d\e Y', strtotime($articulo->creado_en)) ?>
                </strong>
                <?php if ($articulo->autor_nombre): ?>· <?= s($articulo->autor_nombre) ?><?php endif; ?>
                <span class="admin-badge <?= $estadoBadge['class'] ?>" style="margin-left:.75rem;"><?= $estadoBadge['label'] ?></span>
            </div>

            <?php if (!empty($alertas['error'])): ?>
            <div class="admin-alerta admin-alerta--error" id="alertaError">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul class="admin-alerta__list">
                    <?php foreach ($alertas['error'] as $msg): ?>
                        <li><?= s($msg) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="admin-alerta__close" onclick="this.parentElement.remove()" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <?php endif; ?>

            <form
                action="/dashboard/articulos/editar?id=<?= (int)$articulo->id ?>"
                method="POST"
                enctype="multipart/form-data"
                class="article-editor-layout"
                id="articuloForm"
                data-titulo-ref="<?= s($articulo->titulo ?? '') ?>"
            >
                <input type="hidden" name="id" value="<?= (int)$articulo->id ?>">

                <!-- ===== COLUMNA PRINCIPAL ===== -->
                <div class="article-editor-main">

                    <!-- Título -->
                    <div class="article-title-field">
                        <input
                            type="text"
                            name="titulo"
                            id="titulo"
                            placeholder="Escribe el título del artículo..."
                            class="article-title-input"
                            autocomplete="off"
                            value="<?= s($articulo->titulo ?? '') ?>"
                        >
                        <div class="article-slug-preview">
                            <span class="article-slug-label">URL:</span>
                            <span class="article-slug-value">blog/<em id="slugPreviewText"><?= s($articulo->slug ?? 'el-titulo-del-articulo') ?></em></span>
                            <button type="button" class="article-slug-edit-btn" id="slugToggleBtn" title="Editar slug manualmente">
                                <i class="fa-solid fa-pen"></i> Editar slug
                            </button>
                        </div>
                        <div id="slugEditWrap" style="display:none; margin-top:10px;">
                            <input type="text" name="slug" id="slugManual" class="admin-input" style="font-size:.82rem;" placeholder="slug-del-articulo" value="<?= s($articulo->slug ?? '') ?>">
                        </div>
                        <input type="hidden" name="slug" id="slugHidden" value="<?= s($articulo->slug ?? '') ?>">
                        <div style="margin-top:8px;">
                            <span class="seo-hint" id="seoTituloHint"></span>
                        </div>
                    </div>

                    <!-- Extracto -->
                    <div class="admin-field-group">
                        <label class="admin-field-label" for="extracto">
                            Extracto
                            <span class="admin-field-hint">Aparece en las tarjetas del blog y en los resultados de búsqueda Google. Escribe tu frase clave aquí.</span>
                        </label>
                        <textarea
                            name="extracto"
                            id="extracto"
                            rows="3"
                            class="admin-textarea"
                            placeholder="Una o dos frases que inviten a leer el artículo completo..."
                            maxlength="200"
                        ><?= s($articulo->extracto ?? '') ?></textarea>
                        <div class="admin-char-count"><span id="extractoCount"><?= strlen($articulo->extracto ?? '') ?></span> / 200</div>
                    </div>

                    <!-- Contenido — Editor WYSIWYG -->
                    <div class="admin-field-group">
                        <label class="admin-field-label">
                            Contenido
                            <span class="admin-field-hint">Formatea el texto con la barra de herramientas. Lo que ves aquí es aproximado a cómo lucirá publicado.</span>
                        </label>

                        <div class="editor-toolbar" id="editorToolbar">
                            <button type="button" class="editor-toolbar__btn" data-cmd="bold" title="Negrita (Ctrl+B)">
                                <i class="fa-solid fa-bold"></i>
                            </button>
                            <button type="button" class="editor-toolbar__btn" data-cmd="italic" title="Cursiva (Ctrl+I)">
                                <i class="fa-solid fa-italic"></i>
                            </button>
                            <div class="editor-toolbar__sep"></div>
                            <button type="button" class="editor-toolbar__btn" data-cmd="h2" title="Encabezado H2">
                                <i class="fa-solid fa-heading"></i><span style="font-size:.65rem;font-weight:800;margin-left:1px;">2</span>
                            </button>
                            <button type="button" class="editor-toolbar__btn" data-cmd="h3" title="Encabezado H3">
                                <i class="fa-solid fa-heading"></i><span style="font-size:.65rem;font-weight:800;margin-left:1px;">3</span>
                            </button>
                            <div class="editor-toolbar__sep"></div>
                            <button type="button" class="editor-toolbar__btn" data-cmd="list" title="Lista con viñetas">
                                <i class="fa-solid fa-list-ul"></i>
                            </button>
                            <button type="button" class="editor-toolbar__btn" data-cmd="quote" title="Cita destacada">
                                <i class="fa-solid fa-quote-left"></i>
                            </button>
                            <button type="button" class="editor-toolbar__btn" data-cmd="plain" title="Texto normal (párrafo)">
                                <i class="fa-solid fa-paragraph"></i>
                            </button>
                            <button type="button" class="editor-toolbar__btn" data-cmd="link" title="Insertar enlace">
                                <i class="fa-solid fa-link"></i>
                            </button>
                        </div>

                        <div
                            id="wysiwyg"
                            class="wysiwyg-editor"
                            contenteditable="true"
                            data-placeholder="Escribe aquí el cuerpo del artículo..."
                            role="textbox"
                            aria-multiline="true"
                            aria-label="Contenido del artículo"
                        ><?= $articulo->contenido ?? '' ?></div>
                        <input type="hidden" name="contenido" id="contenidoHidden" value="">
                    </div>

                    <!-- Imagen de portada -->
                    <div class="admin-field-group">
                        <label class="admin-field-label">
                            Imagen de portada
                            <span class="admin-field-hint">Recomendado: 1200 × 630 px. Formatos JPG, PNG o WebP. Máx. 20 MB.</span>
                        </label>
                        <div class="image-upload-zone <?= $articulo->imagen ? 'image-upload-zone--has-image' : '' ?>" id="imageUploadZone">
                            <?php if ($articulo->imagen): ?>
                            <img src="<?= s($articulo->imagen) ?>" alt="Portada actual" class="image-upload-zone__preview" id="imagePreview" onerror="this.src='/build/assets/img/blog/blog-placeholder.png'">
                            <div class="image-upload-zone__overlay">
                                <label for="imagen" class="image-upload-zone__change-btn">
                                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Cambiar imagen
                                </label>
                            </div>
                            <?php else: ?>
                            <div class="image-upload-zone__content" id="imageUploadContent">
                                <div class="image-upload-zone__icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                                <p class="image-upload-zone__text">Arrastra una imagen aquí o</p>
                                <label for="imagen" class="image-upload-zone__btn">Seleccionar archivo</label>
                                <p class="image-upload-zone__hint">JPG, PNG o WebP · Máx. 20 MB</p>
                            </div>
                            <img src="" alt="" class="image-upload-zone__preview" id="imagePreview" style="display:none;">
                            <?php endif; ?>
                            <input type="file" name="imagen" id="imagen" accept="image/jpeg,image/png,image/webp" style="display:none;">
                        </div>
                        <label for="imagen" class="image-change-trigger" id="imageChangeTrigger">
                            <i class="fa-solid fa-arrow-up-from-bracket"></i>
                            <?= $articulo->imagen ? 'Cambiar imagen' : 'Seleccionar imagen' ?>
                        </label>
                        <?php if ($articulo->imagen): ?>
                        <a href="<?= s($articulo->imagen) ?>" download class="admin-btn admin-btn--ghost" style="margin-top:8px;">
                            <i class="fa-solid fa-download"></i> Descargar portada
                        </a>
                        <?php else: ?>
                        <p class="admin-field-hint" style="margin-top:8px;">Este artículo no tiene portada aún, agrégala cuanto antes.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Tip de Alex -->
                    <?php if (($articulo->estado ?? '') === 'publicado'): ?>
                    <div class="alex-tip alex-tip--edit">
                        <img src="/build/assets/img/alex/alex-point.png" alt="Alex" class="alex-tip__img">
                        <div class="alex-tip__bubble">
                            <strong><i class="fa-solid fa-circle-check" style="color:#38a169;margin-right:5px;font-size:.8rem;"></i> Este artículo está publicado</strong>
                            <p>Cualquier cambio que guardes será visible de inmediato en el sitio público. Si quieres revisarlo antes de mostrarlo, cámbialo a <strong>Borrador</strong>.</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alex-tip">
                        <img src="/build/assets/img/alex/alex-ciencia.png" alt="Alex" class="alex-tip__img">
                        <div class="alex-tip__bubble">
                            <strong><i class="fa-solid fa-magnifying-glass" style="color:var(--col-bilbao);margin-right:5px;font-size:.8rem;"></i> Tips SEO para mejorar este artículo</strong>
                            <ul class="alex-tip__list">
                                <li><i class="fa-solid fa-check"></i> <strong>Título ideal:</strong> hasta 40 caracteres con la palabra clave al inicio.</li>
                                <li><i class="fa-solid fa-check"></i> <strong>Extracto:</strong> es tu meta description. Invita al clic, no solo resumas.</li>
                                <li><i class="fa-solid fa-check"></i> <strong>Imagen:</strong> usa nombre descriptivo de archivo para mejor indexación.</li>
                                <li><i class="fa-solid fa-check"></i> <strong>Slug:</strong> corto, con guiones, sin acentos. Evita cambiarlo si ya está publicado.</li>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Zona de peligro -->
                    <div class="admin-danger-zone">
                        <div class="admin-danger-zone__header">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Zona de peligro
                        </div>
                        <div class="admin-danger-zone__body">
                            <div>
                                <p class="admin-danger-zone__label">Eliminar este artículo</p>
                                <p class="admin-danger-zone__desc">Esta acción es permanente y no se puede deshacer.</p>
                            </div>
                            <button type="button" class="admin-btn admin-btn--danger" onclick="abrirModalEliminar()">
                                <i class="fa-regular fa-trash-can"></i> Eliminar artículo
                            </button>
                        </div>
                    </div>

                </div>
                <!-- /article-editor-main -->

                <!-- ===== PANEL LATERAL ===== -->
                <aside class="article-editor-sidebar">

                    <!-- Revisión pendiente (solo admins) -->
                    <?php if (!empty($articulo->envio_revision) && ($_SESSION['blog_usuario']['rol'] ?? '') === 'administrador'): ?>
                    <div class="editor-panel editor-panel--review">
                        <div class="editor-panel__header rev-panel-header">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            En revisión
                            <span class="rev-panel-autor">· <?= s($articulo->autor_nombre ?? 'editor') ?></span>
                        </div>
                        <div class="editor-panel__body">
                            <?php
                            $vp = null;
                            if (!empty($articulo->version_pendiente)) {
                                $vp = json_decode($articulo->version_pendiente, true);
                            }
                            ?>
                            <?php if (is_array($vp)): ?>
                            <div class="rev-pending-diff" style="margin-bottom:12px;">
                                <p class="rev-pending-diff__label">
                                    <i class="fa-solid fa-code-compare"></i> Cambios propuestos
                                </p>
                                <?php if (!empty($vp['titulo']) && $vp['titulo'] !== $articulo->titulo): ?>
                                <div class="rev-diff-row">
                                    <span class="rev-diff-row__key">Título:</span>
                                    <span class="rev-diff-row__val"><?= s($vp['titulo']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($vp['extracto']) && $vp['extracto'] !== $articulo->extracto): ?>
                                <div class="rev-diff-row">
                                    <span class="rev-diff-row__key">Extracto:</span>
                                    <span class="rev-diff-row__val"><?= s($vp['extracto']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($vp['contenido']) && $vp['contenido'] !== $articulo->contenido): ?>
                                <div class="rev-diff-row">
                                    <span class="rev-diff-row__key">Contenido:</span>
                                    <span class="rev-diff-row__val rev-diff-row__val--modified">Modificado</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <!-- Aprobar -->
                            <form method="POST" action="/dashboard/articulos/aprobar" class="rev-aprobar-form" style="margin-bottom:10px;">
                                <input type="hidden" name="id" value="<?= (int)$articulo->id ?>">
                                <select name="estado" class="admin-select rev-select" id="revEstadoArt" style="width:100%;margin-bottom:8px;font-size:.82rem;">
                                    <option value="publicado">Publicar ahora</option>
                                    <option value="programado">Programar fecha</option>
                                </select>
                                <input type="datetime-local" name="fecha_publicacion" class="admin-input" id="revFechaArt" style="display:none;width:100%;font-size:.82rem;margin-bottom:8px;">
                                <button type="submit" class="admin-btn admin-btn--primary" style="width:100%;font-size:.82rem;">
                                    <i class="fa-solid fa-check"></i> Aprobar
                                </button>
                            </form>
                            <!-- Rechazar -->
                            <button type="button" class="rev-rechazar-toggle rev-rechazar-toggle--v2" data-target="revRechazarArt">
                                <i class="fa-solid fa-comment-exclamation"></i> Devolver feedback
                            </button>
                            <div id="revRechazarArt" class="rev-feedback-panel" style="display:none;">
                                <p class="rev-feedback-para">
                                    <i class="fa-solid fa-user-pen"></i>
                                    Enviando feedback a <strong><?= s($articulo->autor_nombre ?? 'editor') ?></strong>
                                </p>
                                <form method="POST" action="/dashboard/articulos/rechazar">
                                    <input type="hidden" name="id" value="<?= (int)$articulo->id ?>">
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
                    <?php if (($_SESSION['blog_usuario']['rol'] ?? '') === 'editor' && !empty($articulo->comentario_revision)): ?>
                    <div class="editor-feedback-banner">
                        <div class="editor-feedback-banner__head">
                            <i class="fa-solid fa-comment-exclamation"></i>
                            Retroalimentación del administrador
                        </div>
                        <p class="editor-feedback-banner__body">
                            <?= nl2br(htmlspecialchars($articulo->comentario_revision)) ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <!-- Publicar -->
                    <div class="editor-panel">
                        <div class="editor-panel__header">
                            <i class="fa-solid fa-rocket"></i> Publicación
                        </div>
                        <div class="editor-panel__body">
                            <?php $esEditor = ($_SESSION['blog_usuario']['rol'] ?? '') === 'editor'; ?>
                            <?php if ($esEditor): ?>
                                <input type="hidden" name="estado" value="borrador">
                                <div class="editor-estado-badge"><i class="fa-regular fa-file-lines"></i> Borrador</div>
                            <?php else: ?>
                            <div class="admin-field-group">
                                <label class="admin-field-label" for="estado">Estado</label>
                                <select name="estado" id="estado" class="admin-select">
                                    <option value="borrador" <?= ($articulo->estado ?? '') === 'borrador' ? 'selected' : '' ?>>Borrador</option>
                                    <option value="publicado" <?= ($articulo->estado ?? '') === 'publicado' ? 'selected' : '' ?>>Publicado</option>
                                    <option value="programado" <?= ($articulo->estado ?? '') === 'programado' ? 'selected' : '' ?>>Programado</option>
                                </select>
                            </div>
                            <div class="admin-field-group" id="fechaPublicacionGroup" style="display:<?= ($articulo->estado ?? '') === 'programado' ? 'block' : 'none' ?>;">
                                <label class="admin-field-label" for="fecha_publicacion">Fecha de publicación</label>
                                <input type="datetime-local" name="fecha_publicacion" id="fecha_publicacion" class="admin-input" value="<?= s($fechaLocal) ?>">
                            </div>
                            <?php endif; ?>
                            <div class="editor-panel__divider"></div>
                            <div class="editor-panel__actions">
                                <button type="submit" id="submitBtn" class="admin-btn admin-btn--secondary" style="width:100%;justify-content:center;">
                                    <i id="submitIcon" class="fa-regular fa-floppy-disk"></i>
                                    <span id="submitLabel">Guardar como borrador</span>
                                </button>
                            </div>
                            <?php if ($esEditor): ?>
                            <?php
                            $yaEnRevision = !empty($articulo->envio_revision);
                            $esPublicado  = in_array($articulo->estado ?? '', ['publicado','programado'], true);
                            ?>
                            <div class="editor-panel__divider"></div>
                            <?php
                            $tieneComentario = !$yaEnRevision && !$esPublicado && !empty($articulo->comentario_revision);
                            ?>
                            <?php if ($yaEnRevision): ?>
                            <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;font-size:.82rem;color:#854d0e;display:flex;align-items:center;gap:8px;">
                                <i class="fa-solid fa-hourglass-half"></i> En espera de revisión. No puedes editar hasta recibir respuesta.
                            </div>
                            <?php elseif ($esPublicado): ?>
                            <button type="button" class="admin-btn admin-btn--primary submit-full" id="btnEnviarRevision">
                                <i class="fa-solid fa-paper-plane"></i> Guardar y enviar a revisión
                            </button>
                            <p class="editor-panel__hint" style="margin-top:6px;font-size:.75rem;color:#94a3b8;text-align:center;">
                                Los cambios no se publicarán hasta que el admin los apruebe
                            </p>
                            <?php elseif ($tieneComentario): ?>
                            <button type="button" class="admin-btn admin-btn--primary submit-full" id="btnGuardarReenviar">
                                <i class="fa-solid fa-rotate-right"></i> Reenviar a revisión
                            </button>
                            <p class="editor-panel__hint" style="margin-top:6px;font-size:.75rem;color:#94a3b8;text-align:center;">
                                Los cambios se guardan y el artículo vuelve a revisión del admin
                            </p>
                            <?php else: ?>
                            <form method="POST" action="/dashboard/articulos/enviar-revision" id="enviarRevisionForm" style="margin:0;">
                                <input type="hidden" name="id" value="<?= (int)$articulo->id ?>">
                                <button type="button" class="admin-btn admin-btn--primary submit-full" id="btnEnviarRevision">
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
                        <div class="editor-panel__header">
                            <i class="fa-solid fa-tags"></i> Categoría <span style="color:#e53e3e;font-size:.75rem;">*</span>
                        </div>
                        <div class="editor-panel__body">
                            <select name="categoria_id" class="admin-select">
                                <option value="">— Selecciona una categoría —</option>
                                <?php foreach ($categorias as $cat): ?>
                                <option value="<?= (int)$cat->id ?>" <?= (int)($articulo->categoria_id ?? 0) === (int)$cat->id ? 'selected' : '' ?>>
                                    <?= s($cat->nombre) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <a href="/dashboard/categorias/crear" class="editor-panel__link">
                                <i class="fa-solid fa-plus"></i> Nueva categoría
                            </a>
                        </div>
                    </div>

                    <!-- Autor -->
                    <div class="editor-panel">
                        <div class="editor-panel__header">
                            <i class="fa-solid fa-user-pen"></i> Autor <span style="color:#e53e3e;font-size:.75rem;">*</span>
                        </div>
                        <div class="editor-panel__body">
                            <select name="autor_id" class="admin-select">
                                <option value="">— Selecciona un autor —</option>
                                <?php foreach ($usuarios as $u): ?>
                                <option value="<?= (int)$u->id ?>" <?= (int)($articulo->autor_id ?? 0) === (int)$u->id ? 'selected' : '' ?>>
                                    <?= s($u->nombre) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Tiempo de lectura -->
                    <div class="editor-panel">
                        <div class="editor-panel__header">
                            <i class="fa-regular fa-clock"></i> Tiempo de lectura <span style="color:#e53e3e;font-size:.75rem;">*</span>
                        </div>
                        <div class="editor-panel__body">
                            <div class="reading-time-display">
                                <input type="number" name="tiempo_lectura" id="tiempoInput" min="1" max="999" class="reading-time-input" value="<?= (int)($articulo->tiempo_lectura ?? 0) ?: '' ?>" placeholder="—">
                                <span class="reading-time-unit">min</span>
                            </div>
                            <p class="editor-panel__hint" style="margin-top:6px;">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                Calculado automáticamente. Puedes editarlo.
                            </p>
                        </div>
                    </div>

                    <!-- Etiquetas -->
                    <div class="editor-panel">
                        <div class="editor-panel__header">
                            <i class="fa-solid fa-hashtag"></i> Etiquetas
                        </div>
                        <div class="editor-panel__body">
                            <div class="tag-input-wrap" id="tagWrap">
                                <input
                                    type="text"
                                    id="tagInputField"
                                    class="tag-input-field"
                                    placeholder="Escribe y presiona coma..."
                                    autocomplete="off"
                                    spellcheck="false"
                                >
                            </div>
                            <input type="hidden" name="tags" id="tagsHidden" value="<?= s($tagsActuales ?? '') ?>">
                            <p class="editor-panel__hint" style="margin-top:6px;">
                                <i class="fa-solid fa-circle-info"></i>
                                Escribe una etiqueta y presiona <kbd style="font-size:.7rem;padding:1px 5px;background:#f0f4f8;border-radius:4px;border:1px solid #dde3ea;">,</kbd> para añadirla.
                            </p>
                        </div>
                    </div>

                </aside>
                <!-- /article-editor-sidebar -->

            </form>

        </main>
    </div>

</div>

<!-- MODAL ELIMINAR -->
<div id="deleteModal" class="ubm-backdrop" aria-modal="true" role="dialog">
    <div class="ubm-card">
        <div class="ubm-icon"><i class="fa-solid fa-newspaper"></i></div>
        <h2 class="ubm-title">¿Eliminar este artículo?</h2>
        <p class="ubm-text">Estás a punto de eliminar <strong><?= s($articulo->titulo ?? '') ?></strong>. Esta acción no se puede deshacer.</p>
        <div class="ubm-field">
            <label class="ubm-field__label" for="ubm-input">Escribe el título para confirmar</label>
            <div class="ubm-field__wrap">
                <input type="text" id="ubm-input" class="ubm-field__input" placeholder="Título del artículo" autocomplete="off" spellcheck="false">
                <i class="fa-solid fa-check ubm-field__check"></i>
            </div>
        </div>
        <form method="POST" action="/dashboard/articulos/eliminar" id="ubm-form">
            <input type="hidden" name="id" value="<?= (int)$articulo->id ?>">
            <div class="ubm-actions">
                <button type="button" class="admin-btn admin-btn--ghost" onclick="cerrarModalEliminar()">Cancelar</button>
                <button type="submit" class="admin-btn ubm-confirm" id="ubm-submit" disabled>
                    <i class="fa-solid fa-trash-can"></i> Sí, eliminar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CONFIRMAR ENVÍO A REVISIÓN -->
<div id="revisionModal" class="revision-confirm-modal" role="dialog" aria-modal="true">
    <div class="revision-confirm-modal__card">
        <div class="revision-confirm-modal__icon">
            <i class="fa-solid fa-paper-plane"></i>
        </div>
        <p class="revision-confirm-modal__title">¿Enviar a revisión?</p>
        <p class="revision-confirm-modal__text">Una vez enviado, no podrás editar este artículo hasta que el administrador lo revise.</p>
        <div class="revision-confirm-modal__actions">
            <button type="button" class="admin-btn admin-btn--secondary" id="btnRevCancelar">Cancelar</button>
            <button type="button" class="admin-btn admin-btn--primary" id="btnRevConfirmar">
                <i class="fa-solid fa-paper-plane"></i> Enviar a revisión
            </button>
        </div>
    </div>
</div>

<!-- MODAL DE ENLACE -->
<div id="linkModal" class="link-modal-backdrop" role="dialog" aria-modal="true">
    <div class="link-modal-card">
        <p class="link-modal-title"><i class="fa-solid fa-link"></i> Insertar enlace</p>
        <input type="text" id="linkUrl" class="admin-input" placeholder="https://ejemplo.com" style="font-size:.9rem;">
        <div class="link-modal-actions">
            <button type="button" class="admin-btn admin-btn--secondary" id="linkCancelBtn">Cancelar</button>
            <button type="button" class="admin-btn admin-btn--primary" id="linkConfirmBtn">Insertar</button>
        </div>
    </div>
</div>


