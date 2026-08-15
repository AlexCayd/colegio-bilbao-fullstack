<?php $paginaVista = 'blog-swaps-crear'; ?>
<?php
/**
 * Proponer un intercambio.
 *
 * Tres pasos en una sola pantalla, en el orden en que se piensa el problema:
 *   1. qué clase no puedo dar (y qué día)
 *   2. con quién quiero cambiarla
 *   3. cuál de sus clases tomo a cambio  ← se carga por AJAX dentro de la ventana
 *
 * El paso 3 depende de los dos anteriores, así que arranca bloqueado.
 *
 * El paso 1 usa la MISMA rejilla semanal que Suplencias (.supl-week, window.SuplWeek):
 * un <select> con "Lunes · 08:00–08:50 · Matemáticas (1A)" obliga a reconstruir la
 * semana en la cabeza para encontrar la clase, y aquí el profesor ya sabe dónde está
 * mirando su horario. Se marca UNA sola (opts.single).
 *
 * Quien COORDINA ve un paso 0 más: elige también al primer profesor, porque no está
 * cediendo una clase suya sino reasignando las de otros dos. Su intercambio nace ya
 * validado, así que la pantalla se lo dice antes de enviarlo.
 *
 * @var \Model\Horario[] $misClases  solo para el empty state: la rejilla las trae por AJAX
 * @var int   $ventana   días de margen (Swap::DIAS_VENTANA)
 * @var bool  $coordina  prefectura o dirección
 * @var int   $uid
 */
$misClasesReales = array_filter($misClases, fn($h) => ($h->tipo ?? 'clase') !== 'guardia');
$s = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$coordina = $coordina ?? false;
// Los pasos se renumeran cuando aparece el paso 0, o habría dos «1» en pantalla.
$p = fn(int $n) => $coordina ? $n + 1 : $n;
$nomProf = fn($id) => $id ? (\Model\UsuarioBlog::find((int)$id)->nombre ?? '') : '';
?>
<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">

        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title"><?= $coordina ? 'Registrar intercambio' : 'Proponer intercambio' ?></span>
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

            <?php if ($coordina): ?>
            <div class="swp-nota-rol">
                <span class="swp-nota-rol__ico"><i class="fa-solid fa-user-shield"></i></span>
                <span class="swp-nota-rol__txt">
                    <strong>Este intercambio quedará validado al registrarlo.</strong>
                    No se pide la conformidad de los profesores: se les avisa de que su clase cambió.
                </span>
            </div>
            <?php endif; ?>

            <?php /* Los pasos van en tarjetas encadenadas por una guía vertical: es un
                     recorrido con dependencias (el paso 3 necesita los dos anteriores),
                     y apilar secciones sueltas no dejaba verlo. `data-uid` es de quién se
                     carga la rejilla; para un profesor es él mismo, y quien coordina lo
                     reescribe desde el picker del paso 1. */ ?>
            <form method="POST" action="/dashboard/swaps/crear" class="swp-form-wrap"
                  data-swap-form data-ventana="<?= (int)$ventana ?>"
                  data-coordina="<?= $coordina ? '1' : '0' ?>"
                  data-uid="<?= $coordina ? (int)($swap->solicitante_id ?? 0) : (int)$uid ?>">

                <?php if ($coordina): ?>
                <section class="swp-step">
                    <div class="swp-step__aside"><span class="swp-step__n">1</span></div>
                    <div class="swp-step__body">
                        <h2 class="swp-step__title">¿Quién no puede dar su clase?</h2>
                        <p class="swp-step__sub">Su horario aparecerá en el paso siguiente.</p>
                        <div class="admin-form__group" style="max-width:420px;">
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
                    </div>
                </section>
                <?php endif; ?>

                <section class="swp-step">
                    <div class="swp-step__aside"><span class="swp-step__n"><?= $p(1) ?></span></div>
                    <div class="swp-step__body">
                        <h2 class="swp-step__title"><?= $coordina ? '¿Qué clase se cede?' : '¿Qué clase no puedes dar?' ?></h2>
                        <p class="swp-step__sub">Elige el día y marca la clase en la rejilla.</p>

                    <div class="admin-form__group" style="max-width:340px;">
                        <?php
                        // El datepicker propio (.bilbao-date). Días hábiles solo:
                        // un intercambio de clase no tiene sentido en fin de semana.
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
                            Todavía no tienes clases cargadas en tu horario, así que no hay nada que intercambiar.
                        </p>
                        <?php else: ?>
                        <?php /* La pinta window.SuplWeek en modo select con `single`: la misma
                                 rejilla y el mismo endpoint que Suplencias, así que las dos no
                                 pueden divergir. El hidden lo escribe blog-swaps-crear.js con
                                 el `horario_id` de la celda. */ ?>
                        <div class="swp-week" data-swap-week></div>
                        <p class="swp-week__pie" data-swap-week-pie><?= $coordina ? 'Elige primero el profesor y el día.' : 'Elige primero el día.' ?></p>
                        <?php endif; ?>

                        <input type="hidden" name="horario_origen_id" data-swap-origen
                               value="<?= (int)($swap->horario_origen_id ?? 0) ?: '' ?>">
                    </div>
                </section>

                <section class="swp-step">
                    <div class="swp-step__aside"><span class="swp-step__n"><?= $p(2) ?></span></div>
                    <div class="swp-step__body">
                        <h2 class="swp-step__title"><?= $coordina ? '¿Con quién se cambia?' : '¿Con quién quieres cambiarla?' ?></h2>
                        <p class="swp-step__sub">Solo aparece personal docente.</p>
                        <div class="admin-form__group" style="max-width:420px;">
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
                    </div>
                </section>

                <section class="swp-step">
                    <div class="swp-step__aside"><span class="swp-step__n"><?= $p(3) ?></span></div>
                    <div class="swp-step__body">
                        <h2 class="swp-step__title"><?= $coordina ? '¿Cuál de sus clases toma a cambio?' : '¿Cuál de sus clases das tú a cambio?' ?></h2>
                        <p class="swp-step__sub">
                            Dentro de los <strong><?= (int)$ventana ?> días</strong> siguientes al día que se cede.
                        </p>

                        <div class="swp-opciones" data-swap-opciones>
                            <p class="swp-opciones__vacio">Completa los pasos anteriores para ver sus clases disponibles.</p>
                        </div>

                        <input type="hidden" name="horario_destino_id" data-swap-destino value="<?= (int)($swap->horario_destino_id ?? 0) ?: '' ?>">
                        <input type="hidden" name="fecha_destino"      data-swap-fecha   value="<?= $s($swap->fecha_destino ?? '') ?>">
                    </div>
                </section>

                <?php /* El motivo NO es un paso numerado: es opcional y no condiciona
                         nada, así que sale de la cadena y va como cierre del formulario. */ ?>
                <section class="swp-motivo">
                    <label class="admin-form__label" for="motivo">
                        <i class="fa-regular fa-comment"></i> ¿Por qué?
                        <small>(opcional, lo verá<?= $coordina ? 'n los dos profesores' : ' tu compañero' ?>)</small>
                    </label>
                    <input type="text" id="motivo" name="motivo" class="admin-form__input"
                           maxlength="255" value="<?= $s($swap->motivo ?? '') ?>"
                           placeholder="Ej.: Tengo una cita médica esa mañana.">
                </section>

                <div class="swp-acciones">
                    <a href="/dashboard/swaps" class="admin-btn admin-btn--ghost">Cancelar</a>
                    <button type="submit" class="admin-btn admin-btn--primary">
                        <?php if ($coordina): ?>
                        <i class="fa-solid fa-circle-check"></i> Registrar y validar
                        <?php else: ?>
                        <i class="fa-solid fa-paper-plane"></i> Enviar propuesta
                        <?php endif; ?>
                    </button>
                </div>
            </form>

        </main>
    </div>
</div>
