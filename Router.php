<?php

namespace MVC;

class Router
{
    public array $getRoutes = [];
    public array $postRoutes = [];
    public array $params = [];
    private array $getPatterns = [];

    public function get($url, $fn)
    {
        $this->getRoutes[$url] = $fn;
    }

    public function post($url, $fn)
    {
        $this->postRoutes[$url] = $fn;
    }

    public function pattern($url, $fn)
    {
        $this->getPatterns[] = ['url' => $url, 'fn' => $fn];
    }

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

    public function renderBlog($view, $datos = [])
    {
        foreach ($datos as $key => $value) {
            $$key = $value;
        }

        include_once __DIR__ . "/views/$view.php";
    }
}
