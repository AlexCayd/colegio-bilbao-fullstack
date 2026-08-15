<?php
/**
 * Rejilla semanal de horario (solo lectura). La comparten `index.php` (por profesor/aula/grupo)
 * y `mi-horario.php`. Espera:
 *   @var array  $tramos   eje de tiempo, de Horario::rejilla()['tramos']
 *   @var array  $rejilla  [dia] => celdas ya colocadas, de Horario::rejilla()['rejilla']
 *   @var string $vista    'profesor' | 'aula' | 'grupo'  (qué dato secundario se pinta)
 *
 * Las filas NO son periodos, son tramos de reloj: la jornada es distinta en cada nivel y
 * un profesor puede dar clase en varios, así que sus clases no caben en una sola jornada.
 * Cada clase ocupa tantos tramos como dure (`span` → `rowspan`). Con un solo nivel el eje
 * coincide con su jornada y todos los span valen 1: la rejilla se ve igual que siempre.
 * El cálculo vive entero en Horario::rejilla(), compartido con la rejilla de suplencias.
 */
$dias   = \Model\Horario::DIAS;
$diasLg = \Model\Horario::DIAS_LABEL;

// El color lo calcula BlogController::colorMateria(): fuente única compartida con el
// JSON de suplencias y con el editor. Antes esta vista tenía su propia copia de la
// paleta y del crc32, y el color de un bloque elegido a mano no le habría llegado.
// Qué "otra" dimensión mostrar en cada celda según la vista.
//
// Recibe el BLOQUE, no la fila: una clase puede tener titular + acompañante y, si es
// conjunta, ir a dos grupos. En la vista por grupo se listan TODOS los docentes —que es
// justo lo que hay que ver ahí, y lo que muestran los horarios oficiales—; en la vista
// por profesor se listan todos los grupos.
if (!function_exists('celdaSecundaria')) {
    function celdaSecundaria(string $vista, \Model\BloqueHorario $b): string {
        $h      = $b->principal;
        $profes = implode(' · ', $b->nombresDocentes());
        $grupos = implode(' · ', $b->nombresGrupos());
        if ($b->esGuardia()) return trim('Guardia · ' . ($h->lugar_nombre ?? ''), ' ·');
        return match ($vista) {
            'aula'  => trim($profes . ' · ' . $grupos, ' ·'),
            'grupo' => trim($profes . ' · ' . ($h->aula_nombre ?? ''), ' ·'),
            default => trim($grupos . ' · ' . ($h->aula_nombre ?? ''), ' ·'),
        };
    }
}
// Una opción de materia dividida dentro de la celda.
if (!function_exists('celdaOpcion')) {
    function celdaOpcion(string $vista, \Model\BloqueHorario $b): string {
        $col = \Controllers\BlogController::colorMateria($b->principal->materia, $b->principal->color ?? null);
        return '<span class="hor-cell__opt" style="--c:' . htmlspecialchars($col['hex']) . ';">'
             . '<span class="hor-cell__mat">' . htmlspecialchars($b->principal->materia ?: 'Clase', ENT_QUOTES, 'UTF-8') . '</span>'
             . '<span class="hor-cell__sec">' . htmlspecialchars(celdaSecundaria($vista, $b), ENT_QUOTES, 'UTF-8') . '</span>'
             . '</span>';
    }
}
$vista = $vista ?? 'profesor';

// Celdas indexadas por el tramo en que arrancan: los tramos que no aparecen los cubre
// el rowspan de la celda de arriba, y ahí no se emite <td>.
$porTramo = [];
foreach ($dias as $d) {
    foreach ($rejilla[$d] ?? [] as $c) $porTramo[$d][$c['tramo']] = $c;
}
$discrepantes = $discrepantes ?? [];

// Con dos jornadas mezcladas hay filas que se repiten de rótulo ("Receso" de
// Secundaria a las 09:30 y el de Primaria a las 10:00): el nivel es lo que las
// distingue. Con un solo nivel sobra, y no se pinta.
$mixto = count($niveles ?? []) > 1;
$NIVEL_CORTO = ['Maternal' => 'Mat', 'Kinder' => 'Kín', 'Primaria' => 'Prim',
                'Secundaria' => 'Sec', 'Bachillerato' => 'Bach'];
?>
<?php if ($discrepantes): ?>
<?php /* Da clase en un nivel que no consta en su ficha. No se oculta nada —esas clases
          se siguen pintando, marcadas— pero conviene cuadrar el dato: los niveles
          declarados son los que acotan su rejilla y ordenan las suplencias. */ ?>
<div class="hor-ajeno-aviso">
    <i class="fa-solid fa-circle-question"></i>
    <span>
        Imparte clases de <strong><?= s(implode(' y ', $discrepantes)) ?></strong>, que no
        <?= count($discrepantes) === 1 ? 'consta' : 'constan' ?> entre sus niveles declarados.
        Se muestran igual, marcadas con <i class="fa-solid fa-circle-question"></i>.
    </span>
</div>
<?php endif; ?>
<div class="hor-grid-wrap">
    <table class="hor-grid">
        <thead>
            <tr>
                <th class="hor-grid__corner">Hora</th>
                <?php foreach ($dias as $d): ?>
                <th><?= s($diasLg[$d]) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($tramos as $i => $t): ?>
            <?php /* La altura va en proporción a `alto`, no a los minutos: en el eje
                      comprimido un tramo puede durar dos horas, y sin tope la fila se iría
                      a 174px. Un tramo que ningún día usa baja a una franja. Con eso el
                      rowspan lo sigue cuadrando el navegador (border-spacing incluido) y
                      no queda aritmética en el CSS. */ ?>
            <?php
            /* Tres formas de rotular una fila, según lo que el tramo signifique:
                 · hora con nombre  → "3ª hora" + su rango (y el nivel si el eje es mixto)
                 · fragmento        → solo la hora de inicio; no tiene nombre propio, y
                                      repetirla arriba y abajo era el rótulo duplicado
                                      "08:20 / 08:20" que se veía en la rejilla mixta
                 · hueco            → la hora, atenuada, sin rótulo                    */
            $esHueco = !empty($t['hueco']);
            $rotulo  = $t['etiqueta'] !== '' ? s($t['etiqueta']) : '';
            $nivTr   = $mixto && !empty($t['nivel']) ? ($NIVEL_CORTO[$t['nivel']] ?? $t['nivel']) : '';
            ?>
            <tr style="--min:<?= (int)($t['alto'] ?? $t['minutos']) ?>" class="<?= $esHueco ? 'hor-grid__gap' : ($rotulo === '' ? 'hor-grid__frag' : '') ?>">
                <th class="hor-grid__hour">
                    <?php if ($rotulo !== ''): ?>
                    <span class="hor-grid__hour-label"><?= $rotulo ?></span>
                    <span class="hor-grid__hour-time">
                        <?= substr($t['inicio'], 0, 5) ?>–<?= substr($t['fin'], 0, 5) ?><?= $nivTr ? ' · ' . s($nivTr) : '' ?>
                    </span>
                    <?php else: ?>
                    <span class="hor-grid__hour-time"><?= substr($t['inicio'], 0, 5) ?></span>
                    <?php endif; ?>
                </th>
                <?php foreach ($dias as $d): ?>
                    <?php
                    $c = $porTramo[$d][$i] ?? null;
                    if ($c === null) continue;   // lo cubre un rowspan de más arriba
                    $span = (int)$c['span'];
                    ?>
                    <?php if ($c['tipo'] === 'receso'): ?>
                        <td class="hor-grid__receso-cell"<?= $span > 1 ? ' rowspan="' . $span . '"' : '' ?>
                            title="Receso de <?= s($c['nivel']) ?>">
                            <i class="fa-solid fa-mug-hot"></i>
                        </td>
                    <?php elseif ($c['tipo'] === 'clase'):
                          $h = $c['horario'];
                          /* `bloque` es la clase entera (titular + acompañantes, y los dos
                             grupos si es conjunta). Se conserva el fallback a la fila suelta
                             por si alguna vista antigua llama a rejilla() sin agrupar. */
                          $b   = $c['bloque'] ?? new \Model\BloqueHorario($h);
                          $ops = $c['opciones'] ?? [];
                          $col = \Controllers\BlogController::colorMateria($h->materia, $h->color ?? null);
                          $titulo = ($b->esGuardia() ? 'Guardia' : ($h->materia ?: 'Clase'))
                                  . ' · ' . substr($c['inicio'], 0, 5) . '–' . substr($c['fin'], 0, 5)
                                  . ($h->periodo_nivel ? ' · ' . $h->periodo_nivel : '')
                                  . ($b->tieneAcompanantes() ? ' · ' . implode(' y ', $b->nombresDocentes()) : '');
                          if ($ops) $titulo .= ' — el grupo se reparte entre ' . (count($ops) + 1) . ' opciones'; ?>
                        <?php
                        /* Ficha completa de la celda, para el modal de detalle.
                           En pantalla la materia, el grupo, el aula y los docentes
                           compiten por una línea con `text-overflow: ellipsis`, así que
                           la casilla miente por omisión y el dato entero solo vivía en
                           el `title`. Todo esto ya está en $h/$b: ni una consulta más.

                           Viaja como una isla JSON en un data-* y no como quince
                           atributos sueltos, porque las claves opcionales (lugar,
                           opciones, conflicto) irían la mitad de las veces vacías. */
                        $ficha = [
                            'materia'  => $b->esGuardia() ? 'Guardia' : ($h->materia ?: 'Clase'),
                            'guardia'  => $b->esGuardia(),
                            'lugar'    => $h->lugar_nombre,
                            'inicio'   => substr($c['inicio'], 0, 5),
                            'fin'      => substr($c['fin'], 0, 5),
                            'periodo'  => $h->periodo_etiqueta,
                            'nivel'    => $h->periodo_nivel,
                            'grupos'   => $b->nombresGrupos(),
                            'aula'     => $h->aula_nombre,
                            'docentes' => $b->nombresDocentes(),
                            'color'    => $col['hex'],
                            'ajeno'    => !empty($c['ajeno']),
                            'opciones' => array_map(fn($op) => [
                                'materia' => $op->principal->materia ?: 'Clase',
                                'grupos'  => $op->nombresGrupos(),
                                'aula'    => $op->principal->aula_nombre,
                                'docentes'=> $op->nombresDocentes(),
                            ], $ops),
                            'conflicto' => array_map(fn($x) => [
                                'materia' => $x->materia ?: 'Clase',
                                'inicio'  => substr((string)$x->periodo_inicio, 0, 5),
                                'nivel'   => $x->periodo_nivel,
                            ], $c['conflicto'] ?? []),
                        ];
                        ?>
                        <td<?= $span > 1 ? ' rowspan="' . $span . '"' : '' ?>>
                            <?php /* <button> y no <div>: es interactivo, así que tiene que
                                     poder recibir foco y activarse con teclado. */ ?>
                            <button type="button" class="hor-cell<?= $col['oscuro'] ? ' hor-cell--dark' : '' ?><?= !empty($c['ajeno']) ? ' hor-cell--ajeno' : '' ?><?= $ops ? ' hor-cell--split' : '' ?><?= $b->esGuardia() ? ' hor-cell--guardia' : '' ?>" style="--c:<?= s($col['hex']) ?>;"
                                 data-hor-cell="<?= s(json_encode($ficha, JSON_UNESCAPED_UNICODE)) ?>"
                                 title="<?= s($titulo) ?>">
                                <?php if ($ops): ?>
                                    <?php /* Materia dividida: el alumnado se reparte entre dos o más
                                              opciones simultáneas (1ºA de Secundaria tiene Arte y
                                              Música a la vez). Se pintan todas, como en el horario
                                              oficial del grupo. */ ?>
                                    <?= celdaOpcion($vista, $b) ?>
                                    <?php foreach ($ops as $op): ?><?= celdaOpcion($vista, $op) ?><?php endforeach; ?>
                                <?php else: ?>
                                    <span class="hor-cell__mat"><?= s($b->esGuardia() ? 'Guardia' : ($h->materia ?: 'Clase')) ?></span>
                                    <span class="hor-cell__sec"><?= s(celdaSecundaria($vista, $b)) ?></span>
                                <?php endif; ?>
                                <?php if ($b->tieneAcompanantes() && $vista !== 'grupo'): ?>
                                <span class="hor-cell__duo" title="<?= s(implode(' y ', $b->nombresDocentes())) ?>">
                                    <i class="fa-solid fa-user-group"></i>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($c['conflicto'])): ?>
                                <?php /* Dos clases que se pisan: la BD ya no puede impedirlo entre
                                          niveles distintos, así que se señala en vez de perderla. */ ?>
                                <span class="hor-cell__conflicto" title="Se solapa con: <?= s(implode(', ', array_map(fn($x) => ($x->materia ?: 'Clase') . ' (' . substr($x->periodo_inicio, 0, 5) . ')', $c['conflicto']))) ?>">
                                    <i class="fa-solid fa-triangle-exclamation"></i><?= count($c['conflicto']) + 1 ?>
                                </span>
                                <?php elseif (!empty($c['ajeno'])): ?>
                                <?php /* Clase de un nivel que no tiene declarado. No es un choque de
                                          datos sino un ajuste de configuración: otro icono y otro color. */ ?>
                                <span class="hor-cell__ajeno" title="<?= s($h->periodo_nivel) ?>: nivel no declarado en su ficha">
                                    <i class="fa-solid fa-circle-question"></i>
                                </span>
                                <?php endif; ?>
                            </button>
                        </td>
                    <?php else: ?>
                        <td class="hor-grid__free"<?= $span > 1 ? ' rowspan="' . $span . '"' : '' ?>><span>Libre</span></td>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php /* El modal de detalle. Se incluye una sola vez por página aunque haya varias
         rejillas: admin-horario-celda.js lo busca por id y lo rellena al vuelo. */ ?>
<?php include __DIR__ . '/_celda-modal.php'; ?>
