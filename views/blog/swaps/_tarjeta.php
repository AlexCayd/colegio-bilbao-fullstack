<?php
/**
 * Una tarjeta de intercambio.
 *
 * El intercambio se lee como una permuta: a la izquierda lo que cede el
 * solicitante, a la derecha lo que da a cambio. Las acciones dependen de quién
 * mira, así que llegan resueltas en $puedeResponder / $puedeValidar / $puedeCancelar.
 *
 * @var \Model\Swap $sw
 * @var bool $puedeResponder  el que mira es el destinatario y sigue pendiente
 * @var bool $puedeValidar    coordina y el intercambio está aceptado
 * @var bool $puedeCancelar   es el solicitante y sigue pendiente
 */
$s   = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$col = \Model\Swap::ESTADO_COLOR[$sw->estado] ?? 'nil';
$hora = fn($i, $f) => substr((string)$i, 0, 5) . '–' . substr((string)$f, 0, 5);
?>
<article class="swp-card swp-card--<?= $col ?>">

    <header class="swp-card__head">
        <span class="swp-estado swp-estado--<?= $col ?>">
            <?= $s(\Model\Swap::ESTADO_LABEL[$sw->estado] ?? $sw->estado) ?>
        </span>
        <?php if ($sw->validador_nombre): ?>
        <span class="swp-card__val">por <?= $s($sw->validador_nombre) ?></span>
        <?php endif; ?>
    </header>

    <div class="swp-permuta">
        <!-- Lo que cede quien solicita -->
        <div class="swp-lado">
            <span class="swp-lado__quien">
                <i class="fa-solid fa-arrow-up-from-bracket"></i> <?= $s($sw->solicitante_nombre) ?> cede
            </span>
            <span class="swp-clase">
                <strong><?= $s($sw->origen_materia ?: 'Clase') ?></strong>
                <small>
                    <?= $s(fecha_larga($sw->fecha_origen)) ?> · <?= $s($hora($sw->origen_ini, $sw->origen_fin)) ?>
                    <?php if ($sw->origen_grupo): ?> · <?= $s($sw->origen_grupo) ?><?php endif; ?>
                    <?php if ($sw->origen_aula): ?> · <?= $s($sw->origen_aula) ?><?php endif; ?>
                </small>
            </span>
        </div>

        <span class="swp-permuta__ico" aria-hidden="true"><i class="fa-solid fa-right-left"></i></span>

        <!-- Lo que da a cambio -->
        <div class="swp-lado">
            <span class="swp-lado__quien">
                <i class="fa-solid fa-arrow-down-to-bracket"></i> <?= $s($sw->destinatario_nombre) ?> cede
            </span>
            <span class="swp-clase">
                <strong><?= $s($sw->destino_materia ?: 'Clase') ?></strong>
                <small>
                    <?= $s(fecha_larga($sw->fecha_destino)) ?> · <?= $s($hora($sw->destino_ini, $sw->destino_fin)) ?>
                    <?php if ($sw->destino_grupo): ?> · <?= $s($sw->destino_grupo) ?><?php endif; ?>
                    <?php if ($sw->destino_aula): ?> · <?= $s($sw->destino_aula) ?><?php endif; ?>
                </small>
            </span>
        </div>
    </div>

    <?php if ($sw->estado === 'aceptado'): ?>
    <?php /* Que las dos partes se hayan puesto de acuerdo NO lo hace efectivo: falta
              la validación. Decirlo aquí evita que alguien deje de ir a su clase. */ ?>
    <p class="swp-card__pendiente">
        <i class="fa-solid fa-hourglass-half"></i>
        Ambos profesores están de acuerdo, pero el intercambio <strong>aún no es efectivo</strong>:
        falta la validación de prefectura o dirección.
    </p>
    <?php endif; ?>

    <?php if ($sw->motivo): ?>
    <p class="swp-card__motivo"><i class="fa-regular fa-comment"></i> <?= $s($sw->motivo) ?></p>
    <?php endif; ?>

    <?php /* Las dos notas van SEPARADAS y con autor. Antes compartían columna y la
              validación pisaba la respuesta del profesor, así que el solicitante se
              quedaba sin saber quién había dicho qué. */ ?>
    <?php if ($sw->respuesta_nota): ?>
    <p class="swp-card__nota">
        <i class="fa-solid fa-reply"></i>
        <strong><?= $s($sw->destinatario_nombre) ?>:</strong> <?= $s($sw->respuesta_nota) ?>
    </p>
    <?php endif; ?>
    <?php if ($sw->validacion_nota): ?>
    <p class="swp-card__nota swp-card__nota--val">
        <i class="fa-solid fa-gavel"></i>
        <strong><?= $s($sw->validador_nombre ?: 'Coordinación') ?>:</strong> <?= $s($sw->validacion_nota) ?>
    </p>
    <?php endif; ?>
    <?php if ($sw->creado_por && (int)$sw->creado_por !== (int)$sw->solicitante_id): ?>
    <?php /* Lo abrió prefectura o dirección: no fue un trato entre los dos profesores
              sino una reasignación, y conviene que se lea como tal. */ ?>
    <p class="swp-card__nota swp-card__nota--val">
        <i class="fa-solid fa-user-shield"></i>
        Registrado por <?= $s($sw->creador_nombre ?: 'coordinación') ?>.
    </p>
    <?php endif; ?>

    <?php if ($puedeResponder || $puedeValidar || $puedeCancelar): ?>
    <footer class="swp-card__acts">
        <?php if ($puedeResponder): ?>
            <form method="POST" action="/dashboard/swaps/responder" class="swp-form">
                <input type="hidden" name="id" value="<?= (int)$sw->id ?>">
                <input type="hidden" name="respuesta" value="aceptar">
                <button type="submit" class="admin-btn admin-btn--sm admin-btn--ok">
                    <i class="fa-solid fa-check"></i> Aceptar
                </button>
            </form>
            <form method="POST" action="/dashboard/swaps/responder" class="swp-form swp-form--rechazo">
                <input type="hidden" name="id" value="<?= (int)$sw->id ?>">
                <input type="hidden" name="respuesta" value="rechazar">
                <?php /* Obligatorio: decir que no sin decir por qué deja al solicitante
                          sin saber si puede proponer otra cosa o buscar a otra persona.
                          El servidor lo vuelve a comprobar. */ ?>
                <input type="text" name="nota" class="swp-nota" maxlength="255" required
                       placeholder="¿Por qué no puedes?">
                <button type="submit" class="admin-btn admin-btn--sm admin-btn--ghost">
                    <i class="fa-solid fa-xmark"></i> No puedo
                </button>
            </form>
        <?php endif; ?>

        <?php if ($puedeValidar): ?>
            <form method="POST" action="/dashboard/swaps/validar" class="swp-form">
                <input type="hidden" name="id" value="<?= (int)$sw->id ?>">
                <input type="hidden" name="decision" value="validar">
                <button type="submit" class="admin-btn admin-btn--sm admin-btn--ok">
                    <i class="fa-solid fa-circle-check"></i> Validar
                </button>
            </form>
            <form method="POST" action="/dashboard/swaps/validar" class="swp-form swp-form--rechazo">
                <input type="hidden" name="id" value="<?= (int)$sw->id ?>">
                <input type="hidden" name="decision" value="denegar">
                <?php /* Denegar es tumbar un acuerdo ya cerrado entre dos personas: el
                          motivo es obligatorio, y se guarda en su propia columna. */ ?>
                <input type="text" name="nota" class="swp-nota" maxlength="255" required
                       placeholder="¿Por qué se deniega?">
                <button type="submit" class="admin-btn admin-btn--sm admin-btn--ghost">
                    <i class="fa-solid fa-ban"></i> Denegar
                </button>
            </form>
        <?php endif; ?>

        <?php if ($puedeCancelar): ?>
            <form method="POST" action="/dashboard/swaps/cancelar" class="swp-form">
                <input type="hidden" name="id" value="<?= (int)$sw->id ?>">
                <button type="submit" class="admin-btn admin-btn--sm admin-btn--ghost">
                    <i class="fa-solid fa-rotate-left"></i> Retirar propuesta
                </button>
            </form>
        <?php endif; ?>
    </footer>
    <?php endif; ?>
</article>
