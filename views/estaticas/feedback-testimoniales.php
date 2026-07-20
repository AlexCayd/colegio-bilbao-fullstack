<?php $paginaVista = 'estaticas-feedback-testimoniales'; ?>

<section class="fb-hero">
    <p class="fb-hero__eyebrow" data-i18n="feedback-testimoniales.hero.eyebrow">Voces Bilbao</p>
    <h1 class="fb-hero__title"><span data-i18n="feedback-testimoniales.hero.titlePre">Tu experiencia</span><br><span data-i18n="feedback-testimoniales.hero.titlePost">nos importa</span></h1>
    <p class="fb-hero__sub" data-i18n="feedback-testimoniales.hero.subtitle">Comparte cómo ha sido formar parte de la comunidad Colegio Bilbao. Tu historia inspira a otras familias.</p>
</section>

<div class="fb-wrap">
    <div class="fb-card">

        <?php if ($enviado): ?>

        <div class="fb-success">
            <img src="/build/assets/img/alex/alex-lee.png" alt="" class="fb-success__mascot" aria-hidden="true">
            <h2 class="fb-success__title" data-i18n="feedback-testimoniales.success.title">¡Gracias por compartir!</h2>
            <p class="fb-success__msg" data-i18n="feedback-testimoniales.success.message">Tu testimonio fue recibido y será revisado por nuestro equipo antes de publicarse en el sitio.</p>
            <a href="/" class="fb-success__back" data-i18n="feedback-testimoniales.success.backLink">Volver al inicio</a>
        </div>

        <?php else: ?>

        <h2 class="fb-card__title" data-i18n="feedback-testimoniales.form.title">Deja tu testimonio</h2>

        <?php if (!empty($errores)): ?>
        <ul class="fb-errores" role="alert">
            <?php foreach ($errores as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <form method="POST" action="/feedback-testimoniales" novalidate>
            <div class="fb-field">
                <label class="fb-label" for="nombre"><span data-i18n="feedback-testimoniales.form.nameLabel">Tu nombre o seudónimo</span> <span style="color:#e51022">*</span></label>
                <input class="fb-input" type="text" id="nombre" name="nombre"
                       value="<?= htmlspecialchars($datos['nombre'] ?? '') ?>"
                       placeholder="Ej. Claudia R., Familia Torres…" maxlength="100" required
                       data-i18n-attr="placeholder:feedback-testimoniales.form.namePlaceholder">
            </div>

            <div class="fb-field">
                <label class="fb-label" for="rol"><span data-i18n="feedback-testimoniales.form.roleLabel">Tu relación con el colegio</span> <span style="color:#e51022">*</span></label>
                <select class="fb-select" id="rol" name="rol" required>
                    <option value="" data-i18n="feedback-testimoniales.form.roleSelectPlaceholder">— Selecciona —</option>
                    <?php foreach (['Papá', 'Mamá', 'Exalumno', 'Exalumna', 'Familia'] as $r): ?>
                    <option value="<?= $r ?>" <?= ($datos['rol'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="fb-field">
                <label class="fb-label" for="comentario"><span data-i18n="feedback-testimoniales.form.messageLabel">Tu mensaje</span> <span style="color:#e51022">*</span></label>
                <textarea class="fb-textarea" id="comentario" name="comentario"
                          placeholder="Cuéntanos cómo ha sido tu experiencia en el Colegio Bilbao…"
                          maxlength="60" required
                          data-i18n-attr="placeholder:feedback-testimoniales.form.messagePlaceholder"><?= htmlspecialchars($datos['comentario'] ?? '') ?></textarea>
                <p class="fb-counter"><span id="char-count">0</span><span data-i18n="feedback-testimoniales.form.charCounterSuffix">/60 caracteres</span></p>
            </div>

            <button type="submit" class="fb-btn" data-i18n="feedback-testimoniales.form.submitButton">Enviar mi testimonio</button>
        </form>

        <?php endif; ?>
    </div>
</div>

