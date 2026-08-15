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

                <div class="admin-form__group">
                    <label class="admin-form__label" for="tipo"><i class="fa-solid fa-tag"></i> Tipo</label>
                    <div class="admin-form__input-wrapper">
                        <select id="tipo" name="tipo" class="admin-form__input">
                            <?php foreach ($tipoLabel as $v => $l): ?>
                            <option value="<?= $v ?>" <?= ($evento->tipo ?? 'evento') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="admin-form__group">
                    <label class="admin-form__label" for="descripcion"><i class="fa-regular fa-note-sticky"></i> Descripción</label>
                    <textarea id="descripcion" name="descripcion" class="admin-form__input" rows="3" placeholder="Detalles para las familias…"><?= s($evento->descripcion ?? '') ?></textarea>
                </div>

                <?php
                // ── ¿Quién ve este evento? ──
                // Excluyente y explícito: un evento tiene un público, y de él depende
                // dónde se publica. Sustituye al antiguo interruptor «Visible para las
                // familias», que no sabía expresar un evento dirigido al alumnado.
                $audActual = $evento->audiencia ?? 'interno';
                ?>
                <div class="admin-form__group">
                    <span class="admin-form__label"><i class="fa-solid fa-bullhorn"></i> ¿Quién lo ve?</span>
                    <div class="ev-aud" role="radiogroup" aria-label="Audiencia del evento">
                        <?php foreach (\Model\Evento::AUDIENCIAS as $a): ?>
                        <label class="ev-aud__opt ev-aud__opt--<?= $a ?>">
                            <input type="radio" name="audiencia" value="<?= $a ?>" <?= $audActual === $a ? 'checked' : '' ?>>
                            <span class="ev-aud__box">
                                <span class="ev-aud__ico"><i class="fa-solid <?= \Model\Evento::AUDIENCIA_ICONO[$a] ?>"></i></span>
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
                    <span class="admin-form__label"><i class="fa-solid fa-layer-group"></i> Niveles educativos</span>
                    <div class="ev-niv">
                        <?php foreach (\Model\Materia::NIVELES as $n): ?>
                        <label class="ev-niv__chip">
                            <input type="checkbox" name="niveles[]" value="<?= $n ?>" <?= in_array($n, $nivSel, true) ? 'checked' : '' ?>>
                            <span><?= $n ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="admin-form__hint">Sin marcar ninguno, el evento es para todo el colegio.</p>
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
