<?php
/**
 * Rejilla semanal de horario (solo lectura). La comparten `index.php` (por profesor/aula/grupo)
 * y `mi-horario.php`. Espera:
 *   @var \Model\Periodo[] $periodos
 *   @var array            $matriz    [dia][periodo_id] => \Model\Horario
 *   @var string           $vista     'profesor' | 'aula' | 'grupo'  (qué dato secundario se pinta)
 */
$dias   = \Model\Horario::DIAS;
$diasLg = \Model\Horario::DIAS_LABEL;

// Paleta institucional para colorear por materia. El mismo crc32 lo replica
// src/js/admin/admin-supl-week.js para que una materia tenga siempre el mismo color.
$matColores = ['#4285f4', '#46bdc6', '#8ac926', '#f5b400', '#fc6722', '#aa2296', '#4267ac', '#34a853', '#ea075a', '#e51022'];
// Tonos claros: sobre ellos el texto blanco no contrasta, se usa tinta oscura
$matClaros  = ['#f5b400', '#8ac926', '#46bdc6'];

if (!function_exists('colorMateria')) {
    function colorMateria(?string $m, array $pal): string {
        if (!$m) return '#94a3b8';
        return $pal[abs(crc32($m)) % count($pal)];
    }
}
// Qué "otra" dimensión mostrar en cada celda según la vista
if (!function_exists('celdaSecundaria')) {
    function celdaSecundaria(string $vista, \Model\Horario $h): string {
        return match ($vista) {
            'aula'  => trim(($h->profesor_nombre ?? '') . ' · ' . ($h->grupo_nombre ?? ''), ' ·'),
            'grupo' => trim(($h->profesor_nombre ?? '') . ' · ' . ($h->aula_nombre ?? ''), ' ·'),
            default => trim(($h->grupo_nombre ?? '') . ' · ' . ($h->aula_nombre ?? ''), ' ·'),
        };
    }
}
$vista = $vista ?? 'profesor';
?>
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
        <?php foreach ($periodos as $p): $pid = (int)$p->id; ?>
            <tr class="<?= (int)$p->es_receso === 1 ? 'hor-grid__receso' : '' ?>">
                <th class="hor-grid__hour">
                    <span class="hor-grid__hour-label"><?= s($p->etiqueta) ?></span>
                    <span class="hor-grid__hour-time"><?= substr($p->hora_inicio, 0, 5) ?>–<?= substr($p->hora_fin, 0, 5) ?></span>
                </th>
                <?php foreach ($dias as $d): ?>
                    <?php if ((int)$p->es_receso === 1): ?>
                        <td class="hor-grid__receso-cell"><i class="fa-solid fa-mug-hot"></i></td>
                    <?php elseif (isset($matriz[$d][$pid])): $h = $matriz[$d][$pid]; $col = colorMateria($h->materia, $matColores); ?>
                        <td>
                            <div class="hor-cell<?= in_array($col, $matClaros, true) ? ' hor-cell--dark' : '' ?>" style="--c:<?= $col ?>;" title="<?= s($h->materia ?: 'Clase') ?>">
                                <span class="hor-cell__mat"><?= s($h->materia ?: 'Clase') ?></span>
                                <span class="hor-cell__sec"><?= s(celdaSecundaria($vista, $h)) ?></span>
                            </div>
                        </td>
                    <?php else: ?>
                        <td class="hor-grid__free"><span>Libre</span></td>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
