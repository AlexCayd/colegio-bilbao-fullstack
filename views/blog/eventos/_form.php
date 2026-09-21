<?php
/** @var \Model\Evento $evento  @var string $action  @var array $alertas */
$esEdicion = !empty($evento->id);
$tipoLabel = \Model\Evento::TIPO_LABEL;
?>
<?php if (!empty($alertas['error'])): ?>
<div class="admin-alerta admin-alerta--error" style="margin-bottom:16px;">
    <i class="fa-solid fa-circle-exclamation"></i>
    <ul class="admin-alerta__list"><?php foreach ($alertas['error'] as $e): ?><li><?= s($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form action="<?= $action ?>" method="POST" class="admin-form">
    <?php if ($esEdicion): ?><input type="hidden" name="id" value="<?= (int)$evento->id ?>"><?php endif; ?>
    <div class="supl-form-grid">
        <div class="admin-panel">
            <div class="admin-panel__header"><h2 class="admin-panel__title">Datos del evento</h2></div>
            <div class="admin-form-section">
                <div class="admin-form__group">
                    <label class="admin-form__label" for="titulo"><i class="fa-solid fa-heading"></i> Título</label>
                    <div class="admin-form__input-wrapper">
                        <input type="text" id="titulo" name="titulo" class="admin-form__input" required placeholder="Ej. Junta de padres" value="<?= s($evento->titulo ?? '') ?>">
                    </div>
                </div>

                <?php /* Datepicker propio del panel. Un evento sí puede caer en fin de
                         semana (festivos, actividades), así que no se filtran días hábiles. */ ?>
                <div class="admin-form-row">
                    <?php
                    $fechaName = 'fecha'; $fechaLabel = 'Fecha'; $fechaHabiles = false;
                    $fechaValor = $evento->fecha ?? date('Y-m-d');
                    include __DIR__ . '/../_campo-fecha.php';

                    $fechaName = 'fecha_fin'; $fechaLabel = 'Fecha fin (opcional)'; $fechaHabiles = false;
                    $fechaValor = $evento->fecha_fin ?? ''; $fechaPlaceholder = 'Sin fecha de fin';
                    include __DIR__ . '/../_campo-fecha.php';
                    ?>
                </div>

                <?php
                // ── Tipo ──
                // Pastillas y no un <select>: el tipo decide el color y el icono con los
                // que el evento se publica, y un desplegable esconde justo eso detrás de
                // cinco nombres. Cada pastilla lleva su color y su glifo, así que la
                // elección se ve antes de hacerla.
                $tipoAct = $evento->tipo ?? 'evento';
                ?>
                <div class="admin-form__group">
                    <span class="admin-form__label" id="ev-tipo-lbl"><i class="fa-solid fa-tag"></i> Tipo</span>
                    <div class="ev-tipo" role="radiogroup" aria-labelledby="ev-tipo-lbl" aria-describedby="ev-tipo-hint">
                        <?php foreach ($tipoLabel as $v => $l): ?>
                        <label class="ev-tipo__opt" style="--c:<?= s(\Model\Evento::TIPO_COLOR[$v] ?? '#4267ac') ?>;">
                            <input type="radio" name="tipo" value="<?= $v ?>" <?= $tipoAct === $v ? 'checked' : '' ?>>
                            <span class="ev-tipo__box">
                                <?php /* El glifo no es decoración: sin marcar identifica el tipo, y
                                         marcado se rellena en disco con el glifo en blanco — la marca
                                         de selección, para que «elegido» no dependa solo del color. */ ?>
                                <i class="fa-solid <?= s(\Model\Evento::TIPO_ICONO[$v] ?? 'fa-calendar-day') ?>" aria-hidden="true"></i>
                                <?= $l ?>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="admin-form__hint" id="ev-tipo-hint">Define el color y el icono con los que el evento se marca en los calendarios.</p>
                </div>

                <?php
                // ── Icono ──
                // La primera opción es «Automático»: el icono del tipo. Se guarda como
                // NULL, así que el vínculo se conserva — cambiar el tipo del evento más
                // adelante le cambia también el icono, que es lo que se espera.
                //
                // El catálogo es lista blanca (Evento::normalizarIcono()): lo que se
                // elige aquí acaba como clase CSS en la web pública.
                $icoActual = $evento->icono ?? '';   // $tipoAct ya viene del campo de tipo
                ?>
                <div class="admin-form__group">
                    <span class="admin-form__label" id="ev-ico-lbl"><i class="fa-solid fa-icons"></i> Icono</span>
                    <?php /* Los dos mapas viajan como isla de datos y no interpolados en el JS:
                             el icono y el color de «Automático» dependen del radio de tipo
                             que esté marcado (antes era un <select>). */ ?>
                    <div class="ev-ico" data-evento-icono role="radiogroup" aria-labelledby="ev-ico-lbl"
                         data-tipo-iconos='<?= s(json_encode(\Model\Evento::TIPO_ICONO)) ?>'
                         data-tipo-colores='<?= s(json_encode(\Model\Evento::TIPO_COLOR)) ?>'>
                        <label class="ev-ico__opt ev-ico__opt--auto" title="Usa el icono del tipo elegido">
                            <input type="radio" name="icono" value="" <?= $icoActual === '' ? 'checked' : '' ?>>
                            <span class="ev-ico__box">
                                <i class="fa-solid <?= s(\Model\Evento::TIPO_ICONO[$tipoAct] ?? 'fa-calendar-day') ?>" data-ico-auto aria-hidden="true"></i>
                                <span class="ev-ico__auto">Automático</span>
                            </span>
                        </label>

                        <?php foreach (\Model\Evento::ICONOS as $grupo => $iconos): ?>
                        <div class="ev-ico__grupo">
                            <span class="ev-ico__grupo-tit"><?= s($grupo) ?></span>
                            <div class="ev-ico__set">
                                <?php /* ⚠️ `aria-label` en el INPUT, no `title` en el <label>.
                                         El label envuelve al input y su único contenido es un <i>
                                         sin texto, así que el nombre accesible del radio salía
                                         VACÍO: un lector de pantalla anunciaba «botón de opción,
                                         3 de 36» treinta y cinco veces seguidas. El `title` del
                                         label no nombra al control; se conserva para el ratón. */ ?>
                                <?php foreach ($iconos as $clase => $etiqueta): ?>
                                <label class="ev-ico__opt" title="<?= s($etiqueta) ?>">
                                    <input type="radio" name="icono" value="<?= s($clase) ?>"
                                           aria-label="<?= s($etiqueta) ?> · <?= s($grupo) ?>"
                                           <?= $icoActual === $clase ? 'checked' : '' ?>>
                                    <span class="ev-ico__box"><i class="fa-solid <?= s($clase) ?>" aria-hidden="true"></i></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="admin-form__hint">Se muestra en el calendario de la web pública, junto al título del evento.</p>
                </div>

                <div class="admin-form__group">
                    <label class="admin-form__label" for="descripcion"><i class="fa-regular fa-note-sticky"></i> Descripción</label>
                    <textarea id="descripcion" name="descripcion" class="admin-form__input" rows="3" placeholder="Detalles para las familias…"><?= s($evento->descripcion ?? '') ?></textarea>
                </div>
            </div>

            <?php /* ⚠️ SEGUNDA SECCIÓN, y no un séptimo campo de la primera.
                     Tipo, audiencia y niveles son tres bloques llenos de color, cada uno
                     con su propio código cromático, y apilados sin corte competían: nada
                     decía que los tres primeros campos describen QUÉ es el evento y los dos
                     últimos DÓNDE se publica. El corte es lo que devuelve a cada color un
                     solo significado dentro de su sección, y repite el título de la tarjeta
                     de ayuda de la derecha, que ya hablaba de esto. */ ?>
            <div class="admin-form-section">
                <h3 class="admin-form-section__title"><i class="fa-solid fa-share-nodes"></i> Dónde se publica</h3>

                <?php
                // ── ¿Quién ve este evento? ──
                // Excluyente y explícito: un evento tiene un público, y de él depende
                // dónde se publica. Sustituye al antiguo interruptor «Visible para las
                // familias», que no sabía expresar un evento dirigido al alumnado.
                $audActual = $evento->audiencia ?? 'interno';
                ?>
                <div class="admin-form__group">
                    <span class="admin-form__label" id="ev-aud-lbl"><i class="fa-solid fa-bullhorn"></i> ¿Quién lo ve?</span>
                    <div class="ev-aud" role="radiogroup" aria-labelledby="ev-aud-lbl">
                        <?php foreach (\Model\Evento::AUDIENCIAS as $a): ?>
                        <label class="ev-aud__opt ev-aud__opt--<?= $a ?>">
                            <input type="radio" name="audiencia" value="<?= $a ?>" <?= $audActual === $a ? 'checked' : '' ?>>
                            <span class="ev-aud__box">
                                <span class="ev-aud__ico"><i class="fa-solid <?= \Model\Evento::AUDIENCIA_ICONO[$a] ?>" aria-hidden="true"></i></span>
                                <span class="ev-aud__nom"><?= \Model\Evento::AUDIENCIA_LABEL[$a] ?></span>
                                <span class="ev-aud__desc"><?= \Model\Evento::AUDIENCIA_DESC[$a] ?></span>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php
                // ── Niveles ──
                // Ninguno marcado = todo el colegio, que es también lo que se guarda si
                // se marcan los cinco (ver Evento::normalizarNiveles()).
                $nivSel = $evento->nivelesLista();
                ?>
                <div class="admin-form__group">
                    <span class="admin-form__label" id="ev-niv-lbl">
                        <i class="fa-solid fa-layer-group"></i> Niveles educativos
                        <span style="font-weight:400;color:var(--text-gray);">(uno o varios)</span>
                    </span>
                    <?php /* ⚠️ `.admin-nivel-check`, el MISMO componente que «Niveles que
                             imparte» del formulario de usuarios. Hubo aquí un `.ev-niv__chip`
                             propio que era un clon casi literal —mismo `Materia::NIVEL_COLOR`
                             en `--c`, mismo punto, mismo velo— y la misma pregunta se pintaba
                             distinta en dos pantallas del panel.
                             Y es una CASILLA, no una pastilla de radio: el punto se rellena con
                             un ✓ al marcarse, que es lo que separa «varios» de «uno solo» —el
                             tipo, justo arriba, son pastillas de radio y se parecían demasiado. */ ?>
                    <div class="admin-niveles-grid" role="group" aria-labelledby="ev-niv-lbl" aria-describedby="ev-niv-hint">
                        <?php foreach (\Model\Materia::NIVELES as $n): ?>
                        <label class="admin-nivel-check" style="--c:<?= s(\Model\Materia::colorNivel($n)) ?>;">
                            <input type="checkbox" name="niveles[]" value="<?= $n ?>" <?= in_array($n, $nivSel, true) ? 'checked' : '' ?>>
                            <span class="admin-nivel-check__box">
                                <span class="admin-nivel-check__dot"></span>
                                <span class="admin-nivel-check__name"><?= $n ?></span>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="admin-form__hint" id="ev-niv-hint">Sin marcar ninguno, el evento es para todo el colegio.</p>
                </div>
            </div>
            <div class="admin-form-footer">
                <button type="submit" class="admin-btn admin-btn--primary"><i class="fa-solid fa-floppy-disk"></i> <?= $esEdicion ? 'Guardar cambios' : 'Crear evento' ?></button>
                <a href="/dashboard/eventos" class="admin-btn admin-btn--ghost">Cancelar</a>
            </div>
        </div>

        <div class="admin-helper-card">
            <img src="/build/assets/img/alex/alex-recicla.png" alt="Alex" class="admin-helper-card__alex">
            <h3 class="admin-helper-card__title">¿Dónde se publica?</h3>
            <p class="admin-helper-card__text">Los eventos <strong>internos</strong> se quedan en el panel. Los de <strong>Familias</strong> salen en el calendario de Comunidad&nbsp;›&nbsp;Familias, y los de <strong>Estudiantes</strong> en Comunidad&nbsp;›&nbsp;Estudiantes. Todos aparecen en el calendario del inicio.</p>
        </div>
    </div>
</form>
