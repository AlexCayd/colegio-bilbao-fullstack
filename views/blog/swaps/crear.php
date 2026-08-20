<?php $paginaVista = 'blog-swaps-crear'; ?>
<?php
/**
 * Alta de un swap, como ASISTENTE POR PASOS.
 *
 * Una pregunta en pantalla cada vez, en el orden en que se piensa el problema:
 *   1. qué clase no puedo dar (y qué día)
 *   2. con quién quiero cambiarla
 *   3. cuál de sus clases tomo a cambio  ← se carga por AJAX dentro de la ventana
 *   4. repasar y enviar
 *
 * Quien COORDINA ve un paso más al principio: elige también al primer profesor, porque
 * no está cediendo una clase suya sino reasignando las de otros dos. Su swap nace ya
 * validado, así que la pantalla se lo dice antes de enviarlo.
 *
 * ── Por qué asistente y no un formulario largo ──
 * Antes las cuatro preguntas se apilaban en la misma pantalla, dentro de una columna
 * de 940px. Dos problemas a la vez: el último paso caía fuera del viewport (había que
 * hacer scroll para descubrir que existía), y la REJILLA SEMANAL —que es el corazón del
 * paso 1, cinco días × la jornada entera— se pintaba en poco más de la mitad del ancho
 * disponible. Con un paso a la vez la rejilla ocupa todo el ancho, que es justo lo que
 * necesita, y el recorrido cabe entero sin scroll.
 *
 * El progreso es explícito y permanente (`.swp-wiz__pasos` + `.swp-wiz__bar`): en un
 * formulario troceado, no ver cuánto queda es peor que verlo todo de golpe.
 *
 * Alex acompaña el recorrido: cambia de postura y de frase en cada paso
 * (`.swp-wiz__alex`). No decora — dice lo que el título no puede decir sin alargarse, y
 * es el mismo recurso que ya usan el home, «Trabajo por revisar» y los empty states.
 *
 * ⚠️ Los cinco hidden del POST (`solicitante_id`, `horario_origen_id`, `destinatario_id`,
 * `horario_destino_id`, `fecha_destino`) siguen DENTRO del <form> y en el DOM aunque su
 * panel esté oculto: el atributo `hidden` no desactiva un input, así que el contrato del
 * POST es idéntico al de antes. Aquí no se envía nada hasta el último paso.
 *
 * @var \Model\Swap      $swap       repinta los campos si `validar()` rebotó el POST
 * @var array            $alertas
 * @var \Model\Horario[] $misClases  solo para el empty state: la rejilla las trae por AJAX
 * @var int   $ventana   días de margen (Swap::DIAS_VENTANA)
 * @var bool  $coordina  prefectura o dirección
 * @var int   $uid
 */
$misClasesReales = array_filter($misClases, fn($h) => ($h->tipo ?? 'clase') !== 'guardia');
$s = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$coordina = $coordina ?? false;
$nomProf  = fn($id) => $id ? (\Model\UsuarioBlog::find((int)$id)->nombre ?? '') : '';

/* Los pasos son DATOS, no markup repetido: de aquí salen a la vez el índice lateral, la
   barra de progreso y la frase de Alex, así que no pueden desincronizarse. `clave` es lo
   que el JS usa para saber qué falta en cada uno. */
$PASOS = [];
if ($coordina) {
    $PASOS[] = ['clave' => 'cede', 'titulo' => '¿Quién no puede dar su clase?', 'corto' => 'Quién cede',
                'sub'   => 'Elige al profesor que cede la clase. Su horario aparecerá en el paso siguiente.',
                'alex'  => 'bby-alex-saluda', 'dice' => 'Un swap siempre es entre dos profesores. Empecemos por quien no puede dar su clase.'];
}
$PASOS[] = ['clave' => 'clase', 'titulo' => $coordina ? '¿Qué clase se cede?' : '¿Qué clase no puedes dar?', 'corto' => 'La clase',
            'sub'   => 'Elige el día y toca la clase en la rejilla.',
            'alex'  => 'alex-point', 'dice' => 'Toca la clase en la semana. Solo se puede ceder una: el swap cambia una clase por otra.'];
$PASOS[] = ['clave' => 'quien', 'titulo' => $coordina ? '¿Con quién se cambia?' : '¿Con quién quieres cambiarla?', 'corto' => 'El compañero',
            'sub'   => 'Solo aparece personal docente.',
            'alex'  => 'alex-dice', 'dice' => 'Escribe unas letras del nombre. Solo salen profesores, y nunca quien ya cede la clase.'];
$PASOS[] = ['clave' => 'cual', 'titulo' => $coordina ? '¿Cuál de sus clases toma a cambio?' : '¿Cuál de sus clases das tú a cambio?', 'corto' => 'La clase a cambio',
            'sub'   => 'Dentro de los ' . (int)$ventana . ' días siguientes al día que se cede.',
            'alex'  => 'alex-lee', 'dice' => 'Estas son sus clases dentro de la ventana de ' . (int)$ventana . ' días. Más allá ya no sería un swap, sería un cambio de horario.'];
$PASOS[] = ['clave' => 'fin', 'titulo' => 'Repasa el cambio', 'corto' => 'Confirmar',
            'sub'   => 'Comprueba que las dos clases son las correctas antes de enviarlo.',
            'alex'  => 'bby-alex-feliz',
            'dice'  => $coordina
                ? 'Al registrarlo queda validado y se avisa a los dos profesores. No se les pide conformidad.'
                : 'Tu compañero recibirá la propuesta. Hasta que la acepte y prefectura la valide, sigue siendo tu clase.'];

$TOTAL = count($PASOS);
?>
<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">

        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title"><?= $coordina ? 'Registrar swap' : 'Proponer swap' ?></span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">

            <?php if (!empty($alertas['error'])): ?>
            <div class="admin-alert admin-alert--error">
                <?php foreach ($alertas['error'] as $a): ?><p><?= $s($a) ?></p><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="/dashboard/swaps/crear" class="swp-wiz"
                  data-swap-form data-ventana="<?= (int)$ventana ?>"
                  data-coordina="<?= $coordina ? '1' : '0' ?>"
                  data-uid="<?= $coordina ? (int)($swap->solicitante_id ?? 0) : (int)$uid ?>">

                <!-- ── Columna de acompañamiento: Alex + índice de pasos ──────────── -->
                <aside class="swp-wiz__aside">
                    <?php /* Alex cambia de postura y de frase en cada paso. Los <img> se
                             emiten todos de una vez y el JS alterna cuál se ve: así no hay
                             un parpadeo de carga al avanzar.
                             La FRASE viaja en `data-dice` de cada imagen, no copiada en el
                             JS: el catálogo de pasos es uno solo y vive arriba, en PHP. */ ?>
                    <div class="swp-wiz__alex">
                        <div class="swp-wiz__alex-img">
                            <?php foreach ($PASOS as $i => $ps): ?>
                            <img src="/build/assets/img/alex/<?= $s($ps['alex']) ?>.png" alt=""
                                 data-alex-paso="<?= $i ?>" data-dice="<?= $s($ps['dice']) ?>"<?= $i === 0 ? '' : ' hidden' ?>>
                            <?php endforeach; ?>
                        </div>
                        <p class="swp-wiz__dice" data-swap-dice><?= $s($PASOS[0]['dice']) ?></p>
                    </div>

                    <ol class="swp-wiz__pasos" data-swap-indice>
                        <?php foreach ($PASOS as $i => $ps): ?>
                        <li class="swp-wiz__paso<?= $i === 0 ? ' is-actual' : '' ?>" data-indice="<?= $i ?>">
                            <span class="swp-wiz__paso-n"><?= $i + 1 ?></span>
                            <span class="swp-wiz__paso-txt">
                                <span class="swp-wiz__paso-corto"><?= $s($ps['corto']) ?></span>
                                <span class="swp-wiz__paso-val" data-paso-val></span>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                </aside>

                <!-- ── Panel del paso activo ──────────────────────────────────────── -->
                <div class="swp-wiz__main">

                    <div class="swp-wiz__bar">
                        <div class="swp-wiz__bar-txt">
                            <span>Paso <strong data-swap-actual>1</strong> de <?= $TOTAL ?></span>
                            <span data-swap-restante></span>
                        </div>
                        <div class="swp-wiz__bar-riel">
                            <span class="swp-wiz__bar-fill" data-swap-fill style="width:<?= round(100 / $TOTAL) ?>%"></span>
                        </div>
                    </div>

                    <?php if ($coordina): ?>
                    <?php /* El aviso vive fuera de los pasos: aplica al swap entero y hay
                             que verlo desde el principio, no solo al confirmar. */ ?>
                    <div class="swp-nota-rol">
                        <span class="swp-nota-rol__ico"><i class="fa-solid fa-user-shield"></i></span>
                        <span class="swp-nota-rol__txt">
                            <strong>Este swap quedará validado al registrarlo.</strong>
                            No se pide la conformidad de los profesores: se les avisa de que su clase cambió.
                        </span>
                    </div>
                    <?php endif; ?>

                    <?php foreach ($PASOS as $i => $ps): ?>
                    <?php /* ⚠️ `.swp-panel` tiene `display` propio, así que necesita SU PROPIO
                             `&[hidden]` en el SCSS: `[hidden]{display:none}` vive en
                             `base/_normalize.scss`, misma especificidad y capa anterior, y sin
                             esa línea el `flex` gana y no se oculta ningún paso. */ ?>
                    <section class="swp-panel" data-step="<?= $s($ps['clave']) ?>" data-indice="<?= $i ?>"<?= $i === 0 ? '' : ' hidden' ?>>
                        <header class="swp-panel__head">
                            <h2 class="swp-panel__title"><?= $s($ps['titulo']) ?></h2>
                            <p class="swp-panel__sub"><?= $s($ps['sub']) ?></p>
                        </header>

                        <div class="swp-panel__body">
                        <?php switch ($ps['clave']):
                            case 'cede': ?>
                            <div class="swp-campo">
                                <div class="picker<?= !empty($swap->solicitante_id) ? ' has-value' : '' ?>"
                                     data-picker data-picker-endpoint="/dashboard/swaps/buscar" data-swap-solicitante>
                                    <div class="admin-form__input-wrapper">
                                        <input type="text" class="admin-form__input" data-picker-input autocomplete="off"
                                               placeholder="Escribe el nombre del profesor…"
                                               value="<?= $s($nomProf($swap->solicitante_id ?? 0)) ?>">
                                        <button type="button" class="picker__clear" data-picker-clear aria-label="Quitar">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="solicitante_id" data-picker-value
                                           value="<?= (int)($swap->solicitante_id ?? 0) ?: '' ?>">
                                    <div class="picker__results" data-picker-results></div>
                                </div>
                            </div>
                            <?php break;

                            case 'clase': ?>
                            <div class="swp-campo swp-campo--fecha">
                                <?php
                                // El datepicker propio (.bilbao-date). Días hábiles solo:
                                // un swap de clase no tiene sentido en fin de semana.
                                $fechaName    = 'fecha_origen';
                                $fechaLabel   = $coordina ? 'Día que se cede' : 'Día que faltas';
                                $fechaValor   = $swap->fecha_origen ?: date('Y-m-d');
                                $fechaMin     = date('Y-m-d');
                                $fechaHabiles = true;
                                include __DIR__ . '/../_campo-fecha.php';
                                ?>
                            </div>

                            <?php if (!$coordina && !$misClasesReales): ?>
                            <p class="swp-vacio">
                                <i class="fa-regular fa-calendar-xmark"></i>
                                Todavía no tienes clases cargadas en tu horario, así que no hay nada que cambiar.
                            </p>
                            <?php else: ?>
                            <?php /* La pinta window.SuplWeek en modo select con `single`: la misma
                                     rejilla y el mismo endpoint que Suplencias, así que las dos no
                                     pueden divergir. Aquí dispone del ancho ENTERO de la pantalla,
                                     que es la razón de fondo de partir el formulario en pasos. */ ?>
                            <div class="swp-week" data-swap-week></div>
                            <p class="swp-week__pie" data-swap-week-pie><?= $coordina ? 'Marca la clase que se cede.' : 'Marca la clase que no vas a poder dar.' ?></p>
                            <?php endif; ?>
                            <input type="hidden" name="horario_origen_id" data-swap-origen
                                   value="<?= (int)($swap->horario_origen_id ?? 0) ?: '' ?>">
                            <?php break;

                            case 'quien': ?>
                            <div class="swp-campo">
                                <?php /* Buscador con autocompletado (admin-picker.js) en vez del
                                         <select> con el claustro entero: son decenas de nombres y
                                         se sabe a quién se busca antes de abrirlo. */ ?>
                                <div class="picker<?= !empty($swap->destinatario_id) ? ' has-value' : '' ?>"
                                     data-picker data-picker-endpoint="/dashboard/swaps/buscar" data-swap-destinatario>
                                    <div class="admin-form__input-wrapper">
                                        <input type="text" class="admin-form__input" data-picker-input autocomplete="off"
                                               placeholder="Escribe el nombre de un compañero…"
                                               value="<?= $s($nomProf($swap->destinatario_id ?? 0)) ?>">
                                        <button type="button" class="picker__clear" data-picker-clear aria-label="Quitar">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="destinatario_id" data-picker-value
                                           value="<?= (int)($swap->destinatario_id ?? 0) ?: '' ?>">
                                    <div class="picker__results" data-picker-results></div>
                                </div>
                            </div>
                            <?php break;

                            case 'cual': ?>
                            <div class="swp-opciones" data-swap-opciones></div>
                            <input type="hidden" name="horario_destino_id" data-swap-destino value="<?= (int)($swap->horario_destino_id ?? 0) ?: '' ?>">
                            <input type="hidden" name="fecha_destino"      data-swap-fecha   value="<?= $s($swap->fecha_destino ?? '') ?>">
                            <?php break;

                            case 'fin': ?>
                            <?php /* El trato entero, en una línea. Antes los dos lados vivían
                                     repartidos entre el pie de la rejilla y un radio marcado tres
                                     pantallazos más abajo, así que nadie llegaba a verlo junto.
                                     ⚠️ NO reutiliza `.swp-permuta`: aquella está encerrada en el
                                     scope de `blog-swaps-index` y aquí saldría sin un solo estilo. */ ?>
                            <section class="swp-resumen swp-resumen--pendiente" data-swap-resumen>
                                <div class="swp-resumen__lado" data-swap-resumen-cede>
                                    <span class="swp-resumen__rol"><?= $coordina ? 'Cede' : 'Cedes' ?></span>
                                    <span class="swp-resumen__clase">—</span>
                                    <span class="swp-resumen__meta"></span>
                                </div>
                                <span class="swp-resumen__flecha"><i class="fa-solid fa-right-left"></i></span>
                                <div class="swp-resumen__lado" data-swap-resumen-recibe>
                                    <span class="swp-resumen__rol"><?= $coordina ? 'Toma' : 'Das a cambio' ?></span>
                                    <span class="swp-resumen__clase">—</span>
                                    <span class="swp-resumen__meta"></span>
                                </div>
                                <p class="swp-resumen__falta" data-swap-resumen-falta>
                                    Vuelve atrás y elige las dos clases para ver el cambio completo.
                                </p>
                            </section>

                            <?php /* El motivo cierra el recorrido: es opcional, no condiciona nada
                                     y lo natural es escribirlo cuando ya se sabe qué se está
                                     pidiendo. */ ?>
                            <div class="swp-campo swp-campo--motivo">
                                <label class="admin-form__label" for="motivo">
                                    <i class="fa-regular fa-comment"></i> ¿Por qué?
                                    <small>(opcional, lo verá<?= $coordina ? 'n los dos profesores' : ' tu compañero' ?>)</small>
                                </label>
                                <input type="text" id="motivo" name="motivo" class="admin-form__input"
                                       maxlength="255" value="<?= $s($swap->motivo ?? '') ?>"
                                       placeholder="Ej.: Tengo una cita médica esa mañana.">
                            </div>
                            <?php break;

                        endswitch; ?>
                        </div>
                    </section>
                    <?php endforeach; ?>

                    <!-- ── Navegación ─────────────────────────────────────────────── -->
                    <?php /* El aviso de qué falta se ve ANTES de pulsar, no como castigo
                             después. Y «Siguiente» nunca sale deshabilitado: un botón muerto
                             sin explicación es indistinguible de uno roto. */ ?>
                    <div class="swp-wiz__nav">
                        <p class="swp-wiz__hint" data-swap-hint hidden></p>
                        <div class="swp-wiz__nav-btns">
                            <a href="/dashboard/swaps" class="admin-btn admin-btn--ghost" data-swap-cancelar>Cancelar</a>
                            <button type="button" class="admin-btn admin-btn--ghost" data-swap-atras hidden>
                                <i class="fa-solid fa-arrow-left"></i> Atrás
                            </button>
                            <button type="button" class="admin-btn admin-btn--primary" data-swap-siguiente>
                                Siguiente <i class="fa-solid fa-arrow-right"></i>
                            </button>
                            <button type="submit" class="admin-btn admin-btn--primary" data-swap-enviar hidden>
                                <?php if ($coordina): ?>
                                <i class="fa-solid fa-circle-check"></i> Registrar y validar
                                <?php else: ?>
                                <i class="fa-solid fa-paper-plane"></i> Enviar propuesta
                                <?php endif; ?>
                            </button>
                        </div>
                    </div>

                </div>
            </form>

        </main>
    </div>
</div>
