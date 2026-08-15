<?php
/**
 * Rejilla editable de UN nivel. Una fila = un periodo de esa jornada, cinco columnas de
 * día, y cada casilla es exactamente un `(dia, periodo_id)` — que es lo que la tabla
 * `horarios` guarda, y por eso aquí no se usa el eje de tramos de `Horario::rejilla()`.
 *
 * @var \Model\Periodo[] $periodos  jornada del nivel activo, recesos incluidos
 * @var array            $celdas    [dia][periodo_id] => ['propia'=>?Horario,'ajenas'=>Horario[]]
 * @var string           $nivel
 * @var array            $acomp     [dia][periodo_id] => [['id'=>int,'nombre'=>string], …]
 *                                  acompañantes (coteaching) del bloque de esa casilla
 */
$acomp = $acomp ?? [];
$dias   = \Model\Horario::DIAS;
$diasLg = \Model\Horario::DIAS_LABEL;
$NIVEL_CORTO = ['Maternal' => 'Mat', 'Kinder' => 'Kín', 'Primaria' => 'Prim',
                'Secundaria' => 'Sec', 'Bachillerato' => 'Bach'];
?>
<div class="hed-grid-wrap">
    <table class="hed-grid" data-hed-grid>
        <thead>
            <tr>
                <th class="hed-grid__corner">Hora</th>
                <?php foreach ($dias as $d): ?>
                <th><?= s($diasLg[$d]) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($periodos as $fi => $p): $esReceso = (int)$p->es_receso === 1; ?>
            <tr class="<?= $esReceso ? 'hed-grid__receso-row' : '' ?>">
                <?php /* Rótulo inerte: el atajo "esta hora los cinco días" desapareció
                         con la selección múltiple. Ahora una casilla es un bloque. */ ?>
                <th class="hed-grid__hour">
                    <span class="hed-grid__hour-label"><?= s($p->etiqueta) ?></span>
                    <span class="hed-grid__hour-time"><?= substr($p->hora_inicio, 0, 5) ?>–<?= substr($p->hora_fin, 0, 5) ?></span>
                </th>

                <?php foreach ($dias as $ci => $d):
                    $c      = $celdas[$d][(int)$p->id] ?? ['propia' => null, 'ajenas' => []];
                    $propia = $c['propia'];
                    $ajenas = $c['ajenas'];
                    // Prioridad propia > ajena > receso > libre
                    $tipo = $propia ? 'clase' : ($ajenas ? 'ocupada' : ($esReceso ? 'receso' : 'libre'));
                ?>
                <td class="hed-cell hed-cell--<?= $tipo ?>" data-hed-cell
                    data-dia="<?= $d ?>" data-periodo="<?= (int)$p->id ?>"
                    data-fila="<?= $fi ?>" data-col="<?= $ci ?>"
                    data-tipo="<?= $tipo ?>">

                    <?php if ($tipo === 'clase'):
                        $esGuardia = ($propia->tipo ?? 'clase') === 'guardia';
                        $col = \Controllers\BlogController::colorMateria(
                            $esGuardia ? 'Guardia' : $propia->materia, $propia->color);
                        // Dato preexistente que las reglas de hoy no dejarían crear. Se
                        // pinta y se puede borrar: ocultarlo lo volvería inarreglable.
                        // Una guardia SÍ va sobre el receso, así que ahí no es inválida.
                        $invalida = (!$esGuardia && $esReceso)
                                 || ($esGuardia && !$esReceso)
                                 || (!$esGuardia && $propia->grupo_nivel && $propia->grupo_nivel !== $nivel);
                        // Coteaching: los acompañantes tienen su propia fila en `horarios`,
                        // así que no llegan en $propia (que es la fila de ESTE profesor).
                        $acs      = $esGuardia ? [] : ($acomp[$d][(int)$p->id] ?? []);
                        $acNombres = implode(', ', array_column($acs, 'nombre'));
                    ?>
                    <button type="button" class="hed-block<?= $col['oscuro'] ? ' hed-block--dark' : '' ?><?= $invalida ? ' is-invalida' : '' ?><?= $esGuardia ? ' hed-block--guardia' : '' ?>"
                            style="--c:<?= s($col['hex']) ?>;"
                            data-hed-edit
                            data-id="<?= (int)$propia->id ?>"
                            data-tipo-bloque="<?= $esGuardia ? 'guardia' : 'clase' ?>"
                            data-lugar="<?= (int)$propia->lugar_id ?>"
                            data-grupo="<?= (int)$propia->grupo_id ?>"
                            data-materia="<?= (int)$propia->materia_id ?>"
                            data-aula="<?= (int)$propia->aula_id ?>"
                            data-color="<?= s((string)$propia->color) ?>"
                            data-donde="<?= s($diasLg[$d] . ' · ' . $p->etiqueta) ?>"
                            data-acomp="<?= s(json_encode($acs, JSON_UNESCAPED_UNICODE)) ?>"
                            title="<?= s(($esGuardia ? 'Guardia' : ($propia->materia ?: 'Clase')) . ' · ' . $diasLg[$d] . ' ' . substr($p->hora_inicio, 0, 5) . ($acNombres ? ' · con ' . $acNombres : '')) ?> — pulsa para editar">
                        <span class="hed-block__mat"><?= s($esGuardia ? 'Guardia' : ($propia->materia ?: 'Sin materia')) ?></span>
                        <span class="hed-block__sec"><?= s($esGuardia
                                ? ($propia->lugar_nombre ?: 'Sin lugar')
                                : (trim(($propia->grupo_nombre ?? '') . ' · ' . ($propia->aula_nombre ?? ''), ' ·') ?: '—')) ?></span>
                        <?php if ($acs): ?>
                        <?php /* Un contador y no los nombres: la fila mide 66px y ya lleva
                                 dos líneas. Los nombres van en el `title` y en el modal. */ ?>
                        <span class="hed-block__co" title="Coteaching con <?= s($acNombres) ?>">
                            <i class="fa-solid fa-user-group"></i><?= count($acs) + 1 ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($invalida): ?>
                        <span class="hed-block__flag" title="Este bloque no cuadra con la jornada de <?= s($nivel) ?>. Puedes corregirlo o borrarlo.">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </span>
                        <?php endif; ?>
                    </button>

                    <?php elseif ($tipo === 'ocupada'): $a = $ajenas[0]; ?>
                    <?php /* Clase de OTRO nivel que pisa esta hora en el reloj. No se
                             edita aquí (su periodo es de otra jornada) pero tiene que
                             verse, o parecería que la casilla está libre. */ ?>
                    <span class="hed-ajena" title="<?= s(($a->periodo_nivel ?: '?') . ' · ' . ($a->periodo_etiqueta ?: '') . ' · ' . ($a->materia ?: 'Clase') . ' · ' . substr((string)$a->periodo_inicio, 0, 5) . '–' . substr((string)$a->periodo_fin, 0, 5)) ?>">
                        <span class="hed-ajena__niv"><?= s($NIVEL_CORTO[$a->periodo_nivel] ?? $a->periodo_nivel ?: '?') ?></span>
                        <span class="hed-ajena__mat"><?= s($a->materia ?: 'Clase') ?></span>
                        <span class="hed-ajena__hora"><?= substr((string)$a->periodo_inicio, 0, 5) ?>–<?= substr((string)$a->periodo_fin, 0, 5) ?></span>
                        <?php if ($a->periodo_nivel && $a->periodo_nivel !== $nivel): ?>
                        <a class="hed-ajena__ir" href="?id=<?= (int)$a->profesor_id ?>&nivel=<?= urlencode($a->periodo_nivel) ?>">
                            Editar en <?= s($a->periodo_nivel) ?> <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (count($ajenas) > 1): ?>
                        <span class="hed-ajena__mas">+<?= count($ajenas) - 1 ?></span>
                        <?php endif; ?>
                    </span>

                    <?php elseif ($tipo === 'receso'): ?>
                    <?php /* El receso no es decorativo: es donde se agendan las guardias.
                             El JS abre el modal en modo «Guardia» leyendo `data-tipo` del
                             <td>, así que aquí no hace falta ningún otro marcador. */ ?>
                    <button type="button" class="hed-libre hed-libre--receso" data-hed-pick
                            title="Receso de <?= s($nivel) ?> — pulsa para asignar una guardia"
                            aria-label="<?= s($diasLg[$d] . ', ' . $p->etiqueta) ?> — receso, asignar guardia">
                        <i class="fa-solid fa-mug-hot"></i>
                        <i class="fa-solid fa-plus hed-libre__mas"></i>
                    </button>

                    <?php else: ?>
                    <button type="button" class="hed-libre" data-hed-pick
                            title="Pulsa para dar clase a esta hora"
                            aria-label="<?= s($diasLg[$d] . ', ' . $p->etiqueta) ?> — libre, añadir clase">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                    <?php endif; ?>
                </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
