<?php
/**
 * Calendario del ciclo escolar en PDF (Comunidad › Familias).
 *
 * @var string $pdfCss      CSS ya compilado + las @font-face, inyectado (Dompdf no resuelve URLs)
 * @var string $logoData    logo como data: URI, o '' si falta la extensión GD
 * @var array  $ciclo       ['ini','fin','anio_ini','anio_fin','etiqueta']
 * @var array  $mesesCiclo  los doce meses del curso, en orden
 * @var array  $eventos     ya filtrados por nivel y aplanados por el controlador
 * @var array  $niveles     niveles del filtro; vacío = todo el colegio
 *
 * ⚠️ **Plantilla propia, no el markup de la web.** La página usa CSS grid y celdas
 * con `position:absolute`, y Dompdf no implementa ninguna de las dos cosas: saldría
 * todo apilado en una esquina. Lo que sí se comparte son los DATOS, que llegan de
 * `EstaticasController::aplanarEventos()` — la misma fuente que alimenta la pantalla,
 * así que el papel y la web no pueden enseñar calendarios distintos.
 *
 * Todo se maqueta con tablas por la misma razón.
 */

$MESES = ['', 'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$DOW   = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];

/**
 * Los días que ocupa un evento. Un tramo de varios días existe en todos ellos: unas
 * vacaciones del 20 de diciembre al 6 de enero tienen que pintarse en los dos meses.
 * El tope evita que un `fecha_fin` mal tecleado cuelgue la generación.
 */
$diasDe = static function (array $ev): array {
    $out = [$ev['fecha']];
    $fin = $ev['fecha_fin'] ?? null;
    if (!$fin || $fin <= $ev['fecha']) return $out;
    $cursor = strtotime($ev['fecha']);
    $tope   = strtotime($fin);
    for ($i = 0; $i < 400; $i++) {
        $cursor = strtotime('+1 day', $cursor);
        if ($cursor > $tope) break;
        $out[] = date('Y-m-d', $cursor);
    }
    return $out;
};

/**
 * ¿El blanco se lee sobre este color? Los tres claros de la paleta (ámbar, cyan y
 * lima) necesitan tinta oscura: es la misma regla de la rejilla de horarios, y
 * saltársela deja media paleta ilegible en papel. Se calcula por luminancia en vez
 * de listar los hex a mano, así un color nuevo de la paleta entra solo.
 */
$claro = static function (string $hex): bool {
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) return false;
    [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 150;
};

// ¿Se pueden pintar iconos? Depende de que el TTF de Font Awesome esté versionado en
// src/fonts/. Si no está, el documento sale sin ellos —el color y la etiqueta de tipo
// siguen distinguiendo cada evento— en vez de con la caja del glifo ausente.
$hayIconos = \Classes\Pdf::hayIconos();

// Índice día → eventos, y agrupación por mes para el listado.
$porDia = [];
foreach ($eventos as $ev) {
    foreach ($diasDe($ev) as $k) $porDia[$k][] = $ev;
}

$hoy = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Calendario <?= s($ciclo['etiqueta']) ?></title>
    <style><?= $pdfCss ?></style>
</head>
<?php
/* ⚠️ La rejilla de doce meses más el pie llenan la primera página EXACTA: medido, el
   presupuesto libre debajo de la rejilla es de 0 mm (la leyenda del caso normal cabe
   solo porque ahí el pie se va a la última página, detrás del detalle). Así que la
   nota del calendario vacío no se puede «añadir»: hay que reclamarle los milímetros
   al aire decorativo, y eso es lo que hace esta clase en el SCSS. Sin ella el
   documento sale con una segunda página que solo lleva el pie. */
?>
<body<?= $eventos ? '' : ' class="cp-sin-eventos"' ?>>

<table class="cp-head">
    <tr>
        <td class="cp-head__marca">
            <?php if ($logoData): ?>
                <img src="<?= $logoData ?>" class="cp-head__logo" alt="Colegio Bilbao">
            <?php else: ?>
                <?php /* Sin la extensión GD no hay logo incrustable: cae a marca
                         tipográfica y el PDF sale igual, sin reventar. */ ?>
                <span class="cp-head__wordmark">Colegio Bilbao</span>
            <?php endif; ?>
        </td>
        <td class="cp-head__tit">
            <h1>Calendario escolar</h1>
            <p><?= s($ciclo['etiqueta']) ?></p>
        </td>
        <td class="cp-head__meta">
            <?php if ($niveles): ?>
                <span class="cp-tag">Niveles: <?= s(implode(' · ', $niveles)) ?></span>
            <?php else: ?>
                <span class="cp-tag">Todo el colegio</span>
            <?php endif; ?>
            <span class="cp-fecha">Generado el <?= date('d/m/Y') ?></span>
            <?php if (!$eventos): ?>
                <?php /* ⚠️ El aviso de «sin eventos» va AQUÍ, en el hueco que le sobra a
                         esta celda —el logo es más alto que sus dos líneas—, y no bajo la
                         rejilla: medido con Dompdf, debajo no queda ni un milímetro libre
                         (rejilla + pie llenan la página exacta) y un párrafo de 9pt parte
                         el documento en dos, dejando una segunda página con solo el pie.
                         La leyenda del caso normal cabe porque allí el pie está en la
                         última página, detrás del detalle. */ ?>
                <span class="cp-vacio">Sin eventos publicados todavía</span>
            <?php endif; ?>
        </td>
    </tr>
</table>

<?php
// ── Rejilla de doce meses: 3 filas × 4 columnas ──
// Es lo que hace de este documento un calendario de pared y no un listado: de un
// vistazo se ve dónde caen los días marcados de todo el curso.
//
// ⚠️ **Se pinta SIEMPRE, también sin un solo evento.** Antes un ciclo vacío
// devolvía una hoja con la cabecera y la línea «No hay eventos publicados»: un
// folio en blanco que no se distingue de un PDF roto. Los doce meses del curso no
// dependen de que alguien haya cargado eventos —son el calendario igual—, así que
// lo que falta cuando no hay eventos es el contenido de las celdas, no la rejilla.
$filas = array_chunk($mesesCiclo, 4);
?>
<table class="cp-anio">
    <?php foreach ($filas as $fila): ?>
    <tr>
        <?php foreach ($fila as $info):
            $y = (int)$info['anio']; $m = (int)$info['mes'];
            $primerDow = (int)date('w', mktime(0, 0, 0, $m, 1, $y));
            $totalDias = (int)date('t', mktime(0, 0, 0, $m, 1, $y));
        ?>
        <td class="cp-anio__celda">
            <table class="cp-mes">
                <thead>
                    <tr><th colspan="7" class="cp-mes__tit"><?= $MESES[$m] ?> <span><?= $y ?></span></th></tr>
                    <tr><?php foreach ($DOW as $d): ?><th class="cp-mes__dow"><?= $d ?></th><?php endforeach; ?></tr>
                </thead>
                <tbody>
                <?php
                $celda = 0;
                echo '<tr>';
                for ($i = 0; $i < $primerDow; $i++) { echo '<td class="cp-mes__d cp-mes__d--off"></td>'; $celda++; }
                for ($d = 1; $d <= $totalDias; $d++) {
                    if ($celda % 7 === 0 && $celda > 0) echo '</tr><tr>';
                    $k    = sprintf('%04d-%02d-%02d', $y, $m, $d);
                    $evs  = $porDia[$k] ?? [];
                    $cls  = 'cp-mes__d';
                    $est  = '';
                    if ($evs) {
                        // El color es el del primer evento del día: en una celda de este
                        // tamaño dos franjas no se distinguen, solo ensucian.
                        $c    = $evs[0]['color'] ?? '#4267ac';
                        $cls .= $claro($c) ? ' cp-mes__d--ev cp-mes__d--claro' : ' cp-mes__d--ev';
                        $est  = ' style="background:' . s($c) . '"';
                    }
                    if ($k === $hoy) $cls .= ' cp-mes__d--hoy';
                    echo '<td class="' . $cls . '"' . $est . '>' . $d . '</td>';
                    $celda++;
                }
                while ($celda % 7 !== 0) { echo '<td class="cp-mes__d cp-mes__d--off"></td>'; $celda++; }
                echo '</tr>';
                ?>
                </tbody>
            </table>
        </td>
        <?php endforeach; ?>
        <?php /* Relleno para que la última fila no ensanche sus columnas. */ ?>
        <?php for ($i = count($fila); $i < 4; $i++): ?><td class="cp-anio__celda"></td><?php endfor; ?>
    </tr>
    <?php endforeach; ?>
</table>

<?php if ($eventos): ?>
<?php /* Sin eventos no hay leyenda ni detalle: explicarían colores que no aparecen en
         ninguna celda. El aviso va en el encabezado (ver la nota de arriba). */ ?>

<table class="cp-leyenda">
    <tr>
        <?php foreach (\Model\Evento::TIPO_COLOR as $t => $c):
            // La leyenda enseña el icono por defecto del tipo, que es el que llevan los
            // eventos que no eligieron uno propio.
            $gl = $hayIconos ? \Model\Evento::glifo(\Model\Evento::TIPO_ICONO[$t] ?? '') : '';
        ?>
        <td class="cp-leyenda__item">
            <?php if ($gl !== ''): ?><span class="cp-leyenda__ico" style="color:<?= s($c) ?>"><?= $gl ?></span>
            <?php else: ?><span class="cp-leyenda__punto" style="background:<?= s($c) ?>"></span>
            <?php endif; ?>
            <?= s(\Model\Evento::TIPO_LABEL_PUBLICO[$t] ?? $t) ?>
        </td>
        <?php endforeach; ?>
    </tr>
</table>

<?php
// ── Detalle mes a mes ──
// La rejilla dice CUÁNDO; esta parte dice QUÉ. En un cuadro de mes no cabe el
// título de un evento, así que sin esta sección el calendario impreso sería un
// mosaico de colores sin explicar. Empieza en página nueva para que la portada
// quede limpia.
?>
<div class="cp-detalle">
    <h2 class="cp-detalle__tit">Eventos del ciclo</h2>

    <?php foreach ($mesesCiclo as $info):
        $y = (int)$info['anio']; $m = (int)$info['mes'];
        $ini = sprintf('%04d-%02d-01', $y, $m);
        $fin = date('Y-m-t', mktime(0, 0, 0, $m, 1, $y));
        // Solapamiento, no fecha de inicio: un evento a caballo entre dos meses
        // aparece en los dos, cada uno con su tramo.
        $delMes = array_values(array_filter($eventos, static function (array $e) use ($ini, $fin) {
            return $e['fecha'] <= $fin && ($e['fecha_fin'] ?: $e['fecha']) >= $ini;
        }));
        if (!$delMes) continue;
    ?>
    <table class="cp-mesdet">
        <tr>
            <td class="cp-mesdet__nombre"><?= $MESES[$m] ?> <span><?= $y ?></span></td>
            <td class="cp-mesdet__cuerpo">
                <?php foreach ($delMes as $ev):
                    $dIni = max(strtotime($ev['fecha']), strtotime($ini));
                    $dFin = min(strtotime($ev['fecha_fin'] ?: $ev['fecha']), strtotime($fin));
                    $dia  = (date('d', $dIni) === date('d', $dFin))
                        ? date('d', $dIni)
                        : date('d', $dIni) . '–' . date('d', $dFin);
                ?>
                <table class="cp-ev">
                    <tr>
                        <td class="cp-ev__dia" style="background:<?= s($ev['color']) ?><?= $claro($ev['color']) ? ';color:#16202e' : '' ?>"><?= $dia ?></td>
                        <?php
                        // El icono del evento, el mismo que ve la web. Se escribe como
                        // carácter con la familia FontAwesome aplicada: Dompdf no
                        // entiende `::before`, así que la clase CSS no le sirve de nada.
                        // Sin el TTF en disco no hay columna, y la fila se cierra sola.
                        $glifo = $hayIconos ? \Model\Evento::glifo($ev['icono']) : '';
                        if ($glifo !== ''):
                        ?>
                        <td class="cp-ev__ico" style="color:<?= s($ev['color']) ?>"><?= $glifo ?></td>
                        <?php endif; ?>
                        <td class="cp-ev__txt">
                            <span class="cp-ev__tit"><?= s($ev['titulo']) ?></span>
                            <span class="cp-ev__meta">
                                <?= s($ev['etiqueta']) ?>
                                <?php if (!empty($ev['niveles'])): ?> · <?= s(implode(' · ', $ev['niveles'])) ?>
                                <?php else: ?> · Todo el colegio<?php endif; ?>
                            </span>
                            <?php if (!empty($ev['desc'])): ?>
                            <span class="cp-ev__desc"><?= s($ev['desc']) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <?php endforeach; ?>
            </td>
        </tr>
    </table>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<p class="cp-pie">Colegio Bilbao · Calendario sujeto a cambios. Consulta la versión actualizada en el sitio del colegio.</p>

</body>
</html>
