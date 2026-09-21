<?php
/**
 * Microsoft Clarity — mapas de calor y grabación de sesión.
 *
 * Fuente ÚNICA del tag: lo incluyen `views/layout.php` (sitio público) y
 * `views/layout-admin.php` (intranet), que son los dos únicos documentos HTML
 * que sirve la aplicación. Cualquier otro layout nuevo debe incluir este
 * partial en vez de copiar el snippet.
 *
 * ⚠️ Es una de las excepciones justificadas a «nada de <script> embebido en las
 * vistas» (ver CLAUDE.md § Assets): el loader debe correr en el <head>, antes
 * del primer pintado, y `bundle.min.js` / `admin.min.js` van con `defer` al
 * final del <body> — desde ahí Clarity perdería el inicio de la sesión.
 *
 * ⚠️ NO se carga en local: sin este portazo, cada `php -S localhost:3000`
 * mandaría sesiones de desarrollo al panel de Clarity y ensuciaría las métricas
 * reales. Para probarlo en local, comentar el `return`.
 */

$_clarity_host = strtolower(strtok((string) ($_SERVER['HTTP_HOST'] ?? ''), ':'));

if ($_clarity_host === 'localhost'
    || $_clarity_host === '127.0.0.1'
    || $_clarity_host === '::1'
    || $_clarity_host === ''
) {
    return;
}
?>
<!-- Microsoft Clarity -->
<script type="text/javascript">
    (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "y8pmhw7og6");
</script>
