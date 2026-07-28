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
use Model\Suplencia;
use Model\SuplenciaHora;
use Model\Periodo;
use Model\Aula;
use Model\Grupo;
use Model\Materia;
use Model\Horario;
use Model\Evento;

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
        if (!\in_array($_SESSION['blog_usuario']['rol'] ?? '', ['administrador', 'superadmin'], true)) {
            header('Location: /dashboard');
            exit;
        }
    }

    /** ¿El usuario en sesión es superadmin (nivel máximo)? */
    private static function esSuperadmin(): bool {
        return ($_SESSION['blog_usuario']['rol'] ?? '') === 'superadmin';
    }

    /** Guard reservado a superadmin (directorios de personal, dashboard de suplencias). */
    private static function requireSuperadmin(): void {
        self::requireAuth();
        if (!self::esSuperadmin()) {
            header('Location: /dashboard');
            exit;
        }
    }

    /** ¿El usuario en sesión puede acceder al módulo indicado? Super/admin = todos. */
    private static function puede(string $modulo): bool {
        $u = $_SESSION['blog_usuario'] ?? null;
        if (!$u) return false;
        if (\in_array($u['rol'] ?? '', ['administrador', 'superadmin'], true)) return true;
        $lista = array_filter(array_map('trim', explode(',', (string) ($u['modulos'] ?? ''))));
        return \in_array($modulo, $lista, true);
    }

    /**
     * ¿Puede validar contenido editorial (revisiones + testimoniales)?
     * Super/admin siempre; un 'usuario' solo si es revisor con módulo redaccion.
     */
    private static function puedeRevisar(): bool {
        $u = $_SESSION['blog_usuario'] ?? null;
        if (!$u) return false;
        if (\in_array($u['rol'] ?? '', ['administrador', 'superadmin'], true)) return true;
        return ($u['rol_redaccion'] ?? '') === 'revisor' && self::puede('redaccion');
    }

    /** Guard editorial: redirige si no puede revisar. */
    private static function requireRevisor(): void {
        self::requireAuth();
        if (!self::puedeRevisar()) {
            header('Location: /dashboard');
            exit;
        }
    }

    /** Guard de módulo: redirige al home de módulos si no tiene acceso. */
    private static function requireModulo(string $modulo): void {
        self::requireAuth();
        if (!self::puede($modulo)) {
            header('Location: /dashboard');
            exit;
        }
    }

    /** Lista de módulos disponibles para el usuario en sesión (para el home/sidebar). */
    private static function modulosDisponibles(): array {
        $todos = \Model\UsuarioBlog::MODULOS_ASIGNABLES;
        return array_values(array_filter($todos, fn($m) => self::puede($m)));
    }

    /** ¿La petición viene por fetch/XHR y espera JSON en vez de un redirect? */
    private static function esAjax(): bool {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    /** Responde JSON y corta la ejecución. */
    private static function json(array $datos, int $codigo = 200): void {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
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
                        'id'            => $usuario->id,
                        'nombre'        => $usuario->nombre,
                        'rol'           => $usuario->rol,
                        'rol_redaccion' => $usuario->rol_redaccion ?? '',
                        'tipo_personal' => $usuario->tipo_personal ?? '',
                        'puede_suplir'  => (int)($usuario->puede_suplir ?? 1),
                        'avatar'        => $usuario->avatar ?? '',
                        'modulos'       => $usuario->modulos ?? '',
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

    // ── HOME DE MÓDULOS ────────────────────────────────────────────────────────
    public static function home(Router $router) {
        self::requireAuth();

        $verUsuarios = self::puede('usuarios');
        $router->renderAdmin('blog/home', [
            'titulo'        => 'Inicio',
            'modulos'       => self::modulosDisponibles(),
            'cumpleanos'    => $verUsuarios ? UsuarioBlog::proximosCumpleanos(6) : [],
            'cumpleanosAll' => $verUsuarios ? UsuarioBlog::conCumpleanos() : [],
            // El calendario combina cumpleaños con los eventos institucionales
            'eventos'       => Evento::todos(),
        ]);
    }

    // ── MÓDULO REDACCIÓN (dashboard analítico) ──────────────────────────────────
    public static function redaccion(Router $router) {
        self::requireModulo('redaccion');
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
            'titulo'      => 'Redacción',
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

    // ── MÓDULO SUPLENCIAS ───────────────────────────────────────────────────────

    /** Tipos de personal del usuario en sesión. */
    private static function sesionTipos(): array {
        return array_filter(array_map('trim', explode(',', (string)($_SESSION['blog_usuario']['tipo_personal'] ?? ''))));
    }
    /** ¿Puede agendar suplencias (prefectura/admin/super)? */
    private static function puedeAgendar(): bool {
        if (self::esSuperadmin() || (($_SESSION['blog_usuario']['rol'] ?? '') === 'administrador')) return true;
        return in_array('prefecto', self::sesionTipos(), true);
    }

    /** Agenda: lista de suplencias con filtros (prefectura/admin). */
    public static function suplencias(Router $router) {
        self::requireModulo('suplencias');
        $filtros = [
            'q'      => trim($_GET['q'] ?? ''),
            'estado' => $_GET['estado'] ?? '',
            'origen' => $_GET['origen'] ?? '',
            'desde'  => $_GET['desde'] ?? '',
            'hasta'  => $_GET['hasta'] ?? '',
        ];
        $router->renderAdmin('blog/suplencias/index', [
            'titulo'       => 'Agenda de suplencias',
            'suplencias'   => Suplencia::listar($filtros),
            'conteos'      => Suplencia::conteos(),
            'filtros'      => $filtros,
            'puedeAgendar' => self::puedeAgendar(),
        ]);
    }

    /** Endpoint JSON: autocompletado de profesores (ausente). */
    public static function buscarColaboradores(Router $router) {
        self::requireModulo('suplencias');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(UsuarioBlog::buscar($_GET['q'] ?? '', 8));
        exit;
    }

    /** Endpoint JSON: sugerencias de suplente para una fecha+periodo (algoritmo). */
    public static function sugerirSuplentes(Router $router) {
        self::requireModulo('suplencias');
        header('Content-Type: application/json; charset=utf-8');
        $fecha   = trim($_GET['fecha'] ?? '');
        $periodo = (int)($_GET['periodo'] ?? 0);
        $ausente = (int)($_GET['ausente'] ?? 0);
        if ($fecha === '' || !$periodo) { echo json_encode([]); exit; }
        echo json_encode(SuplenciaHora::sugerir($fecha, $periodo, $ausente));
        exit;
    }

    /**
     * Endpoint JSON: horario semanal de un profesor + sus horas libres para una fecha.
     * Alimenta la rejilla interactiva de solicitar/crear y el preview de candidatos en agendar.
     */
    public static function horarioProfesorJson(Router $router) {
        self::requireModulo('suplencias');
        header('Content-Type: application/json; charset=utf-8');

        $profId = (int)($_GET['profesor'] ?? 0);
        $fecha  = trim($_GET['fecha'] ?? '');
        if (!$profId) { echo json_encode(['error' => 'profesor requerido']); exit; }

        $dow = $fecha !== '' ? (int)date('N', strtotime($fecha)) : 0;
        $dia = \Model\Horario::DIAS[$dow - 1] ?? null;   // null en fin de semana

        $periodos = [];
        foreach (Periodo::todos() as $p) {
            $periodos[] = [
                'id'        => (int)$p->id,
                'etiqueta'  => $p->etiqueta,
                'inicio'    => substr($p->hora_inicio, 0, 5),
                'fin'       => substr($p->hora_fin, 0, 5),
                'es_receso' => (int)$p->es_receso === 1,
            ];
        }

        // Semana completa: [dia][periodo_id] => datos de la clase
        $semana = [];
        foreach (Horario::porProfesor($profId) as $h) {
            $semana[$h->dia][(int)$h->periodo_id] = [
                'materia'    => $h->materia,
                'materia_id' => (int)$h->materia_id,
                'grupo'      => $h->grupo_nombre,
                'grupo_id'   => (int)$h->grupo_id,
                'aula'       => $h->aula_nombre,
                'aula_id'    => (int)$h->aula_id,
            ];
        }

        // Horas libres del día pedido. No hay disponibilidad declarada a mano: toda hora
        // libre es candidata y el descarte lo hacen las reglas de SuplenciaHora::sugerir().
        $disponible = [];
        if ($dia !== null) {
            foreach ($periodos as $p) {
                if ($p['es_receso'] || isset($semana[$dia][$p['id']])) continue;
                $disponible[$p['id']] = true;
            }
        }

        $prof = UsuarioBlog::find($profId);
        echo json_encode([
            'profesor'   => ['id' => $profId, 'nombre' => $prof->nombre ?? '', 'avatar' => $prof->avatar ?? ''],
            'dia'        => $dia,
            'periodos'   => $periodos,
            'semana'     => $semana,
            'disponible' => $disponible,
        ]);
        exit;
    }

    /** Sube un justificante (PDF o imagen). Devuelve ruta pública o null. */
    private static function subirJustificante(): ?string {
        if (empty($_FILES['justificante']) || $_FILES['justificante']['error'] !== UPLOAD_ERR_OK) return null;
        $ext     = strtolower(pathinfo($_FILES['justificante']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed, true) || $_FILES['justificante']['size'] > 4 * 1024 * 1024) {
            Suplencia::setAlerta('error', 'El justificante debe ser PDF o imagen (JPG/PNG/WebP) de máximo 4 MB');
            return null;
        }
        $dir = __DIR__ . '/../public/build/assets/suplencias/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $fn = uniqid('just_', true) . '.' . $ext;
        if (move_uploaded_file($_FILES['justificante']['tmp_name'], $dir . $fn)) {
            return '/build/assets/suplencias/' . $fn;
        }
        return null;
    }

    /** Crea las horas de cobertura desde arrays paralelos del POST. */
    private static function guardarHoras(int $supId, array $post): int {
        $periodos = $post['periodo_id'] ?? [];
        $n = 0;
        foreach ($periodos as $i => $pid) {
            $pid = (int)$pid;
            if (!$pid) continue;
            $h = new SuplenciaHora();
            $h->suplencia_id = $supId;
            $h->periodo_id   = $pid;
            $h->grupo_id     = (int)($post['grupo_id'][$i] ?? 0) ?: null;
            $h->aula_id      = (int)($post['aula_id'][$i] ?? 0) ?: null;
            $h->materia_id   = (int)($post['materia_id'][$i] ?? 0) ?: null;
            $h->estado_hora  = 'pendiente';
            $h->guardar();
            $n++;
        }
        return $n;
    }

    /** Solicitar suplencia (auto-servicio del profesor ausente, flujo anticipado). */
    public static function solicitarSuplencia(Router $router) {
        $sesion = self::requireModulo('suplencias');
        $sesion = $_SESSION['blog_usuario'];
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sup = new Suplencia();
            $sup->sincronizar($_POST);
            $sup->profesor_ausente_id = (int)$sesion['id'];
            $sup->origen  = 'anticipada';
            $sup->estado  = 'solicitada';
            $sup->creado_por = (int)$sesion['id'];
            // El motivo llega del catálogo, o del texto libre cuando se eligió "Otro"
            $sup->motivo  = Suplencia::motivoDesdePost($_POST);
            $alertas = $sup->validar();
            $justificante = self::subirJustificante();
            $alertas = Suplencia::getAlertas();
            if (empty($alertas['error'])) {
                if ($justificante) $sup->justificante = $justificante;
                $r = $sup->guardar();
                if ($r['resultado']) {
                    self::guardarHoras((int)$r['id'], $_POST);
                    header('Location: /dashboard/suplencias?success=1');
                    exit;
                }
            }
        }

        // Horario del profesor para elegir las horas a cubrir (por día)
        $router->renderAdmin('blog/suplencias/solicitar', [
            'titulo'   => 'Solicitar suplencia',
            'periodos' => Periodo::clases(),
            'matriz'   => Horario::comoMatriz(Horario::porProfesor((int)$sesion['id'])),
            'alertas'  => $alertas,
        ]);
    }

    /** Prefectura abre una suplencia (puede ser sin aviso). */
    public static function crearSuplencia(Router $router) {
        self::requireModulo('suplencias');
        if (!self::puedeAgendar()) { header('Location: /dashboard/suplencias'); exit; }
        $suplencia = new Suplencia();
        $alertas   = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $suplencia->sincronizar($_POST);
            $suplencia->creado_por = (int)$_SESSION['blog_usuario']['id'];
            // El motivo llega del catálogo, o del texto libre cuando se eligió "Otro"
            $suplencia->motivo = Suplencia::motivoDesdePost($_POST);
            $alertas = $suplencia->validar();
            $justificante = self::subirJustificante();
            $alertas = Suplencia::getAlertas();
            if (empty($alertas['error'])) {
                if ($justificante) $suplencia->justificante = $justificante;
                $r = $suplencia->guardar();
                if ($r['resultado']) {
                    $nuevaId = (int)$r['id'];
                    self::guardarHoras($nuevaId, $_POST);
                    Suplencia::recalcularEstado($nuevaId);

                    // Prefectura abrió la ausencia: el profesor debe enterarse, y si fue
                    // "sin aviso" y no hay comprobante, saber que tiene que subirlo.
                    $ausenteId = (int)$suplencia->profesor_ausente_id;
                    $quienAbre = (int)($_SESSION['blog_usuario']['id'] ?? 0);
                    if ($ausenteId && $ausenteId !== $quienAbre) {
                        $cuando = date('d/m/Y', strtotime($suplencia->fecha));
                        $faltaJustif = $suplencia->origen === 'sin_aviso' && empty($suplencia->justificante);
                        Notificacion::nueva(
                            $ausenteId,
                            $faltaJustif ? 'justificante_pendiente' : 'suplencia_registrada',
                            $faltaJustif
                                ? "Se registró tu ausencia del {$cuando} sin aviso. Falta subir el justificante."
                                : "Prefectura registró tu ausencia del {$cuando}.",
                            $nuevaId, 'suplencia', 'suplencias',
                            $faltaJustif ? 'aviso' : 'info',
                            '/dashboard/suplencias/agendar?id=' . $nuevaId
                        );
                    }

                    header('Location: /dashboard/suplencias/agendar?id=' . $nuevaId);
                    exit;
                }
                Suplencia::setAlerta('error', 'No se pudo guardar la suplencia. Intenta de nuevo.');
                $alertas = Suplencia::getAlertas();
            }
        }

        $router->renderAdmin('blog/suplencias/crear', [
            'titulo'    => 'Abrir suplencia',
            'suplencia' => $suplencia,
            'periodos'  => Periodo::clases(),
            'grupos'    => Grupo::todos(),
            'aulas'     => Aula::todas(),
            'materias'  => Materia::todas(),
            'alertas'   => $alertas,
        ]);
    }

    /** Detalle + agenda de una suplencia: asignar suplentes por hora. */
    /**
     * Avisa al suplente de que le asignaron una hora concreta.
     * Alex es la voz del sistema: cada acción que afecta a otra persona deja un
     * aviso en su campana, no solo un toast para quien la ejecutó.
     */
    private static function avisarCoberturaAsignada($suplencia, int $horaId, int $suplenteId): void {
        if (!$suplenteId) return;
        $hora = SuplenciaHora::detalle($horaId);
        if (!$hora) return;

        $cuando = date('d/m/Y', strtotime($suplencia->fecha));
        $clase  = trim(($hora->materia ?: 'una clase') . ($hora->grupo_nombre ? ' · ' . $hora->grupo_nombre : ''));

        Notificacion::nueva(
            $suplenteId,
            'cobertura_asignada',
            "Te asignaron {$clase} el {$cuando} ({$hora->periodo_etiqueta}).",
            (int)$suplencia->id, 'suplencia', 'suplencias', 'info',
            '/dashboard/suplencias/mis-coberturas'
        );
    }

    /** Cuando ya no queda ninguna hora pendiente, se lo dice al profesor ausente. */
    private static function avisarSuplenciaCompleta(int $suplenciaId): void {
        $horas = SuplenciaHora::deSuplencia($suplenciaId);
        if (!$horas) return;
        foreach ($horas as $h) {
            if ($h->estado_hora === 'pendiente') return;   // todavía falta alguna
        }
        $s = Suplencia::find($suplenciaId);
        if (!$s || !$s->profesor_ausente_id) return;

        Notificacion::nueva(
            (int)$s->profesor_ausente_id,
            'suplencia_agendada',
            'Tu ausencia del ' . date('d/m/Y', strtotime($s->fecha)) . ' ya tiene todas las horas cubiertas.',
            $suplenciaId, 'suplencia', 'suplencias', 'exito',
            '/dashboard/suplencias/agendar?id=' . $suplenciaId
        );
    }

    public static function agendarSuplencia(Router $router) {
        self::requireModulo('suplencias');
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $suplencia = Suplencia::encontrarConDetalle($id);
        if (!$suplencia) { header('Location: /dashboard/suplencias'); exit; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::puedeAgendar()) { header('Location: /dashboard/suplencias'); exit; }
            $accion = $_POST['_accion'] ?? '';
            $horaId = (int)($_POST['hora_id'] ?? 0);
            // Las horas se fijan al abrir la suplencia desde el horario del ausente:
            // aquí solo se asigna o se retira al suplente.
            if ($accion === 'asignar' && $horaId) {
                $suplenteId = (int)($_POST['suplente_id'] ?? 0);
                SuplenciaHora::asignar($horaId, $suplenteId);
                self::avisarCoberturaAsignada($suplencia, $horaId, $suplenteId);
            } elseif ($accion === 'desasignar' && $horaId) {
                // Se lee antes de desasignar: después ya no se sabe a quién avisar
                $previo = SuplenciaHora::find($horaId);
                SuplenciaHora::desasignar($horaId);
                if ($previo && $previo->suplente_id) {
                    Notificacion::nueva(
                        (int)$previo->suplente_id,
                        'cobertura_retirada',
                        'Ya no tienes que cubrir la clase del ' . date('d/m/Y', strtotime($suplencia->fecha)) . '.',
                        $id, 'suplencia', 'suplencias', 'info',
                        '/dashboard/suplencias/mis-coberturas'
                    );
                }
            } elseif ($accion === 'eliminar_hora' && $horaId) {
                $h = SuplenciaHora::find($horaId);
                if ($h) $h->eliminar();
            }
            Suplencia::recalcularEstado($id);
            self::avisarSuplenciaCompleta($id);
            header('Location: /dashboard/suplencias/agendar?id=' . $id);
            exit;
        }

        $router->renderAdmin('blog/suplencias/agendar', [
            'titulo'       => 'Agendar suplencia',
            'suplencia'    => $suplencia,
            'horas'        => SuplenciaHora::deSuplencia($id),
            'puedeAgendar' => self::puedeAgendar(),
        ]);
    }

    /** El profesor ausente sube su justificante (flujo sin aviso). */
    public static function justificarSuplencia(Router $router) {
        $sesion = self::requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        $sup = Suplencia::find($id);
        if ($sup && $_SERVER['REQUEST_METHOD'] === 'POST') {
            // Solo el profesor ausente o un agendador
            if ((int)$sup->profesor_ausente_id === (int)$sesion['id'] || self::puedeAgendar()) {
                $ruta = self::subirJustificante();
                if ($ruta) {
                    $r = Suplencia::getDB()->escape_string($ruta);
                    Suplencia::getDB()->query("UPDATE suplencias SET justificante='{$r}' WHERE id={$id} LIMIT 1");
                    Suplencia::recalcularEstado($id);
                }
            }
        }
        header('Location: /dashboard/suplencias/agendar?id=' . $id);
        exit;
    }

    /** Mis coberturas: horas que me asignaron y debo validar. */
    public static function misCoberturas(Router $router) {
        $sesion = self::requireModulo('suplencias');
        $sesion = $_SESSION['blog_usuario'];
        $router->renderAdmin('blog/suplencias/mis-coberturas', [
            'titulo'  => 'Mis coberturas',
            'horas'   => SuplenciaHora::porValidarDeSuplente((int)$sesion['id']),
        ]);
    }

    /** El suplente valida que cubrió una hora. */
    public static function validarCobertura(Router $router) {
        $sesion = self::requireAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $horaId = (int)($_POST['hora_id'] ?? 0);
            $h = SuplenciaHora::find($horaId);
            if ($h && SuplenciaHora::validarHora($horaId, (int)$sesion['id'])) {
                Suplencia::recalcularEstado((int)$h->suplencia_id);
            }
        }
        header('Location: /dashboard/suplencias/mis-coberturas?validado=1');
        exit;
    }

    public static function eliminarSuplencia(Router $router) {
        self::requireModulo('suplencias');
        if (!self::puedeAgendar()) { header('Location: /dashboard/suplencias'); exit; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $s  = Suplencia::find($id);
            if ($s) $s->eliminar();
        }
        header('Location: /dashboard/suplencias?deleted=1');
        exit;
    }

    /** Tablero de estadísticas de suplencias (administrador y superadmin). */
    public static function suplenciasDashboard(Router $router) {
        self::requireModulo('suplencias');
        self::requireAdmin();
        $router->renderAdmin('blog/suplencias/dashboard', [
            'titulo'       => 'Tablero de suplencias',
            'conteos'      => Suplencia::conteos(),
            'porEstado'    => Suplencia::porEstado(),
            'porOrigen'    => Suplencia::porOrigen(),
            'topSuplentes' => Suplencia::topSuplentes(8),
            'topAusentes'  => Suplencia::topAusentes(8),
            'porMes'       => Suplencia::porMes(),
            'porMateria'   => Suplencia::porMateria(8),
            'porMotivo'    => Suplencia::porMotivo(8),
            'resumenDia'   => Suplencia::resumenDiario(),
            'detalleDia'   => Suplencia::detalleDiario(),
            'extra_head'   => '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>',
        ]);
    }

    // ── MÓDULO EVENTOS / CALENDARIO ─────────────────────────────────────────────
    public static function eventos(Router $router) {
        self::requireModulo('eventos');
        $router->renderAdmin('blog/eventos/index', [
            'titulo'  => 'Eventos',
            'eventos' => Evento::todos(),
        ]);
    }

    public static function crearEvento(Router $router) {
        self::requireModulo('eventos');
        $evento  = new Evento();
        $alertas = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $evento->sincronizar($_POST);
            $evento->publico = empty($_POST['publico']) ? 0 : 1;
            $alertas = $evento->validar();
            if (empty($alertas['error'])) {
                if ($evento->guardar()['resultado']) { header('Location: /dashboard/eventos?success=1'); exit; }
                Evento::setAlerta('error', 'No se pudo guardar el evento.');
                $alertas = Evento::getAlertas();
            }
        }
        $router->renderAdmin('blog/eventos/crear', ['titulo' => 'Nuevo evento', 'evento' => $evento, 'alertas' => $alertas]);
    }

    public static function editarEvento(Router $router) {
        self::requireModulo('eventos');
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $evento = Evento::find($id);
        if (!$evento) { header('Location: /dashboard/eventos'); exit; }
        $alertas = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $evento->sincronizar($_POST);
            $evento->publico = empty($_POST['publico']) ? 0 : 1;
            $alertas = $evento->validar();
            if (empty($alertas['error'])) {
                $evento->guardar();
                header('Location: /dashboard/eventos?edited=1'); exit;
            }
        }
        $router->renderAdmin('blog/eventos/editar', ['titulo' => 'Editar evento', 'evento' => $evento, 'alertas' => $alertas]);
    }

    public static function eliminarEvento(Router $router) {
        self::requireModulo('eventos');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $e = Evento::find((int)($_POST['id'] ?? 0));
            if ($e) $e->eliminar();
        }
        header('Location: /dashboard/eventos?deleted=1');
        exit;
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
        $esEditor  = ($usuario['rol'] ?? '') === 'usuario';
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

        $esEditor = ($usuario['rol'] ?? '') === 'usuario';

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

        $esEditor = ($usuario['rol'] ?? '') === 'usuario';

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
        self::requireRevisor();

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
        self::requireRevisor();
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
                    'articulo',
                    'redaccion',
                    'exito',
                    '/dashboard/articulos/editar?id=' . $id
                );
            }
        }
        header('Location: /dashboard/revisiones?aprobado=1');
        exit;
    }

    public static function rechazarArticulo(Router $router) {
        self::requireRevisor();
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
                    'articulo',
                    'redaccion',
                    'aviso',
                    '/dashboard/articulos/editar?id=' . $id
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
        self::requireRevisor();
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
                    'noticia',
                    'redaccion',
                    'exito',
                    '/dashboard/noticias/editar?id=' . $id
                );
            }
        }
        header('Location: /dashboard/revisiones?aprobado=1');
        exit;
    }

    public static function rechazarNoticia(Router $router) {
        self::requireRevisor();
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
                    'noticia',
                    'redaccion',
                    'aviso',
                    '/dashboard/noticias/editar?id=' . $id
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
        // Sin acceso al módulo Usuarios: van directo a su propio perfil
        if (!self::puede('usuarios')) {
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

    public static function cumpleanos(Router $router) {
        self::requireModulo('usuarios');
        $router->renderAdmin('blog/usuarios/cumpleanos', [
            'titulo'     => 'Cumpleaños',
            'cumpleanos' => UsuarioBlog::conCumpleanos(),
        ]);
    }

    /**
     * Directorios de personal (solo superadmin): Profesores / Prefectura / Administrativos.
     * Reutiliza una vista de índice filtrada por tipo_personal.
     */
    private static function renderDirectorio(Router $router, string $slug, string $tipo, string $titulo): void {
        self::requireSuperadmin();
        $router->renderAdmin('blog/personal/index', [
            'titulo'   => $titulo,
            'tipo'     => $tipo,
            'slug'     => $slug,
            'usuarios' => UsuarioBlog::porTipo($tipo),
        ]);
    }
    public static function profesores(Router $router)      { self::renderDirectorio($router, 'profesores', 'profesor', 'Profesores'); }
    public static function prefectura(Router $router)       { self::renderDirectorio($router, 'prefectura', 'prefecto', 'Prefectura'); }
    public static function administrativos(Router $router)  { self::renderDirectorio($router, 'administrativos', 'administrativo', 'Administrativos'); }

    // ── MÓDULO HORARIOS ─────────────────────────────────────────────────────────
    public static function horarios(Router $router) {
        self::requireModulo('horarios');
        header('Location: /dashboard/horarios/profesor');
        exit;
    }

    private static function horariosVista(Router $router, string $vista): void {
        self::requireModulo('horarios');

        // Entidades disponibles según la vista
        if ($vista === 'aula') {
            $entidades = Aula::todas();
            $titulo = 'Horarios por aula';
        } elseif ($vista === 'grupo') {
            $entidades = Grupo::todos();
            $titulo = 'Horarios por grupo';
        } else {
            $vista = 'profesor';
            $entidades = UsuarioBlog::porTipo('profesor');
            $titulo = 'Horarios por profesor';
        }

        $entidadId = (int)($_GET['id'] ?? 0);
        if (!$entidadId && !empty($entidades)) $entidadId = (int)$entidades[0]->id;

        $filas = [];
        if ($entidadId) {
            $filas = match ($vista) {
                'aula'  => Horario::porAula($entidadId),
                'grupo' => Horario::porGrupo($entidadId),
                default => Horario::porProfesor($entidadId),
            };
        }

        $router->renderAdmin('blog/horarios/index', [
            'titulo'    => $titulo,
            'vista'     => $vista,
            'entidades' => $entidades,
            'entidadId' => $entidadId,
            'periodos'  => Periodo::todos(),
            'matriz'    => Horario::comoMatriz($filas),
        ]);
    }
    public static function horariosProfesor(Router $router) { self::horariosVista($router, 'profesor'); }
    public static function horariosAula(Router $router)     { self::horariosVista($router, 'aula'); }
    public static function horariosGrupo(Router $router)    { self::horariosVista($router, 'grupo'); }

    /**
     * "Mi horario": el colaborador en sesión consulta su propio horario, solo lectura.
     * El horario lo carga dirección por CSV; las horas libres las usa el sistema para
     * proponer suplencias, así que aquí no hay nada que editar.
     */
    public static function miHorario(Router $router) {
        $sesion = self::requireAuth();
        $profId = (int)$sesion['id'];

        $profesor = UsuarioBlog::find($profId);
        if (!$profesor) { header('Location: /dashboard'); exit; }

        $router->renderAdmin('blog/horarios/mi-horario', [
            'titulo'   => 'Mi horario',
            'profesor' => $profesor,
            'periodos' => Periodo::todos(),
            'matriz'   => Horario::comoMatriz(Horario::porProfesor($profId)),
        ]);
    }

    // ── Importación de horarios por CSV (superadmin) ────────────────────────────

    /** Normaliza para comparar catálogos: minúsculas, sin acentos ni espacios de sobra. */
    private static function claveCatalogo(string $v): string {
        $v = trim(mb_strtolower($v, 'UTF-8'));
        $v = strtr($v, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
        return (string)preg_replace('/\s+/', ' ', $v);
    }

    /**
     * Lee el CSV subido y devuelve [filas, resumen]. Cada fila trae su estado
     * ('ok' | 'aviso' | 'error') y el motivo, para pintar la vista previa antes de escribir nada.
     */
    private static function parsearCsvHorarios(string $ruta): array {
        $cabeceraEsperada = ['profesor_email', 'dia', 'periodo', 'materia', 'grupo', 'aula'];

        // Índices de catálogo por nombre normalizado
        $profesores = [];
        foreach (UsuarioBlog::todosParaImportar() as $u) $profesores[self::claveCatalogo($u->email)] = $u;
        $periodos = [];
        foreach (Periodo::todos() as $p) {
            $periodos[self::claveCatalogo($p->etiqueta)] = $p;
            $periodos[self::claveCatalogo((string)$p->orden)] = $p;   // "3" además de "3ª hora"
        }
        $materias = [];
        foreach (Materia::todas() as $m) $materias[self::claveCatalogo($m->nombre)] = $m;
        $grupos = [];
        foreach (Grupo::todos() as $g)   $grupos[self::claveCatalogo($g->nombre)] = $g;
        $aulas = [];
        foreach (Aula::todas() as $a)    $aulas[self::claveCatalogo($a->nombre)] = $a;

        $fh = fopen($ruta, 'r');
        if (!$fh) return [[], ['error' => 'No se pudo leer el archivo.']];

        // BOM de Excel
        $primerBloque = fgets($fh);
        if ($primerBloque !== false && str_starts_with($primerBloque, "\xEF\xBB\xBF")) {
            $primerBloque = substr($primerBloque, 3);
        }
        rewind($fh);
        if ($primerBloque !== false) { fgets($fh); }

        $cabecera = array_map(fn($c) => self::claveCatalogo((string)$c), str_getcsv(trim((string)$primerBloque)));
        if (array_slice($cabecera, 0, 6) !== $cabeceraEsperada) {
            fclose($fh);
            return [[], ['error' => 'La cabecera debe ser exactamente: ' . implode(',', $cabeceraEsperada)]];
        }

        $filas   = [];
        $vistos  = [];   // profesor|dia|periodo → nº de línea, para detectar duplicados
        $ocupaAula = []; // aula|dia|periodo → profesor, para detectar choques
        $n = 1;

        while (($col = fgetcsv($fh)) !== false) {
            $n++;
            if ($col === [null] || (count($col) === 1 && trim((string)$col[0]) === '')) continue;

            $email   = trim((string)($col[0] ?? ''));
            $dia     = self::claveCatalogo((string)($col[1] ?? ''));
            $periodo = trim((string)($col[2] ?? ''));
            $materia = trim((string)($col[3] ?? ''));
            $grupo   = trim((string)($col[4] ?? ''));
            $aula    = trim((string)($col[5] ?? ''));

            $fila = [
                'linea' => $n, 'email' => $email, 'dia' => $dia, 'periodo' => $periodo,
                'materia' => $materia, 'grupo' => $grupo, 'aula' => $aula,
                'estado' => 'ok', 'motivo' => '',
                'profesor_id' => 0, 'periodo_id' => 0, 'materia_id' => 0, 'grupo_id' => 0, 'aula_id' => 0,
            ];
            $marcar = function (string $estado, string $motivo) use (&$fila) {
                // Un error nunca lo degrada un aviso posterior
                if ($fila['estado'] === 'error') return;
                $fila['estado'] = $estado;
                $fila['motivo'] = $motivo;
            };

            $prof = $profesores[self::claveCatalogo($email)] ?? null;
            if (!$prof)      $marcar('error', "No existe un colaborador con el correo «{$email}».");
            else             $fila['profesor_id'] = (int)$prof->id;

            if (!in_array($dia, Horario::DIAS, true)) {
                $marcar('error', "Día inválido «{$dia}»: usa " . implode(', ', Horario::DIAS) . '.');
            }

            $per = $periodos[self::claveCatalogo($periodo)] ?? null;
            if (!$per)                            $marcar('error', "No existe el periodo «{$periodo}».");
            elseif ((int)$per->es_receso === 1)   $marcar('error', "«{$per->etiqueta}» es un receso: no admite clase.");
            else                                  $fila['periodo_id'] = (int)$per->id;

            if ($materia === '') {
                $marcar('error', 'Falta la materia.');
            } elseif (!isset($materias[self::claveCatalogo($materia)])) {
                $marcar('error', "No existe la materia «{$materia}».");
            } else {
                $fila['materia_id'] = (int)$materias[self::claveCatalogo($materia)]->id;
            }

            // grupo y aula son opcionales (las FK admiten NULL)
            if ($grupo !== '') {
                if (!isset($grupos[self::claveCatalogo($grupo)])) $marcar('error', "No existe el grupo «{$grupo}».");
                else $fila['grupo_id'] = (int)$grupos[self::claveCatalogo($grupo)]->id;
            }
            if ($aula !== '') {
                if (!isset($aulas[self::claveCatalogo($aula)])) $marcar('error', "No existe el aula «{$aula}».");
                else $fila['aula_id'] = (int)$aulas[self::claveCatalogo($aula)]->id;
            }

            if ($fila['estado'] !== 'error') {
                $claveProf = $fila['profesor_id'] . '|' . $dia . '|' . $fila['periodo_id'];
                if (isset($vistos[$claveProf])) {
                    $marcar('error', "Duplicado: ese profesor ya tiene clase a esa hora en la línea {$vistos[$claveProf]}.");
                } else {
                    $vistos[$claveProf] = $n;
                }
            }
            if ($fila['estado'] !== 'error' && $fila['aula_id']) {
                $claveAula = $fila['aula_id'] . '|' . $dia . '|' . $fila['periodo_id'];
                if (isset($ocupaAula[$claveAula])) {
                    $marcar('aviso', "El aula ya está ocupada a esa hora en la línea {$ocupaAula[$claveAula]}.");
                } else {
                    $ocupaAula[$claveAula] = $n;
                }
            }

            $filas[] = $fila;
        }
        fclose($fh);

        $errores   = 0;
        $avisos    = 0;
        $profesIds = [];
        foreach ($filas as $f) {
            if ($f['estado'] === 'error') { $errores++; continue; }
            if ($f['estado'] === 'aviso') $avisos++;
            $profesIds[$f['profesor_id']] = true;
        }

        return [$filas, [
            'total'      => count($filas),
            'errores'    => $errores,
            'avisos'     => $avisos,
            'profesores' => count($profesIds),
        ]];
    }

    // ── CATÁLOGOS ACADÉMICOS · AULAS Y GRUPOS (superadmin) ────────────────────
    //
    // Aulas y grupos ya existían en la BD pero solo se podían tocar por SQL. Son
    // solo-superadmin porque `horarios` tiene UNIQUE por (día, periodo, aula) y por
    // (día, periodo, grupo): renombrar o borrar aquí repercute en todo el horario.

    public static function aulas(Router $router) {
        self::requireSuperadmin();
        $router->renderAdmin('blog/aulas/index', [
            'titulo' => 'Aulas',
            'aulas'  => Aula::todasConUso(),
        ]);
    }

    public static function crearAula(Router $router) {
        self::requireSuperadmin();
        $aula    = new Aula();
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $aula->sincronizar($_POST);
            $alertas = $aula->validar();
            if (empty($alertas['error'])) {
                $aula->guardar();
                header('Location: /dashboard/aulas?success=1');
                exit;
            }
        }

        $router->renderAdmin('blog/aulas/crear', [
            'titulo'  => 'Nueva aula',
            'aula'    => $aula,
            'alertas' => $alertas,
        ]);
    }

    public static function editarAula(Router $router) {
        self::requireSuperadmin();
        $aula = Aula::find((int)($_GET['id'] ?? 0));
        if (!$aula) { header('Location: /dashboard/aulas'); exit; }
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $aula->sincronizar($_POST);
            $alertas = $aula->validar();
            if (empty($alertas['error'])) {
                $aula->guardar();
                header('Location: /dashboard/aulas?edited=1');
                exit;
            }
        }

        $router->renderAdmin('blog/aulas/editar', [
            'titulo'  => 'Editar aula',
            'aula'    => $aula,
            'usos'    => Aula::usos((int)$aula->id),
            'alertas' => $alertas,
        ]);
    }

    public static function eliminarAula(Router $router) {
        self::requireSuperadmin();
        $id   = (int)($_POST['id'] ?? 0);
        $aula = $id ? Aula::find($id) : null;
        if (!$aula) { header('Location: /dashboard/aulas'); exit; }

        // Contar antes de borrar: es más claro que dejar reventar la FK
        $usos = Aula::usos($id);
        if (array_sum($usos)) {
            header('Location: /dashboard/aulas?enuso=' . array_sum($usos));
            exit;
        }

        $aula->eliminar();
        header('Location: /dashboard/aulas?deleted=1');
        exit;
    }

    public static function grupos(Router $router) {
        self::requireSuperadmin();
        $router->renderAdmin('blog/grupos/index', [
            'titulo' => 'Grupos',
            'grupos' => Grupo::todosConUso(),
        ]);
    }

    public static function crearGrupo(Router $router) {
        self::requireSuperadmin();
        $grupo = new Grupo();
        $grupo->orden = Grupo::siguienteOrden();   // sugerencia: el primer hueco libre
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $grupo->sincronizar($_POST);
            $alertas = $grupo->validar();
            if (empty($alertas['error'])) {
                $grupo->guardar();
                header('Location: /dashboard/grupos?success=1');
                exit;
            }
        }

        $router->renderAdmin('blog/grupos/crear', [
            'titulo'  => 'Nuevo grupo',
            'grupo'   => $grupo,
            'alertas' => $alertas,
        ]);
    }

    public static function editarGrupo(Router $router) {
        self::requireSuperadmin();
        $grupo = Grupo::find((int)($_GET['id'] ?? 0));
        if (!$grupo) { header('Location: /dashboard/grupos'); exit; }
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $grupo->sincronizar($_POST);
            $alertas = $grupo->validar();
            if (empty($alertas['error'])) {
                $grupo->guardar();
                header('Location: /dashboard/grupos?edited=1');
                exit;
            }
        }

        $router->renderAdmin('blog/grupos/editar', [
            'titulo'  => 'Editar grupo',
            'grupo'   => $grupo,
            'usos'    => Grupo::usos((int)$grupo->id),
            'alertas' => $alertas,
        ]);
    }

    public static function eliminarGrupo(Router $router) {
        self::requireSuperadmin();
        $id    = (int)($_POST['id'] ?? 0);
        $grupo = $id ? Grupo::find($id) : null;
        if (!$grupo) { header('Location: /dashboard/grupos'); exit; }

        $usos = Grupo::usos($id);
        if (array_sum($usos)) {
            header('Location: /dashboard/grupos?enuso=' . array_sum($usos));
            exit;
        }

        $grupo->eliminar();
        header('Location: /dashboard/grupos?deleted=1');
        exit;
    }

    /**
     * Carga de horarios por CSV. Dos pasos: subir → vista previa → confirmar.
     * El archivo reemplaza el horario completo de los profesores que aparecen en él.
     */
    public static function importarHorarios(Router $router) {
        self::requireSuperadmin();

        // Descarga de la plantilla de ejemplo
        if (isset($_GET['plantilla'])) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="horarios-plantilla.csv"');
            echo "\xEF\xBB\xBF";
            echo "profesor_email,dia,periodo,materia,grupo,aula\n";
            $prof = UsuarioBlog::porTipo('profesor');
            $ej   = $prof[0]->email ?? 'nombre.apellido@bilbao.edu.mx';
            echo "{$ej},lunes,1,Matemáticas,1A,A-101\n";
            echo "{$ej},lunes,2,Matemáticas,2B,A-101\n";
            exit;
        }

        $filas    = $_SESSION['horarios_import']['filas'] ?? [];
        $resumen  = $_SESSION['horarios_import']['resumen'] ?? [];
        $alertas  = [];
        $guardado = 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['_accion'] ?? 'previsualizar';

            if ($accion === 'cancelar') {
                unset($_SESSION['horarios_import']);
                header('Location: /dashboard/horarios/importar');
                exit;
            }

            if ($accion === 'confirmar') {
                $filas = $_SESSION['horarios_import']['filas'] ?? [];
                $validas = array_values(array_filter($filas, fn($f) => $f['estado'] !== 'error'));
                if (!$validas) {
                    $alertas['error'][] = 'No hay ninguna fila válida que importar.';
                } else {
                    $db = UsuarioBlog::getDB();
                    $db->begin_transaction();
                    try {
                        Horario::borrarDeProfesores(array_column($validas, 'profesor_id'));
                        foreach ($validas as $f) {
                            $h = new Horario([
                                'dia'         => $f['dia'],
                                'periodo_id'  => $f['periodo_id'],
                                'profesor_id' => $f['profesor_id'],
                                'grupo_id'    => $f['grupo_id'] ?: null,
                                'aula_id'     => $f['aula_id'] ?: null,
                                'materia_id'  => $f['materia_id'] ?: null,
                            ]);
                            $h->guardar();
                            $guardado++;
                        }
                        $db->commit();

                        // El horario es suyo: cada profesor afectado se entera por su campana
                        foreach (array_unique(array_column($validas, 'profesor_id')) as $pid) {
                            Notificacion::nueva(
                                (int)$pid,
                                'horario_actualizado',
                                'Tu horario se actualizó tras una importación. Revísalo por si algo no cuadra.',
                                null, null, 'horarios', 'aviso',
                                '/dashboard/horarios/mi-horario'
                            );
                        }

                        unset($_SESSION['horarios_import']);
                        header('Location: /dashboard/horarios/importar?ok=' . $guardado);
                        exit;
                    } catch (\Throwable $e) {
                        $db->rollback();
                        $alertas['error'][] = 'No se pudo importar: ' . $e->getMessage();
                    }
                }
            }

            if ($accion === 'previsualizar') {
                $archivo = $_FILES['csv'] ?? null;
                if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    $alertas['error'][] = 'Elige un archivo CSV.';
                } elseif ($archivo['size'] > 2 * 1024 * 1024) {
                    $alertas['error'][] = 'El archivo supera los 2 MB.';
                } else {
                    [$filas, $resumen] = self::parsearCsvHorarios($archivo['tmp_name']);
                    if (!empty($resumen['error'])) {
                        $alertas['error'][] = $resumen['error'];
                        $filas = []; $resumen = [];
                    } elseif (!$filas) {
                        $alertas['error'][] = 'El archivo no tiene filas de datos.';
                    } else {
                        $_SESSION['horarios_import'] = ['filas' => $filas, 'resumen' => $resumen];
                    }
                }
            }
        }

        $router->renderAdmin('blog/horarios/importar', [
            'titulo'    => 'Importar horarios',
            'filas'     => $filas,
            'resumen'   => $resumen,
            'alertas'   => $alertas,
            'importado' => (int)($_GET['ok'] ?? 0),
        ]);
    }

    /**
     * Resuelve `puede_suplir` desde el POST del formulario de usuarios.
     * La exclusión ("No puede suplir a otros profesores") solo tiene sentido para
     * el personal docente: si no se marcó el tipo 'profesor', siempre queda en 1.
     */
    private static function resolverPuedeSuplir(array $post): int {
        $tipos = array_map('trim', (array)($post['tipo_personal'] ?? []));
        if (!in_array('profesor', $tipos, true)) return 1;
        return empty($post['no_puede_suplir']) ? 1 : 0;
    }

    public static function crearUsuario(Router $router) {
        self::requireAuth();
        self::requireAdmin();
        $usuario = new UsuarioBlog();
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario->sincronizar($_POST);
            // Toggle "No puede suplir a otros profesores" (checkbox invertido).
            // Solo aplica al personal docente: sin el tipo 'profesor' nunca se excluye.
            $usuario->puede_suplir = self::resolverPuedeSuplir($_POST);
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
                            $nuevoId = (int)($resultado['id'] ?? 0);
                            UsuarioBlog::guardarFechaNacimiento($nuevoId, $usuario->fecha_nacimiento);
                            UsuarioBlog::guardarAtributos($nuevoId, $usuario->rol_redaccion, $usuario->tipo_personal);
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
        $esEditor = ($sesion['rol'] ?? '') === 'usuario';

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

            // Editores no pueden cambiar su rol ni sus atributos de personal
            if ($esEditor) {
                $usuario->rol = 'usuario';
            } else {
                $usuario->puede_suplir = self::resolverPuedeSuplir($_POST);
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
                            UsuarioBlog::guardarFechaNacimiento((int)$usuario->id, $usuario->fecha_nacimiento);
                            UsuarioBlog::guardarAtributos((int)$usuario->id, $usuario->rol_redaccion, $usuario->tipo_personal);
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
        $esEditor = ($usuario['rol'] ?? '') === 'usuario';
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
        $esEditor            = ($usuario['rol'] ?? '') === 'usuario';
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
        $esEditor = ($usuario['rol'] ?? '') === 'usuario';
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
        if (self::esAjax()) return self::json(['ok' => true]);
        header('Location: /dashboard/notificaciones');
        exit;
    }

    public static function marcarTodasLeidas(Router $router) {
        $usuario = self::requireAuth();
        Notificacion::marcarTodasLeidas((int)$usuario['id']);
        header('Location: /dashboard/notificaciones?marcadas=1');
        exit;
    }

    /**
     * Borra una notificación. Se borra YA, no se difiere: si el usuario navega o
     * recarga a mitad de la cuenta atrás del "Deshacer", el estado final ya está
     * persistido. La respuesta devuelve la fila completa para poder restaurarla.
     */
    public static function eliminarNotificacion(Router $router) {
        $usuario = self::requireAuth();
        $uid     = (int)$usuario['id'];
        $id      = (int)($_POST['id'] ?? 0);

        $notif = $id ? Notificacion::delUsuario($id, $uid) : null;
        if (!$notif) {
            if (self::esAjax()) return self::json(['ok' => false, 'error' => 'No encontrada'], 404);
            header('Location: /dashboard/notificaciones');
            exit;
        }

        Notificacion::borrarDeUsuario($id, $uid);

        if (self::esAjax()) {
            return self::json(['ok' => true, 'notif' => [
                'tipo'            => $notif->tipo,
                'mensaje'         => $notif->mensaje,
                'modulo'          => $notif->modulo,
                'nivel'           => $notif->nivel,
                'enlace'          => $notif->enlace,
                'referencia_id'   => $notif->referencia_id,
                'referencia_tipo' => $notif->referencia_tipo,
            ]]);
        }
        header('Location: /dashboard/notificaciones?eliminada=1');
        exit;
    }

    /** Reinserta la notificación que el usuario acaba de borrar por error. */
    public static function restaurarNotificacion(Router $router) {
        $usuario = self::requireAuth();
        Notificacion::restaurar((int)$usuario['id'], [
            'tipo'            => $_POST['tipo']            ?? 'restaurada',
            'mensaje'         => $_POST['mensaje']         ?? '',
            'modulo'          => $_POST['modulo']          ?? 'general',
            'nivel'           => $_POST['nivel']           ?? 'info',
            'enlace'          => $_POST['enlace']          ?? null,
            'referencia_id'   => $_POST['referencia_id']   ?? null,
            'referencia_tipo' => $_POST['referencia_tipo'] ?? null,
        ]);
        if (self::esAjax()) return self::json(['ok' => true]);
        header('Location: /dashboard/notificaciones');
        exit;
    }

    /** Vacía la bandeja completa (tras el modal de confirmación). */
    public static function limpiarNotificaciones(Router $router) {
        $usuario = self::requireAuth();
        $n = Notificacion::borrarTodasDeUsuario((int)$usuario['id']);
        header('Location: /dashboard/notificaciones?limpiadas=' . $n);
        exit;
    }

    // ── TESTIMONIALES ──────────────────────────────────────────────────────────

    public static function testimoniales(Router $router) {
        self::requireRevisor();

        $testimoniales = Testimonial::todos();

        $router->renderAdmin('blog/testimoniales/index', [
            'titulo'        => 'Testimoniales',
            'testimoniales' => $testimoniales,
        ]);
    }

    public static function aprobarTestimonial(Router $router) {
        self::requireRevisor();

        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db = Testimonial::getDB();
            $db->query("UPDATE testimoniales SET aprobado=1 WHERE id={$id}");
        }
        header('Location: /dashboard/testimoniales?aprobado=1');
        exit;
    }

    public static function rechazarTestimonial(Router $router) {
        self::requireRevisor();

        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db = Testimonial::getDB();
            $db->query("DELETE FROM testimoniales WHERE id={$id}");
        }
        header('Location: /dashboard/testimoniales?rechazado=1');
        exit;
    }
}
