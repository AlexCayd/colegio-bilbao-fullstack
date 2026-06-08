<?php

/**
 * Genera un elemento <picture> con fuentes WebP y AVIF cuando estén disponibles.
 *
 * @param string $src     Ruta pública de la imagen original (ej. /build/assets/img/foto.jpg)
 * @param string $alt     Texto alternativo
 * @param string $class   Clases CSS del <img>
 * @param string $loading lazy | eager
 * @param array  $attrs   Atributos extra del <img> como ['decoding'=>'async']
 */
function picture(string $src, string $alt, string $class = '', string $loading = 'lazy', array $attrs = []): string
{
    $docRoot = defined('PUBLIC_PATH') ? PUBLIC_PATH : dirname(__DIR__) . '/public';

    $ext  = pathinfo($src, PATHINFO_EXTENSION);
    $base = substr($src, 0, -(strlen($ext) + 1));

    $avif = $base . '.avif';
    $webp = $base . '.webp';

    $extraAttrs = '';
    foreach ($attrs as $k => $v) {
        $extraAttrs .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
    }

    $html = '<picture>';

    if (file_exists($docRoot . $avif)) {
        $html .= '<source type="image/avif" srcset="' . htmlspecialchars($avif) . '">';
    }
    if (file_exists($docRoot . $webp)) {
        $html .= '<source type="image/webp" srcset="' . htmlspecialchars($webp) . '">';
    }

    $html .= '<img src="' . htmlspecialchars($src) . '"'
           . ' alt="' . htmlspecialchars($alt) . '"'
           . ($class ? ' class="' . htmlspecialchars($class) . '"' : '')
           . ' loading="' . $loading . '"'
           . $extraAttrs
           . '>';
    $html .= '</picture>';

    return $html;
}
