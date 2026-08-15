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

        $extra_head = three_js_tag();
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

    /** ¿El usuario en sesión es administrador (acceso total)? */
    private static function esAdmin(): bool {
        return ($_SESSION['blog_usuario']['rol'] ?? '') === 'administrador';
    }

    /** Guard de operaciones sensibles/destructivas y de tableros analíticos. */
    private static function requireAdmin(): void {
        self::requireAuth();
        if (!self::esAdmin()) {
            header('Location: /dashboard');
            exit;
        }
    }

    /**
     * Módulos que no se asignan porque los tiene todo el mundo. Soporte técnico es la
     * vía para pedir ayuda cuando algo del panel falla: condicionarla a un permiso
     * dejaría sin ella justo a quien no puede entrar a arreglarlo por su cuenta.
     */
    private const MODULOS_TRANSVERSALES = ['soporte'];

    /** ¿El usuario en sesión puede acceder al módulo indicado? El admin accede a todos. */
    private static function puede(string $modulo): bool {
        $u = $_SESSION['blog_usuario'] ?? null;
        if (!$u) return false;
        if (\in_array($modulo, self::MODULOS_TRANSVERSALES, true)) return true;
        if (($u['rol'] ?? '') === 'administrador') return true;
        $lista = array_filter(array_map('trim', explode(',', (string) ($u['modulos'] ?? ''))));
        return \in_array($modulo, $lista, true);
    }

    /**
     * ¿Puede validar contenido editorial (revisiones + testimoniales)?
     * El admin siempre; un 'usuario' solo si es revisor con módulo redaccion.
     */
    private static function puedeRevisar(): bool {
        $u = $_SESSION['blog_usuario'] ?? null;
        if (!$u) return false;
        if (($u['rol'] ?? '') === 'administrador') return true;
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

    /**
     * Guard de módulo: redirige al home de módulos si no tiene acceso.
     * El `?sinacceso=` no es decorativo: una notificación puede apuntar a un módulo
     * que el usuario ya perdió, y el rebote mudo parecía que el enlace no hacía nada.
     */
    private static function requireModulo(string $modulo): void {
        self::requireAuth();
        if (!self::puede($modulo)) {
            header('Location: /dashboard?sinacceso=' . urlencode($modulo));
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
                        // CSV crudo, igual que `modulos`. Lo necesita nivelesAlcance()
                        // y su espejo en las vistas, que no pueden consultar la BD.
                        // Se guarda el DATO, no la decisión: si mañana cambia la regla
                        // de alcance no quedan sesiones vivas con una versión vieja.
                        'niveles'       => $usuario->niveles ?? '',
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

        // El login estrena el bosque de la landing. El layout admin no inyecta
        // Three.js por defecto (ninguna otra vista del panel lo usa).
        $router->renderAdmin('blog/login', [
            'titulo'     => 'Iniciar Sesión',
            'alertas'    => $alertas,
            'errorCampo' => $errorCampo,
            'extra_head' => three_js_tag(),
        ]);
    }

    public static function logout(Router $router) {
        unset($_SESSION['blog_usuario']);
        header('Location: /login');
        exit;
    }

    // ── HOME DE MÓDULOS ────────────────────────────────────────────────────────
    public static function home(Router $router) {
        self::requireAuth();

        // Al entrar al panel se emiten los recordatorios de coberturas cuya fecha
        // ya pasó sin confirmar. Es el disparador del ciclo: el proyecto no tiene
        // cron, así que la comprobación va aquí (indexada y con marca anti-duplicado).
        self::recordarCoberturasVencidas();
        // Mismo patrón: sin cron, el mantenimiento cuelga de la carga del panel.
        self::purgarJustificantes();

        // Estado del día: lo que le toca a ESTE usuario. Cada cifra enlaza a su
        // destino y la vista oculta las que están a cero — un panel de estado
        // lleno de ceros no informa, solo hace ruido.
        $uid = (int)($_SESSION['blog_usuario']['id'] ?? 0);
        $pendientes = [
            'notificaciones' => Notificacion::noLeidasPorUsuario($uid),
            'coberturas'     => count(SuplenciaHora::porValidarDeSuplente($uid)),
            // Ausencias todavía sin suplente: solo tiene sentido para quien agenda.
            'sinSuplente'    => self::puedeCoordinar() ? (Suplencia::conteos()['solicitada'] ?? 0) : 0,
        ];

        $router->renderAdmin('blog/home', [
            'titulo'        => 'Inicio',
            'modulos'       => self::modulosDisponibles(),
            // Cumpleaños y eventos los ve todo el mundo: son información de
            // convivencia. El módulo `usuarios` controla quién los *edita*.
            'cumpleanosAll' => UsuarioBlog::conCumpleanos(),
            'eventos'       => Evento::todos(),
            'pendientes'    => $pendientes,
            // El hero lleva el mismo bosque WebGL que el login. El layout del panel no
            // carga Three.js por defecto (solo lo necesita esta vista), así que se
            // inyecta aquí igual que en login().
            'extra_head'    => three_js_tag(),
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
    /**
     * ¿Coordina la operación académica (admin, prefectura o dirección)?
     * Es quien puede ver datos de terceros: horarios ajenos, motivos de ausencia,
     * justificantes. Un profesor raso solo ve lo suyo.
     */
    private static function puedeCoordinar(): bool {
        if (self::esAdmin()) return true;
        return (bool)array_intersect(\Model\UsuarioBlog::TIPOS_COORDINAN, self::sesionTipos());
    }

    /** ¿Puede agendar suplencias (prefectura/dirección/admin)? */
    private static function puedeAgendar(): bool {
        return self::puedeCoordinar();
    }

    /** ¿Es directivo? Coordina y resuelve justificantes, pero no edita configuración. */
    private static function esDirectivo(): bool {
        return in_array('directivo', self::sesionTipos(), true);
    }

    /**
     * El parte médico es competencia de DIRECCIÓN, y de nadie más.
     *
     * Prefectura coordina la ausencia —ve que hay justificante o que falta, y con eso
     * decide— pero no accede al documento ni a su nombre: es un dato de salud y su
     * revisión es una decisión de dirección. Por eso este guard NO es puedeCoordinar().
     */
    private static function puedeVerJustificante(): bool {
        return self::esAdmin() || self::esDirectivo();
    }

    // ── Alcance por nivel educativo ─────────────────────────────────────────────

    /**
     * Niveles a los que se acota lo que ve este usuario. **[] = SIN FILTRO.**
     *
     * Tres cortes, y los tres devuelven []:
     *   · admin          → ve todo el colegio, siempre.
     *   · no directivo   → `usuarios.niveles` en un profesor significa "IMPARTE estos
     *                      niveles", no "gestiona estos niveles". Este `return` es lo
     *                      ÚNICO que mantiene separados los dos significados de la
     *                      columna, así que no se toca sin leer normalizarNiveles().
     *   · los cinco      → declararlos todos es lo mismo que no declarar ninguno.
     *
     * ⚠️ Comprueba el TIPO, no la columna. Un UPDATE directo puede meterle niveles a
     * un prefecto aunque el formulario los rechace; invertir la comprobación abriría
     * una vía de escalada por BD.
     *
     * Espeja blog_modulos_niveles() de views/blog/_modulos.php.
     */
    private static function nivelesAlcance(): array {
        static $memo = null;
        if ($memo !== null) return $memo;

        if (self::esAdmin() || !self::esDirectivo()) return $memo = [];

        $raw   = (string)($_SESSION['blog_usuario']['niveles'] ?? '');
        $lista = array_filter(array_map('trim', explode(',', $raw)));
        // Lista blanca contra el vocabulario canónico: ordena y neutraliza una sesión
        // manipulada de paso.
        $out = array_values(array_intersect(\Model\Materia::NIVELES, $lista));
        return $memo = (count($out) === count(\Model\Materia::NIVELES)) ? [] : $out;
    }

    /**
     * Alcance efectivo de la pantalla: el del usuario, opcionalmente estrechado por
     * `?nivel=`. **Nunca lo amplía** — para una dirección de Primaria,
     * `?nivel=Secundaria` es un no-op y no una escalada.
     */
    private static function nivelesVista(): array {
        $base = self::nivelesAlcance();
        $q    = trim($_GET['nivel'] ?? '');
        if ($q === '' || !in_array($q, \Model\Materia::NIVELES, true)) return $base;
        if ($base && !in_array($q, $base, true))                       return $base;
        return [$q];
    }

    /**
     * ¿Esta suplencia cae dentro del alcance del usuario?
     *
     * Una suplencia SIN horas todavía pasa siempre (fail-open): no tiene nivel, y
     * bloquearla dejaría un registro que nadie salvo un admin podría reparar — justo
     * el que hay que agendar.
     */
    private static function enAlcance(int $suplenciaId): bool {
        $a = self::nivelesAlcance();
        if (!$a) return true;
        $n = Suplencia::nivelesDeSuplencia($suplenciaId);
        return !$n || (bool)array_intersect($a, $n);
    }

    /**
     * Guard de OBJETO. Filtrar un listado sin cerrar el acceso por `?id=` no es
     * seguridad, es maquillaje: hay que llamarlo también en los POST.
     */
    private static function requireAlcance(int $suplenciaId): void {
        if (self::enAlcance($suplenciaId)) return;
        if (self::esAjax()) self::json(['error' => 'Fuera de tu nivel'], 403);
        header('Location: /dashboard/suplencias?sinacceso=nivel');
        exit;
    }

    /**
     * ¿Este usuario tiene acceso de SOLO LECTURA a la configuración del colegio?
     *
     * Dirección necesita ver el claustro y los horarios para coordinar, pero no debe
     * poder alterarlos: quien reparte suplencias no es quien decide quién da qué clase.
     * Se aplica a las escrituras de usuarios, horarios y catálogos; las suplencias y los
     * justificantes —que sí son su trabajo— quedan fuera.
     */
    private static function soloLectura(): bool {
        return !self::esAdmin() && self::esDirectivo();
    }

    /**
     * Guard de escritura sobre la configuración. Un directivo llega hasta la pantalla
     * (la ve entera) pero no puede enviar el formulario.
     */
    private static function requireEscritura(string $modulo): void {
        self::requireModulo($modulo);
        if (self::soloLectura()) {
            if (self::esAjax()) self::json(['error' => 'Tu perfil es de solo lectura'], 403);
            header('Location: /dashboard/' . $modulo . '?sololectura=1');
            exit;
        }
    }

    /**
     * ¿Da clase? Decide quién puede faltar a una clase y quién puede cubrirla, y por
     * tanto quién entra a "Solicitar" y a su histórico de coberturas. Va por
     * `tipo_personal` y no por rol: un admin de sistemas no imparte nada, y `prefecto`
     * —tipo excluyente— coordina las ausencias del claustro pero no tiene ausencias
     * propias. Espeja blog_modulos_imparte() de views/blog/_modulos.php.
     */
    private static function imparte(): bool {
        return in_array('profesor', self::sesionTipos(), true);
    }

    /**
     * Guard de las pantallas personales de suplencias (solicitar, histórico propio).
     *
     * Rebota al home, NO a /dashboard/suplencias: quien no imparte y tampoco coordina
     * (un administrativo con el módulo) se quedaría rebotando entre las dos, porque la
     * agenda a su vez lo manda aquí.
     */
    private static function requireImparte(): void {
        self::requireModulo('suplencias');
        if (!self::imparte()) {
            header('Location: /dashboard?sinacceso=suplencias');
            exit;
        }
    }

    /**
     * Fecha llegada por query (?fecha=YYYY-MM-DD) desde el calendario del listado.
     * Devuelve null si no viene o no es una fecha real, para que la vista caiga a
     * su valor por defecto en vez de pintar basura en el datepicker.
     */
    private static function fechaDeQuery(): ?string {
        $f = trim($_GET['fecha'] ?? '');
        if ($f === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $f)) return null;
        [$y, $m, $d] = array_map('intval', explode('-', $f));
        return checkdate($m, $d, $y) ? $f : null;
    }

    /**
     * Agenda de suplencias: la herramienta de coordinación.
     *
     * Es de quien COORDINA, y solo suya. El calendario, el buscador y la tabla existen
     * para abrir y repartir las ausencias del claustro; un profesor no reparte nada, y
     * lo que necesita —sus ausencias y sus coberturas— vive en su histórico. Antes esta
     * pantalla se le servía filtrada por `mias`, duplicando el histórico con la mitad
     * de la información y una barra de acciones que no podía usar.
     */
    public static function suplencias(Router $router) {
        self::requireModulo('suplencias');
        if (!self::puedeAgendar()) {
            header('Location: ' . (self::imparte() ? '/dashboard/suplencias/mis-coberturas' : '/dashboard?sinacceso=suplencias'));
            exit;
        }

        $filtros = [
            'q'      => trim($_GET['q'] ?? ''),
            'estado' => $_GET['estado'] ?? '',
            'origen' => $_GET['origen'] ?? '',
            'desde'  => $_GET['desde'] ?? '',
            'hasta'  => $_GET['hasta'] ?? '',
        ];
        // Alcance de las direcciones por nivel. El MISMO array a los tres, o la tabla,
        // las tarjetas y el calendario se contradicen entre sí.
        $niv = self::nivelesVista();

        $router->renderAdmin('blog/suplencias/index', [
            'titulo'       => 'Agenda de suplencias',
            'suplencias'   => Suplencia::listar($filtros + ['niveles' => $niv]),
            'conteos'      => Suplencia::conteos(0, $niv),
            'resumenDias'  => Suplencia::resumenDiario(0, $niv),
            'filtros'      => $filtros,
            'puedeAgendar' => true,
            'alcance'      => self::nivelesAlcance(),
        ]);
    }

    /** Guard de los endpoints que exponen datos de terceros (horarios, ranking del claustro). */
    private static function requireAgendar(): void {
        self::requireModulo('suplencias');
        if (!self::puedeAgendar()) {
            if (self::esAjax()) self::json(['error' => 'Sin permiso'], 403);
            header('Location: /dashboard/suplencias');
            exit;
        }
    }

    /** Endpoint JSON: autocompletado de profesores (ausente). */
    public static function buscarColaboradores(Router $router) {
        self::requireAgendar();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(UsuarioBlog::buscar($_GET['q'] ?? '', 8));
        exit;
    }

    /**
     * Endpoint JSON: sugerencias de suplente para una fecha+periodo (algoritmo).
     * Solo prefectura/admin: devuelve el ranking de coberturas y los motivos de
     * bloqueo de todo el claustro.
     */
    public static function sugerirSuplentes(Router $router) {
        self::requireAgendar();
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
     *
     * Un profesor raso solo puede pedir **su propio** horario (lo necesita para
     * marcar las horas de su ausencia en /solicitar). Antes aceptaba cualquier
     * `?profesor=ID` con solo tener el módulo, y devolvía la semana completa de
     * cualquier compañero — justo lo que la vista de Horarios le niega.
     */
    public static function horarioProfesorJson(Router $router) {
        self::requireModulo('suplencias');
        self::rejillaProfesorJson();
    }

    /**
     * El mismo horario semanal, para el paso 1 de un intercambio: el profesor marca en
     * su propia rejilla la clase que no va a poder dar.
     *
     * Es una puerta aparte y no un reuso de la de Suplencias porque el guard de aquella
     * es `requireModulo('suplencias')`, y un profesor con solo el módulo `swaps` recibía
     * un 403 al abrir el formulario. El cuerpo —y por tanto la forma del JSON— es el
     * mismo, así que .supl-week pinta las dos igual.
     */
    public static function horarioSwapJson(Router $router) {
        self::requireModulo('swaps');
        if (!self::imparte() && !self::puedeCoordinar()) self::json(['error' => 'Sin acceso'], 403);
        self::rejillaProfesorJson();
    }

    /**
     * Cuerpo compartido de los dos endpoints anteriores. Emite el JSON y termina.
     *
     * Quien no coordina solo puede pedir SU PROPIO horario. Antes esta comprobación
     * vivía en horarioProfesorJson(), que aceptaba cualquier `?profesor=ID` con solo
     * tener el módulo y devolvía la semana completa de cualquier compañero — justo lo
     * que la vista de Horarios le niega.
     */
    private static function rejillaProfesorJson(): void {
        header('Content-Type: application/json; charset=utf-8');

        $profId = (int)($_GET['profesor'] ?? 0);
        $fecha  = trim($_GET['fecha'] ?? '');
        if (!$profId) { echo json_encode(['error' => 'profesor requerido']); exit; }

        if (!self::puedeCoordinar() && $profId !== (int)($_SESSION['blog_usuario']['id'] ?? 0)) {
            http_response_code(403);
            echo json_encode(['error' => 'Sin permiso para ver ese horario']);
            exit;
        }

        $dow = $fecha !== '' ? (int)date('N', strtotime($fecha)) : 0;
        $dia = \Model\Horario::DIAS[$dow - 1] ?? null;   // null en fin de semana

        // El eje y la colocación de celdas los calcula el servidor (Horario::rejilla()),
        // no el JS: es la misma implementación que usa la rejilla del módulo Horarios, y
        // así las dos no pueden divergir cuando un profesor cruza niveles.
        //
        // `ini`/`fin` = la hora que se quiere cubrir, en el preview de un candidato. Se
        // inyecta como corte porque el eje se construye con las clases DEL CANDIDATO: si
        // la hora a cubrir es de un nivel que él no imparte, no sería frontera suya y el
        // "Cubriría aquí" caería sobre un bloque libre de dos horas.
        $extra = [];
        $hIni = self::horaDeQuery($_GET['ini'] ?? '');
        $hFin = self::horaDeQuery($_GET['fin'] ?? '');
        if ($hIni && $hFin && $hIni < $hFin) $extra[] = [$hIni, $hFin];

        $filas  = Horario::porProfesor($profId);
        $ambito = Horario::ambito($filas, UsuarioBlog::nivelesDe($profId));
        $r      = Horario::rejilla($filas, Periodo::deNiveles($ambito['niveles']), [
            // Con un rango objetivo hay que comprimir sí o sí: es el único camino que
            // mete `extra` en el eje.
            'comprimir'  => $ambito['comprimir'] || $extra !== [],
            'declarados' => $ambito['declarados'],
            'extra'      => $extra,
        ]);

        $tramos = array_map(fn($t) => [
            'inicio'   => substr($t['inicio'], 0, 5),
            'fin'      => substr($t['fin'], 0, 5),
            'minutos'  => $t['minutos'],
            'alto'     => $t['alto'],
            'hueco'    => !empty($t['hueco']),
            'etiqueta' => $t['etiqueta'],
            'nivel'    => $t['nivel'] ?? '',   // distingue dos filas con el mismo rótulo
        ], $r['tramos']);

        // Celdas ya colocadas, con su span y su color: el JS solo pinta.
        $rejilla = [];
        foreach ($r['rejilla'] as $d => $celdas) {
            foreach ($celdas as $c) {
                $celda = [
                    'tramo'  => $c['tramo'],
                    'span'   => $c['span'],
                    'tipo'   => $c['tipo'],
                    'inicio' => substr($c['inicio'], 0, 5),
                    'fin'    => substr($c['fin'], 0, 5),
                ];
                if ($c['tipo'] === 'receso') {
                    $celda['nivel'] = $c['nivel'];
                } elseif ($c['tipo'] === 'clase') {
                    $h = $c['horario'];
                    $b = $c['bloque'] ?? null;
                    $esGuardia = ($h->tipo ?? 'clase') === 'guardia';
                    $celda += [
                        // `horario_id` es la FILA, no el periodo: es lo que referencia un
                        // intercambio (`swap_clases.horario_origen_id`), que cambia una
                        // clase concreta y no "la 3ª hora del lunes".
                        'horario_id' => (int)$h->id,
                        'periodo_id' => (int)$h->periodo_id,
                        'nivel'      => $h->periodo_nivel,
                        // Una guardia se muestra como tal: ocupa igual que una clase y
                        // también se puede suplir, pero no tiene materia ni grupo.
                        'guardia'    => $esGuardia,
                        'lugar'      => $h->lugar_nombre,
                        'materia'    => $esGuardia ? 'Guardia' : $h->materia,
                        'materia_id' => (int)$h->materia_id,
                        // Con clase conjunta son varios grupos; con coteaching, varios docentes.
                        'grupo'      => $b ? implode(' · ', $b->nombresGrupos()) : $h->grupo_nombre,
                        'grupo_id'   => (int)$h->grupo_id,
                        'docentes'   => $b ? $b->nombresDocentes() : array_filter([$h->profesor_nombre]),
                        'aula'       => $esGuardia ? $h->lugar_nombre : $h->aula_nombre,
                        'aula_id'    => (int)$h->aula_id,
                        'color'      => self::colorMateria($esGuardia ? 'Guardia' : $h->materia, $h->color),
                        // Opciones simultáneas de una materia dividida.
                        'opciones'   => array_map(fn($o) => [
                            'materia' => $o->principal->materia,
                            'aula'    => $o->principal->aula_nombre,
                            'color'   => self::colorMateria($o->principal->materia, $o->principal->color),
                        ], $c['opciones'] ?? []),
                        'conflicto'  => count($c['conflicto']),
                        'ajeno'      => !empty($c['ajeno']),
                    ];
                }
                $rejilla[$d][] = $celda;
            }
        }

        $prof = UsuarioBlog::find($profId);
        echo json_encode([
            'profesor'     => ['id' => $profId, 'nombre' => $prof->nombre ?? '', 'avatar' => $prof->avatar ?? ''],
            'dia'          => $dia,
            'niveles'      => $ambito['niveles'],
            'declarados'   => $ambito['declarados'],
            'discrepantes' => $ambito['discrepantes'],
            'tramos'       => $tramos,
            'rejilla'      => $rejilla,
        ]);
        exit;
    }

    /** 'HH:MM' o 'HH:MM:SS' del query string → 'HH:MM:SS', o null si no es una hora. */
    private static function horaDeQuery(string $v): ?string {
        $v = trim($v);
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(:([0-5]\d))?$/', $v, $m)) return null;
        return sprintf('%s:%s:%s', $m[1], $m[2], $m[4] ?? '00');
    }

    /**
     * Color de un bloque de horario. Fuente ÚNICA: la consumen el JSON de suplencias,
     * `.hor-grid` y el editor, para que no puedan desincronizarse — antes
     * admin-supl-week.js replicaba a mano el crc32() y la paleta de _grid.php, y esa
     * duplicación ya se desincronizó una vez.
     *
     * @param ?string $forzado `horarios.color`, el elegido a mano en el editor. Manda
     *        sobre el automático; si no es un hex válido se ignora en silencio (un dato
     *        sucio no debe dejar la celda sin pintar).
     */
    public static function colorMateria(?string $materia, ?string $forzado = null): array {
        $pal    = ['#4285f4', '#46bdc6', '#8ac926', '#f5b400', '#fc6722', '#aa2296', '#4267ac', '#34a853', '#ea075a', '#e51022'];
        $claros = ['#f5b400', '#8ac926', '#46bdc6'];   // sobre estos el blanco no contrasta
        // El color elegido a mano en el editor manda sobre el automático
        $c = ($forzado && preg_match('/^#[0-9a-fA-F]{6}$/', $forzado))
            ? strtolower($forzado)
            : ($materia ? $pal[abs(crc32($materia)) % count($pal)] : '#94a3b8');
        return ['hex' => $c, 'oscuro' => in_array($c, $claros, true)];
    }

    /** La paleta del horario, para los selectores de color. Mismo orden que arriba. */
    public const PALETA_HORARIO = ['#4285f4', '#46bdc6', '#8ac926', '#f5b400', '#fc6722', '#aa2296', '#4267ac', '#34a853', '#ea075a', '#e51022'];

    /**
     * Acompañantes (coteaching) por bloque, sin contar al titular. Dos es lo que se ve
     * en los horarios reales de Peñalara (`Dulce\Laura` y similares); el tope existe
     * para que la casilla siga siendo legible, no por una restricción de la BD.
     */
    public const MAX_ACOMPANANTES = 2;

    /**
     * Carpeta física de los justificantes: **fuera de `public/`**.
     *
     * Antes vivían en `public/build/assets/suplencias/`, que sirve el shim de
     * `index.php` — y ese shim corre ANTES de `includes/app.php`, o sea sin sesión y
     * sin permisos: cualquiera con la URL descargaba el parte médico. Ahora el único
     * camino es descargarJustificante(), que sí comprueba quién pregunta.
     */
    private static function dirJustificantes(): string {
        return __DIR__ . '/../storage/justificantes/';
    }

    /** Carpeta pública heredada. Solo para leer y borrar lo que quedó ahí. */
    private static function dirJustificantesLegacy(): string {
        return __DIR__ . '/../public/build/assets/suplencias/';
    }

    /**
     * Ruta física de un justificante a partir de lo guardado en BD, o null si no está.
     *
     * Los registros nuevos guardan solo el nombre del archivo; los heredados guardan
     * la URL pública antigua. Se resuelven los dos, siempre con realpath + prefijo
     * comprobado: nunca hay que fiarse de un path que viene de la BD.
     */
    private static function rutaJustificante(?string $valor): ?string {
        if (!$valor) return null;
        $nombre = basename($valor);
        foreach ([self::dirJustificantes(), self::dirJustificantesLegacy()] as $dir) {
            $base = realpath($dir);
            $file = realpath($dir . $nombre);
            if ($base === false || $file === false) continue;
            if (!str_starts_with($file, $base . DIRECTORY_SEPARATOR)) continue;
            if (is_file($file)) return $file;
        }
        return null;
    }

    /**
     * Sube un justificante (PDF o imagen). Devuelve el NOMBRE del archivo o null —
     * ya no una URL pública: el archivo no es alcanzable por HTTP.
     * El límite vive en Suplencia::MAX_JUSTIFICANTE_MB y lo leen también las vistas,
     * para que cliente y servidor no puedan desincronizarse.
     */
    private static function subirJustificante(): ?string {
        if (empty($_FILES['justificante'])) return null;

        $err = $_FILES['justificante']['error'];
        if ($err !== UPLOAD_ERR_OK) {
            // Con 50 MB es fácil chocar contra upload_max_filesize / post_max_size
            // antes de llegar aquí: sin este mensaje el fallo era mudo.
            if (in_array($err, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                Suplencia::setAlerta('error',
                    'El archivo excede el límite del servidor. Pide que suban `upload_max_filesize` y '
                    . '`post_max_size` en php.ini a más de ' . Suplencia::MAX_JUSTIFICANTE_MB . ' MB.');
            }
            return null;
        }

        $max     = Suplencia::MAX_JUSTIFICANTE_MB;
        $ext     = strtolower(pathinfo($_FILES['justificante']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed, true) || $_FILES['justificante']['size'] > $max * 1024 * 1024) {
            Suplencia::setAlerta('error', "El justificante debe ser PDF o imagen (JPG/PNG/WebP) de máximo {$max} MB");
            return null;
        }

        // El nombre del archivo no dice qué hay dentro: se comprueba el MIME real.
        $mimeOk = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        $mime   = @mime_content_type($_FILES['justificante']['tmp_name']);
        if ($mime && !in_array($mime, $mimeOk, true)) {
            Suplencia::setAlerta('error', 'El archivo no es un PDF ni una imagen válida.');
            return null;
        }

        $dir = self::dirJustificantes();
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $fn = uniqid('just_', true) . '.' . $ext;
        if (move_uploaded_file($_FILES['justificante']['tmp_name'], $dir . $fn)) {
            return $fn;
        }
        return null;
    }

    /** Borra del disco el archivo de un justificante (nuevo o heredado). */
    private static function borrarJustificante(?string $ruta): bool {
        $file = self::rutaJustificante($ruta);
        return $file !== null && @unlink($file);
    }

    /**
     * Descarga de un justificante. **Única puerta al archivo**, ahora que vive fuera
     * de `public/`.
     *
     * Lo abren dirección y quien lo subió. Prefectura NO: coordina la ausencia y ve
     * que el justificante existe, pero el documento es de dirección.
     */
    public static function descargarJustificante(Router $router) {
        self::requireModulo('suplencias');
        $s = Suplencia::find((int)($_GET['id'] ?? 0));
        if (!$s || empty($s->justificante)) { http_response_code(404); exit; }

        $uid = (int)($_SESSION['blog_usuario']['id'] ?? 0);
        $ok  = self::puedeVerJustificante() || (int)$s->profesor_ausente_id === $uid;
        if (!$ok) { http_response_code(403); exit; }
        // Una dirección de nivel tampoco abre el parte de otro nivel.
        if (self::puedeVerJustificante()) self::requireAlcance((int)$s->id);

        $file = self::rutaJustificante($s->justificante);
        if ($file === null) { http_response_code(404); exit; }

        $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
                 'png' => 'image/png', 'webp' => 'image/webp'][$ext] ?? 'application/octet-stream';
        $nombre = 'justificante-' . $s->fecha . '.' . $ext;

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($file));
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        // Es un dato de salud: que no quede en ninguna caché intermedia.
        header('Cache-Control: private, no-store');
        readfile($file);
        exit;
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

    /**
     * Solicitar suplencia (auto-servicio del profesor ausente, flujo anticipado).
     * Solo para quien imparte: el ausente sale de la sesión, así que un prefecto o un
     * administrativo abriría una ausencia sobre un horario que no existe.
     */
    public static function solicitarSuplencia(Router $router) {
        self::requireImparte();
        $sesion  = $_SESSION['blog_usuario'];
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
                    header('Location: /dashboard/suplencias/mis-coberturas?solicitada=1');
                    exit;
                }
            }
        }

        // La rejilla de horas a cubrir la pinta el JS desde /dashboard/suplencias/horario:
        // la vista no necesita periodos ni matriz (con jornada por nivel tampoco valdrían,
        // porque dependen de en qué niveles dé clase este profesor).
        $router->renderAdmin('blog/suplencias/solicitar', [
            'titulo'   => 'Solicitar suplencia',
            'alertas'  => $alertas,
            // El calendario del listado enlaza con ?fecha=YYYY-MM-DD
            'fechaPrefijada' => self::fechaDeQuery(),
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
            'grupos'    => Grupo::todos(),
            'aulas'     => Aula::todas(),
            'materias'  => Materia::todas(),
            'alertas'   => $alertas,
            // El calendario del listado enlaza con ?fecha=YYYY-MM-DD
            'fechaPrefijada' => self::fechaDeQuery(),
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

        // Fecha escrita a mano ("martes 4 de agosto"): un 04/08/2026 obliga a
        // traducirlo mentalmente y esconde el día de la semana, que es el dato
        // con el que un profesor ubica realmente una clase.
        $cuando  = fecha_larga($suplencia->fecha);
        $materia = $hora->materia ?: 'una clase';
        $donde   = $hora->grupo_nombre ? " con {$hora->grupo_nombre}" : '';
        $aula    = $hora->aula_nombre ? " en el aula {$hora->aula_nombre}" : '';

        Notificacion::nueva(
            $suplenteId,
            'cobertura_asignada',
            "Suplencia asignada: cubres {$materia}{$donde} el {$cuando}, en la {$hora->periodo_etiqueta}{$aula}.",
            (int)$suplencia->id, 'suplencia', 'suplencias', 'info',
            '/dashboard/suplencias/mis-coberturas'
        );
    }

    /**
     * Avisa a la DIRECCIÓN que corresponde a unos niveles.
     *
     * Notificacion::nueva() escribe una fila por usuario y no sabe de grupos, así que
     * el reparto se decide aquí: la dirección de esos niveles más la general (sin
     * niveles declarados), que gobierna el colegio entero.
     *
     * @param string[] $niveles [] = todas las direcciones
     */
    private static function avisarDireccion(array $niveles, string $tipo, string $mensaje,
                                            ?int $refId, ?string $refTipo, string $modulo,
                                            string $nivelAviso = 'info', string $enlace = ''): void {
        // array_flip deduplica y de paso saca a quien ejecuta la acción: una dirección
        // puede validar un swap de su propio nivel, y no se avisa a sí misma. Sin el
        // dedup, un evento que cruza dos niveles le llegaría dos veces a la dirección
        // general — y "marcar como leída = BORRAR" solo se llevaría una.
        $destinos = array_flip(UsuarioBlog::direccionesDeNiveles($niveles));
        unset($destinos[(int)($_SESSION['blog_usuario']['id'] ?? 0)]);

        foreach (array_keys($destinos) as $uid) {
            Notificacion::nueva((int)$uid, $tipo, $mensaje, $refId, $refTipo, $modulo, $nivelAviso, $enlace);
        }
    }

    /**
     * Recordatorio de coberturas ya vencidas y sin confirmar. Se dispara al entrar
     * al panel porque el proyecto no tiene cron: la consulta va indexada por
     * (suplente_id, estado_hora) y `recordatorio_en` evita repetir el aviso.
     */
    private static function recordarCoberturasVencidas(): void {
        $uid = (int)($_SESSION['blog_usuario']['id'] ?? 0);
        if (!$uid) return;

        foreach (SuplenciaHora::vencidasSinRecordatorio($uid) as $h) {
            $clase = $h->materia ?: 'una clase';
            $conQ  = $h->grupo_nombre ? " con {$h->grupo_nombre}" : '';
            Notificacion::nueva(
                $uid,
                'cobertura_por_confirmar',
                "¿Cubriste {$clase}{$conQ} el " . fecha_larga($h->s_fecha) . '? Confírmalo para cerrar la suplencia.',
                (int)$h->suplencia_id, 'suplencia', 'suplencias', 'aviso',
                '/dashboard/suplencias/mis-coberturas'
            );
            SuplenciaHora::marcarRecordatorio((int)$h->id);
        }
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
        if (!$suplencia) { header('Location: /dashboard/suplencias?noexiste=1'); exit; }

        // Esta vista muestra el motivo de la ausencia y enlaza el justificante
        // médico. Un profesor solo puede abrir la suya: antes bastaba el módulo
        // para leer el parte de cualquier compañero cambiando el ?id=.
        if (!self::puedeAgendar() && (int)$suplencia->profesor_ausente_id !== (int)($_SESSION['blog_usuario']['id'] ?? 0)) {
            header('Location: /dashboard/suplencias?sinacceso=1');
            exit;
        }
        // Y una dirección de nivel tampoco abre por ?id= la ausencia de otro nivel:
        // filtrar el listado sin cerrar esta puerta sería solo maquillaje.
        self::requireAlcance($id);

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
            // Solo se calcula si quien mira puede abrirlo: si no, el nombre y el peso
            // del archivo se filtrarían al HTML aunque la descarga esté bloqueada.
            'veJustif'     => self::puedeVerJustificante(),
            'justifInfo'   => self::puedeVerJustificante()
                                ? self::infoJustificante($suplencia->justificante) : null,
        ]);
    }

    /**
     * Nombre, tamaño y tipo del justificante en disco, para que el modal de borrado diga
     * QUÉ se va a borrar. Aprobarlo lo elimina para siempre y hasta ahora la única pista
     * era la palabra "justificante".
     *
     * @return array{nombre:string,peso:string,ext:string,icono:string}|null null si no
     *         hay archivo o si el registro apunta a algo que ya no existe en disco.
     */
    private static function infoJustificante(?string $ruta): ?array {
        $disco = self::rutaJustificante($ruta);
        if ($disco === null) return null;
        $base  = basename($disco);

        $bytes = (int)filesize($disco);
        $ext   = strtolower(pathinfo($base, PATHINFO_EXTENSION));
        $u = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($u) - 1) { $bytes /= 1024; $i++; }

        return [
            'nombre' => $base,
            'peso'   => ($i === 0 ? (string)(int)$bytes : number_format($bytes, 1, ',', '')) . ' ' . $u[$i],
            'ext'    => $ext,
            'icono'  => $ext === 'pdf' ? 'fa-file-pdf' : (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) ? 'fa-file-image' : 'fa-file'),
        ];
    }

    /** El profesor ausente sube su justificante (flujo sin aviso). */
    public static function justificarSuplencia(Router $router) {
        $sesion = self::requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        $sup = Suplencia::find($id);
        if ($sup && $_SERVER['REQUEST_METHOD'] === 'POST') {
            // El propio ausente o dirección. Prefectura ya no sube el justificante de
            // otro: subirlo es tenerlo en la mano, y el documento no es suyo.
            if ((int)$sup->profesor_ausente_id === (int)$sesion['id'] || self::puedeVerJustificante()) {
                $ruta = self::subirJustificante();
                if ($ruta) {
                    $r = Suplencia::getDB()->escape_string($ruta);
                    Suplencia::getDB()->query("UPDATE suplencias SET justificante='{$r}' WHERE id={$id} LIMIT 1");
                    // Sella la subida y limpia cualquier resolución previa: subir un
                    // archivo nuevo reinicia el ciclo de revisión.
                    Suplencia::marcarSubida($id);
                    Suplencia::recalcularEstado($id);
                }
            }
        }
        header('Location: /dashboard/suplencias/agendar?id=' . $id);
        exit;
    }

    /**
     * Dirección aprueba el justificante y borra el archivo del servidor.
     * Es una acción deliberada y con confirmación: un parte médico no se conserva
     * más de lo necesario, pero tampoco debe desaparecer sin que nadie lo haya visto.
     */
    public static function aprobarJustificante(Router $router) {
        self::requireModulo('suplencias');
        if (!self::puedeVerJustificante()) { header('Location: /dashboard/suplencias'); exit; }

        $id  = (int)($_POST['id'] ?? 0);
        self::requireAlcance($id);
        $sup = Suplencia::find($id);
        if ($sup && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($sup->justificante)) {
            $uid  = (int)($_SESSION['blog_usuario']['id'] ?? 0);
            // La resolución queda firmada (quién y qué hizo) en vez de solo vaciar la
            // ruta: el histórico tiene que distinguir "nunca hubo justificante" de
            // "lo hubo y se revisó".
            $ruta = Suplencia::resolverJustificante($id, 'descargado', $uid);
            if ($ruta) self::borrarJustificante($ruta);

            if ($sup->profesor_ausente_id) {
                Notificacion::nueva(
                    (int)$sup->profesor_ausente_id,
                    'justificante_aprobado',
                    'Se revisó y aprobó tu justificante del ' . fecha_larga($sup->fecha)
                        . '. El archivo se eliminó del servidor.',
                    $id, 'suplencia', 'suplencias', 'exito',
                    '/dashboard/suplencias/agendar?id=' . $id
                );
            }
        }
        header('Location: /dashboard/suplencias/agendar?id=' . $id . '&aprobado=1');
        exit;
    }

    /**
     * ── Cola de justificantes ──
     *
     * Pasados Suplencia::DIAS_DESCARGA días desde la ausencia, el justificante deja
     * de estar disponible sin más y pasa a esta lista, donde DIRECCIÓN decide si lo
     * descarga o lo elimina. A los DIAS_PURGA se borra solo.
     */
    public static function justificantes(Router $router) {
        self::requireModulo('suplencias');
        if (!self::puedeVerJustificante()) { header('Location: /dashboard/suplencias'); exit; }

        $cola = Suplencia::colaJustificantes(self::nivelesVista());
        // Info de disco (nombre, peso, tipo) para poder decidir sin abrir el archivo.
        $info = [];
        foreach ($cola as $s) $info[(int)$s->id] = self::infoJustificante($s->justificante);

        $router->renderAdmin('blog/suplencias/justificantes', [
            'titulo'     => 'Justificantes',
            'cola'       => $cola,
            'info'       => $info,
            'purgados'   => (int)($_GET['purgados'] ?? 0),
            'alcance'    => self::nivelesAlcance(),
        ]);
    }

    /** Resuelve un justificante de la cola: descargar (y borrar) o eliminar. */
    public static function resolverJustificante(Router $router) {
        self::requireModulo('suplencias');
        if (!self::puedeVerJustificante()) { header('Location: /dashboard/suplencias'); exit; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /dashboard/suplencias/justificantes'); exit; }

        $id     = (int)($_POST['id'] ?? 0);
        self::requireAlcance($id);
        $accion = ($_POST['accion'] ?? '') === 'descargado' ? 'descargado' : 'eliminado';
        $uid    = (int)($_SESSION['blog_usuario']['id'] ?? 0);
        $sup    = Suplencia::find($id);

        $ruta = Suplencia::resolverJustificante($id, $accion, $uid);
        if ($ruta) {
            self::borrarJustificante($ruta);
            if ($sup && $sup->profesor_ausente_id) {
                Notificacion::nueva(
                    (int)$sup->profesor_ausente_id,
                    'justificante_resuelto',
                    'Tu justificante del ' . fecha_larga($sup->fecha) . ' se '
                        . ($accion === 'descargado' ? 'descargó y archivó' : 'eliminó sin descargar')
                        . ' tras superar el plazo de revisión.',
                    $id, 'suplencia', 'suplencias', 'info',
                    '/dashboard/suplencias/agendar?id=' . $id
                );
            }
        }
        header('Location: /dashboard/suplencias/justificantes?resuelto=1');
        exit;
    }

    /**
     * Prefectura registra si el profesor ausente dejó trabajo para el grupo.
     *
     * Va por HORA: puede haber dejado material para su clase de 3º y no para la de
     * 5º. `dejo_trabajo = NULL` significa "todavía sin revisar", que no es lo mismo
     * que "no dejó" — por eso el valor `-` devuelve la hora a ese estado.
     */
    public static function marcarTrabajo(Router $router) {
        self::requireModulo('suplencias');
        if (!self::puedeAgendar()) { header('Location: /dashboard/suplencias'); exit; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /dashboard/suplencias'); exit; }

        $horaId = (int)($_POST['hora_id'] ?? 0);
        $supId  = (int)($_POST['id'] ?? 0);
        self::requireAlcance($supId);

        $v    = (string)($_POST['dejo'] ?? '');
        $dejo = $v === '1' ? true : ($v === '0' ? false : null);
        SuplenciaHora::marcarTrabajo($horaId, $dejo, $_POST['notas'] ?? null,
                                     (int)($_SESSION['blog_usuario']['id'] ?? 0));

        header('Location: /dashboard/suplencias/agendar?id=' . $supId . '&trabajo=1');
        exit;
    }

    /**
     * Borra los justificantes que superaron Suplencia::DIAS_PURGA.
     *
     * No hay cron en este proyecto: lo dispara la carga del panel, igual que
     * recordarCoberturasVencidas(). Es idempotente y barato — la consulta está
     * indexada por (justificante_resuelto_en, fecha) y en un día normal no
     * devuelve nada.
     */
    private static function purgarJustificantes(): int {
        $n = 0;
        foreach (Suplencia::purgables() as $p) {
            if (Suplencia::resolverJustificante($p['id'], 'purgado')) {
                self::borrarJustificante($p['ruta']);
                $n++;
            }
        }
        return $n;
    }

    /**
     * Cierre por prefectura de una cobertura vencida que el suplente no confirmó.
     * Sin esto, un suplente que nunca responde dejaba la hora 'agendada' y la
     * suplencia sin llegar nunca a 'completada'.
     */
    public static function validarPrefectura(Router $router) {
        self::requireModulo('suplencias');
        if (!self::puedeAgendar()) { header('Location: /dashboard/suplencias'); exit; }

        $id = (int)($_POST['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $horaId = (int)($_POST['hora_id'] ?? 0);
            $cubrio = ($_POST['cubrio'] ?? '') === '1';
            $hora   = SuplenciaHora::detalle($horaId);
            if ($hora) self::requireAlcance((int)$hora->suplencia_id);

            if ($hora && SuplenciaHora::resolverPorPrefectura($horaId, $cubrio)) {
                $sup = Suplencia::find((int)$hora->suplencia_id);
                // "No se cubrió" queda registrado como incidencia con responsable
                // (suplencia_horas.incumplio_id) y cuenta en el tablero. Se avisa a los
                // dos implicados: al suplente, porque el incumplimiento es suyo; al
                // ausente, porque su clase se quedó sin nadie.
                if (!$cubrio && $sup) {
                    $cuando = fecha_larga($sup->fecha);
                    $donde  = trim(($hora->materia ?: 'La clase') . ($hora->grupo_nombre ? ' de ' . $hora->grupo_nombre : ''));
                    if ($hora->suplente_id) {
                        Notificacion::nueva((int)$hora->suplente_id, 'cobertura_no_cubierta',
                            "Consta que no cubriste {$donde} del {$cuando}. Queda registrado en tus estadísticas; "
                                . "si es un error, avisa a prefectura.",
                            (int)$sup->id, 'suplencia', 'suplencias', 'error',
                            '/dashboard/suplencias/mis-coberturas');
                    }
                    if ($sup->profesor_ausente_id) {
                        Notificacion::nueva((int)$sup->profesor_ausente_id, 'cobertura_no_cubierta',
                            "{$donde} del {$cuando} quedó sin cubrir: el suplente asignado no se presentó.",
                            (int)$sup->id, 'suplencia', 'suplencias', 'aviso',
                            '/dashboard/suplencias/agendar?id=' . (int)$sup->id);
                    }
                }
                // La cobertura cerrada llega a la dirección del nivel de esa hora
                // (más la general): es el registro con el que gobierna su nivel.
                if ($sup) {
                    $donde = trim(($hora->materia ?: 'Una clase') . ($hora->grupo_nombre ? ' de ' . $hora->grupo_nombre : ''));
                    self::avisarDireccion(
                        array_filter([$hora->periodo_nivel]),
                        $cubrio ? 'cobertura_validada' : 'cobertura_no_cubierta',
                        $cubrio
                            ? "{$donde} del " . fecha_larga($sup->fecha) . ' quedó cubierta y validada.'
                            : "{$donde} del " . fecha_larga($sup->fecha) . ' quedó SIN cubrir: el suplente no se presentó.',
                        (int)$sup->id, 'suplencia', 'suplencias', $cubrio ? 'info' : 'aviso',
                        '/dashboard/suplencias/agendar?id=' . (int)$sup->id
                    );
                }
                Suplencia::recalcularEstado((int)$hora->suplencia_id);
                $id = (int)$hora->suplencia_id;
            }
        }
        header('Location: /dashboard/suplencias/agendar?id=' . $id . '&resuelto=1');
        exit;
    }

    /**
     * Devuelve al circuito una hora marcada `no_cubierta` para reasignarla.
     *
     * Es un paso aparte y deliberado: al registrar el incumplimiento la hora NO vuelve
     * sola a la bolsa, porque entonces la incidencia se confundiría con una hora que
     * nadie ha tomado todavía. `incumplio_id` se conserva, así que reasignar no borra
     * la falta de quien no se presentó.
     */
    public static function reabrirHora(Router $router) {
        self::requireModulo('suplencias');
        if (!self::puedeAgendar()) { header('Location: /dashboard/suplencias'); exit; }

        $id = (int)($_POST['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $horaId = (int)($_POST['hora_id'] ?? 0);
            $hora   = SuplenciaHora::detalle($horaId);
            if ($hora) self::requireAlcance((int)$hora->suplencia_id);
            if ($hora && SuplenciaHora::reabrirHora($horaId)) {
                $id = (int)$hora->suplencia_id;
                Suplencia::recalcularEstado($id);
            }
        }
        header('Location: /dashboard/suplencias/agendar?id=' . $id . '&reabierta=1');
        exit;
    }

    /**
     * "Mis suplencias": la pantalla de suplencias de quien da clase.
     *
     * Reúne los dos lados del ciclo a lo largo del tiempo —lo que ha cubierto y lo que
     * ha pedido— porque para un profesor son la misma pregunta ("¿cómo voy de
     * suplencias?") y antes ninguno de los dos historiales existía: esta vista solo
     * listaba las coberturas en estado `agendada`, así que confirmar una la hacía
     * desaparecer, y sus propias ausencias solo se veían en la agenda del claustro.
     */
    public static function misCoberturas(Router $router) {
        self::requireImparte();
        $uid = (int)$_SESSION['blog_usuario']['id'];

        // Las que reclaman acción van aparte: el histórico completo las contiene, pero
        // enterradas entre las cerradas nadie las confirmaría.
        $pendientes = SuplenciaHora::porValidarDeSuplente($uid);
        $coberturas = SuplenciaHora::historicoDeSuplente($uid);
        $pendIds    = array_flip(array_map(fn($h) => (int)$h->id, $pendientes));

        $router->renderAdmin('blog/suplencias/mis-coberturas', [
            'titulo'      => 'Mis suplencias',
            'pendientes'  => $pendientes,
            'coberturas'  => $coberturas,
            'pendIds'     => $pendIds,
            // Sus propias ausencias. El filtro `ausente_id` de listar() ya existía y no
            // lo invocaba nadie.
            'solicitadas' => Suplencia::listar(['ausente_id' => $uid]),
        ]);
    }

    /**
     * Histórico de suplencias de todo el plantel, en versión resumida.
     *
     * Existe porque la agenda (`/dashboard/suplencias`) solo la abre quien coordina —y
     * con razón: expone motivos y justificantes—, así que un profesor no tenía dónde ver
     * quién cubrió a quién. Aquí solo hay tres datos, ninguno sensible: la fecha, quién
     * faltó y quién le cubrió. Lo delicado se queda en la agenda.
     *
     * Basta con tener el módulo: no distingue entre impartir y coordinar, porque lo que
     * muestra puede verlo cualquiera de los dos.
     */
    public static function historialSuplencias(Router $router) {
        self::requireModulo('suplencias');
        $router->renderAdmin('blog/suplencias/historial', [
            'titulo' => 'Histórico del plantel',
            'filas'  => SuplenciaHora::historialPlantel(),
        ]);
    }

    /** El suplente valida que cubrió una hora. */
    public static function validarCobertura(Router $router) {
        $sesion = self::requireAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $horaId = (int)($_POST['hora_id'] ?? 0);
            $h = SuplenciaHora::detalle($horaId);
            if ($h && SuplenciaHora::validarHora($horaId, (int)$sesion['id'])) {
                Suplencia::recalcularEstado((int)$h->suplencia_id);
                // La cobertura validada llega a la dirección de ese nivel: cerrar el
                // ciclo es justo el evento que la dirección tiene que ver.
                $sup   = Suplencia::find((int)$h->suplencia_id);
                $donde = trim(($h->materia ?: 'Una clase') . ($h->grupo_nombre ? ' de ' . $h->grupo_nombre : ''));
                if ($sup) {
                    self::avisarDireccion(
                        array_filter([$h->periodo_nivel]),
                        'cobertura_validada',
                        "{$sesion['nombre']} confirmó que cubrió {$donde} del " . fecha_larga($sup->fecha) . '.',
                        (int)$sup->id, 'suplencia', 'suplencias', 'info',
                        '/dashboard/suplencias/agendar?id=' . (int)$sup->id
                    );
                }
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

    /**
     * Tablero de estadísticas de suplencias: administración y dirección.
     *
     * Dirección entra porque son SUS datos de gestión, y los ve acotados a su nivel
     * (una dirección sin niveles declarados ve el colegio entero). Prefectura sigue
     * fuera: abrirlo a puedeCoordinar() la metería SIN acotar —normalizarNiveles()
     * fuerza NULL en prefecto—, o sea una ampliación de permisos disfrazada de
     * cambio de alcance. Si algún día se quiere, es un término más en este `if`.
     */
    public static function suplenciasDashboard(Router $router) {
        self::requireModulo('suplencias');
        if (!self::esAdmin() && !self::esDirectivo()) {
            header('Location: /dashboard/suplencias?sinacceso=tablero');
            exit;
        }
        $niv = self::nivelesVista();

        $router->renderAdmin('blog/suplencias/dashboard', [
            'titulo'       => 'Tablero de suplencias',
            'conteos'      => Suplencia::conteos(0, $niv),
            'porEstado'    => Suplencia::porEstado(0, $niv),
            'porOrigen'    => Suplencia::porOrigen($niv),
            'topSuplentes' => Suplencia::topSuplentes(8, $niv),
            'topAusentes'  => Suplencia::topAusentes(8, $niv),
            'porMes'       => Suplencia::porMes($niv),
            'porMateria'   => Suplencia::porMateria(8, $niv),
            'porMotivo'    => Suplencia::porMotivo(8, $niv),
            'resumenDia'   => Suplencia::resumenDiario(0, $niv),
            'detalleDia'   => Suplencia::detalleDiario($niv),
            // Coberturas asignadas a las que el suplente no se presentó. Es el dato
            // que justifica registrar el incumplimiento en vez de reabrir la hora sin
            // más: sin esto no habría forma de ver un patrón.
            'incumple'     => SuplenciaHora::topIncumplimientos(8, $niv),
            'nIncumple'    => SuplenciaHora::contarIncumplimientos($niv),
            // ¿Dejó el ausente trabajo para el grupo? Lo captura prefectura en
            // /agendar, hora a hora.
            'trabajo'      => SuplenciaHora::estadisticaTrabajo($niv),
            'sinTrabajo'   => SuplenciaHora::topSinTrabajo(8, $niv),
            // Un tablero filtrado que no lo dice miente: "Suplencias totales: 12" se
            // leería como el dato del colegio entero.
            'alcance'      => self::nivelesAlcance(),
            'nivelActivo'  => $niv,
            'extra_head'   => '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>',
        ]);
    }

    // ── MÓDULO SWAP DE CLASES ───────────────────────────────────────────────────

    /**
     * Listado de intercambios. Dos públicos, como en Suplencias:
     * quien imparte ve los suyos; quien coordina, los de todo el claustro para
     * validarlos.
     */
    public static function swaps(Router $router) {
        self::requireModulo('swaps');
        $uid      = (int)($_SESSION['blog_usuario']['id'] ?? 0);
        $coordina = self::puedeCoordinar();
        $imparte  = self::imparte();

        if (!$coordina && !$imparte) { header('Location: /dashboard?sinacceso=swaps'); exit; }
        // Alcance de las direcciones por nivel. Criterio OR sobre los dos lados del
        // intercambio: le compete si cualquiera de las dos clases es de su nivel.
        $niv = self::nivelesVista();

        $router->renderAdmin('blog/swaps/index', [
            'titulo'     => 'Intercambios de clase',
            'mios'       => $imparte  ? \Model\Swap::deProfesor($uid) : [],
            // Quien coordina Y además imparte ya tiene los suyos arriba: se excluyen
            // de la lista del claustro para no verlos dos veces en la misma pantalla.
            'todos'      => $coordina ? \Model\Swap::todos(false, $imparte ? $uid : 0, $niv) : [],
            'coordina'   => $coordina,
            'imparte'    => $imparte,
            'uid'        => $uid,
            'porValidar' => $coordina ? \Model\Swap::contarPorValidar($niv) : 0,
            'alcance'    => self::nivelesAlcance(),
        ]);
    }

    /**
     * Alta de un intercambio. Dos caminos según quién lo abre:
     *
     * - **Un profesor** propone: elige una clase SUYA y, a cambio, una del otro dentro
     *   de la ventana de Swap::DIAS_VENTANA días. Nace `pendiente` y recorre los dos
     *   pasos (respuesta del compañero → validación).
     * - **Prefectura o dirección** lo impone: elige a los DOS profesores y nace
     *   `validado`. No es una petición sino una reasignación, así que no tiene sentido
     *   pedirle permiso a nadie — pero ambos reciben el aviso de que su clase cambió.
     *
     * En los dos casos se comprueba que cada clase sea de quien dice ser: que lo abra
     * prefectura no exime del chequeo, o un POST manipulado regalaría la de un tercero.
     */
    public static function crearSwap(Router $router) {
        self::requireModulo('swaps');
        $coordina = self::puedeCoordinar();
        if (!self::imparte() && !$coordina) { header('Location: /dashboard/swaps'); exit; }

        $uid     = (int)$_SESSION['blog_usuario']['id'];
        $swap    = new \Model\Swap();
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $swap->sincronizar($_POST);
            // Quien coordina designa a los dos; quien imparte solo puede cederse a sí
            // mismo, y `solicitante_id` nunca sale del POST en ese caso.
            $swap->solicitante_id = $coordina
                ? (int)($_POST['solicitante_id'] ?? 0)
                : $uid;
            $swap->creado_por = $uid;
            $swap->estado     = $coordina ? 'validado' : 'pendiente';
            if ($coordina) {
                $swap->validado_por  = $uid;
                $swap->validado_en   = date('Y-m-d H:i:s');
                $swap->respondido_en = date('Y-m-d H:i:s');
            }
            $alertas = $swap->validar();

            if (empty($alertas['error'])) {
                $ho = Horario::find((int)$swap->horario_origen_id);
                $hd = Horario::find((int)$swap->horario_destino_id);
                if (!$ho || (int)$ho->profesor_id !== (int)$swap->solicitante_id) {
                    \Model\Swap::setAlerta('error', $coordina
                        ? 'La primera clase no es del profesor indicado'
                        : 'La clase que quieres ceder no es tuya');
                }
                if (!$hd || (int)$hd->profesor_id !== (int)$swap->destinatario_id) {
                    \Model\Swap::setAlerta('error', 'La clase que pides no es de ese profesor');
                }
                $alertas = \Model\Swap::getAlertas();
            }

            if (empty($alertas['error'])) {
                $r = $swap->guardar();
                if ($r['resultado']) {
                    if ($coordina) {
                        // No es una petición: es un aviso de que su clase cambió.
                        $quien = $_SESSION['blog_usuario']['nombre'] ?? 'Coordinación';
                        foreach ([(int)$swap->solicitante_id, (int)$swap->destinatario_id] as $d) {
                            if (!$d || $d === $uid) continue;
                            Notificacion::nueva($d, 'swap_impuesto',
                                "{$quien} registró un intercambio de clase que te afecta el "
                                    . fecha_larga($swap->fecha_origen) . '. Ya está validado.',
                                (int)$r['id'], 'swap', 'horarios', 'aviso', '/dashboard/swaps');
                        }
                        self::avisarSwapDireccion((int)$r['id'], 'swap_validado',
                            'Se registró un intercambio de clase validado para el '
                                . fecha_larga($swap->fecha_origen) . '.');
                    } else {
                        Notificacion::nueva(
                            (int)$swap->destinatario_id, 'swap_solicitado',
                            ($_SESSION['blog_usuario']['nombre'] ?? 'Un compañero')
                                . ' te propone un intercambio de clase para el ' . fecha_larga($swap->fecha_origen) . '.',
                            (int)$r['id'], 'swap', 'horarios', 'info', '/dashboard/swaps'
                        );
                    }
                    header('Location: /dashboard/swaps?creado=1');
                    exit;
                }
            }
        }

        // El compañero ya no se elige de una lista precargada: lo busca el picker contra
        // /dashboard/swaps/buscar, que filtra a personal docente y excluye al solicitante.
        $router->renderAdmin('blog/swaps/crear', [
            'titulo'      => $coordina ? 'Registrar intercambio' : 'Proponer intercambio',
            'swap'        => $swap,
            'alertas'     => $alertas,
            'misClases'   => Horario::porProfesor($uid),
            'ventana'     => \Model\Swap::DIAS_VENTANA,
            // Con esto la vista pide los dos profesores en vez de dar por hecho el
            // primero, y anuncia que el intercambio nacerá ya validado.
            'coordina'    => $coordina,
            'uid'         => $uid,
        ]);
    }

    /**
     * Un intercambio validado llega a la dirección de los niveles que toca.
     * Son uno o dos: un swap cruza dos clases y pueden ser de niveles distintos.
     */
    private static function avisarSwapDireccion(int $swapId, string $tipo, string $texto): void {
        $sw = \Model\Swap::encontrar($swapId);
        if (!$sw) return;
        self::avisarDireccion($sw->niveles(), $tipo, $texto,
            $swapId, 'swap', 'horarios', 'info', '/dashboard/swaps');
    }

    /**
     * Busca al compañero con quien intercambiar. Solo personal docente y nunca uno
     * mismo: intercambiar con quien no da clase no significa nada, y consigo mismo lo
     * rechaza Swap::validar() después de haber dejado elegirlo.
     */
    public static function buscarProfesoresSwap(Router $router) {
        self::requireModulo('swaps');
        if (!self::imparte() && !self::puedeCoordinar()) self::json(['error' => 'Sin acceso'], 403);
        // Excluirse a uno mismo solo tiene sentido buscando al COMPAÑERO. Quien
        // coordina designa a los dos profesores y no es parte del intercambio, así que
        // filtrarlo dejaría fuera a un admin que además imparte.
        $excluir = self::puedeCoordinar() ? 0 : (int)($_SESSION['blog_usuario']['id'] ?? 0);
        self::json(UsuarioBlog::buscarProfesores((string)($_GET['q'] ?? ''), $excluir));
    }

    /**
     * Clases de un profesor en un rango de fechas concreto, para el selector del
     * intercambio. Devuelve una entrada por (clase × día del rango en que se imparte),
     * porque lo que se intercambia es una clase EN UN DÍA, no la clase en abstracto.
     */
    public static function clasesSwapJson(Router $router) {
        self::requireModulo('swaps');
        if (!self::imparte() && !self::puedeCoordinar()) self::json(['error' => 'Sin acceso'], 403);

        $prof  = (int)($_GET['profesor'] ?? 0);
        $desde = self::fechaValida($_GET['desde'] ?? '');
        if (!$prof || !$desde) self::json(['clases' => []]);

        $ini  = new \DateTime($desde);
        $out  = [];
        $filas = Horario::porProfesor($prof);
        $porDia = [];
        foreach ($filas as $h) $porDia[$h->dia][] = $h;

        // Ventana: desde el día que se falta hasta DIAS_VENTANA después.
        for ($d = 0; $d <= \Model\Swap::DIAS_VENTANA; $d++) {
            $f   = (clone $ini)->modify("+{$d} day");
            $dow = (int)$f->format('N');
            if ($dow > 5) continue;                       // sin clases el fin de semana
            $dia = Horario::DIAS[$dow - 1];
            foreach ($porDia[$dia] ?? [] as $h) {
                if (($h->tipo ?? 'clase') === 'guardia') continue;   // una guardia no se intercambia
                $out[] = [
                    'horario_id' => (int)$h->id,
                    'fecha'      => $f->format('Y-m-d'),
                    'fecha_txt'  => fecha_larga($f->format('Y-m-d')),
                    'dia'        => Horario::DIAS_LABEL[$dia],
                    'etiqueta'   => $h->periodo_etiqueta,
                    'inicio'     => substr((string)$h->periodo_inicio, 0, 5),
                    'fin'        => substr((string)$h->periodo_fin, 0, 5),
                    'materia'    => $h->materia ?: 'Clase',
                    'grupo'      => $h->grupo_nombre,
                    'aula'       => $h->aula_nombre,
                    'color'      => self::colorMateria($h->materia, $h->color),
                ];
            }
        }
        usort($out, fn($a, $b) => [$a['fecha'], $a['inicio']] <=> [$b['fecha'], $b['inicio']]);
        self::json(['clases' => $out]);
    }

    /** 'Y-m-d' válida o null. */
    private static function fechaValida(string $v): ?string {
        $v = trim($v);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return null;
        [$y, $m, $d] = array_map('intval', explode('-', $v));
        return checkdate($m, $d, $y) ? $v : null;
    }

    /** El destinatario acepta o rechaza. */
    public static function responderSwap(Router $router) {
        self::requireModulo('swaps');
        $uid = (int)($_SESSION['blog_usuario']['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /dashboard/swaps'); exit; }

        $swap = \Model\Swap::encontrar((int)($_POST['id'] ?? 0));
        // Solo el destinatario responde, y solo mientras siga pendiente.
        if ($swap && (int)$swap->destinatario_id === $uid && $swap->estado === 'pendiente') {
            $acepta = ($_POST['respuesta'] ?? '') === 'aceptar';
            $nota   = mb_substr(trim((string)($_POST['nota'] ?? '')), 0, 255);

            // Decir que no sin decir por qué deja al solicitante sin saber si puede
            // proponer otra cosa o tiene que buscar a otra persona.
            if (!$acepta && $nota === '') {
                header('Location: /dashboard/swaps?faltamotivo=1');
                exit;
            }

            $swap->estado         = $acepta ? 'aceptado' : 'rechazado';
            $swap->respuesta_nota = $nota ?: null;
            $swap->respondido_en  = date('Y-m-d H:i:s');
            $swap->guardar();

            Notificacion::nueva(
                (int)$swap->solicitante_id,
                $acepta ? 'swap_aceptado' : 'swap_rechazado',
                ($_SESSION['blog_usuario']['nombre'] ?? 'Tu compañero')
                    . ($acepta
                        ? ' aceptó el intercambio del ' . fecha_larga($swap->fecha_origen) . '. Queda pendiente de validación.'
                        // El motivo viaja EN el aviso: si no, hay que abrir el listado
                        // para enterarse de por qué te han dicho que no.
                        : ' no puede hacer el intercambio del ' . fecha_larga($swap->fecha_origen) . ': ' . $nota),
                (int)$swap->id, 'swap', 'horarios', $acepta ? 'exito' : 'aviso', '/dashboard/swaps'
            );

            // Un swap aceptado NO es efectivo: espera validación. Sin este aviso solo
            // lo delataba el badge del subnav, y podía quedarse ahí para siempre.
            if ($acepta) {
                self::avisarSwapDireccion((int)$swap->id, 'swap_por_validar',
                    'Hay un intercambio de clase aceptado que espera validación, para el '
                        . fecha_larga($swap->fecha_origen) . '.');
            }
        }
        header('Location: /dashboard/swaps?respondido=1');
        exit;
    }

    /** Prefectura o dirección da el visto bueno final. */
    public static function validarSwap(Router $router) {
        self::requireModulo('swaps');
        if (!self::puedeCoordinar()) { header('Location: /dashboard/swaps'); exit; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /dashboard/swaps'); exit; }

        $swap = \Model\Swap::encontrar((int)($_POST['id'] ?? 0));
        // Solo se valida lo que las dos partes ya acordaron.
        if ($swap && $swap->estado === 'aceptado') {
            $ok   = ($_POST['decision'] ?? '') === 'validar';
            $nota = mb_substr(trim((string)($_POST['nota'] ?? '')), 0, 255);

            // Denegar es tumbar un acuerdo ya cerrado entre dos personas: como mínimo
            // hay que decir por qué.
            if (!$ok && $nota === '') {
                header('Location: /dashboard/swaps?faltamotivo=1');
                exit;
            }

            $swap->estado       = $ok ? 'validado' : 'denegado';
            $swap->validado_por = (int)($_SESSION['blog_usuario']['id'] ?? 0);
            $swap->validado_en  = date('Y-m-d H:i:s');
            // Columna propia: antes escribía en `respuesta_nota` y borraba lo que
            // hubiera dicho el destinatario.
            if (!$ok) $swap->validacion_nota = $nota;
            $swap->guardar();

            $texto = $ok
                ? 'Se validó el intercambio de clase del ' . fecha_larga($swap->fecha_origen) . '. Ya es efectivo.'
                : 'Se denegó el intercambio de clase del ' . fecha_larga($swap->fecha_origen) . ': ' . $nota;
            foreach ([(int)$swap->solicitante_id, (int)$swap->destinatario_id] as $destino) {
                if ($destino) {
                    Notificacion::nueva($destino, $ok ? 'swap_validado' : 'swap_denegado', $texto,
                        (int)$swap->id, 'swap', 'horarios', $ok ? 'exito' : 'aviso', '/dashboard/swaps');
                }
            }
            // El intercambio efectivo llega a la dirección de su nivel.
            if ($ok) self::avisarSwapDireccion((int)$swap->id, 'swap_validado', $texto);
        }
        header('Location: /dashboard/swaps?validado=1');
        exit;
    }

    /** El solicitante retira su propia propuesta mientras nadie ha respondido. */
    public static function cancelarSwap(Router $router) {
        self::requireModulo('swaps');
        $uid = (int)($_SESSION['blog_usuario']['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $swap = \Model\Swap::encontrar((int)($_POST['id'] ?? 0));
            if ($swap && (int)$swap->solicitante_id === $uid && $swap->estado === 'pendiente') {
                $swap->estado = 'cancelado';
                $swap->guardar();
                Notificacion::nueva((int)$swap->destinatario_id, 'swap_cancelado',
                    ($_SESSION['blog_usuario']['nombre'] ?? 'Tu compañero')
                        . ' retiró la propuesta de intercambio del ' . fecha_larga($swap->fecha_origen) . '.',
                    (int)$swap->id, 'swap', 'horarios', 'info', '/dashboard/swaps');
            }
        }
        header('Location: /dashboard/swaps?cancelado=1');
        exit;
    }

    // ── MÓDULO SOPORTE TÉCNICO ──────────────────────────────────────────────────

    /** WhatsApp de soporte, en formato internacional (52 = México). */
    private const SOPORTE_WHATSAPP = '525637185620';

    /**
     * Soporte técnico. Lo tiene todo el mundo (MODULOS_TRANSVERSALES): es la vía para
     * pedir ayuda cuando algo del panel falla, y condicionarla a un permiso dejaría
     * sin ella justo a quien no puede arreglarlo por su cuenta.
     *
     * El mensaje de WhatsApp se compone en el cliente porque el usuario escribe su
     * problema en el propio formulario; aquí solo viajan el nombre y el tipo de
     * personal ya resueltos, para no tener que traducir el CSV en JS.
     */
    public static function soporte(Router $router) {
        $sesion = self::requireAuth();

        // Etiquetas legibles del tipo de personal ("Profesor y administrativo").
        $tipos = array_map(
            fn($t) => \Model\UsuarioBlog::TIPO_LABEL[$t] ?? $t,
            self::sesionTipos()
        );
        if (!$tipos) $tipos = [self::esAdmin() ? 'Administrador' : 'Colaborador'];

        // El Q&A se acota a los módulos del usuario: enseñarle el resto solo hace
        // más difícil encontrar lo suyo.
        $faq    = require __DIR__ . '/../views/blog/soporte/_faq.php';
        $mios   = self::modulosDisponibles();
        $bloques = [];
        foreach ($faq as $clave => $bloque) {
            if ($clave !== 'general' && !in_array($clave, $mios, true)) continue;
            $bloques[$clave] = $bloque;
        }

        $router->renderAdmin('blog/soporte/index', [
            'titulo'   => 'Soporte técnico',
            'whatsapp' => self::SOPORTE_WHATSAPP,
            'quien'    => $sesion['nombre'] ?? 'Colaborador',
            'puesto'   => implode(' y ', $tipos),
            'bloques'  => $bloques,
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
            // `niveles` llega como array de checkboxes: sincronizar() no lo aplana, y
            // ninguno marcado significa "todo el colegio".
            $evento->niveles = implode(',', array_map('strval', (array)($_POST['niveles'] ?? [])));
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
            // `niveles` llega como array de checkboxes: sincronizar() no lo aplana, y
            // ninguno marcado significa "todo el colegio".
            $evento->niveles = implode(',', array_map('strval', (array)($_POST['niveles'] ?? [])));
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
        $usuarios = UsuarioBlog::todos();
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

    // ── Editor de horario de un profesor (módulo Usuarios · solo admin) ─────────
    //
    // El módulo Horarios sigue siendo de solo lectura: allí se consulta la semana ya
    // consolidada. Aquí se ESCRIBE, y por eso vive en Usuarios y pide admin — igual que
    // la importación por CSV, que hasta ahora era el único camino de escritura y obliga
    // a regenerar el archivo entero para corregir una clase suelta.

    /**
     * Carga y valida el profesor de `?id=`. Devuelve null (y ya ha redirigido) si no
     * existe o no da clase: un administrativo no tiene jornada en la que colocar nada.
     */
    private static function profesorEditable(int $id): ?UsuarioBlog {
        $prof = UsuarioBlog::find($id);
        $tipos = $prof ? array_filter(array_map('trim', explode(',', (string)$prof->tipo_personal))) : [];
        if (!$prof || !in_array('profesor', $tipos, true)) {
            header('Location: /dashboard/usuarios?nohorario=1');
            exit;
        }
        return $prof;
    }

    /**
     * Alta de un lugar de guardia desde el propio editor de horario.
     *
     * No tiene módulo ni pantalla propios: es un catálogo de dos palabras que hace
     * falta justo en el momento de agendar la guardia, y obligar a salir a otra
     * pantalla para crearlo y volver sería el camino largo. Responde JSON porque el
     * modal no debe perder la selección de casillas.
     */
    public static function crearLugarGuardia(Router $router) {
        self::requireModulo('usuarios');
        self::requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') self::json(['error' => 'Método no permitido'], 405);

        $nombre = trim((string)($_POST['nombre'] ?? ''));
        // Si ya existe se devuelve el que hay en vez de fallar: el admin quería
        // usarlo, no crearlo por duplicado.
        $ya = \Model\LugarGuardia::porNombre($nombre);
        if ($ya) self::json(['id' => (int)$ya->id, 'nombre' => $ya->nombre, 'existia' => true]);

        // `sincronizar()` y no el constructor: ActiveRecord no acepta argumentos ahí.
        $lugar = new \Model\LugarGuardia();
        $lugar->sincronizar(['nombre' => $nombre]);
        $alertas = $lugar->validar();
        if (!empty($alertas['error'])) self::json(['error' => $alertas['error'][0]], 422);

        $r = $lugar->guardar();
        if (empty($r['resultado'])) self::json(['error' => 'No se pudo crear el lugar'], 500);
        self::json(['id' => (int)$r['id'], 'nombre' => $lugar->nombre, 'existia' => false]);
    }

    /** Casillas 'dia:periodo_id' del POST → [['dia'=>string, 'periodo'=>Periodo], …]. */
    private static function celdasDelPost(array $post, string $nivel): array {
        $porId = [];
        foreach (Periodo::todos($nivel) as $p) $porId[(int)$p->id] = $p;

        $out = [];
        foreach ((array)($post['celdas'] ?? []) as $celda) {
            $partes = explode(':', (string)$celda);
            if (count($partes) !== 2) continue;
            [$dia, $pid] = $partes;
            $p = $porId[(int)$pid] ?? null;
            // El periodo tiene que ser de la jornada declarada en el POST: si no, un
            // formulario manipulado colocaría una clase en la jornada de otro nivel.
            if (!$p || !in_array($dia, Horario::DIAS, true)) continue;
            $out[$dia . ':' . (int)$pid] = ['dia' => $dia, 'periodo' => $p];
        }
        return array_values($out);   // sin repetidos: la clave los deduplica
    }

    /**
     * Editor: rejilla de UN nivel, una fila por periodo.
     *
     * Las pestañas son los CINCO niveles, no los del ámbito del profesor. Si salieran
     * del ámbito, un admin no podría darle a un profesor de Primaria su primera clase de
     * Secundaria — no habría pestaña donde crearla. Y como todo periodo pertenece a uno
     * de los cinco, con cinco pestañas ninguna clase queda ineditable.
     */
    public static function horarioEditor(Router $router) {
        self::requireModulo('usuarios');
        self::requireAdmin();

        $profId = (int)($_GET['id'] ?? 0);
        $prof   = self::profesorEditable($profId);
        $filas  = Horario::porProfesor($profId);

        // Cuántos bloques tiene en cada nivel: alimenta el badge de las pestañas y elige
        // la pestaña por defecto cuando el profesor no declara niveles.
        $porNivel = array_fill_keys(Materia::NIVELES, 0);
        foreach ($filas as $h) if (isset($porNivel[$h->periodo_nivel])) $porNivel[$h->periodo_nivel]++;

        $declarados = UsuarioBlog::nivelesDe($profId);
        $nivel = $_GET['nivel'] ?? '';
        if (!in_array($nivel, Materia::NIVELES, true)) {
            $nivel = $declarados[0] ?? (array_sum($porNivel) ? array_search(max($porNivel), $porNivel, true) : Materia::NIVELES[0]);
        }

        // La rejilla del editor incluye los recesos: son casillas no escribibles, pero
        // sin ellas la jornada tendría un salto sin explicar.
        $periodos = Periodo::todos($nivel);

        // Vista consolidada de abajo: el camino canónico, sin tocar. Es la red de
        // seguridad — el editor muestra un nivel, esta rejilla muestra la verdad.
        $ambito  = Horario::ambito($filas, $declarados);
        $consol  = Horario::rejilla($filas, Periodo::deNiveles($ambito['niveles']), [
            'comprimir'  => $ambito['comprimir'],
            'declarados' => $ambito['declarados'],
        ]);

        // Flash de la validación: se lee Y SE BORRA, o los errores reaparecerían al
        // abrir el horario de otro profesor.
        $flash = $_SESSION['horario_editor'] ?? null;
        unset($_SESSION['horario_editor']);

        $router->renderAdmin('blog/usuarios/horario', [
            'titulo'       => 'Horario de ' . $prof->nombre,
            'profesor'     => $prof,
            'nivel'        => $nivel,
            'niveles'      => Materia::NIVELES,
            'declarados'   => $declarados,
            'porNivel'     => $porNivel,
            'periodos'     => $periodos,
            'celdas'       => Horario::rejillaPorPeriodo($filas, $periodos),
            // Coteaching: los acompañantes son filas de OTROS profesores, así que no
            // vienen en $filas. Una sola consulta para toda la rejilla.
            'acomp'        => Horario::acompanantesDeBloques($filas),
            'grupos'       => Grupo::porNivel(),
            'materias'     => Materia::porNivel(),
            'aulas'        => Aula::todas(),
            // Catálogo de guardias. Se pinta como tabs en el modal y admite alta
            // en línea (crearLugarGuardia), sin salir del editor.
            'lugares'      => \Model\LugarGuardia::todos(),
            'paleta'       => self::PALETA_HORARIO,
            'flash'        => $flash,
            // Vista consolidada (partial compartido con el módulo Horarios)
            'vista'        => 'profesor',
            'tramos'       => $consol['tramos'],
            'rejilla'      => $consol['rejilla'],
            'consNiveles'  => $ambito['niveles'],
            'discrepantes' => $ambito['discrepantes'],
        ]);
    }

    /**
     * Busca personal docente para el campo de acompañantes del editor de horario.
     * Mismos guards que el editor (es su único consumidor); el picker manda `q` y el
     * `excluir` del titular, que nunca puede ser acompañante de sí mismo.
     */
    public static function buscarProfesoresHorario(Router $router) {
        self::requireModulo('usuarios');
        self::requireAdmin();
        self::json(UsuarioBlog::buscarProfesores(
            (string)($_GET['q'] ?? ''),
            (int)($_GET['excluir'] ?? 0)
        ));
    }

    /**
     * Guarda UN bloque (grupo · materia · aula · acompañantes · color) sobre UNA casilla.
     *
     * Atómico a propósito: un bloque de coteaching son varias filas —titular más un
     * acompañante por profesor— y escribir la mitad dejaría una clase con dos docentes
     * de los que solo uno la tiene en su horario.
     */
    public static function guardarBloqueHorario(Router $router) {
        self::requireModulo('usuarios');
        self::requireAdmin();

        $profId   = (int)($_POST['profesor_id'] ?? 0);
        $profesor = self::profesorEditable($profId);

        $nivel = $_POST['nivel'] ?? '';
        if (!in_array($nivel, Materia::NIVELES, true)) $nivel = Materia::NIVELES[0];
        $volver = '/dashboard/usuarios/horario?id=' . $profId . '&nivel=' . urlencode($nivel);

        $editandoId = (int)($_POST['id'] ?? 0);
        // Una guardia no tiene grupo ni materia ni aula: tiene lugar. Se normaliza
        // aquí para que el resto del método no tenga que preguntarlo en cada paso.
        $esGuardia  = ($_POST['tipo'] ?? 'clase') === 'guardia';
        $grupoId    = $esGuardia ? null : ((int)($_POST['grupo_id'] ?? 0) ?: null);
        $materiaId  = $esGuardia ? null : ((int)($_POST['materia_id'] ?? 0) ?: null);
        $aulaId     = $esGuardia ? null : ((int)($_POST['aula_id'] ?? 0) ?: null);
        $lugarId    = $esGuardia ? ((int)($_POST['lugar_id'] ?? 0) ?: null) : null;
        $color      = trim((string)($_POST['color'] ?? ''));
        $forzarAula = !empty($_POST['forzar_aula']);

        // Acompañantes (coteaching). Cap a 2 —lo que se ve en los horarios reales—, sin
        // el titular y sin repetidos. Una guardia de receso es de una sola persona.
        $acompIds = [];
        if (!$esGuardia) {
            foreach ((array)($_POST['acompanantes'] ?? []) as $aid) {
                $aid = (int)$aid;
                if ($aid > 0 && $aid !== $profId && !in_array($aid, $acompIds, true)) $acompIds[] = $aid;
            }
            $acompIds = array_slice($acompIds, 0, self::MAX_ACOMPANANTES);
        }

        $celdas  = self::celdasDelPost($_POST, $nivel);
        $errores = [];
        $avisos  = [];

        if (!$celdas) $errores[] = 'No se eligió ninguna casilla del horario.';
        if ($color !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $errores[] = 'El color no es válido.';
            $color = '';
        }

        // Coherencia de nivel. El grupo y la materia pertenecen a un nivel, y la casilla
        // a la jornada de otro: colocar "1A Primaria" en la 3ª hora de Secundaria no es
        // un choque, es un dato que no significa nada.
        $grupo = $grupoId ? Grupo::find($grupoId) : null;
        if ($grupoId && (!$grupo || $grupo->nivel !== $nivel)) {
            $errores[] = 'El grupo «' . ($grupo->nombre ?? '?') . '» no es de ' . $nivel . '.';
        }
        $materia = $materiaId ? Materia::find($materiaId) : null;
        if ($materiaId && (!$materia || $materia->nivel !== $nivel)) {
            $errores[] = 'La materia «' . ($materia->nombre ?? '?') . '» no existe en la jornada de ' . $nivel . '.';
        }

        // Editar es siempre de una sola casilla: mover un bloque = borrarlo y crearlo.
        if ($editandoId && count($celdas) > 1) $celdas = [$celdas[0]];

        if ($esGuardia && !$lugarId) $errores[] = 'Elige el lugar de la guardia.';
        if ($esGuardia && $lugarId && !\Model\LugarGuardia::find($lugarId)) {
            $errores[] = 'Ese lugar de guardia ya no existe.';
        }

        // Los acompañantes tienen que existir y dar clase: un POST manipulado no debe
        // poder colar a un administrativo en la rejilla de un aula.
        $acompUsr = [];
        foreach ($acompIds as $aid) {
            $u = UsuarioBlog::find($aid);
            if (!$u) { $errores[] = 'Uno de los acompañantes ya no existe.'; continue; }
            if (!in_array('profesor', array_filter(array_map('trim', explode(',', (string)$u->tipo_personal))), true)) {
                $errores[] = '«' . $u->nombre . '» no es personal docente, no puede acompañar una clase.';
                continue;
            }
            $acompUsr[$aid] = $u;
        }

        foreach ($celdas as $c) {
            $receso = (int)$c['periodo']->es_receso === 1;
            // Las guardias van justamente EN el receso; una clase, nunca.
            if (!$esGuardia && $receso) {
                $errores[] = (Horario::DIAS_LABEL[$c['dia']]) . ' · ' . $c['periodo']->etiqueta
                           . ': es un descanso de ' . $nivel . ', no admite clase.';
                continue;
            }
            if ($esGuardia && !$receso) {
                $errores[] = (Horario::DIAS_LABEL[$c['dia']]) . ' · ' . $c['periodo']->etiqueta
                           . ': una guardia se hace en un receso, no sobre una hora de clase.';
                continue;
            }

            // Filas del bloque que ya existen y por tanto NO son un choque consigo
            // mismas: la del titular y las de sus acompañantes actuales. Sin esto,
            // reeditar un bloque de coteaching se autodetecta como conflicto.
            $excluir = $editandoId ? [$editandoId] : [];
            $yaAcomp = [];
            if ($editandoId && !$esGuardia) {
                $yaAcomp = Horario::acompanantes($c['dia'], (int)$c['periodo']->id, $grupoId, $materiaId, 0);
                foreach ($yaAcomp as $ya) $excluir[] = (int)$ya->id;
            }

            $r = Horario::conflictos($c['dia'], $c['periodo'], [
                'profesor_id' => $profId, 'aula_id' => $aulaId, 'grupo_id' => $grupoId,
                'materia_id'  => $materiaId, 'division' => 0,
            ], $excluir);
            $errores = array_merge($errores, $r['errores']);
            $avisos  = array_merge($avisos,  $r['avisos']);

            /* Cada acompañante se valida SOLO en su dimensión de persona («¿está libre a
               esa hora?»). Nada de pasar aula ni grupo: son los mismos del titular y ya
               se comprobaron arriba, así que repetirlos solo duplicaría el mismo error
               una vez por acompañante. */
            foreach ($acompUsr as $aid => $u) {
                $ra = Horario::conflictos($c['dia'], $c['periodo'], ['profesor_id' => $aid], $excluir);
                foreach ($ra['errores'] as $e) $errores[] = $u->nombre . ' — ' . $e;
            }
        }

        // El aula solo advierte, y advertir dos veces de lo mismo no aporta: se pide
        // confirmación una vez y el segundo envío trae forzar_aula=1.
        if (!$errores && $avisos && !$forzarAula) {
            $_SESSION['horario_editor'] = ['avisos' => $avisos, 'reabrir' => $_POST];
            header('Location: ' . $volver . '&confirmar=1');
            exit;
        }

        if ($errores) {
            $_SESSION['horario_editor'] = ['errores' => $errores, 'reabrir' => $_POST];
            header('Location: ' . $volver . '&err=1');
            exit;
        }

        /* Transacción: las validaciones de arriba son de aplicación, pero los UNIQUE de
           `horarios` siguen ahí y un caso no contemplado reventaría a mitad del bloque.
           Con coteaching esto pasa de precaución a requisito: un bloque son N filas y
           escribir solo algunas dejaría una clase con dos docentes de los que solo uno
           la tiene en su horario. */
        $db = UsuarioBlog::getDB();
        $db->begin_transaction();
        $n = 0;
        $avisar = [];   // a quién notificar: [id => 'agregado'|'quitado']
        try {
            foreach ($celdas as $c) {
                $h = $editandoId ? Horario::find($editandoId) : new Horario();
                if (!$h) continue;
                $comun = [
                    'dia'         => $c['dia'],
                    'periodo_id'  => (int)$c['periodo']->id,
                    'tipo'        => $esGuardia ? 'guardia' : 'clase',
                    'grupo_id'    => $grupoId,
                    'aula_id'     => $aulaId,
                    'materia_id'  => $materiaId,
                    'lugar_id'    => $lugarId,
                    // Las materias divididas siguen llegando solo del CSV: aquí un
                    // bloque es siempre para todo el grupo.
                    'division'    => 0,
                    'color'       => $color !== '' ? strtolower($color) : null,
                ];
                $h->sincronizar($comun + ['profesor_id' => $profId, 'rol_docente' => 'titular']);
                if (!$h->guardar()['resultado']) throw new \RuntimeException('No se pudo guardar el bloque.');
                $n++;

                if ($esGuardia) continue;

                /* Reconciliar los acompañantes: se borran los que ya no están y se
                   insertan los nuevos. Reescribir siempre las filas sería más simple
                   pero cambiaría sus ids, y con ellos las referencias de swaps y
                   suplencias que apunten a esa clase. */
                $existentes = Horario::acompanantes($c['dia'], (int)$c['periodo']->id, $grupoId, $materiaId, 0);
                $vivos = [];
                foreach ($existentes as $ex) {
                    $exId = (int)$ex->profesor_id;
                    if (in_array($exId, $acompIds, true)) {
                        $vivos[] = $exId;
                        // Puede haber cambiado el aula o el color del bloque.
                        $ex->sincronizar($comun + ['profesor_id' => $exId, 'rol_docente' => 'acompanante']);
                        if (!$ex->guardar()['resultado']) throw new \RuntimeException('No se pudo actualizar un acompañante.');
                    } else {
                        if (!$ex->eliminar()) throw new \RuntimeException('No se pudo quitar un acompañante.');
                        $avisar[$exId] = 'quitado';
                    }
                }
                foreach ($acompIds as $aid) {
                    if (in_array($aid, $vivos, true)) continue;
                    $ac = new Horario();
                    $ac->sincronizar($comun + ['profesor_id' => $aid, 'rol_docente' => 'acompanante']);
                    if (!$ac->guardar()['resultado']) throw new \RuntimeException('No se pudo añadir un acompañante.');
                    $avisar[$aid] = 'agregado';
                    $n++;
                }
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            $_SESSION['horario_editor'] = ['errores' => ['No se pudo guardar: ' . $e->getMessage()], 'reabrir' => $_POST];
            header('Location: ' . $volver . '&err=1');
            exit;
        }

        // Una sola notificación por persona y por petición: editar varias casillas
        // seguidas le reventaría la campana al profesor.
        if ($n) {
            $que = $esGuardia ? 'guardia' : 'clase';
            Notificacion::nueva(
                $profId, 'horario_actualizado',
                'Tu horario se actualizó: ' . ($editandoId
                    ? "se modificó una {$que}"
                    : "se agregó una {$que}") . '.',
                null, 'horario', 'horarios', 'info', '/dashboard/horarios/mi-horario'
            );
        }
        foreach ($avisar as $aid => $accion) {
            Notificacion::nueva(
                $aid, 'horario_actualizado',
                $accion === 'agregado'
                    ? 'Te asignaron una clase compartida con ' . $profesor->nombre . '. Revisa tu horario.'
                    : 'Ya no compartes una clase con ' . $profesor->nombre . '. Revisa tu horario.',
                null, 'horario', 'horarios', 'info', '/dashboard/horarios/mi-horario'
            );
        }

        // El nivel no declarado no bloquea (es opcional por diseño), pero conviene
        // cuadrar la ficha: acota su rejilla y prioriza sus suplencias.
        $declarados = UsuarioBlog::nivelesDe($profId);
        if ($declarados && !in_array($nivel, $declarados, true)) {
            $_SESSION['horario_editor'] = ['nivelAjeno' => $nivel];
        }
        header('Location: ' . $volver . '&ok=' . $n);
        exit;
    }

    /**
     * Borra un bloque. Comprueba que sea del profesor: si no, un id cualquiera vale.
     *
     * Si el bloque tenía acompañantes se van con él: una fila de acompañante sin titular
     * es un huérfano que no aparece en ninguna rejilla de este editor y que por tanto
     * nadie podría volver a borrar, pero que sigue ocupándole la hora a esa persona.
     */
    public static function eliminarBloqueHorario(Router $router) {
        self::requireModulo('usuarios');
        self::requireAdmin();

        $profId = (int)($_POST['profesor_id'] ?? 0);
        $nivel  = $_POST['nivel'] ?? '';
        if (!in_array($nivel, Materia::NIVELES, true)) $nivel = Materia::NIVELES[0];

        $h  = Horario::find((int)($_POST['id'] ?? 0));
        $ok = $h && (int)$h->profesor_id === $profId;
        if ($ok) {
            $acomp = ($h->tipo ?? 'clase') === 'guardia' ? [] : Horario::acompanantes(
                $h->dia, (int)$h->periodo_id, $h->grupo_id ? (int)$h->grupo_id : null,
                $h->materia_id ? (int)$h->materia_id : null, (int)$h->division
            );
            $h->eliminar();
            foreach ($acomp as $ac) {
                $acId = (int)$ac->profesor_id;
                $ac->eliminar();
                Notificacion::nueva(
                    $acId, 'horario_actualizado',
                    'Se eliminó una clase que compartías. Revisa tu horario.',
                    null, 'horario', 'horarios', 'aviso', '/dashboard/horarios/mi-horario'
                );
            }
        }

        header('Location: /dashboard/usuarios/horario?id=' . $profId . '&nivel=' . urlencode($nivel) . ($ok ? '&deleted=1' : '&err=1'));
        exit;
    }

    /**
     * Directorios de personal: Profesores / Prefectura / Administrativos / Directivos.
     * Reutiliza una vista de índice filtrada por tipo_personal. Cada directorio
     * es su propio módulo asignable, así que el guard va por slug.
     */
    private static function renderDirectorio(Router $router, string $slug, string $tipo, string $titulo): void {
        self::requireModulo($slug);
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
    public static function directivos(Router $router)       { self::renderDirectorio($router, 'directivos', 'directivo', 'Directivos'); }

    // ── MÓDULO HORARIOS ─────────────────────────────────────────────────────────
    public static function horarios(Router $router) {
        self::requireModulo('horarios');
        header('Location: /dashboard/horarios/profesor');
        exit;
    }

    private static function horariosVista(Router $router, string $vista): void {
        self::requireModulo('horarios');

        // Estas tres vistas exponen el horario de TODO el claustro, de todas las
        // aulas y de todos los grupos: el desplegable se llena con el catálogo
        // completo y `?id=` no se comparaba con la sesión. Las abren quienes
        // coordinan (admin y prefectura); un profesor se queda con su propio
        // horario en /mi-horario.
        if (!self::puedeCoordinar()) {
            header('Location: /dashboard/horarios/mi-horario');
            exit;
        }

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

        // El eje se acota a los niveles del profesor (los que tiene DECLARADOS, o los de
        // sus clases si no ha declarado ninguno) o al nivel del grupo. Meter los cinco
        // siempre doblaría las filas sin aportar nada.
        $declarados = match (true) {
            $vista === 'profesor' && (bool)$entidadId => UsuarioBlog::nivelesDe($entidadId),
            $vista === 'grupo'    && (bool)$entidadId => [Grupo::find($entidadId)->nivel ?? Materia::NIVELES[0]],
            default => [],
        };
        $ambito  = Horario::ambito($filas, $declarados);
        $rejilla = Horario::rejilla($filas, Periodo::deNiveles($ambito['niveles']), [
            'comprimir'  => $ambito['comprimir'],
            'declarados' => $ambito['declarados'],
        ]);

        $router->renderAdmin('blog/horarios/index', [
            'titulo'       => $titulo,
            'vista'        => $vista,
            'entidades'    => $entidades,
            'entidadId'    => $entidadId,
            'niveles'      => $ambito['niveles'],
            'discrepantes' => $ambito['discrepantes'],
            'tramos'       => $rejilla['tramos'],
            'rejilla'      => $rejilla['rejilla'],
        ]);
    }
    public static function horariosProfesor(Router $router) { self::horariosVista($router, 'profesor'); }
    public static function horariosAula(Router $router)     { self::horariosVista($router, 'aula'); }
    public static function horariosGrupo(Router $router)    { self::horariosVista($router, 'grupo'); }

    /**
     * "Mi horario": el colaborador en sesión consulta su propio horario, solo lectura.
     * El horario lo carga dirección (por CSV o desde el editor del módulo Usuarios); las
     * horas sin clase las usa el sistema para proponer suplencias, así que aquí no hay
     * nada que editar.
     */
    public static function miHorario(Router $router) {
        $sesion = self::requireAuth();
        $datos  = self::datosHorarioProfesor((int)$sesion['id']);
        if ($datos === null) { header('Location: /dashboard'); exit; }

        $router->renderAdmin('blog/horarios/mi-horario', ['titulo' => 'Mi horario'] + $datos);
    }

    /**
     * Rejilla semanal de un profesor y su carga, en un solo sitio.
     *
     * La comparten la vista web y el PDF: si cada una montara su ámbito por su cuenta
     * podrían acabar pintando semanas distintas.
     *
     * @return array|null null si el profesor no existe
     */
    private static function datosHorarioProfesor(int $profId): ?array {
        $profesor = UsuarioBlog::find($profId);
        if (!$profesor) return null;

        $filas   = Horario::porProfesor($profId);
        $ambito  = Horario::ambito($filas, UsuarioBlog::nivelesDe($profId));
        $rejilla = Horario::rejilla($filas, Periodo::deNiveles($ambito['niveles']), [
            'comprimir'  => $ambito['comprimir'],
            'declarados' => $ambito['declarados'],
        ]);

        // Tiempo de CLASE por día, en MINUTOS. Es lo que el profesor reconoce como "su
        // carga"; las horas libres son un residuo del cálculo de suplencias y anunciarlas
        // en la cabecera de su propio horario sobraba.
        // Van en minutos y no en celdas: con el eje mezclado un fragmento de 20 min
        // contaría como una hora, y en las celdas de clase `inicio`/`fin` son las horas
        // reales de la clase, así que la suma es exacta venga del eje que venga.
        $ocupadoPorDia = [];
        foreach (Horario::DIAS as $d) {
            $ocupadoPorDia[$d] = array_sum(array_map(
                fn($c) => Periodo::minutos($c['inicio'], $c['fin']),
                array_filter($rejilla['rejilla'][$d] ?? [], fn($c) => $c['tipo'] === 'clase')
            ));
        }

        return [
            'profesor'      => $profesor,
            'niveles'       => $ambito['niveles'],
            'discrepantes'  => $ambito['discrepantes'],
            'tramos'        => $rejilla['tramos'],
            'rejilla'       => $rejilla['rejilla'],
            'ocupadoPorDia' => $ocupadoPorDia,   // minutos de clase
            'totalClases'   => count($filas),
        ];
    }

    /**
     * "Mi horario" en PDF, para llevarlo en papel.
     *
     * Se genera en servidor con Dompdf y una plantilla propia: **no** reutiliza
     * `_grid.php` porque su celda es `position:absolute; inset:0` dentro de un `<td>`
     * relativo, y Dompdf no implementa posicionamiento absoluto en tabla. Lo que sí se
     * reutiliza —que es lo que importa— son los datos: el mismo `Horario::rejilla()`,
     * los mismos `rowspan` y el mismo `colorMateria()` que la pantalla.
     */
    public static function miHorarioPdf(Router $router) {
        $sesion = self::requireAuth();
        $datos  = self::datosHorarioProfesor((int)$sesion['id']);
        if ($datos === null) { header('Location: /dashboard'); exit; }

        // El CSS vive en src/scss como todo lo demás (aquí no vale una hoja enlazada:
        // Dompdf no resuelve URLs del sitio). Se compila a este archivo y se inyecta.
        $css  = @file_get_contents(__DIR__ . '/../public/build/css/horario-pdf.css') ?: '';
        $logo = self::dataUri(__DIR__ . '/../public/build/assets/img/global/logo-bilbao-horizontal-azul.png');
        // El @font-face se arma aquí y no en el SCSS porque necesita rutas ABSOLUTAS
        // del disco de este servidor, que un archivo compilado no puede conocer.
        $css  = self::cssFuentePdf() . $css;

        ob_start();
        $pdfCss   = $css;
        $logoData = $logo;
        extract($datos);
        require __DIR__ . '/../views/blog/horarios/pdf.php';
        $html = ob_get_clean();

        $opciones = new \Dompdf\Options();
        $opciones->set('isRemoteEnabled', false);   // el logo va embebido; nada sale a la red
        // Outfit es la tipografía del panel, así que el PDF se lee como parte del
        // mismo producto. Dompdf necesita el TTF en disco —no sirve la hoja de Google
        // Fonts— y `chroot` es lo que le permite leer src/fonts/ con `@font-face`.
        $opciones->set('defaultFont', 'Outfit');
        $opciones->set('fontDir', __DIR__ . '/../storage/fuentes-pdf');
        $opciones->set('fontCache', __DIR__ . '/../storage/fuentes-pdf');
        $opciones->set('chroot', [realpath(__DIR__ . '/..')]);
        if (!is_dir($opciones->get('fontDir'))) @mkdir($opciones->get('fontDir'), 0755, true);
        $dompdf = new \Dompdf\Dompdf($opciones);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-',
            strtr($datos['profesor']->nombre, 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunAEIOUUN')));
        $dompdf->stream('horario-' . trim($slug, '-') . '.pdf', ['Attachment' => true]);
        exit;
    }

    /**
     * Declaraciones `@font-face` de Outfit para el PDF.
     *
     * Es la tipografía del panel, así que el horario impreso se lee como parte del
     * mismo producto y no como un volcado genérico. Dompdf necesita el TTF **en
     * disco** (la hoja de Google Fonts no le sirve) y solo acepta rutas absolutas
     * dentro del `chroot`, que por eso se fija en la raíz del proyecto.
     *
     * Si faltan los archivos devuelve '' y Dompdf cae a su fuente por defecto: el PDF
     * sale con otra tipografía, pero sale.
     */
    private static function cssFuentePdf(): string {
        $dir = realpath(__DIR__ . '/../src/fonts');
        if ($dir === false) return '';
        $css = '';
        foreach ([400 => 'normal', 600 => 'normal', 700 => 'bold', 800 => 'bold'] as $peso => $estilo) {
            $ttf = $dir . DIRECTORY_SEPARATOR . "Outfit-{$peso}.ttf";
            if (!is_file($ttf)) continue;
            $css .= "@font-face{font-family:'Outfit';font-style:normal;font-weight:{$peso};"
                  . "src:url('" . str_replace('\\', '/', $ttf) . "') format('truetype');}\n";
        }
        return $css;
    }

    /**
     * Un archivo local como data: URI. Dompdf con rutas relativas es frágil.
     *
     * ⚠️ Devuelve '' si falta la extensión **GD**: Dompdf la necesita para incrustar
     * un PNG y sin ella lanza una excepción que se llevaba por delante el PDF entero.
     * La plantilla ya trata el logo como opcional, así que sin GD sale sin él en vez
     * de no salir. (Habilitar `extension=gd` en php.ini lo devuelve; `intervention/image`
     * —los avatares y la optimización de subidas— también la necesita.)
     */
    private static function dataUri(string $ruta): string {
        if (!is_file($ruta) || !extension_loaded('gd')) return '';
        $ext  = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        $mime = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'][$ext] ?? 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode((string)file_get_contents($ruta));
    }

    // ── Importación de horarios por CSV (módulo horarios + admin) ───────────────

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
        $cabeceraEsperada = ['profesor_email', 'dia', 'nivel', 'periodo', 'materia', 'grupo', 'aula'];

        // Índices de catálogo por nombre normalizado
        $profesores = [];
        foreach (UsuarioBlog::todosParaImportar() as $u) $profesores[self::claveCatalogo($u->email)] = $u;

        // La jornada es por nivel, así que "3" o "3ª hora" ya no identifican un periodo:
        // la clave lleva el nivel delante. Sin eso, la 3ª de Primaria y la de Secundaria
        // se pisaban en el índice y ganaba la última cargada.
        $periodos = [];
        foreach (Periodo::todos() as $p) {
            $niv = self::claveCatalogo($p->nivel);
            $periodos[$niv . '|' . self::claveCatalogo($p->etiqueta)]      = $p;
            $periodos[$niv . '|' . self::claveCatalogo((string)$p->orden)] = $p;   // "3" además de "3ª hora"
        }
        // Mismo problema con las materias: `materias` tiene UNIQUE (nombre, nivel), así
        // que «Arte» existe en Kinder y en Primaria y el índice por nombre las mezclaba.
        $materias = [];
        foreach (Materia::todas() as $m) $materias[self::claveCatalogo($m->nivel) . '|' . self::claveCatalogo($m->nombre)] = $m;
        $grupos = [];
        foreach (Grupo::todos() as $g)   $grupos[self::claveCatalogo($g->nombre)] = $g;
        $aulas = [];
        foreach (Aula::todas() as $a)    $aulas[self::claveCatalogo($a->nombre)] = $a;

        $nivelesValidos = [];
        foreach (Materia::NIVELES as $niv) $nivelesValidos[self::claveCatalogo($niv)] = $niv;

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
        if (array_slice($cabecera, 0, 7) !== $cabeceraEsperada) {
            fclose($fh);
            return [[], ['error' => 'La cabecera debe ser exactamente: ' . implode(',', $cabeceraEsperada)]];
        }

        // Ocupación acumulada del archivo, por reloj. Ya no basta con la clave
        // profesor|dia|periodo: dos periodos distintos de niveles distintos pueden ser
        // la misma hora, y ahí el duplicado exacto no aparece pero el choque sí existe.
        $filas  = [];
        $ocupa  = ['prof' => [], 'aula' => [], 'grupo' => []];   // tipo → clave → [[ini,fin,linea]]
        $n = 1;

        while (($col = fgetcsv($fh)) !== false) {
            $n++;
            if ($col === [null] || (count($col) === 1 && trim((string)$col[0]) === '')) continue;

            $email   = trim((string)($col[0] ?? ''));
            $dia     = self::claveCatalogo((string)($col[1] ?? ''));
            $nivel   = trim((string)($col[2] ?? ''));
            $periodo = trim((string)($col[3] ?? ''));
            $materia = trim((string)($col[4] ?? ''));
            $grupo   = trim((string)($col[5] ?? ''));
            $aula    = trim((string)($col[6] ?? ''));

            $fila = [
                'linea' => $n, 'email' => $email, 'dia' => $dia, 'nivel' => $nivel, 'periodo' => $periodo,
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

            // El grupo se resuelve primero porque de él se deduce el nivel, y el nivel
            // decide en qué jornada cae el periodo. grupo y aula son opcionales
            // (las FK admiten NULL).
            $gr = $grupo !== '' ? ($grupos[self::claveCatalogo($grupo)] ?? null) : null;
            if ($grupo !== '' && !$gr) $marcar('error', "No existe el grupo «{$grupo}».");
            elseif ($gr)               $fila['grupo_id'] = (int)$gr->id;

            // El nivel decide a qué jornada pertenece «3ª hora». Lo normal es dejarlo
            // vacío y deducirlo del grupo; solo hace falta escribirlo en una clase sin
            // grupo asignado.
            $nivelResuelto = null;
            if ($nivel !== '') {
                $nivelResuelto = $nivelesValidos[self::claveCatalogo($nivel)] ?? null;
                if (!$nivelResuelto) {
                    $marcar('error', "Nivel inválido «{$nivel}»: usa " . implode(', ', Materia::NIVELES) . '.');
                } elseif ($gr && $gr->nivel !== $nivelResuelto) {
                    $marcar('error', "El nivel «{$nivelResuelto}» no cuadra con el grupo «{$gr->nombre}», que es de {$gr->nivel}.");
                }
            } elseif ($gr) {
                $nivelResuelto = $gr->nivel;
            } else {
                $marcar('error', 'Sin grupo no se puede saber a qué jornada pertenece: rellena la columna «nivel».');
            }
            $fila['nivel'] = $nivelResuelto ?: $nivel;

            $per = $nivelResuelto ? ($periodos[self::claveCatalogo($nivelResuelto) . '|' . self::claveCatalogo($periodo)] ?? null) : null;
            if ($nivelResuelto && !$per)          $marcar('error', "No existe el periodo «{$periodo}» en la jornada de {$nivelResuelto}.");
            elseif ($per && (int)$per->es_receso === 1) $marcar('error', "«{$per->etiqueta}» es un receso de {$per->nivel}: no admite clase.");
            elseif ($per)                         $fila['periodo_id'] = (int)$per->id;

            if ($materia === '') {
                $marcar('error', 'Falta la materia.');
            } elseif ($nivelResuelto) {
                $claveMat = self::claveCatalogo($nivelResuelto) . '|' . self::claveCatalogo($materia);
                if (!isset($materias[$claveMat])) $marcar('error', "No existe la materia «{$materia}» en {$nivelResuelto}.");
                else $fila['materia_id'] = (int)$materias[$claveMat]->id;
            }

            // El aula va la última: es el dato menos determinante, y si va antes su error
            // tapa el de nivel/periodo/materia, que es el que hay que corregir primero.
            if ($aula !== '') {
                if (!isset($aulas[self::claveCatalogo($aula)])) $marcar('error', "No existe el aula «{$aula}».");
                else $fila['aula_id'] = (int)$aulas[self::claveCatalogo($aula)]->id;
            }

            // Choques POR RELOJ. La BD ya no puede garantizarlos: sus tres UNIQUE son por
            // periodo_id y dos periodos distintos pueden ser la misma hora.
            if ($fila['estado'] !== 'error' && $per) {
                $ini = $per->hora_inicio; $fin = $per->hora_fin;
                $buscar = function (string $tipo, $id) use (&$ocupa, $dia, $ini, $fin) {
                    foreach ($ocupa[$tipo][$dia . '|' . $id] ?? [] as $x) {
                        if (Periodo::solapan($ini, $fin, $x[0], $x[1])) return $x;
                    }
                    return null;
                };
                $rango = substr($ini, 0, 5) . '–' . substr($fin, 0, 5);

                if ($ch = $buscar('prof', $fila['profesor_id'])) {
                    $marcar('error', "Ese profesor ya tiene clase de {$ch[2]} (línea {$ch[3]}), que se pisa con {$rango}.");
                } elseif ($fila['grupo_id'] && ($ch = $buscar('grupo', $fila['grupo_id']))) {
                    // Antes no se validaba: el UNIQUE (dia, periodo_id, grupo_id) reventaba
                    // la transacción con un error ilegible en vez de avisar en la previa.
                    $marcar('error', "Ese grupo ya tiene clase de {$ch[2]} (línea {$ch[3]}), que se pisa con {$rango}.");
                } elseif ($fila['aula_id'] && ($ch = $buscar('aula', $fila['aula_id']))) {
                    $marcar('aviso', "El aula ya está ocupada de {$ch[2]} (línea {$ch[3]}).");
                }

                if ($fila['estado'] !== 'error') {
                    $marca = [$ini, $fin, $rango, $n];
                    $ocupa['prof'][$dia . '|' . $fila['profesor_id']][] = $marca;
                    if ($fila['grupo_id']) $ocupa['grupo'][$dia . '|' . $fila['grupo_id']][] = $marca;
                    if ($fila['aula_id'])  $ocupa['aula'][$dia . '|' . $fila['aula_id']][]   = $marca;
                }
            }

            $filas[] = $fila;
        }
        fclose($fh);

        $errores   = 0;
        $avisos    = 0;
        // ── Choques contra el horario YA CARGADO ─────────────────────────────────
        // El archivo solo reemplaza a los profesores que aparecen en él, así que un
        // grupo o un aula pueden chocar con la clase de un profesor ajeno. Eso no lo ve
        // ninguna comprobación en memoria y antes reventaba la transacción al insertar,
        // con un "Duplicate entry 'lunes-41-5'" que no dice nada a quien lo lee.
        $delArchivo = [];
        foreach ($filas as $f) if ($f['estado'] !== 'error') $delArchivo[(int)$f['profesor_id']] = true;

        foreach ($filas as &$f) {
            if ($f['estado'] === 'error' || !$f['periodo_id']) continue;
            $per = Periodo::find((int)$f['periodo_id']);
            if (!$per) continue;

            $externos = array_filter(
                Horario::choques($f['dia'], $per->hora_inicio, $per->hora_fin, [
                    'grupo_id' => $f['grupo_id'] ?: null,
                    'aula_id'  => $f['aula_id']  ?: null,
                ]),
                // A los profesores del archivo se les borra el horario antes de insertar:
                // chocar con lo que van a dejar de tener no es un choque.
                fn($c) => !isset($delArchivo[$c['profesor_id']])
            );
            foreach ($externos as $c) {
                $quien = $c['profesor'] ?: 'otro profesor';
                if ($c['dimension'] === 'grupo') {
                    $f['estado'] = 'error';
                    $f['motivo'] = "El grupo ya tiene clase de {$c['rango']} con {$quien}, que no viene en este archivo.";
                    break;
                }
                if ($f['estado'] === 'ok') {
                    $f['estado'] = 'aviso';
                    $f['motivo'] = "El aula ya está ocupada de {$c['rango']} por {$quien}.";
                }
            }
        }
        unset($f);

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

    // ── CATÁLOGOS ACADÉMICOS · AULAS Y GRUPOS ─────────────────────────────────
    //
    // Aulas y grupos ya existían en la BD pero solo se podían tocar por SQL.
    // Cada uno es su propio módulo asignable: `horarios` tiene UNIQUE por
    // (día, periodo, aula) y por (día, periodo, grupo), así que renombrar o
    // borrar aquí repercute en todo el horario. Antes de borrar se cuentan las
    // dependencias (Aula::usos() / Grupo::usos()).

    public static function aulas(Router $router) {
        self::requireModulo('aulas');
        $router->renderAdmin('blog/aulas/index', [
            'titulo' => 'Aulas',
            'aulas'  => Aula::todasConUso(),
        ]);
    }

    public static function crearAula(Router $router) {
        self::requireEscritura('aulas');
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
        self::requireEscritura('aulas');
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
        self::requireEscritura('aulas');
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
        self::requireModulo('grupos');
        $router->renderAdmin('blog/grupos/index', [
            'titulo' => 'Grupos',
            'grupos' => Grupo::todosConUso(),
        ]);
    }

    public static function crearGrupo(Router $router) {
        self::requireEscritura('grupos');
        $grupo = new Grupo();
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
        self::requireEscritura('grupos');
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
        self::requireEscritura('grupos');
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
        self::requireModulo('horarios');
        self::requireAdmin();

        // Descarga de la plantilla de ejemplo
        if (isset($_GET['plantilla'])) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="horarios-plantilla.csv"');
            echo "\xEF\xBB\xBF";
            echo "profesor_email,dia,nivel,periodo,materia,grupo,aula\n";
            $prof = UsuarioBlog::porTipo('profesor');
            $ej   = $prof[0]->email ?? 'nombre.apellido@bilbao.edu.mx';
            $g    = Grupo::todos();
            $g1   = $g[0]->nombre ?? '1A Primaria';
            $g2   = $g[1]->nombre ?? '2A Primaria';
            // `nivel` se deja vacío cuando hay grupo: se deduce de él. Solo hace falta
            // escribirlo en una clase sin grupo, donde no hay de dónde sacar la jornada.
            echo "{$ej},lunes,,1,Matemáticas,{$g1},A-101\n";
            echo "{$ej},lunes,,2,Matemáticas,{$g2},A-101\n";
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
                        // ⚠️ El CSV tiene 7 columnas y ninguna es el color, pero
                        // borrarDeProfesores() se lleva el horario completo: sin esta
                        // foto, reimportar revertía en silencio todo color elegido a
                        // mano en el editor. Es el único dato que el archivo no sabe
                        // expresar, así que conservarlo no compite con él.
                        $colores = Horario::coloresDeProfesores(array_column($validas, 'profesor_id'));

                        Horario::borrarDeProfesores(array_column($validas, 'profesor_id'));
                        foreach ($validas as $f) {
                            // ActiveRecord no tiene __construct: `new Horario([...])`
                            // devolvía un objeto vacío y el INSERT moría con
                            // "Column 'dia' cannot be null". Los valores se cargan con
                            // sincronizar(), que es la puerta que sí existe.
                            $clave = $f['profesor_id'] . '|' . $f['dia'] . '|' . $f['periodo_id'];
                            $h = new Horario();
                            $h->sincronizar([
                                'dia'         => $f['dia'],
                                'periodo_id'  => $f['periodo_id'],
                                'profesor_id' => $f['profesor_id'],
                                'grupo_id'    => $f['grupo_id'] ?: null,
                                'aula_id'     => $f['aula_id'] ?: null,
                                'materia_id'  => $f['materia_id'] ?: null,
                                'color'       => $colores[$clave] ?? null,
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
                            UsuarioBlog::guardarAtributos($nuevoId, $usuario->rol_redaccion, $usuario->tipo_personal, $usuario->niveles);
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
                            UsuarioBlog::guardarAtributos((int)$usuario->id, $usuario->rol_redaccion, $usuario->tipo_personal, $usuario->niveles);
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
