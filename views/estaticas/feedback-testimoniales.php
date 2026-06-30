
<section class="fb-hero">
    <p class="fb-hero__eyebrow">Voces Bilbao</p>
    <h1 class="fb-hero__title">Tu experiencia<br>nos importa</h1>
    <p class="fb-hero__sub">Comparte cómo ha sido formar parte de la comunidad Colegio Bilbao. Tu historia inspira a otras familias.</p>
</section>

<div class="fb-wrap">
    <div class="fb-card">

        <?php if ($enviado): ?>

        <div class="fb-success">
            <img src="/build/assets/img/alex/alex-lee.png" alt="" class="fb-success__mascot" aria-hidden="true">
            <h2 class="fb-success__title">¡Gracias por compartir!</h2>
            <p class="fb-success__msg">Tu testimonio fue recibido y será revisado por nuestro equipo antes de publicarse en el sitio.</p>
            <a href="/" class="fb-success__back">Volver al inicio</a>
        </div>

        <?php else: ?>

        <h2 class="fb-card__title">Deja tu testimonio</h2>

        <?php if (!empty($errores)): ?>
        <ul class="fb-errores" role="alert">
            <?php foreach ($errores as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <form method="POST" action="/feedback-testimoniales" novalidate>
            <div class="fb-field">
                <label class="fb-label" for="nombre">Tu nombre o seudónimo <span style="color:#e51022">*</span></label>
                <input class="fb-input" type="text" id="nombre" name="nombre"
                       value="<?= htmlspecialchars($datos['nombre'] ?? '') ?>"
                       placeholder="Ej. Claudia R., Familia Torres…" maxlength="100" required>
            </div>

            <div class="fb-field">
                <label class="fb-label" for="rol">Tu relación con el colegio <span style="color:#e51022">*</span></label>
                <select class="fb-select" id="rol" name="rol" required>
                    <option value="">— Selecciona —</option>
                    <?php foreach (['Papá', 'Mamá', 'Exalumno', 'Exalumna', 'Familia'] as $r): ?>
                    <option value="<?= $r ?>" <?= ($datos['rol'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="fb-field">
                <label class="fb-label" for="comentario">Tu mensaje <span style="color:#e51022">*</span></label>
                <textarea class="fb-textarea" id="comentario" name="comentario"
                          placeholder="Cuéntanos cómo ha sido tu experiencia en el Colegio Bilbao…"
                          maxlength="60" required><?= htmlspecialchars($datos['comentario'] ?? '') ?></textarea>
                <p class="fb-counter"><span id="char-count">0</span>/60 caracteres</p>
            </div>

            <button type="submit" class="fb-btn">Enviar mi testimonio</button>
        </form>

        <?php endif; ?>
    </div>
</div>

<script>
(function(){
    var ta = document.getElementById('comentario');
    var ct = document.getElementById('char-count');
    if (!ta || !ct) return;
    function update(){ ct.textContent = ta.value.length; }
    ta.addEventListener('input', update);
    update();
})();
</script>
