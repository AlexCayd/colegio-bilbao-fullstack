<?php

namespace MVC;

/**
 * Router y sistema de plantillas de la aplicación.
 *
 * Enrutador propio, sin dependencias. Mantiene tres tablas de rutas —GET exactas,
 * POST exactas y patrones GET— y despacha la petición al controlador que case.
 * Es también el motor de plantillas: los tres métodos `render*()` inyectan los
 * datos en la vista y la envuelven en su layout.
 *
 * Las rutas se declaran en index.php; la última línea de ese archivo dispara
 * comprobarRutas().
 *
 * ⚠️ Los patrones con {param} son EXCLUSIVOS de GET: pattern() solo alimenta
 * $getPatterns. Por eso todos los POST del panel reciben el identificador en el
 * cuerpo del formulario y no en la URI.
 *
 * @package MVC
 */
class Router
{
    /**
     * Rutas GET exactas, indexadas por URI.
     *
     * Al ser un mapa por clave, declarar dos veces la misma URI pisa la anterior
     * en silencio: el orden de declaración no desempata.
     *
     * @var array<string, callable|array{0:class-string, 1:string}>
     */
    public array $getRoutes = [];

    /**
     * Rutas POST exactas, indexadas por URI.
     *
     * @var array<string, callable|array{0:class-string, 1:string}>
     */
    public array $postRoutes = [];

    /**
     * Parámetros capturados de la URI por un patrón, disponibles para el controlador.
     *
     * Ejemplo: con el patrón '/blog/{slug}' y la URI '/blog/mi-articulo',
     * queda ['slug' => 'mi-articulo'].
     *
     * @var array<string, string>
     */
    public array $params = [];

    /**
     * Rutas GET con parámetros, en orden de declaración.
     *
     * A diferencia de $getRoutes esto sí es una lista: se recorre en orden y gana
     * el primer patrón que case, así que declarar el más genérico antes que el más
     * específico lo hace inalcanzable.
     *
     * @var array<int, array{url:string, fn:callable|array{0:class-string, 1:string}}>
     */
    private array $getPatterns = [];

    /**
     * Registra una ruta GET exacta.
     *
     * @param string                                     $url URI absoluta, sin barra final (ej. '/dashboard/suplencias').
     * @param callable|array{0:class-string, 1:string}   $fn  Controlador: [Clase::class, 'metodo'] o closure.
     * @return void
     */
    public function get($url, $fn)
    {
        $this->getRoutes[$url] = $fn;
    }

    /**
     * Registra una ruta POST exacta.
     *
     * @param string                                     $url URI absoluta, sin barra final.
     * @param callable|array{0:class-string, 1:string}   $fn  Controlador que procesa el envío.
     * @return void
     */
    public function post($url, $fn)
    {
        $this->postRoutes[$url] = $fn;
    }

    /**
     * Registra una ruta GET con parámetros en la URI.
     *
     * Cada `{nombre}` se traduce a un grupo con nombre `(?P<nombre>[^/]+)`, así que
     * un parámetro NO puede contener barras: '/blog/{slug}' no casa con
     * '/blog/2026/titulo'.
     *
     * @param string                                     $url Patrón con marcadores (ej. '/noticias/{slug}').
     * @param callable|array{0:class-string, 1:string}   $fn  Controlador. Lee los valores en $router->params.
     * @return void
     */
    public function pattern($url, $fn)
    {
        $this->getPatterns[] = ['url' => $url, 'fn' => $fn];
    }

    /**
     * Resuelve la petición actual y ejecuta el controlador que le corresponda.
     *
     * Orden de resolución:
     *   1. Se extrae la ruta de REQUEST_URI (la query string nunca participa).
     *   2. Se normaliza la barra final, excepto en la raíz.
     *   3. GET: coincidencia exacta y, si no la hay, el primer patrón que case.
     *      POST: solo coincidencia exacta.
     *   4. Sin coincidencia: 404 + views/errors/404.php.
     *
     * @return void
     */
    public function comprobarRutas()
    {

        $url_actual = parse_url(
            $_SERVER['REQUEST_URI'] ?? '/',
            PHP_URL_PATH
        );
        // Normalizar trailing slash (excepto la raíz "/")
        if ($url_actual !== '/') {
            $url_actual = rtrim($url_actual, '/');
        }
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            $fn = $this->getRoutes[$url_actual] ?? null;
            if (!$fn) {
                foreach ($this->getPatterns as $route) {
                    $regex = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $route['url']);
                    if (preg_match('#^' . $regex . '$#', $url_actual, $matches)) {
                        $fn = $route['fn'];
                        // preg_match devuelve los grupos con nombre Y su índice numérico;
                        // el filtro por clave de tipo string se queda solo con los nombrados.
                        $this->params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                        break;
                    }
                }
            }
        } else {
            $fn = $this->postRoutes[$url_actual] ?? null;
        }

        if ( $fn ) {
            call_user_func($fn, $this);
        } else {
            http_response_code(404);
            $this->render('errors/404');
        }
    }

    /**
     * Renderiza una vista dentro del layout del SITIO PÚBLICO (views/layout.php).
     *
     * Cada clave de $datos se convierte en una variable local de la plantilla, así que
     * ['titulo' => 'Inicio'] se lee como $titulo dentro de la vista. La vista se captura
     * en un buffer y el layout la imprime desde $contenido.
     *
     * Variables que el layout interpreta de forma especial: $seo_titulo, $seo_descripcion,
     * $seo_imagen (meta y Open Graph), $extra_head (HTML crudo en el <head>) y
     * $paginaVista (se emite como <body data-page="...">, base del aislamiento de CSS y JS).
     *
     * @param string              $view  Ruta de la vista relativa a views/, sin extensión.
     * @param array<string,mixed> $datos Variables que se exponen a la plantilla.
     * @return void
     */
    public function render($view, $datos = [])
    {
        foreach ($datos as $key => $value) {
            $$key = $value;
        }

        ob_start();

        include_once __DIR__ . "/views/$view.php";

        $contenido = ob_get_clean(); // Limpia el Buffer

        include_once __DIR__ . '/views/layout.php';
    }

    /**
     * Renderiza una vista dentro del layout del PANEL (views/layout-admin.php).
     *
     * Idéntico a render() salvo el layout. El de administración añade el menú lateral,
     * la barra superior con campana y avatar, y el guard anti-salto del sidebar.
     *
     * @param string              $view  Ruta de la vista relativa a views/, sin extensión.
     * @param array<string,mixed> $datos Variables que se exponen a la plantilla.
     * @return void
     */
    public function renderAdmin($view, $datos = [])
    {
        foreach ($datos as $key => $value) {
            $$key = $value;
        }

        ob_start();

        include_once __DIR__ . "/views/$view.php";

        $contenido = ob_get_clean();

        include_once __DIR__ . '/views/layout-admin.php';
    }

    /**
     * Incluye una vista SIN envolverla en ningún layout.
     *
     * A diferencia de los otros dos no usa buffer: la vista se imprime directamente.
     * Es para plantillas que emiten su propio documento HTML completo.
     *
     * @param string              $view  Ruta de la vista relativa a views/, sin extensión.
     * @param array<string,mixed> $datos Variables que se exponen a la plantilla.
     * @return void
     */
    public function renderBlog($view, $datos = [])
    {
        foreach ($datos as $key => $value) {
            $$key = $value;
        }

        include_once __DIR__ . "/views/$view.php";
    }
}
