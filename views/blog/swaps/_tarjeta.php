<?php
/**
 * Una tarjeta de swap.
 *
 * El swap se lee como una permuta: a la izquierda lo que cede el
 * solicitante, a la derecha lo que da a cambio. Las acciones dependen de quién
 * mira, así que llegan resueltas en $puedeResponder / $puedeValidar / $puedeCancelar.
 *
 * @var \Model\Swap $sw
 * @var bool $puedeResponder  el que mira es el destinatario y sigue pendiente
 * @var bool $puedeValidar    coordina y el swap está aceptado
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

    <?php /* ⚠️ Los dos lados decían «X cede» con el MISMO tipo, peso y color, y la única
             pista de la dirección era una flecha de 12px. Leer la tarjeta obligaba a
             reconstruir mentalmente quién acaba dando cada clase — que es lo único que
             un profesor necesita saber de aquí.

             Ahora cada lado nombra las DOS partes explícitamente: quién suelta la clase
             y quién la toma. Lo que cambia de un lado a otro es el orden de los nombres,
             y eso sí se ve. El color refuerza el mismo eje: ámbar suelta, verde cubre. */ ?>
    <?php
    // Cada movimiento: la clase, quién la deja y quién la da en su lugar.
    $movimientos = [
        [
            'rol'     => 'origen',
            'materia' => $sw->origen_materia ?: 'Clase',
            'fecha'   => $sw->fecha_origen,
            'hora'    => $hora($sw->origen_ini, $sw->origen_fin),
            'grupo'   => $sw->origen_grupo,
            'aula'    => $sw->origen_aula,
            'cede'    => $sw->solicitante_nombre,
            'cubre'   => $sw->destinatario_nombre,
        ],
        [
            'rol'     => 'destino',
            'materia' => $sw->destino_materia ?: 'Clase',
            'fecha'   => $sw->fecha_destino,
            'hora'    => $hora($sw->destino_ini, $sw->destino_fin),
            'grupo'   => $sw->destino_grupo,
            'aula'    => $sw->destino_aula,
            'cede'    => $sw->destinatario_nombre,
            'cubre'   => $sw->solicitante_nombre,
        ],
    ];
    ?>
    <div class="swp-permuta">
        <?php foreach ($movimientos as $i => $m): ?>

        <?php if ($i === 1): ?>
        <span class="swp-permuta__ico" aria-hidden="true"><i class="fa-solid fa-right-left"></i></span>
        <?php endif; ?>

        <div class="swp-mov swp-mov--<?= $m['rol'] ?>">
            <p class="swp-mov__cuando">
                <i class="fa-regular fa-calendar"></i>
                <?= $s(fecha_larga($m['fecha'])) ?> · <?= $s($m['hora']) ?>
            </p>

            <p class="swp-mov__clase"><?= $s($m['materia']) ?></p>

            <?php if ($m['grupo'] || $m['aula']): ?>
            <p class="swp-mov__donde">
                <?= $s(trim(($m['grupo'] ?? '') . ($m['aula'] ? ' · ' . $m['aula'] : ''), ' ·')) ?>
            </p>
            <?php endif; ?>

            <div class="swp-traspaso">
                <span class="swp-quien swp-quien--cede">
                    <span class="swp-quien__rol"><i class="fa-solid fa-arrow-right-from-bracket"></i> No la da</span>
                    <span class="swp-quien__nombre"><?= $s($m['cede']) ?></span>
                </span>
                <i class="fa-solid fa-arrow-right swp-traspaso__flecha" aria-hidden="true"></i>
                <span class="swp-quien swp-quien--cubre">
                    <span class="swp-quien__rol"><i class="fa-solid fa-chalkboard-user"></i> La cubre</span>
                    <span class="swp-quien__nombre"><?= $s($m['cubre']) ?></span>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
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
