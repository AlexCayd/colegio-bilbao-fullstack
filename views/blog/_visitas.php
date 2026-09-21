<?php
/**
 * Gráfica de visitas al sitio público. Solo la incluye el home cuando quien mira es
 * administrador (el controlador manda `$visitas = null` al resto, y con él tampoco
 * se carga Chart.js).
 *
 * Las CUATRO series viajan ya calculadas en la isla JSON: son cuatro COUNT baratos sobre
 * una tabla indexada por fecha, y así cambiar de rango no va al servidor — que para un
 * botón que se pulsa cuatro veces seguidas sería un endpoint JSON nuevo a cambio de nada.
 *
 * @var array $visitas     ['7'=>serie, '30'=>…, '60'=>…, '0'=>…]
 * @var array $visitasTop  [['ruta','total'], …] de los últimos 30 días
 */
$rangos = \Model\Visita::RANGOS;
?>
<section class="vis" data-visitas>
    <div class="vis__head">
        <div>
            <p class="mh-section-label"><i class="fa-solid fa-chart-line"></i> Visitas al sitio público</p>
            <p class="vis__sub">
                Páginas vistas en <a href="/" target="_blank" rel="noopener">colegiobilbao</a>,
                sin contar el panel ni los robots.
            </p>
        </div>

        <?php /* Los rangos son botones y no un <select>: son cuatro, se comparan entre sí
                 y se pulsan seguidos. Un desplegable esconde tres de las cuatro opciones
                 justo cuando la gracia es saltar de una a otra. */ ?>
        <div class="vis__rangos" role="group" aria-label="Periodo">
            <?php foreach ($rangos as $d => $label): ?>
            <button type="button" class="vis__rango<?= (int)$d === 30 ? ' is-on' : '' ?>"
                    data-vis-rango="<?= (int)$d ?>" <?= (int)$d === 30 ? 'aria-pressed="true"' : 'aria-pressed="false"' ?>>
                <?= s($label) ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="vis__cifras">
        <div class="vis__cifra">
            <span class="vis__n" data-vis-total>—</span>
            <span class="vis__l">visitas en el periodo</span>
        </div>
        <div class="vis__cifra">
            <span class="vis__n" data-vis-media>—</span>
            <span class="vis__l">media por día</span>
        </div>
    </div>

    <div class="vis__chart">
        <canvas id="visChart" aria-label="Visitas por día" role="img"></canvas>
        <?php /* Sin Chart.js (CDN caído, bloqueador) el canvas se queda vacío y sin este
                 texto la tarjeta parecería rota. Las cifras de arriba las escribe el JS
                 propio, así que siguen apareciendo aunque falte la librería. */ ?>
        <p class="vis__sinchart" data-vis-sinchart hidden>
            No se pudo cargar la gráfica, pero las cifras de arriba son correctas.
        </p>
    </div>

    <?php if ($visitasTop): ?>
    <div class="vis__top">
        <p class="vis__top-t">Más visitadas · últimos 30 días</p>
        <ol class="vis__top-list">
            <?php foreach ($visitasTop as $t): ?>
            <li>
                <span class="vis__top-ruta" title="<?= s($t['ruta']) ?>"><?= s($t['ruta']) ?></span>
                <span class="vis__top-n"><?= number_format((int)$t['total'], 0, ',', '.') ?></span>
            </li>
            <?php endforeach; ?>
        </ol>
    </div>
    <?php endif; ?>

    <script type="application/json" id="visData"><?= json_encode($visitas, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</section>
