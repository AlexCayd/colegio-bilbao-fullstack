<?php
/**
 * Horario semanal en PDF (Dompdf). Documento completo e independiente: sin layout,
 * sin sidebar y sin el bundle del panel.
 *
 * NO incluye `_grid.php`. Aquella celda es `position:absolute; inset:0` dentro de un
 * `<td>` relativo, y Dompdf no implementa posicionamiento absoluto en tabla: saldría
 * todo apilado en la esquina. Aquí el contenido va en flujo normal. Lo que sí se
 * comparte —que es lo que evita que las dos versiones diverjan— son los datos:
 * el mismo Horario::rejilla(), los mismos `span` y el mismo colorMateria().
 *
 * @var object   $profesor        el sujeto del horario. Basta con que tenga `nombre`,
 *                                así que sirve igual un UsuarioBlog, un Aula o un Grupo:
 *                                es lo que permite que las tres vistas del módulo
 *                                Horarios reusen esta plantilla sin ninguna rama.
 * @var string   $subtitulo       opcional; de qué horario se trata («Horario del grupo»)
 * @var array    $tramos          eje de tiempo
 * @var array    $rejilla         [dia] => celdas colocadas
 * @var array    $ocupadoPorDia   minutos de clase por día
 * @var int      $totalClases
 * @var string[] $niveles
 * @var string   $pdfCss          compilado de src/scss/horario-pdf.scss
 * @var string   $logoData        logo como data: URI
 */
$e      = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$dias   = \Model\Horario::DIAS;
$diasLg = \Model\Horario::DIAS_LABEL;

$minOcupados  = array_sum($ocupadoPorDia);
$horasEnteras = intdiv($minOcupados, 60);
$restoMin     = $minOcupados % 60;

// Ciclo escolar: de agosto a julio, así que antes de agosto seguimos en el que empezó
// el año pasado.
$hoy   = new DateTime();
$anioA = (int)$hoy->format('n') >= 8 ? (int)$hoy->format('Y') : (int)$hoy->format('Y') - 1;

// Celdas indexadas por el tramo en que arrancan; las que cubre un rowspan no emiten <td>.
$porTramo = [];
foreach ($dias as $d) {
    foreach ($rejilla[$d] ?? [] as $c) $porTramo[$d][$c['tramo']] = $c;
}

/* Altura de fila: TODAS iguales.
   En pantalla las filas van en proporción a su duración, pero en papel eso producía una
   rejilla desigual —la fila con clase se estiraba al contenido y la vacía se aplastaba—
   que se lee como un fallo de maquetación. Un horario impreso se consulta de un vistazo,
   y para eso la retícula regular vale más que la proporcionalidad.
   El reparto se hace en PHP: el CSS de Dompdf no sabe calcular sobre el alto de página.
   A4 apaisado = 297×210mm, menos 10mm de margen por lado y ~34mm de encabezado y pie. */
$altoUtilMm = 210 - 20 - 34;
$altoFilaMm = count($tramos) ? max(7, round($altoUtilMm / count($tramos), 2)) : 10;
?>
<style><?= $pdfCss ?></style>

<div class="hp-head">
    <?php /* Sin GD, Dompdf no puede incrustar el PNG y `$logoData` llega vacío. En vez
             de dejar la cabecera coja, la marca se escribe. */ ?>
    <?php if ($logoData): ?>
    <img src="<?= $logoData ?>" alt="Colegio Bilbao" class="hp-head__logo">
    <?php else: ?>
    <span class="hp-head__marca">Colegio<br>Bilbao</span>
    <?php endif; ?>
    <div class="hp-head__txt">
        <span class="hp-head__eyebrow"><?= $e($subtitulo ?? 'Horario semanal') ?> · Ciclo <?= $anioA ?>–<?= $anioA + 1 ?></span>
        <h1 class="hp-head__name"><?= $e($profesor->nombre) ?></h1>
        <?php if ($niveles): ?>
        <span class="hp-head__niv"><?= $e(implode(' · ', $niveles)) ?></span>
        <?php endif; ?>
    </div>
    <div class="hp-head__stat">
        <span class="hp-head__stat-n"><?= $horasEnteras ?>h<?= $restoMin ? ' ' . sprintf('%02d', $restoMin) : '' ?></span>
        <span class="hp-head__stat-l"><?= $totalClases ?> <?= $totalClases === 1 ? 'clase' : 'clases' ?></span>
    </div>
</div>

<?php if (empty($tramos)): ?>
<p class="hp-vacio">Todavía no hay clases cargadas en este horario.</p>
<?php else: ?>
<table class="hp-grid">
    <thead>
        <tr>
            <th class="hp-grid__corner">Hora</th>
            <?php foreach ($dias as $d): ?>
            <th><?= $e($diasLg[$d]) ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($tramos as $i => $t):
        $esHueco = !empty($t['hueco']);
        $rotulo  = $t['etiqueta'] !== '' ? $t['etiqueta'] : '';
    ?>
        <tr style="height:<?= $altoFilaMm ?>mm">
            <th class="hp-grid__hour<?= $esHueco ? ' is-hueco' : '' ?>">
                <?php if ($rotulo !== ''): ?>
                <span class="hp-grid__hour-l"><?= $e($rotulo) ?></span>
                <?php endif; ?>
                <span class="hp-grid__hour-t"><?= substr($t['inicio'], 0, 5) ?>–<?= substr($t['fin'], 0, 5) ?></span>
            </th>
            <?php foreach ($dias as $d):
                $c = $porTramo[$d][$i] ?? null;
                if ($c === null) continue;          // lo cubre un rowspan de más arriba
                $span = (int)$c['span'];
                $rs   = $span > 1 ? ' rowspan="' . $span . '"' : '';
            ?>
                <?php if ($c['tipo'] === 'receso'): ?>
                    <td class="hp-receso"<?= $rs ?>><span>Receso</span></td>
                <?php elseif ($c['tipo'] === 'clase'):
                    $h   = $c['horario'];
                    $b   = $c['bloque'] ?? new \Model\BloqueHorario($h);
                    $ops = $c['opciones'] ?? [];
                    $col = \Controllers\BlogController::colorMateria($h->materia, $h->color ?? null);
                    // En un horario impreso el profesor quiere ver el grupo y el aula:
                    // en pantalla compiten por una línea con ellipsis, aquí caben las dos.
                    $grupos = implode(' · ', $b->nombresGrupos());
                    $aula   = $h->aula_nombre ?? '';
                ?>
                    <?php /* Grupo y aula en UNA línea separadas por punto medio: dos
                             líneas sueltas estiraban la celda y descuadraban la retícula,
                             que es justo lo que se quiere evitar en papel. La hora NO se
                             repite: ya la dice la cabecera de la fila. Solo se escribe
                             cuando la clase abarca varios tramos, que es cuando la
                             cabecera de una sola fila no la cubre. */ ?>
                    <td class="hp-cell<?= $col['oscuro'] ? ' hp-cell--claro' : '' ?>"<?= $rs ?>
                        style="background:<?= $e($col['hex']) ?>">
                        <?php if ($b->esGuardia()): ?>
                            <span class="hp-cell__mat">Guardia</span>
                            <?php if ($h->lugar_nombre): ?>
                            <span class="hp-cell__sec"><?= $e($h->lugar_nombre) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="hp-cell__mat"><?= $e($h->materia ?: 'Clase') ?></span>
                            <?php $meta = array_filter([$grupos, $aula]); ?>
                            <?php if ($meta): ?>
                            <span class="hp-cell__sec"><?= $e(implode(' · ', $meta)) ?></span>
                            <?php endif; ?>
                            <?php /* Una materia dividida se pinta entera: el grupo se
                                      reparte y las dos opciones son igual de reales. */ ?>
                            <?php foreach ($ops as $op): ?>
                            <span class="hp-cell__sec">+ <?= $e($op->principal->materia ?: 'Clase') ?></span>
                            <?php endforeach; ?>
                            <?php if ($b->tieneAcompanantes()): ?>
                            <span class="hp-cell__sec">con <?= $e(implode(' y ', $b->nombresDocentes())) ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($span > 1): ?>
                        <span class="hp-cell__h"><?= substr($c['inicio'], 0, 5) ?>–<?= substr($c['fin'], 0, 5) ?></span>
                        <?php endif; ?>
                    </td>
                <?php else: ?>
                    <td class="hp-free"<?= $rs ?>></td>
                <?php endif; ?>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<div class="hp-foot">
    Colegio Bilbao · generado el <?= $e(fecha_larga($hoy->format('Y-m-d'), true)) ?>
</div>
