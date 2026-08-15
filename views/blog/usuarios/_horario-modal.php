<?php
/**
 * Alta y edición de un bloque de horario.
 *
 * Un solo formulario para los dos modos: crear (la casilla pulsada) y editar (la casilla
 * del bloque pulsado). El JS rellena `celdas[]`, `id` y los valores al abrirlo; el
 * servidor no distingue más que por la presencia de `id`. Siempre es UNA casilla: el
 * alta por lote desapareció, un bloque = un clic.
 *
 * @var \Model\UsuarioBlog $profesor  @var string $nivel
 * @var array $grupos    ['Primaria' => Grupo[], …]
 * @var array $materias  ['Primaria' => Materia[], …]
 * @var \Model\Aula[] $aulas  @var string[] $paleta
 * @var \Model\LugarGuardia[] $lugares
 */
$gruposNivel   = $grupos[$nivel]   ?? [];
$materiasNivel = $materias[$nivel] ?? [];
?>
<div class="hed-modal" id="hedModal" hidden>
    <div class="hed-modal__card" role="dialog" aria-modal="true" aria-labelledby="hedModalTitle">

        <div class="hed-modal__head">
            <h3 class="hed-modal__title" id="hedModalTitle" data-hed-title>Nuevo bloque</h3>
            <button type="button" class="hed-modal__x" data-hed-cancel aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <?php /* Qué casillas se van a escribir. Sin esto, "4 casillas" no dice cuáles y
                 el admin tiene que fiarse de lo que creía haber marcado. */ ?>
        <p class="hed-modal__scope"><span class="hed-modal__chips" data-hed-chips></span></p>

        <form method="POST" action="/dashboard/usuarios/horario/bloque" class="hed-form" data-hed-form>
            <input type="hidden" name="profesor_id" value="<?= (int)$profesor->id ?>">
            <input type="hidden" name="nivel" value="<?= s($nivel) ?>">
            <input type="hidden" name="id" value="" data-hed-id>
            <input type="hidden" name="forzar_aula" value="" data-hed-forzar>
            <span data-hed-celdas></span>

            <?php /* ── Clase o guardia ──
                     No se elige: lo decide la casilla. Una guardia va SIEMPRE sobre un
                     receso y una clase nunca (guardarBloqueHorario() rechaza el cruce),
                     así que un selector solo servía para provocar ese error. Es una
                     insignia que informa; el hidden es lo que lee el servidor. */ ?>
            <div class="hed-tipo-badge" data-hed-tipo-badge>
                <i class="fa-solid fa-chalkboard" data-hed-tipo-ico></i>
                <span data-hed-tipo-txt>Clase</span>
                <small data-hed-tipo-sub>en una hora de la jornada</small>
            </div>
            <input type="hidden" name="tipo" value="clase" data-hed-tipo-val>

            <?php /* ── Lugar de la guardia ──
                     Tabs y no un <select>: son pocos, se eligen de un vistazo y la
                     última opción da de alta uno nuevo sin salir del modal. */ ?>
            <div class="hed-field hed-solo-guardia" data-hed-lugares-wrap hidden>
                <span class="hed-field__label">¿Dónde es la guardia?</span>
                <div class="hed-lugares" data-hed-lugares>
                    <?php foreach (($lugares ?? []) as $lg): ?>
                    <button type="button" class="hed-lugar" data-lugar="<?= (int)$lg->id ?>">
                        <?= s($lg->nombre) ?>
                    </button>
                    <?php endforeach; ?>
                    <button type="button" class="hed-lugar hed-lugar--nuevo" data-hed-lugar-nuevo>
                        <i class="fa-solid fa-plus"></i> Nuevo lugar
                    </button>
                </div>
                <div class="hed-lugar-alta" data-hed-lugar-alta hidden>
                    <input type="text" class="hed-lugar-alta__input" maxlength="80"
                           placeholder="Ej.: Pasillo de Kinder" data-hed-lugar-nombre>
                    <button type="button" class="admin-btn admin-btn--sm admin-btn--primary" data-hed-lugar-crear>
                        Añadir
                    </button>
                </div>
                <input type="hidden" name="lugar_id" value="" data-hed-lugar-val>
                <?php if (empty($lugares)): ?>
                <span class="hed-field__hint">Aún no hay lugares de guardia. Crea el primero.</span>
                <?php endif; ?>
            </div>

            <label class="hed-field hed-solo-clase">
                <span class="hed-field__label">Grupo</span>
                <?php /* Solo los del nivel de la pestaña: ofrecer los de otro nivel solo
                         serviría para provocar el error de coherencia del servidor. */ ?>
                <select class="hor-select" name="grupo_id" data-hed-grupo>
                    <option value="0">Sin grupo</option>
                    <?php foreach ($gruposNivel as $g): ?>
                    <option value="<?= (int)$g->id ?>"><?= s($g->nombre) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!$gruposNivel): ?>
                <span class="hed-field__hint">No hay grupos de <?= s($nivel) ?> en el catálogo.</span>
                <?php endif; ?>
            </label>

            <label class="hed-field hed-solo-clase">
                <span class="hed-field__label">Materia</span>
                <select class="hor-select" name="materia_id" data-hed-materia>
                    <option value="0">Sin materia</option>
                    <?php foreach ($materiasNivel as $m): ?>
                    <option value="<?= (int)$m->id ?>"><?= s($m->nombre) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!$materiasNivel): ?>
                <span class="hed-field__hint">No hay materias de <?= s($nivel) ?> en el catálogo.</span>
                <?php endif; ?>
            </label>

            <label class="hed-field hed-solo-clase">
                <span class="hed-field__label">Aula</span>
                <select class="hor-select" name="aula_id" data-hed-aula>
                    <option value="0">Sin aula</option>
                    <?php foreach ($aulas as $a): ?>
                    <option value="<?= (int)$a->id ?>"><?= s($a->nombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <?php /* ── Coteaching ──
                     El titular es el profesor que se está editando y no se elige aquí (se
                     cambia abriendo el editor de otra persona). Los acompañantes tienen
                     SU PROPIA fila en `horarios` con rol_docente='acompanante', así que
                     su hora también queda ocupada y sus suplencias cuadran sin casos
                     especiales. Máximo dos: es lo que se ve en los horarios reales. */ ?>
            <div class="hed-field hed-field--wide hed-solo-clase">
                <span class="hed-field__label">Profesores</span>
                <p class="hed-titular">
                    <i class="fa-solid fa-user-pen"></i>
                    <strong><?= s($profesor->nombre) ?></strong>
                    <span class="hed-titular__rol">titular</span>
                </p>
                <div class="picker" data-picker
                     data-picker-endpoint="/dashboard/usuarios/horario/profesores"
                     data-picker-multi
                     data-picker-name="acompanantes"
                     data-picker-max="2"
                     data-picker-exclude="<?= (int)$profesor->id ?>">
                    <div class="admin-form__input-wrapper">
                        <input type="text" class="admin-form__input" data-picker-input autocomplete="off"
                               placeholder="Añadir acompañante (opcional)…">
                    </div>
                    <div class="picker__results" data-picker-results></div>
                    <div class="hed-acomp" data-picker-chips></div>
                </div>
                <span class="hed-field__hint">
                    Solo si la clase la dan dos o tres profesores a la vez. Cada uno queda
                    con la hora ocupada, así que no se le asignará una suplencia en ella.
                </span>
            </div>

            <div class="hed-field">
                <span class="hed-field__label">Color del bloque</span>
                <div class="hed-colors" data-hed-colors>
                    <?php /* "Automático" = NULL en BD: el color lo deriva colorMateria()
                             del nombre de la materia, que es como se pintaba toda la
                             rejilla antes de que existiera la columna. */ ?>
                    <button type="button" class="hed-color hed-color--auto is-on" data-color=""
                            title="Automático: según la materia">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </button>
                    <?php foreach ($paleta as $hex): ?>
                    <button type="button" class="hed-color" data-color="<?= s($hex) ?>"
                            style="--c:<?= s($hex) ?>" title="<?= s($hex) ?>"></button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="color" value="" data-hed-color>
            </div>

            <div class="hed-modal__acts">
                <?php /* Solo en modo edición; el JS lo muestra. Va fuera del <form> de
                         creación porque son dos acciones distintas sobre la misma fila. */ ?>
                <button type="button" class="admin-btn admin-btn--danger hed-modal__del" data-hed-del hidden>
                    <i class="fa-solid fa-trash"></i> Eliminar
                </button>
                <span class="hed-modal__spacer"></span>
                <button type="button" class="admin-btn admin-btn--ghost" data-hed-cancel>Cancelar</button>
                <button type="submit" class="admin-btn admin-btn--primary" data-hed-save>
                    <i class="fa-solid fa-check"></i> Guardar
                </button>
            </div>
        </form>

        <?php /* Formulario de borrado aparte: el de arriba escribe, éste destruye, y
                 anidarlos no es HTML válido. Lo dispara el botón con data-hed-del. */ ?>
        <form method="POST" action="/dashboard/usuarios/horario/eliminar" id="hedDelForm" hidden>
            <input type="hidden" name="profesor_id" value="<?= (int)$profesor->id ?>">
            <input type="hidden" name="nivel" value="<?= s($nivel) ?>">
            <input type="hidden" name="id" value="" data-hed-del-id>
        </form>
    </div>
</div>
