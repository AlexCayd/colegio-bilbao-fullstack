<?php $paginaVista = 'blog-soporte'; ?>
<?php
/**
 * Soporte técnico.
 *
 * El CTA es lo primero y lo único que ocupa la mitad superior: quien entra aquí
 * tiene un problema y quiere resolverlo, no leer. El Q&A va debajo y solo con los
 * módulos que el usuario tiene.
 *
 * @var string $whatsapp  número en formato internacional
 * @var string $quien     nombre completo del usuario
 * @var string $puesto    tipo de personal legible
 * @var array  $bloques   Q&A ya filtrado
 */
$s = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">

        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">Soporte técnico</span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">

            <!-- CTA: contar el problema y abrir WhatsApp -->
            <section class="sop-hero"
                     data-sop
                     data-tel="<?= $s($whatsapp) ?>"
                     data-quien="<?= $s($quien) ?>"
                     data-puesto="<?= $s($puesto) ?>">
                <img src="/build/assets/img/alex/alex-tech.png" alt="Alex" class="sop-hero__alex">

                <div class="sop-hero__body">
                    <p class="sop-hero__eyebrow"><i class="fa-brands fa-whatsapp"></i> Te respondemos por WhatsApp</p>
                    <h1 class="sop-hero__titulo">¿Algo no funciona? Cuéntanos y lo resolvemos</h1>
                    <p class="sop-hero__sub">
                        Escribe qué te está pasando con tus palabras. Preparamos el mensaje por ti
                        —con tu nombre y tu puesto— y solo tendrás que pulsar enviar.
                    </p>

                    <label class="sop-campo">
                        <span class="sop-campo__label">¿Qué está pasando?</span>
                        <textarea id="sopProblema"
                                  class="sop-campo__input"
                                  rows="4"
                                  maxlength="600"
                                  placeholder="Ej.: No puedo entrar a Suplencias, me dice que no tengo acceso aunque ayer sí entraba."></textarea>
                        <span class="sop-campo__hint">
                            <span data-sop-contador>0</span>/600 · Cuanto más concreto, más rápido lo arreglamos
                        </span>
                    </label>

                    <div class="sop-acciones">
                        <a class="sop-btn" data-sop-enviar target="_blank" rel="noopener">
                            <i class="fa-brands fa-whatsapp"></i> Abrir WhatsApp con mi mensaje
                        </a>
                        <span class="sop-acciones__nota" data-sop-nota>
                            Escribe tu problema para continuar
                        </span>
                    </div>

                    <?php /* Se enseña el mensaje que se va a mandar: nadie debería pulsar un
                              botón que envía algo que no ha visto. */ ?>
                    <details class="sop-preview">
                        <summary>Ver el mensaje que se enviará</summary>
                        <p class="sop-preview__txt" data-sop-preview></p>
                    </details>
                </div>
            </section>

            <!-- Q&A -->
            <?php if (!empty($bloques)): ?>
            <p class="mh-section-label"><i class="fa-solid fa-circle-question"></i> Preguntas frecuentes</p>

            <div class="sop-faq">
                <?php foreach ($bloques as $clave => $b): ?>
                <section class="sop-faq__bloque">
                    <h2 class="sop-faq__titulo">
                        <i class="fa-solid <?= $s($b['icon']) ?>"></i> <?= $s($b['titulo']) ?>
                    </h2>
                    <?php foreach ($b['items'] as $item): ?>
                    <details class="sop-qa">
                        <summary class="sop-qa__q">
                            <span><?= $s($item['q']) ?></span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </summary>
                        <?php /* La respuesta lleva <strong> del catálogo: es contenido nuestro,
                                  no entrada de usuario, así que no se escapa. */ ?>
                        <div class="sop-qa__a"><?= $item['a'] ?></div>
                    </details>
                    <?php endforeach; ?>
                </section>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>
