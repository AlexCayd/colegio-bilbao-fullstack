<?php $paginaVista = 'blog-articulos-crear'; ?>
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
                    <span>Nuevo artículo</span>
                </div>
                <span class="admin-topbar__title">Nuevo artículo</span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</button>
                </form>
            </div>
        </header>

        <!-- CONTENIDO -->
        <main class="admin-content">

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

            <form action="/dashboard/articulos/crear" method="POST" enctype="multipart/form-data" class="article-editor-layout" id="articuloForm">

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
                        <!-- slug hidden para cuando no se edita manualmente -->
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
                        <input type="hidden" name="contenido" id="contenidoHidden" value="<?= s($articulo->contenido ?? '') ?>">
                    </div>

                    <!-- Imagen de portada -->
                    <div class="admin-field-group">
                        <label class="admin-field-label">
                            Imagen de portada
                            <span class="admin-field-hint">Recomendado: 1200 × 630 px. Formatos JPG, PNG o WebP. Máx. 20 MB.</span>
                        </label>
                        <div class="image-upload-zone" id="imageUploadZone">
                            <div class="image-upload-zone__content" id="imageUploadContent">
                                <div class="image-upload-zone__icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                                <p class="image-upload-zone__text">Arrastra una imagen aquí o</p>
                                <label for="imagen" class="image-upload-zone__btn">Seleccionar archivo</label>
                                <p class="image-upload-zone__hint">JPG, PNG o WebP · Máx. 20 MB</p>
                            </div>
                            <img src="" alt="" class="image-upload-zone__preview" id="imagePreview" style="display:none;">
                            <input type="file" name="imagen" id="imagen" accept="image/jpeg,image/png,image/webp" style="display:none;">
                        </div>
                    </div>

                    <!-- Consejo de Alex — SEO mejorado -->
                    <div class="alex-tip">
                        <img
                            src="/build/assets/img/alex/alex-pinta.png"
                            alt="Alex"
                            class="alex-tip__img"
                        >
                        <div class="alex-tip__bubble">
                            <strong><i class="fa-solid fa-magnifying-glass" style="color:var(--col-bilbao);margin-right:5px;font-size:.8rem;"></i> Consejo SEO de Alex</strong>
                            <ul class="alex-tip__list">
                                <li><i class="fa-solid fa-check"></i> <strong>Título ideal:</strong> hasta 40 caracteres. Incluye la palabra clave al inicio.</li>
                                <li><i class="fa-solid fa-check"></i> <strong>Extracto:</strong> es tu meta description. Redáctalo para invitar al clic, no solo para resumir.</li>
                                <li><i class="fa-solid fa-check"></i> <strong>Imagen:</strong> usa un nombre descriptivo en el archivo y añade texto alt cuando sea posible.</li>
                                <li><i class="fa-solid fa-check"></i> <strong>URL (slug):</strong> corta, con guiones y sin acentos. Evita cambiarla una vez publicada.</li>
                            </ul>
                        </div>
                    </div>

                </div>
                <!-- /article-editor-main -->

                <!-- ===== PANEL LATERAL ===== -->
                <aside class="article-editor-sidebar">

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
                                    <option value="borrador" <?= ($articulo->estado ?? 'borrador') === 'borrador' ? 'selected' : '' ?>>Borrador</option>
                                    <option value="publicado" <?= ($articulo->estado ?? '') === 'publicado' ? 'selected' : '' ?>>Publicado</option>
                                    <option value="programado" <?= ($articulo->estado ?? '') === 'programado' ? 'selected' : '' ?>>Programado</option>
                                </select>
                            </div>
                            <div class="admin-field-group" id="fechaPublicacionGroup" style="display:none;">
                                <label class="admin-field-label" for="fecha_publicacion">Fecha de publicación</label>
                                <input type="datetime-local" name="fecha_publicacion" id="fecha_publicacion" class="admin-input" value="<?= s($articulo->fecha_publicacion ?? '') ?>">
                            </div>
                            <?php endif; ?>
                            <div class="editor-panel__divider"></div>
                            <div class="editor-panel__actions">
                                <input type="hidden" name="_accion" id="_accionHidden" value="guardar">
                                <button type="submit" id="submitBtn" class="admin-btn admin-btn--secondary submit-full">
                                    <i id="submitIcon" class="fa-regular fa-floppy-disk"></i>
                                    <span id="submitLabel">Guardar como borrador</span>
                                </button>
                                <?php if ($esEditor): ?>
                                <div class="editor-panel__divider" style="margin:8px 0 4px;"></div>
                                <button type="button" class="admin-btn admin-btn--primary submit-full" id="btnEnviarRevisionCrear">
                                    <i class="fa-solid fa-paper-plane"></i> Crear y enviar a revisión
                                </button>
                                <?php endif; ?>
                            </div>
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
                            <input type="hidden" name="tags" id="tagsHidden" value="<?= s($articulo->tags ?? '') ?>">
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

<!-- MODAL DE ENLACE -->
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


