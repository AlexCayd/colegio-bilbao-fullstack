<?php

function debuguear($variable) : string {
    echo "<pre>";
    var_dump($variable);
    echo "</pre>";
    exit;
}
function s($html) : string {
    return htmlspecialchars($html ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Etiqueta <script> de Three.js (fondos 3D del sitio y del login).
 * Centralizado: la URL estaba repetida en cuatro sitios de dos controladores,
 * así que actualizar la versión obligaba a acordarse de todos.
 */
function three_js_tag(): string {
    return '<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>';
}

/** Nombres en español. `strftime` está deprecado e `IntlDateFormatter` no siempre está. */
const MESES_ES = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
const DIAS_ES  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];

/**
 * Fecha escrita a mano, no en formato numérico: "martes 4 de agosto".
 * Un `04/08/2026` en un aviso obliga a traducirlo mentalmente; el día de la
 * semana es justo el dato que un profesor necesita para ubicar una clase.
 *
 * @param string $fecha  Y-m-d
 * @param bool   $conAnio  añade " de 2026" (útil fuera del curso actual)
 */
function fecha_larga(?string $fecha, bool $conAnio = false): string {
    if (!$fecha) return '';
    $ts = strtotime($fecha);
    if ($ts === false) return '';
    $txt = DIAS_ES[(int)date('w', $ts)] . ' ' . (int)date('j', $ts)
         . ' de ' . MESES_ES[(int)date('n', $ts) - 1];
    return $conAnio ? $txt . ' de ' . date('Y', $ts) : $txt;
}

/**
 * Inicial del día laborable para las tiras L·M·X·J·V: 1=L … 5=V, 0 en fin de semana.
 * Se usa X para el miércoles (M ya es martes), como en todos los horarios escolares.
 */
function dia_habil_indice(?string $fecha): int {
    if (!$fecha) return 0;
    $ts = strtotime($fecha);
    if ($ts === false) return 0;
    $n = (int)date('N', $ts);          // 1=lunes … 7=domingo
    return $n >= 1 && $n <= 5 ? $n : 0;
}
