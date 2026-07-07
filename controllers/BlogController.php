<?php

namespace Controllers;

use MVC\Router;
use Model\UsuarioBlog;
use Model\Categoria;
use Model\Articulo;
use Model\Noticia;
use Model\CategoriaNoticia;
use Model\Notificacion;
use Model\Testimonial;

class BlogController {

    // ── PÚBLICO ────────────────────────────────────────────────────────────────

    public static function blogPublico(Router $router) {
        Articulo::publicarProgramados();
        $articulos  = Articulo::allConDetalles('publicado');
        $categorias = Categoria::allConArticulosPublicados();

        $extra_head = '<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>';
        $router->render('blog/index', [
            'seo_titulo'       => 'Voces Bilbao - Artículos',
            'seo_descripcion'  => 'Artículos, reflexiones y perspectivas sobre educación, aprendizaje y la vida dentro del Colegio Bilbao.',
            'extra_head'       => $extra_head,
            'articulos'        => $articulos,
            'categorias'       => $categorias,
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

        // Incrementar contador de vistas
        Articulo::incrementarVistas((int)$articulo->id);

        $tags         = $articulo->obtenerTags();
        $recomendados = Articulo::recomendados(
            (int) $articulo->id,
            $articulo->categoria_id ? (int) $articulo->categoria_id : null
        );

        $router->render('blog/articulo', [
            'seo_titulo'      => s($articulo->titulo),
            'seo_descripcion' => s($articulo->extracto ?? ''),
            'seo_imagen'      => $articulo->imagen ?? '',
            'articulo'        => $articulo,
            'tags'            => $tags,
            'recomendados'    => $recomendados,
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
        header('Location: /login');
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

    // ── AUTORES ────────────────────────────────────────────────────────────────

    public static function autores(Router $router) {
        self::requireAuth();
        self::requireAdmin();

        $autores = UsuarioBlog::conArticulosYNoticias();

        // Si se solicita el contenido de un autor específico (AJAX o inline)
        $detalleAutorId = isset($_GET['autor']) ? (int)$_GET['autor'] : null;
        $contenidoAutor = $detalleAutorId ? UsuarioBlog::contenidoDeAutor($detalleAutorId) : [];

        $router->renderAdmin('blog/autores/index', [
            'titulo'         => 'Contenido por autor',
            'autores'        => $autores,
            'detalleAutorId' => $detalleAutorId,
            'contenidoAutor' => $contenidoAutor,
        ]);
    }

    // ── ARTÍCULOS ──────────────────────────────────────────────────────────────

    public static function articulos(Router $router) {
        $usuario        = self::requireAuth();
        $estadosValidos = ['publicado', 'borrador', 'programado'];
        $estado    = in_array($_GET['estado'] ?? '', $estadosValidos, true) ? $_GET['estado'] : '';
        $esEditor  = ($usuario['rol'] ?? '') === 'editor';
        $articulos = Articulo::allConDetalles($estado);
        $success   = isset($_GET['success']);
        $edited    = isset($_GET['edited']);

        $router->renderAdmin('blog/articulos/index', [
            'titulo'    => 'Artículos',
            'articulos' => $articulos,
            'success'   => $success,
            'edited'    => $edited,
            'estado'    => $estado,
            'esEditor'  => $esEditor,
            'usuarioId' => (int)($usuario['id'] ?? 0),
        ]);
    }

    public static function crearArticulo(Router $router) {
        $usuario              = self::requireAuth();
        $articulo             = new Articulo();
        $articulo->autor_id   = $usuario['id'] ?? null;
        $categorias = Categoria::all();
        $usuarios   = UsuarioBlog::all();
        $alertas    = [];

        $esEditor = ($usuario['rol'] ?? '') === 'editor';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $articulo->sincronizar($_POST);

            // Los editores solo pueden guardar borradores
            if ($esEditor) {
                $articulo->estado = 'borrador';
                $articulo->autor_id = $usuario['id'];
            }

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
                    if (isset($_FILES['imagen'])) {
                        $alertas = self::subirImagen($_FILES['imagen'], $articulo);
                    }

                    if (empty($alertas['error'])) {
                        $resultado = $articulo->guardar();
                        if ($resultado['resultado']) {
                            $articulo->id = $resultado['id'];
                            $articulo->guardarTags(trim($_POST['tags'] ?? ''));
                            $accion = $_POST['_accion'] ?? 'guardar';
                            if ($esEditor && $accion === 'enviar_revision') {
                                Articulo::getDB()->query("UPDATE articulos SET envio_revision=1, comentario_revision=NULL WHERE id={$articulo->id}");
                                header('Location: /dashboard/articulos?revision=1');
                            } else {
                                header('Location: /dashboard/articulos?success=1');
                            }
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
            'esEditor'   => $esEditor,
            'alertas'    => $alertas,
        ]);
    }

    public static function editarArticulo(Router $router) {
        $usuario = self::requireAuth();
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$id) {
            header('Location: /dashboard/articulos');
            exit;
        }

        $articulo = Articulo::findConDetalles($id);
        if (!$articulo) {
            header('Location: /dashboard/articulos');
            exit;
        }

        $esEditor = ($usuario['rol'] ?? '') === 'editor';

        $categorias  = Categoria::all();
        $usuarios    = UsuarioBlog::all();
        $tagsActuales = $articulo->obtenerTags();
        $alertas     = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $estadoActual = $articulo->estado;
            $slugOriginal = $articulo->slug;
            $articulo->sincronizar($_POST);

            // Editor editando artículo ya publicado → guardar como version_pendiente
            if ($esEditor && in_array($estadoActual, ['publicado', 'programado'], true)) {
                // Procesar nueva imagen si se subió
                $imagenPendiente = $articulo->imagen;
                if (isset($_FILES['imagen'])) {
                    $tmpArticulo = new Articulo();
                    $alertasImg  = self::subirImagen($_FILES['imagen'], $tmpArticulo);
                    if (empty($alertasImg['error'])) {
                        $imagenPendiente = $tmpArticulo->imagen;
                    }
                }
                $vp = [
                    'titulo'         => trim($articulo->titulo ?? ''),
                    'extracto'       => trim($articulo->extracto ?? ''),
                    'contenido'      => $articulo->contenido ?? '',
                    'imagen'         => $imagenPendiente,
                    'categoria_id'   => (int)($articulo->categoria_id ?? 0) ?: null,
                    'tiempo_lectura' => (int)($articulo->tiempo_lectura ?? 0) ?: null,
                    'tags'           => trim($_POST['tags'] ?? ''),
                ];
                $vpEsc = Articulo::getDB()->escape_string(json_encode($vp, JSON_UNESCAPED_UNICODE));
                Articulo::getDB()->query(
                    "UPDATE articulos SET version_pendiente='{$vpEsc}', envio_revision=1, comentario_revision=NULL WHERE id={$id}"
                );
                header('Location: /dashboard/articulos?revision=1');
                exit;
            }

            // Flujo normal para borradores
            if ($esEditor) {
                $articulo->estado = 'borrador';
            }

            if (!trim($articulo->slug ?? '')) {
                $articulo->slug = self::generarSlug($articulo->titulo ?? '');
            }

            // Limpiar NULLables
            if (!trim($articulo->fecha_publicacion ?? '')) {
                $articulo->fecha_publicacion = null;
            }
            $articulo->tiempo_lectura = (int) ($articulo->tiempo_lectura ?? 0) ?: null;
            if (!(int) ($articulo->categoria_id ?? 0)) $articulo->categoria_id = null;
            if (!(int) ($articulo->autor_id     ?? 0)) $articulo->autor_id     = null;

            $alertas = $articulo->validar();

            if (empty($alertas['error'])) {
                if ($articulo->slug !== $slugOriginal && $articulo->existeSlug()) {
                    Articulo::setAlerta('error', 'Ya existe un artículo con ese slug. Edítalo manualmente.');
                    $alertas = Articulo::getAlertas();
                } else {
                    if (isset($_FILES['imagen'])) {
                        $alertas = self::subirImagen($_FILES['imagen'], $articulo);
                    }

                    if (empty($alertas['error'])) {
                        if (!$esEditor) {
                            // Admin siempre cierra el ciclo de revisión al guardar
                            $articulo->envio_revision     = 0;
                            $articulo->comentario_revision = null;
                            $articulo->version_pendiente   = null;
                        }
                        $articulo->guardar();
                        $articulo->guardarTags(trim($_POST['tags'] ?? ''));
                        if ($esEditor && ($_POST['_accion'] ?? '') === 'reenviar_revision') {
                            Articulo::getDB()->query(
                                "UPDATE articulos SET envio_revision=1 WHERE id={$id}"
                            );
                            header('Location: /dashboard/mis-revisiones?reenviado=1');
                            exit;
                        }
                        $tagsActuales = $articulo->obtenerTags();
                        header('Location: /dashboard/articulos?edited=1');
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
            'esEditor'     => $esEditor,
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
        header('Location: /dashboard/articulos?deleted=1');
        exit;
    }

    // ── REVISIONES ─────────────────────────────────────────────────────────────

    public static function revisiones(Router $router) {
        self::requireAuth();
        self::requireAdmin();

        $arts  = Articulo::conRevisionPendiente();
        $nots  = Noticia::conRevisionPendiente();

        $router->renderAdmin('blog/revisiones/index', [
            'titulo'    => 'Revisiones pendientes',
            'articulos' => $arts,
            'noticias'  => $nots,
        ]);
    }

    public static function enviarRevisionArticulo(Router $router) {
        $usuario = self::requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $art = Articulo::find($id);
            if ($art && $art->estado === 'borrador') {
                Articulo::getDB()->query("UPDATE articulos SET envio_revision=1, comentario_revision=NULL WHERE id={$id}");
            }
        }
        header('Location: /dashboard/articulos?revision=1');
        exit;
    }

    public static function aprobarArticulo(Router $router) {
        self::requireAuth();
        self::requireAdmin();
        $id     = (int)($_POST['id'] ?? 0);
        $estado = in_array($_POST['estado'] ?? '', ['publicado','programado'], true) ? $_POST['estado'] : 'publicado';
        $fecha  = trim($_POST['fecha_publicacion'] ?? '');
        if ($id) {
            $fechaSql = ($estado === 'programado' && $fecha) ? "'" . Articulo::getDB()->escape_string($fecha) . "'" : 'NULL';
            Articulo::getDB()->query("UPDATE articulos SET estado='{$estado}', fecha_publicacion={$fechaSql}, envio_revision=0, comentario_revision=NULL WHERE id={$id}");

            // Aplicar version_pendiente si existe
            $art = Articulo::find($id);
            if ($art && !empty($art->version_pendiente)) {
                $vp = json_decode($art->version_pendiente, true);
                if (is_array($vp)) {
                    $sets = [];
                    foreach (['titulo','extracto','contenido','imagen','categoria_id','tiempo_lectura'] as $col) {
                        if (array_key_exists($col, $vp)) {
                            $v = $vp[$col];
                            $sets[] = ($v === null || $v === '')
                                ? "{$col} = NULL"
                                : "{$col} = '" . Articulo::getDB()->escape_string((string)$v) . "'";
                        }
                    }
                    if ($sets) {
                        Articulo::getDB()->query(
                            "UPDATE articulos SET " . implode(', ', $sets) . ", version_pendiente = NULL WHERE id = {$id}"
                        );
                    }
                    if (!empty($vp['tags'])) {
                        $art->id = $id;
                        $art->guardarTags($vp['tags']);
                    }
                }
            } else {
                Articulo::getDB()->query("UPDATE articulos SET version_pendiente = NULL WHERE id = {$id}");
            }

            // Notificar al autor
            $artFinal = Articulo::find($id);
            if ($artFinal && $artFinal->autor_id) {
                Notificacion::nueva(
                    (int)$artFinal->autor_id,
                    'articulo_aprobado',
                    "Tu artículo \"{$artFinal->titulo}\" fue publicado.",
                    $id,
                    'articulo'
                );
            }
        }
        header('Location: /dashboard/revisiones?aprobado=1');
        exit;
    }

    public static function rechazarArticulo(Router $router) {
        self::requireAuth();
        self::requireAdmin();
        $id         = (int)($_POST['id'] ?? 0);
        $comentario = substr(trim($_POST['comentario'] ?? ''), 0, 1000);
        if ($id) {
            $c = Articulo::getDB()->escape_string($comentario);
            Articulo::getDB()->query("UPDATE articulos SET envio_revision=0, comentario_revision='{$c}', version_pendiente=NULL WHERE id={$id}");

            // Notificar al autor
            $art = Articulo::find($id);
            if ($art && $art->autor_id) {
                Notificacion::nueva(
                    (int)$art->autor_id,
                    'articulo_rechazado',
                    "Tu artículo \"{$art->titulo}\" requiere cambios.",
                    $id,
                    'articulo'
                );
            }
        }
        header('Location: /dashboard/revisiones?rechazado=1');
        exit;
    }

    public static function likeArticulo(Router $router) {
        self::requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            Articulo::getDB()->query("UPDATE articulos SET likes=likes+1 WHERE id={$id}");
        }
        header('Content-Type: application/json');
        $r = Articulo::getDB()->query("SELECT likes FROM articulos WHERE id={$id}");
        $likes = $r ? (int)$r->fetch_assoc()['likes'] : 0;
        echo json_encode(['likes' => $likes]);
        exit;
    }

    public static function enviarRevisionNoticia(Router $router) {
        $usuario = self::requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $not = Noticia::find($id);
            if ($not && $not->estado === 'borrador') {
                Noticia::getDB()->query("UPDATE noticias SET envio_revision=1, comentario_revision=NULL WHERE id={$id}");
            }
        }
        header('Location: /dashboard/noticias?revision=1');
        exit;
    }

    public static function aprobarNoticia(Router $router) {
        self::requireAuth();
        self::requireAdmin();
        $id     = (int)($_POST['id'] ?? 0);
        $estado = in_array($_POST['estado'] ?? '', ['publicado','programado'], true) ? $_POST['estado'] : 'publicado';
        $fecha  = trim($_POST['fecha_publicacion'] ?? '');
        if ($id) {
            $fechaSql = ($estado === 'programado' && $fecha) ? "'" . Noticia::getDB()->escape_string($fecha) . "'" : 'NULL';
            Noticia::getDB()->query("UPDATE noticias SET estado='{$estado}', fecha_publicacion={$fechaSql}, envio_revision=0, comentario_revision=NULL WHERE id={$id}");

            // Aplicar version_pendiente si existe
            $not = Noticia::find($id);
            if ($not && !empty($not->version_pendiente)) {
                $vp = json_decode($not->version_pendiente, true);
                if (is_array($vp)) {
                    $sets = [];
                    foreach (['titulo','extracto','contenido','portada','portada_alt','categoria_id','tiempo_lectura'] as $col) {
                        if (array_key_exists($col, $vp)) {
                            $v = $vp[$col];
                            $sets[] = ($v === null || $v === '')
                                ? "{$col} = NULL"
                                : "{$col} = '" . Noticia::getDB()->escape_string((string)$v) . "'";
                        }
                    }
                    if ($sets) {
                        Noticia::getDB()->query(
                            "UPDATE noticias SET " . implode(', ', $sets) . ", version_pendiente = NULL WHERE id = {$id}"
                        );
                    }
                }
            } else {
                Noticia::getDB()->query("UPDATE noticias SET version_pendiente = NULL WHERE id = {$id}");
            }

            // Notificar al autor
            $notFinal = Noticia::find($id);
            if ($notFinal && $notFinal->autor_id) {
                Notificacion::nueva(
                    (int)$notFinal->autor_id,
                    'noticia_aprobada',
                    "Tu noticia \"{$notFinal->titulo}\" fue publicada.",
                    $id,
                    'noticia'
                );
            }
        }
        header('Location: /dashboard/revisiones?aprobado=1');
        exit;
    }

    public static function rechazarNoticia(Router $router) {
        self::requireAuth();
        self::requireAdmin();
        $id         = (int)($_POST['id'] ?? 0);
        $comentario = substr(trim($_POST['comentario'] ?? ''), 0, 1000);
        if ($id) {
            $c = Noticia::getDB()->escape_string($comentario);
            Noticia::getDB()->query("UPDATE noticias SET envio_revision=0, comentario_revision='{$c}', version_pendiente=NULL WHERE id={$id}");

            // Notificar al autor
            $not = Noticia::find($id);
            if ($not && $not->autor_id) {
                Notificacion::nueva(
                    (int)$not->autor_id,
                    'noticia_rechazada',
                    "Tu noticia \"{$not->titulo}\" requiere cambios.",
                    $id,
                    'noticia'
                );
            }
        }
        header('Location: /dashboard/revisiones?rechazado=1');
        exit;
    }

    public static function likeNoticia(Router $router) {
        self::requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            Noticia::getDB()->query("UPDATE noticias SET likes=likes+1 WHERE id={$id}");
        }
        header('Content-Type: application/json');
        $r = Noticia::getDB()->query("SELECT likes FROM noticias WHERE id={$id}");
        $likes = $r ? (int)$r->fetch_assoc()['likes'] : 0;
        echo json_encode(['likes' => $likes]);
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

    /**
     * Traduce el código de error de una subida ($_FILES[...]['error']) a un
     * mensaje legible. Devuelve null cuando no hubo error o cuando no se envió
     * ningún archivo (ambos casos "sin novedad").
     */
    private static function mensajeErrorUpload(int $err): ?string {
        switch ($err) {
            case UPLOAD_ERR_OK:
            case UPLOAD_ERR_NO_FILE:
                return null;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'La imagen es demasiado pesada para el servidor. Redúcela o comprímela e inténtalo de nuevo.';
            case UPLOAD_ERR_PARTIAL:
                return 'La imagen se subió incompleta. Vuelve a intentarlo.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'El servidor no tiene carpeta temporal para subir imágenes.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'El servidor no pudo escribir la imagen en disco. Revisa los permisos.';
            default:
                return 'No se pudo subir la imagen (error ' . $err . ').';
        }
    }

    private static function subirImagen(array $file, Articulo $articulo): array {
        // Nada seleccionado: no es un error, simplemente no hay imagen que procesar.
        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) {
            return [];
        }
        // La subida falló antes de llegar aquí (p.ej. supera upload_max_filesize).
        if ($err !== UPLOAD_ERR_OK) {
            Articulo::setAlerta('error', self::mensajeErrorUpload($err));
            return Articulo::getAlertas();
        }

        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $maxSize = 20 * 1024 * 1024;

        if (!\in_array($ext, $allowed)) {
            Articulo::setAlerta('error', 'Formato de imagen no permitido. Usa JPG, PNG o WebP.');
            return Articulo::getAlertas();
        }

        if ($file['size'] > $maxSize) {
            Articulo::setAlerta('error', 'La imagen supera el límite de 20 MB.');
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
        $sesion = self::requireAuth();
        // Los editores no ven la lista: van directo a su propio perfil
        if (($sesion['rol'] ?? '') === 'editor') {
            header('Location: /dashboard/usuarios/editar?id=' . (int)$sesion['id']);
            exit;
        }
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
                            header('Location: /dashboard/usuarios?success=1');
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
        $sesion = self::requireAuth();
        $esEditor = ($sesion['rol'] ?? '') === 'editor';

        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

        // Editores solo pueden editar su propio perfil
        if ($esEditor && $id !== (int)$sesion['id']) {
            header('Location: /dashboard/usuarios/editar?id=' . (int)$sesion['id']);
            exit;
        }

        if (!$id && !$esEditor) {
            header('Location: /dashboard/usuarios');
            exit;
        }
        if (!$id) $id = (int)$sesion['id'];

        $usuario = UsuarioBlog::findConArticulos($id);
        if (!$usuario) {
            header('Location: /dashboard/usuarios');
            exit;
        }

        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $passwordOriginal = $usuario->password;

            $usuario->sincronizar($_POST);

            // Editores no pueden cambiar su rol
            if ($esEditor) {
                $usuario->rol = 'editor';
            }

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
                            $redirect = $esEditor
                                ? '/dashboard/usuarios/editar?id=' . (int)$usuario->id . '&edited=1'
                                : '/dashboard/usuarios?edited=1';
                            header('Location: ' . $redirect);
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
        header('Location: /dashboard/usuarios?deleted=1');
        exit;
    }

    // ── PERFIL ────────────────────────────────────────────────────────────────

    public static function perfil(Router $router) {
        $sesion  = self::requireAuth();
        $usuario = UsuarioBlog::find((int)$sesion['id']);
        if (!$usuario) {
            header('Location: /dashboard');
            exit;
        }

        $alertas  = [];
        $guardado = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $passwordOriginal = $usuario->password;
            $usuario->sincronizar($_POST);

            // Rol no cambia desde el perfil
            $usuario->rol = $sesion['rol'];

            $alertas = $usuario->validarPerfil();

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
                            if (\in_array($ext, $allowed) && $_FILES['avatar']['size'] <= 2 * 1024 * 1024) {
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
                            $_SESSION['blog_usuario']['nombre'] = $usuario->nombre;
                            $_SESSION['blog_usuario']['avatar'] = $usuario->avatar ?? '';
                            header('Location: /dashboard/perfil?saved=1');
                            exit;
                        }
                    }
                }
            }
        }

        $guardado = isset($_GET['saved']);

        $router->renderAdmin('blog/perfil', [
            'titulo'   => 'Mi perfil',
            'usuario'  => $usuario,
            'alertas'  => $alertas,
            'guardado' => $guardado,
        ]);
    }

    // ── MIS REVISIONES (editor) ────────────────────────────────────────────────

    public static function misRevisiones(Router $router) {
        $usuario = self::requireAuth();

        $id = (int)$usuario['id'];

        $articulos = Articulo::consultarSQL("
            SELECT a.*, u.nombre AS autor_nombre, u.avatar AS autor_avatar,
                   c.nombre AS categoria_nombre, c.color AS categoria_color
            FROM articulos a
            LEFT JOIN usuarios u ON a.autor_id = u.id
            LEFT JOIN categorias c ON a.categoria_id = c.id
            WHERE a.autor_id = {$id}
              AND (a.envio_revision = 1 OR a.comentario_revision IS NOT NULL)
            ORDER BY a.actualizado_en DESC
        ");

        $noticias = Noticia::consultarSQL("
            SELECT n.*, u.nombre AS autor_nombre, u.avatar AS autor_avatar,
                   c.nombre AS categoria_nombre, c.color AS categoria_color
            FROM noticias n
            LEFT JOIN usuarios u ON n.autor_id = u.id
            LEFT JOIN categorias_noticias c ON n.categoria_id = c.id
            WHERE n.autor_id = {$id}
              AND (n.envio_revision = 1 OR n.comentario_revision IS NOT NULL)
            ORDER BY n.actualizado_en DESC
        ");

        $router->renderAdmin('blog/revisiones/mis-revisiones', [
            'titulo'    => 'Mis revisiones',
            'articulos' => $articulos,
            'noticias'  => $noticias,
        ]);
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
                        header('Location: /dashboard/categorias?success=1');
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
            header('Location: /dashboard/categorias');
            exit;
        }

        $categoria = Categoria::findConArticulos($id);
        if (!$categoria) {
            header('Location: /dashboard/categorias');
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
                    header('Location: /dashboard/categorias?edited=1');
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
        header('Location: /dashboard/categorias?deleted=1');
        exit;
    }

    // ── NOTICIAS ───────────────────────────────────────────────────────────────

    public static function noticias(Router $router) {
        $usuario        = self::requireAuth();
        $estadosValidos = ['publicado', 'borrador', 'programado'];
        $estado   = in_array($_GET['estado'] ?? '', $estadosValidos, true) ? $_GET['estado'] : '';
        $esEditor = ($usuario['rol'] ?? '') === 'editor';
        $noticias = Noticia::allConDetalles($estado);
        $success  = isset($_GET['success']);
        $edited   = isset($_GET['edited']);

        $router->renderAdmin('blog/noticias/index', [
            'titulo'    => 'Noticias',
            'noticias'  => $noticias,
            'success'   => $success,
            'edited'    => $edited,
            'estado'    => $estado,
            'esEditor'  => $esEditor,
            'usuarioId' => (int)($usuario['id'] ?? 0),
        ]);
    }

    public static function crearNoticia(Router $router) {
        $usuario             = self::requireAuth();
        $esEditor            = ($usuario['rol'] ?? '') === 'editor';
        $noticia             = new Noticia();
        $noticia->autor_id   = $usuario['id'] ?? null;
        $categorias = CategoriaNoticia::all();
        $usuarios   = UsuarioBlog::all();
        $alertas    = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $noticia->sincronizar($_POST);
            $noticia->destacada = isset($_POST['destacada']) ? 1 : 0;

            // Los editores solo pueden guardar borradores
            if ($esEditor) {
                $noticia->estado    = 'borrador';
                $noticia->autor_id  = $usuario['id'];
                $noticia->destacada = 0;
            }

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
                    if (isset($_FILES['portada'])) {
                        $alertas = self::subirPortada($_FILES['portada'], $noticia);
                    }
                    if (empty($alertas['error'])) {
                        if ($noticia->destacada) Noticia::quitarDestacadaDeOtras();
                        $resultado = $noticia->crear();
                        if ($resultado['resultado']) {
                            $noticiaId = $resultado['id'];
                            $accion    = $_POST['_accion'] ?? 'guardar';
                            if ($esEditor && $accion === 'enviar_revision') {
                                Noticia::getDB()->query("UPDATE noticias SET envio_revision=1, comentario_revision=NULL WHERE id={$noticiaId}");
                                header('Location: /dashboard/noticias?revision=1');
                            } else {
                                header('Location: /dashboard/noticias?success=1');
                            }
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
            'esEditor'   => $esEditor,
        ]);
    }

    public static function editarNoticia(Router $router) {
        $usuario  = self::requireAuth();
        $esEditor = ($usuario['rol'] ?? '') === 'editor';
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$id) {
            header('Location: /dashboard/noticias');
            exit;
        }

        $noticia = Noticia::findConDetalles($id);
        if (!$noticia) {
            header('Location: /dashboard/noticias');
            exit;
        }

        $categorias = CategoriaNoticia::all();
        $usuarios   = UsuarioBlog::all();
        $alertas    = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $estadoActual = $noticia->estado;
            $slugOriginal = $noticia->slug;
            $noticia->sincronizar($_POST);
            $noticia->destacada = isset($_POST['destacada']) ? 1 : 0;

            // Editor editando noticia ya publicada → guardar como version_pendiente
            if ($esEditor && in_array($estadoActual, ['publicado', 'programado'], true)) {
                $portadaPendiente = $noticia->portada;
                if (isset($_FILES['portada'])) {
                    $tmpNoticia = new Noticia();
                    $alertasImg = self::subirPortada($_FILES['portada'], $tmpNoticia);
                    if (empty($alertasImg['error'])) {
                        $portadaPendiente = $tmpNoticia->portada;
                    }
                }
                $vp = [
                    'titulo'         => trim($noticia->titulo ?? ''),
                    'extracto'       => trim($noticia->extracto ?? ''),
                    'contenido'      => $noticia->contenido ?? '',
                    'portada'        => $portadaPendiente,
                    'portada_alt'    => trim($noticia->portada_alt ?? ''),
                    'categoria_id'   => (int)($noticia->categoria_id ?? 0) ?: null,
                    'tiempo_lectura' => (int)($noticia->tiempo_lectura ?? 0) ?: null,
                ];
                $vpEsc = Noticia::getDB()->escape_string(json_encode($vp, JSON_UNESCAPED_UNICODE));
                Noticia::getDB()->query(
                    "UPDATE noticias SET version_pendiente='{$vpEsc}', envio_revision=1, comentario_revision=NULL WHERE id={$id}"
                );
                header('Location: /dashboard/noticias?revision=1');
                exit;
            }

            // Flujo normal para borradores
            if ($esEditor) {
                $noticia->estado    = 'borrador';
                $noticia->destacada = 0;
            }

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
                    if (isset($_FILES['portada'])) {
                        $alertas = self::subirPortada($_FILES['portada'], $noticia);
                    }
                    if (empty($alertas['error'])) {
                        if (!$esEditor) {
                            // Admin siempre cierra el ciclo de revisión al guardar
                            $noticia->envio_revision     = 0;
                            $noticia->comentario_revision = null;
                            $noticia->version_pendiente   = null;
                        }
                        if ($noticia->destacada) Noticia::quitarDestacadaDeOtras((int)$noticia->id);
                        $noticia->actualizar();
                        if ($esEditor && ($_POST['_accion'] ?? '') === 'reenviar_revision') {
                            Noticia::getDB()->query(
                                "UPDATE noticias SET envio_revision=1 WHERE id={$id}"
                            );
                            header('Location: /dashboard/mis-revisiones?reenviado=1');
                            exit;
                        }
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
            'esEditor'   => $esEditor,
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
        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) {
            return [];
        }
        if ($err !== UPLOAD_ERR_OK) {
            Noticia::setAlerta('error', self::mensajeErrorUpload($err));
            return Noticia::getAlertas();
        }

        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $maxSize = 20 * 1024 * 1024;

        if (!in_array($ext, $allowed)) {
            Noticia::setAlerta('error', 'Formato no permitido. Usa JPG, PNG o WebP.');
            return Noticia::getAlertas();
        }
        if ($file['size'] > $maxSize) {
            Noticia::setAlerta('error', 'La imagen supera el límite de 20 MB.');
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

    // ── NOTIFICACIONES ────────────────────────────────────────────────────────

    public static function notificaciones(Router $router) {
        $usuario = self::requireAuth();
        $notifs  = Notificacion::porUsuario((int)$usuario['id'], 30);
        $router->renderAdmin('blog/notificaciones/index', [
            'titulo' => 'Notificaciones',
            'notifs' => $notifs,
        ]);
    }

    public static function marcarNotificacionLeida(Router $router) {
        $usuario = self::requireAuth();
        $id      = (int)($_POST['id'] ?? 0);
        if ($id) {
            Notificacion::marcarLeida($id, (int)$usuario['id']);
        }
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            exit;
        }
        header('Location: /dashboard/notificaciones');
        exit;
    }

    public static function marcarTodasLeidas(Router $router) {
        $usuario = self::requireAuth();
        Notificacion::marcarTodasLeidas((int)$usuario['id']);
        header('Location: /dashboard/notificaciones?marcadas=1');
        exit;
    }

    // ── TESTIMONIALES ──────────────────────────────────────────────────────────

    public static function testimoniales(Router $router) {
        self::requireAuth();
        self::requireAdmin();

        $testimoniales = Testimonial::todos();

        $router->renderAdmin('blog/testimoniales/index', [
            'titulo'        => 'Testimoniales',
            'testimoniales' => $testimoniales,
        ]);
    }

    public static function aprobarTestimonial(Router $router) {
        self::requireAuth();
        self::requireAdmin();

        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db = Testimonial::getDB();
            $db->query("UPDATE testimoniales SET aprobado=1 WHERE id={$id}");
        }
        header('Location: /dashboard/testimoniales?aprobado=1');
        exit;
    }

    public static function rechazarTestimonial(Router $router) {
        self::requireAuth();
        self::requireAdmin();

        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db = Testimonial::getDB();
            $db->query("DELETE FROM testimoniales WHERE id={$id}");
        }
        header('Location: /dashboard/testimoniales?rechazado=1');
        exit;
    }
}
