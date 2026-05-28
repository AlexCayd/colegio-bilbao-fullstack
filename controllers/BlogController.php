<?php

namespace Controllers;

use MVC\Router;
use Model\UsuarioBlog;
use Model\Categoria;
use Model\Articulo;
use Model\Noticia;
use Model\CategoriaNoticia;

class BlogController {

    // ── PÚBLICO ────────────────────────────────────────────────────────────────

    public static function blogPublico(Router $router) {
        Articulo::publicarProgramados();
        $articulos  = Articulo::allConDetalles('publicado');
        $categorias = Categoria::allConArticulosPublicados();

        $router->renderBlog('blog/index', [
            'titulo'     => 'Voces Bilbao | Artículos del Colegio Bilbao',
            'articulos'  => $articulos,
            'categorias' => $categorias,
        ]);
    }

    public static function verArticulo(Router $router) {
        Articulo::publicarProgramados();
        $slug = $router->params['slug'] ?? '';
        if (!$slug) {
            header('Location: /blog');
            exit;
        }

        $articulo = Articulo::findConDetallesBySlug($slug);
        if (!$articulo || $articulo->estado !== 'publicado') {
            header('Location: /blog');
            exit;
        }

        $tags         = $articulo->obtenerTags();
        $recomendados = Articulo::recomendados(
            (int) $articulo->id,
            $articulo->categoria_id ? (int) $articulo->categoria_id : null
        );

        $router->renderBlog('blog/articulo', [
            'titulo'       => s($articulo->titulo) . ' | Voces Bilbao',
            'articulo'     => $articulo,
            'tags'         => $tags,
            'recomendados' => $recomendados,
        ]);
    }

    // ── AUTH GUARDS ────────────────────────────────────────────────────────────

    private static function requireAuth(): array {
        if (empty($_SESSION['blog_usuario'])) {
            header('Location: /');
            exit;
        }
        return $_SESSION['blog_usuario'];
    }

    private static function requireAdmin(): void {
        if (($_SESSION['blog_usuario']['rol'] ?? '') !== 'administrador') {
            header('Location: /dashboard');
            exit;
        }
    }

    // ── ADMIN ──────────────────────────────────────────────────────────────────

    public static function login(Router $router) {
        if (!empty($_SESSION['blog_usuario'])) {
            header('Location: /dashboard');
            exit;
        }

        $alertas    = [];
        $errorCampo = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!$email || !$password) {
                if (!$email) $errorCampo = 'email';
                if (!$password) $errorCampo = $errorCampo ?? 'password';
                UsuarioBlog::setAlerta('error', 'Completa todos los campos antes de continuar.');
                $alertas = UsuarioBlog::getAlertas();
            } else {
                $usuario = UsuarioBlog::findByEmail($email);

                if ($usuario && password_verify($password, $usuario->password)) {
                    UsuarioBlog::registrarAcceso($usuario->id);
                    $_SESSION['blog_usuario'] = [
                        'id'     => $usuario->id,
                        'nombre' => $usuario->nombre,
                        'rol'    => $usuario->rol,
                        'avatar' => $usuario->avatar ?? '',
                    ];
                    header('Location: /dashboard');
                    exit;
                }

                if (!$usuario) {
                    $errorCampo = 'email';
                    UsuarioBlog::setAlerta('error', 'No encontramos ninguna cuenta con ese correo. ¿Está bien escrito?');
                } else {
                    $errorCampo = 'password';
                    UsuarioBlog::setAlerta('error', 'Contraseña incorrecta. Verifica que el bloqueo de mayúsculas esté desactivado.');
                }
                $alertas = UsuarioBlog::getAlertas();
            }
        }

        $router->renderAdmin('blog/login', ['titulo' => 'Iniciar Sesión - Blog', 'alertas' => $alertas, 'errorCampo' => $errorCampo]);
    }

    public static function logout(Router $router) {
        unset($_SESSION['blog_usuario']);
        header('Location: /blog/login');
        exit;
    }

    public static function dashboard(Router $router) {
        self::requireAuth();
        // Artículos
        $publicados  = Articulo::contarPorEstado('publicado');
        $borradores  = Articulo::contarPorEstado('borrador');
        $programados = Articulo::contarPorEstado('programado');
        $totalCats   = Categoria::contarTotal();
        $totalUsers  = UsuarioBlog::contarTotal();
        $recientes   = Articulo::recentesConDetalles(6);
        $porMes      = Articulo::articulosPorMes(6);
        $porCat      = Categoria::articulosPorCategoria();
        // Noticias
        $nPub        = Noticia::contarPorEstado('publicado');
        $nBor        = Noticia::contarPorEstado('borrador');
        $nProg       = Noticia::contarPorEstado('programado');
        $nTotalCats  = CategoriaNoticia::contarTotal();
        $nRecientes  = Noticia::recentesConDetalles(6);
        $nPorMes     = Noticia::noticiasPorMes(6);
        $nPorCat     = CategoriaNoticia::noticiasPorCategoria();

        $router->renderAdmin('blog/dashboard', [
            'titulo'      => 'Dashboard',
            'publicados'  => $publicados,
            'borradores'  => $borradores,
            'programados' => $programados,
            'totalCats'   => $totalCats,
            'totalUsers'  => $totalUsers,
            'recientes'   => $recientes,
            'porMes'      => $porMes,
            'porCat'      => $porCat,
            // noticias
            'nPub'        => $nPub,
            'nBor'        => $nBor,
            'nProg'       => $nProg,
            'nTotalCats'  => $nTotalCats,
            'nRecientes'  => $nRecientes,
            'nPorMes'     => $nPorMes,
            'nPorCat'     => $nPorCat,
        ]);
    }

    // ── ARTÍCULOS ──────────────────────────────────────────────────────────────

    public static function articulos(Router $router) {
        self::requireAuth();
        $estadosValidos = ['publicado', 'borrador', 'programado'];
        $estado    = in_array($_GET['estado'] ?? '', $estadosValidos, true) ? $_GET['estado'] : '';
        $articulos = Articulo::allConDetalles($estado);
        $success   = isset($_GET['success']);
        $edited    = isset($_GET['edited']);

        $router->renderAdmin('blog/articulos/index', [
            'titulo'    => 'Artículos',
            'articulos' => $articulos,
            'success'   => $success,
            'edited'    => $edited,
            'estado'    => $estado,
        ]);
    }

    public static function crearArticulo(Router $router) {
        self::requireAuth();
        $articulo             = new Articulo();
        $articulo->autor_id   = $_SESSION['blog_usuario']['id'] ?? null;
        $categorias = Categoria::all();
        $usuarios   = UsuarioBlog::all();
        $alertas    = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $articulo->sincronizar($_POST);

            // Auto-generar slug si viene vacío
            if (!trim($articulo->slug ?? '')) {
                $articulo->slug = self::generarSlug($articulo->titulo ?? '');
            }

            // Limpiar fecha cuando no aplica
            if (!trim($articulo->fecha_publicacion ?? '')) {
                $articulo->fecha_publicacion = null;
            }

            // Limpiar tiempo_lectura si es 0 o vacío
            $articulo->tiempo_lectura = (int) ($articulo->tiempo_lectura ?? 0) ?: null;

            // Limpiar IDs opcionales
            if (!(int) ($articulo->categoria_id ?? 0)) $articulo->categoria_id = null;
            if (!(int) ($articulo->autor_id     ?? 0)) $articulo->autor_id     = null;

            $alertas = $articulo->validar();

            if (empty($alertas['error'])) {
                if ($articulo->existeSlug()) {
                    Articulo::setAlerta('error', 'Ya existe un artículo con ese slug. Cambia el título o edita el slug manualmente.');
                    $alertas = Articulo::getAlertas();
                } else {
                    // Imagen de portada
                    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                        $alertas = self::subirImagen($_FILES['imagen'], $articulo);
                    }

                    if (empty($alertas['error'])) {
                        $resultado = $articulo->guardar();
                        if ($resultado['resultado']) {
                            $articulo->id = $resultado['id'];
                            $articulo->guardarTags(trim($_POST['tags'] ?? ''));
                            header('Location: /blog/articulos?success=1');
                            exit;
                        }
                        Articulo::setAlerta('error', 'Error al guardar el artículo. Intenta de nuevo.');
                        $alertas = Articulo::getAlertas();
                    }
                }
            }
        }

        $router->renderAdmin('blog/articulos/crear', [
            'titulo'     => 'Nuevo Artículo',
            'articulo'   => $articulo,
            'categorias' => $categorias,
            'usuarios'   => $usuarios,
            'alertas'    => $alertas,
        ]);
    }

    public static function editarArticulo(Router $router) {
        self::requireAuth();
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$id) {
            header('Location: /blog/articulos');
            exit;
        }

        $articulo = Articulo::findConDetalles($id);
        if (!$articulo) {
            header('Location: /blog/articulos');
            exit;
        }

        $categorias  = Categoria::all();
        $usuarios    = UsuarioBlog::all();
        $tagsActuales = $articulo->obtenerTags();
        $alertas     = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $slugOriginal = $articulo->slug;
            $articulo->sincronizar($_POST);

            if (!trim($articulo->slug ?? '')) {
                $articulo->slug = self::generarSlug($articulo->titulo ?? '');
            }

            // El estado viene siempre del select del formulario (permite pasar a borrador)

            // Limpiar NULLables
            if (!trim($articulo->fecha_publicacion ?? '')) {
                $articulo->fecha_publicacion = null;
            }
            $articulo->tiempo_lectura = (int) ($articulo->tiempo_lectura ?? 0) ?: null;
            if (!(int) ($articulo->categoria_id ?? 0)) $articulo->categoria_id = null;
            if (!(int) ($articulo->autor_id     ?? 0)) $articulo->autor_id     = null;

            $alertas = $articulo->validar();

            if (empty($alertas['error'])) {
                // Solo revisar slug duplicado si cambió
                if ($articulo->slug !== $slugOriginal && $articulo->existeSlug()) {
                    Articulo::setAlerta('error', 'Ya existe un artículo con ese slug. Edítalo manualmente.');
                    $alertas = Articulo::getAlertas();
                } else {
                    // Nueva imagen de portada
                    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                        $alertas = self::subirImagen($_FILES['imagen'], $articulo);
                    }

                    if (empty($alertas['error'])) {
                        $articulo->guardar();
                        $articulo->guardarTags(trim($_POST['tags'] ?? ''));
                        $tagsActuales = $articulo->obtenerTags();
                        header('Location: /blog/articulos?edited=1');
                        exit;
                    }
                }
            }
        }

        $router->renderAdmin('blog/articulos/editar', [
            'titulo'       => 'Editar Artículo',
            'articulo'     => $articulo,
            'categorias'   => $categorias,
            'usuarios'     => $usuarios,
            'tagsActuales' => $tagsActuales,
            'alertas'      => $alertas,
        ]);
    }

    public static function eliminarArticulo(Router $router) {
        self::requireAuth();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $articulo = Articulo::find($id);
            if ($articulo) {
                // Eliminar imagen física si existe
                if ($articulo->imagen) {
                    $ruta = __DIR__ . '/../public' . $articulo->imagen;
                    if (file_exists($ruta)) @unlink($ruta);
                }
                $articulo->eliminar();
            }
        }
        header('Location: /blog/articulos?deleted=1');
        exit;
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    private static function generarSlug(string $texto): string {
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9\s-]/', '', $texto);
        $texto = preg_replace('/[\s-]+/', '-', trim($texto));
        return trim($texto, '-');
    }

    private static function subirImagen(array $file, Articulo $articulo): array {
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $maxSize = 5 * 1024 * 1024;

        if (!\in_array($ext, $allowed)) {
            Articulo::setAlerta('error', 'Formato de imagen no permitido. Usa JPG, PNG o WebP.');
            return Articulo::getAlertas();
        }

        if ($file['size'] > $maxSize) {
            Articulo::setAlerta('error', 'La imagen supera el límite de 5 MB.');
            return Articulo::getAlertas();
        }

        $dir = realpath(__DIR__ . '/../public') . DIRECTORY_SEPARATOR
             . 'build' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'blog' . DIRECTORY_SEPARATOR;

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            Articulo::setAlerta('error', 'No se pudo crear el directorio de imágenes en el servidor.');
            return Articulo::getAlertas();
        }

        $filename = uniqid('art_', true) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            Articulo::setAlerta('error', 'Error al guardar la imagen. Verifica los permisos del servidor.');
            return Articulo::getAlertas();
        }

        $articulo->imagen = '/build/assets/blog/' . $filename;
        return [];
    }

    // ── USUARIOS ───────────────────────────────────────────────────────────────

    public static function usuarios(Router $router) {
        self::requireAuth();
        $usuarios = UsuarioBlog::allConArticulos();
        $success  = isset($_GET['success']);

        $router->renderAdmin('blog/usuarios/index', [
            'titulo'   => 'Usuarios',
            'usuarios' => $usuarios,
            'success'  => $success,
        ]);
    }

    public static function crearUsuario(Router $router) {
        self::requireAuth();
        self::requireAdmin();
        $usuario = new UsuarioBlog();
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validar();

            if (empty($alertas['error'])) {

                // Email duplicado
                if ($usuario->existeEmail()) {
                    UsuarioBlog::setAlerta('error', 'Ya existe un usuario con ese correo electrónico');
                    $alertas = UsuarioBlog::getAlertas();
                } else {

                    // Avatar
                    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                        $ext     = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                        $maxSize = 2 * 1024 * 1024;

                        if (\in_array($ext, $allowed) && $_FILES['avatar']['size'] <= $maxSize) {
                            $dir = __DIR__ . '/../public/build/assets/usuarios/';
                            if (!is_dir($dir)) mkdir($dir, 0755, true);

                            $filename = uniqid('u_', true) . '.' . $ext;
                            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $filename)) {
                                $usuario->avatar = '/build/assets/usuarios/' . $filename;
                            }
                        } else {
                            UsuarioBlog::setAlerta('error', 'La imagen debe ser JPG, PNG o WebP y pesar menos de 2 MB');
                            $alertas = UsuarioBlog::getAlertas();
                        }
                    }

                    if (empty($alertas['error'])) {
                        $usuario->hashPassword();
                        $resultado = $usuario->guardar();

                        if ($resultado['resultado']) {
                            header('Location: /blog/usuarios?success=1');
                            exit;
                        }
                        UsuarioBlog::setAlerta('error', 'Hubo un problema al guardar el usuario. Intenta de nuevo.');
                        $alertas = UsuarioBlog::getAlertas();
                    }
                }
            }
        }

        $router->renderAdmin('blog/usuarios/crear', [
            'titulo'  => 'Nuevo Usuario',
            'usuario' => $usuario,
            'alertas' => $alertas,
        ]);
    }

    public static function editarUsuario(Router $router) {
        self::requireAuth();
        self::requireAdmin();
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$id) {
            header('Location: /blog/usuarios');
            exit;
        }

        $usuario = UsuarioBlog::findConArticulos($id);
        if (!$usuario) {
            header('Location: /blog/usuarios');
            exit;
        }

        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $passwordOriginal = $usuario->password;

            $usuario->sincronizar($_POST);

            $alertas = $usuario->validarEdicion();

            if (empty($alertas['error'])) {

                if ($usuario->existeEmail()) {
                    UsuarioBlog::setAlerta('error', 'Ya existe otro usuario con ese correo electrónico');
                    $alertas = UsuarioBlog::getAlertas();
                } else {
                    $nuevaPassword = trim($_POST['password'] ?? '');

                    if ($nuevaPassword !== '') {
                        if (strlen($nuevaPassword) < 8) {
                            UsuarioBlog::setAlerta('error', 'La contraseña debe tener mínimo 8 caracteres');
                        } elseif (!preg_match('/[A-Z]/', $nuevaPassword)) {
                            UsuarioBlog::setAlerta('error', 'La contraseña debe incluir al menos una mayúscula');
                        } elseif (!preg_match('/[0-9]/', $nuevaPassword)) {
                            UsuarioBlog::setAlerta('error', 'La contraseña debe incluir al menos un número');
                        } else {
                            $usuario->password = password_hash($nuevaPassword, PASSWORD_BCRYPT);
                        }
                        $alertas = UsuarioBlog::getAlertas();
                    } else {
                        $usuario->password = $passwordOriginal;
                    }

                    if (empty($alertas['error'])) {
                        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                            $ext     = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                            $maxSize = 2 * 1024 * 1024;
                            if (\in_array($ext, $allowed) && $_FILES['avatar']['size'] <= $maxSize) {
                                $dir = __DIR__ . '/../public/build/assets/usuarios/';
                                if (!is_dir($dir)) mkdir($dir, 0755, true);
                                $filename = uniqid('u_', true) . '.' . $ext;
                                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $filename)) {
                                    $usuario->avatar = '/build/assets/usuarios/' . $filename;
                                }
                            } else {
                                UsuarioBlog::setAlerta('error', 'La imagen debe ser JPG, PNG o WebP y pesar menos de 2 MB');
                                $alertas = UsuarioBlog::getAlertas();
                            }
                        }

                        if (empty($alertas['error'])) {
                            $usuario->guardar();
                            header('Location: /blog/usuarios?edited=1');
                            exit;
                        }
                    }
                }
            }
        }

        $router->renderAdmin('blog/usuarios/editar', [
            'titulo'  => 'Editar Usuario',
            'usuario' => $usuario,
            'alertas' => $alertas,
        ]);
    }

    public static function eliminarUsuario(Router $router) {
        self::requireAuth();
        self::requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $usuario = UsuarioBlog::find($id);
            if ($usuario) {
                $usuario->eliminar();
            }
        }
        header('Location: /blog/usuarios?deleted=1');
        exit;
    }

    // ── CATEGORÍAS ─────────────────────────────────────────────────────────────

    public static function categorias(Router $router) {
        self::requireAuth();
        $categorias = Categoria::allConArticulos();

        $router->renderAdmin('blog/categorias/index', [
            'titulo'     => 'Categorías',
            'categorias' => $categorias,
        ]);
    }

    public static function crearCategoria(Router $router) {
        self::requireAuth();
        $categoria  = new Categoria();
        $categorias = Categoria::allConArticulos();
        $alertas    = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categoria->sincronizar($_POST);
            $alertas = $categoria->validar();

            if (empty($alertas['error'])) {
                if ($categoria->existeSlug()) {
                    Categoria::setAlerta('error', 'Ya existe una categoría con ese slug');
                    $alertas = Categoria::getAlertas();
                } else {
                    $resultado = $categoria->guardar();
                    if ($resultado['resultado']) {
                        header('Location: /blog/categorias?success=1');
                        exit;
                    }
                    Categoria::setAlerta('error', 'Error al guardar la categoría. Intenta de nuevo.');
                    $alertas = Categoria::getAlertas();
                }
            }
        }

        $router->renderAdmin('blog/categorias/crear', [
            'titulo'     => 'Nueva Categoría',
            'categoria'  => $categoria,
            'categorias' => $categorias,
            'alertas'    => $alertas,
        ]);
    }

    public static function editarCategoria(Router $router) {
        self::requireAuth();
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$id) {
            header('Location: /blog/categorias');
            exit;
        }

        $categoria = Categoria::findConArticulos($id);
        if (!$categoria) {
            header('Location: /blog/categorias');
            exit;
        }

        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categoria->sincronizar($_POST);
            $alertas = $categoria->validar();

            if (empty($alertas['error'])) {
                if ($categoria->existeSlug()) {
                    Categoria::setAlerta('error', 'Ya existe otra categoría con ese slug');
                    $alertas = Categoria::getAlertas();
                } else {
                    $categoria->guardar();
                    header('Location: /blog/categorias?edited=1');
                    exit;
                }
            }
        }

        $router->renderAdmin('blog/categorias/editar', [
            'titulo'    => 'Editar Categoría',
            'categoria' => $categoria,
            'alertas'   => $alertas,
        ]);
    }

    public static function eliminarCategoria(Router $router) {
        self::requireAuth();
        self::requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $categoria = Categoria::find($id);
            if ($categoria) {
                $categoria->eliminar();
            }
        }
        header('Location: /blog/categorias?deleted=1');
        exit;
    }

    // ── NOTICIAS ───────────────────────────────────────────────────────────────

    public static function noticias(Router $router) {
        self::requireAuth();
        $estadosValidos = ['publicado', 'borrador', 'programado'];
        $estado   = in_array($_GET['estado'] ?? '', $estadosValidos, true) ? $_GET['estado'] : '';
        $noticias = Noticia::allConDetalles($estado);
        $success  = isset($_GET['success']);
        $edited   = isset($_GET['edited']);

        $router->renderAdmin('blog/noticias/index', [
            'titulo'   => 'Noticias',
            'noticias' => $noticias,
            'success'  => $success,
            'edited'   => $edited,
            'estado'   => $estado,
        ]);
    }

    public static function crearNoticia(Router $router) {
        self::requireAuth();
        $noticia             = new Noticia();
        $noticia->autor_id   = $_SESSION['blog_usuario']['id'] ?? null;
        $categorias = CategoriaNoticia::all();
        $usuarios   = UsuarioBlog::all();
        $alertas    = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $noticia->sincronizar($_POST);
            $noticia->destacada = isset($_POST['destacada']) ? 1 : 0;

            if (!trim($noticia->slug ?? '')) {
                $noticia->slug = self::generarSlug($noticia->titulo ?? '');
            }
            if (!trim($noticia->fecha_publicacion ?? '')) {
                $noticia->fecha_publicacion = ($noticia->estado === 'publicado')
                    ? date('Y-m-d H:i:s')
                    : null;
            }
            $noticia->tiempo_lectura = (int)($noticia->tiempo_lectura ?? 0) ?: null;
            if (!(int)($noticia->categoria_id ?? 0)) $noticia->categoria_id = null;
            if (!(int)($noticia->autor_id     ?? 0)) $noticia->autor_id     = null;

            $alertas = $noticia->validar();

            if (empty($alertas['error'])) {
                if ($noticia->existeSlug()) {
                    Noticia::setAlerta('error', 'Ya existe una noticia con ese slug.');
                    $alertas = Noticia::getAlertas();
                } else {
                    if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
                        $alertas = self::subirPortada($_FILES['portada'], $noticia);
                    }
                    if (empty($alertas['error'])) {
                        if ($noticia->destacada) Noticia::quitarDestacadaDeOtras();
                        $resultado = $noticia->crear();
                        if ($resultado['resultado']) {
                            header('Location: /dashboard/noticias?success=1');
                            exit;
                        }
                        Noticia::setAlerta('error', 'Error al guardar la noticia. Intenta de nuevo.');
                        $alertas = Noticia::getAlertas();
                    }
                }
            }
        }

        $router->renderAdmin('blog/noticias/crear', [
            'titulo'     => 'Nueva Noticia',
            'noticia'    => $noticia,
            'categorias' => $categorias,
            'usuarios'   => $usuarios,
            'alertas'    => $alertas,
        ]);
    }

    public static function editarNoticia(Router $router) {
        self::requireAuth();
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$id) {
            header('Location: /dashboard/noticias');
            exit;
        }

        $noticia = Noticia::allConDetalles('');
        $noticia = array_values(array_filter($noticia, fn($n) => (int)$n->id === $id))[0] ?? null;
        if (!$noticia) {
            $noticia = Noticia::find($id);
        }
        if (!$noticia) {
            header('Location: /dashboard/noticias');
            exit;
        }

        $categorias = CategoriaNoticia::all();
        $usuarios   = UsuarioBlog::all();
        $alertas    = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $slugOriginal = $noticia->slug;
            $noticia->sincronizar($_POST);
            $noticia->destacada = isset($_POST['destacada']) ? 1 : 0;

            if (!trim($noticia->slug ?? '')) {
                $noticia->slug = self::generarSlug($noticia->titulo ?? '');
            }
            if (!trim($noticia->fecha_publicacion ?? '')) {
                $noticia->fecha_publicacion = ($noticia->estado === 'publicado')
                    ? date('Y-m-d H:i:s')
                    : null;
            }
            $noticia->tiempo_lectura = (int)($noticia->tiempo_lectura ?? 0) ?: null;
            if (!(int)($noticia->categoria_id ?? 0)) $noticia->categoria_id = null;
            if (!(int)($noticia->autor_id     ?? 0)) $noticia->autor_id     = null;

            $alertas = $noticia->validar();

            if (empty($alertas['error'])) {
                if ($noticia->slug !== $slugOriginal && $noticia->existeSlug()) {
                    Noticia::setAlerta('error', 'Ya existe una noticia con ese slug.');
                    $alertas = Noticia::getAlertas();
                } else {
                    if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
                        $alertas = self::subirPortada($_FILES['portada'], $noticia);
                    }
                    if (empty($alertas['error'])) {
                        if ($noticia->destacada) Noticia::quitarDestacadaDeOtras((int)$noticia->id);
                        $noticia->actualizar();
                        header('Location: /dashboard/noticias?edited=1');
                        exit;
                    }
                }
            }
        }

        $router->renderAdmin('blog/noticias/editar', [
            'titulo'     => 'Editar Noticia',
            'noticia'    => $noticia,
            'categorias' => $categorias,
            'usuarios'   => $usuarios,
            'alertas'    => $alertas,
        ]);
    }

    public static function eliminarNoticia(Router $router) {
        self::requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $noticia = Noticia::find($id);
            if ($noticia) {
                if (!empty($noticia->portada)) {
                    $ruta = __DIR__ . '/../public' . $noticia->portada;
                    if (file_exists($ruta)) @unlink($ruta);
                }
                $noticia->eliminar();
            }
        }
        header('Location: /dashboard/noticias?deleted=1');
        exit;
    }

    // ── CATEGORÍAS NOTICIAS ────────────────────────────────────────────────────

    public static function categoriasNoticias(Router $router) {
        self::requireAuth();
        $categorias = CategoriaNoticia::allConNoticias();
        $success    = isset($_GET['success']);

        $router->renderAdmin('blog/noticias/categorias/index', [
            'titulo'     => 'Categorías de Noticias',
            'categorias' => $categorias,
            'success'    => $success,
        ]);
    }

    public static function crearCategoriaNoticia(Router $router) {
        self::requireAuth();
        $categoria = new CategoriaNoticia();
        $alertas   = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categoria->sincronizar($_POST);
            if (!trim($categoria->slug ?? '')) {
                $categoria->slug = self::generarSlug($categoria->nombre ?? '');
            }
            $alertas = $categoria->validar();

            if (empty($alertas['error'])) {
                if ($categoria->existeSlug()) {
                    CategoriaNoticia::setAlerta('error', 'Ya existe una categoría con ese slug');
                    $alertas = CategoriaNoticia::getAlertas();
                } else {
                    $categoria->guardar();
                    header('Location: /dashboard/noticias/categorias?success=1');
                    exit;
                }
            }
        }

        $router->renderAdmin('blog/noticias/categorias/crear', [
            'titulo'     => 'Nueva Categoría de Noticias',
            'categoria'  => $categoria,
            'categorias' => CategoriaNoticia::allConNoticias(),
            'alertas'    => $alertas,
        ]);
    }

    public static function editarCategoriaNoticia(Router $router) {
        self::requireAuth();
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$id) {
            header('Location: /dashboard/noticias/categorias');
            exit;
        }

        $categoria = CategoriaNoticia::findConNoticias($id);
        if (!$categoria) {
            header('Location: /dashboard/noticias/categorias');
            exit;
        }

        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categoria->sincronizar($_POST);
            $alertas = $categoria->validar();

            if (empty($alertas['error'])) {
                if ($categoria->existeSlug()) {
                    CategoriaNoticia::setAlerta('error', 'Ya existe otra categoría con ese slug');
                    $alertas = CategoriaNoticia::getAlertas();
                } else {
                    $categoria->guardar();
                    header('Location: /dashboard/noticias/categorias?edited=1');
                    exit;
                }
            }
        }

        $router->renderAdmin('blog/noticias/categorias/editar', [
            'titulo'    => 'Editar Categoría',
            'categoria' => $categoria,
            'alertas'   => $alertas,
        ]);
    }

    public static function eliminarCategoriaNoticia(Router $router) {
        self::requireAuth();
        self::requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $categoria = CategoriaNoticia::find($id);
            if ($categoria) {
                $categoria->eliminar();
            }
        }
        header('Location: /dashboard/noticias/categorias?deleted=1');
        exit;
    }

    // ── Helper portada noticias ───────────────────────────────────────────────

    private static function subirPortada(array $file, Noticia $noticia): array {
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $maxSize = 5 * 1024 * 1024;

        if (!in_array($ext, $allowed)) {
            Noticia::setAlerta('error', 'Formato no permitido. Usa JPG, PNG o WebP.');
            return Noticia::getAlertas();
        }
        if ($file['size'] > $maxSize) {
            Noticia::setAlerta('error', 'La imagen supera el límite de 5 MB.');
            return Noticia::getAlertas();
        }

        $dir = realpath(__DIR__ . '/../public') . DIRECTORY_SEPARATOR
             . 'build' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'noticias' . DIRECTORY_SEPARATOR;

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            Noticia::setAlerta('error', 'No se pudo crear el directorio de imágenes.');
            return Noticia::getAlertas();
        }

        $filename = uniqid('not_', true) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            Noticia::setAlerta('error', 'Error al guardar la imagen.');
            return Noticia::getAlertas();
        }

        $noticia->portada = '/build/assets/noticias/' . $filename;
        return [];
    }
}
