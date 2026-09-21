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
use Model\Ajuste;
use Model\Visita;
use Model\Actualizacion;
use Model\SolicitudPassword;
use Classes\Pdf;
use Classes\Diccionario;

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

    /** ¿El usuario en sesión puede acceder al módulo indicado? El admin accede a todos. */
    private static function puede(string $modulo): bool {
        $u = $_SESSION['blog_usuario'] ?? null;
        if (!$u) return false;
        // Transversales (soporte + los cuatro directorios): los tiene cualquiera con
        // sesión. La lista vive en el modelo porque también la leen las vistas.
        if (\in_array($modulo, \Model\UsuarioBlog::MODULOS_TRANSVERSALES, true)) return true;
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

    /**
     * Lista de módulos disponibles para el usuario en sesión (para el home/sidebar).
     * ⚠️ Sobre asignables **+ transversales**: si solo mirase la lista blanca de
     * asignables, los cuatro directorios y Soporte desaparecerían del home para todo
     * el mundo, admin incluido, aunque `puede()` los conceda.
     */
    private static function modulosDisponibles(): array {
        $todos = \Model\UsuarioBlog::modulosTodos();
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

                // ⚠️ La baja lógica se comprueba DESPUÉS de verificar la contraseña, no
                // antes: dicho a quien no la acierta, «esa cuenta está dada de baja»
                // convertiría el login en un verificador de qué direcciones pertenecen
                // al claustro. Quien llega hasta aquí ya ha demostrado ser su dueño, así
                // que merece saber por qué no entra en vez de dudar de su teclado.
                $credencialOk = $usuario && password_verify($password, $usuario->password);
                $dadoDeBaja   = $credencialOk && (int)($usuario->activo ?? 1) === 0;

                if ($credencialOk && !$dadoDeBaja) {
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

                if ($dadoDeBaja) {
                    $errorCampo = 'email';
                    UsuarioBlog::setAlerta('error',
                        'Esta cuenta está dada de baja y no puede entrar al panel. '
                        . 'Si sigues en el colegio, pídele a un administrador que la reactive.');
                } elseif (!$usuario) {
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

        // La columna de hoy, solo para quien imparte: a un administrativo la tarjeta le
        // saldría siempre vacía y le empujaría sus módulos fuera de la primera pantalla.
        $hoy = self::imparte() ? self::bloquesDeHoy(self::datosHorarioProfesor($uid)) : null;

        // Gráfica de visitas al sitio público: solo para el admin. A un profesor no le
        // dice nada, y las cuatro series se calculan de golpe para que cambiar de rango
        // no vaya al servidor.
        $esAdmin = self::esAdmin();

        $router->renderAdmin('blog/home', [
            'titulo'        => 'Inicio',
            'modulos'       => self::modulosDisponibles(),
            // Cumpleaños y eventos los ve todo el mundo: son información de
            // convivencia. El módulo `usuarios` controla quién los *edita*.
            'cumpleanosAll' => UsuarioBlog::conCumpleanos(),
            'eventos'       => Evento::todos(),
            'pendientes'    => $pendientes,
            'hoy'           => $hoy,
            'visitas'       => $esAdmin ? Visita::seriesTodas() : null,
            'visitasTop'    => $esAdmin ? Visita::topRutas(30) : [],
            // El hero lleva el mismo bosque WebGL que el login. El layout del panel no
            // carga Three.js por defecto (solo lo necesita esta vista), así que se
            // inyecta aquí igual que en login().
            // Chart.js solo si hay gráfica que pintar: cargarlo para todo el claustro
            // sería una petición de red por usuario que nadie va a usar.
            'extra_head'    => three_js_tag() . ($esAdmin
                ? '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>'
                : ''),
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

    /** ¿Es prefecto? Tipo excluyente: no se combina con ningún otro. */
    private static function esPrefecto(): bool {
        return in_array('prefecto', self::sesionTipos(), true);
    }

    /**
     * ¿Quién registra si el ausente dejó trabajo para el grupo?
     *
     * **Solo prefectura** (más el admin, que puede todo en el panel). Es un dato de
     * campo: quien pisa el aula el día de la ausencia y comprueba si había material
     * es prefectura, no dirección — que lo lee después para decidir, en el tablero.
     *
     * Antes el guard era `puedeAgendar()`, que incluye a `directivo`: dirección podía
     * afirmar un hecho que no le consta, y como el dato alimenta el ranking de
     * «ausencias sin trabajo», eso es imputarle algo a un profesor desde el despacho.
     * Dirección lo sigue VIENDO, en solo lectura.
     */
    private static function puedeMarcarTrabajo(): bool {
        return self::esAdmin() || self::esPrefecto();
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
     * El mismo horario semanal, para el paso 1 de un swap: el profesor marca en
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
                        // swap (`swap_clases.horario_origen_id`), que cambia una
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
        // `txt` entra porque no todo justificante es un escaneo: un permiso administrativo
        // o una constancia interna llega muchas veces como nota de texto, y hasta ahora
        // había que convertirla a PDF para poder adjuntarla. Es también el formato de los
        // ejemplos del seed, que así se pueden leer en un diff.
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'txt'];
        if (!in_array($ext, $allowed, true) || $_FILES['justificante']['size'] > $max * 1024 * 1024) {
            Suplencia::setAlerta('error', "El justificante debe ser PDF, imagen (JPG/PNG/WebP) o texto (TXT) de máximo {$max} MB");
            return null;
        }

        // El nombre del archivo no dice qué hay dentro: se comprueba el MIME real.
        // ⚠️ `mime_content_type()` no es estable con texto plano — devuelve
        // `text/plain`, pero también `text/html` o `application/x-empty` según el
        // contenido y la versión de libmagic. Como la extensión ya está en lista blanca
        // y un .txt no es ejecutable por el servidor (vive fuera de public/ y solo sale
        // por un endpoint con permisos), se acepta cualquier `text/*`.
        $mimeOk = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        $mime   = @mime_content_type($_FILES['justificante']['tmp_name']);
        $esTexto = $ext === 'txt' && (!$mime || str_starts_with($mime, 'text/')
                                      || $mime === 'application/x-empty');
        if ($mime && !$esTexto && !in_array($mime, $mimeOk, true)) {
            Suplencia::setAlerta('error', 'El archivo no es un PDF, una imagen ni un texto válido.');
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
                 'png' => 'image/png', 'webp' => 'image/webp',
                 // `charset` explícito: el parte puede llevar acentos y sin él el
                 // navegador lo interpreta con la codificación del sistema.
                 'txt' => 'text/plain; charset=utf-8'][$ext] ?? 'application/octet-stream';
        $nombre = 'justificante-' . $s->fecha . '.' . $ext;

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($file));
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        // Es un dato de salud: que no quede en ninguna caché intermedia.
        header('Cache-Control: private, no-store');
        readfile($file);
        exit;
    }

    /**
     * Crea las horas de cobertura desde arrays paralelos del POST.
     *
     * `tipo` y `lugar_id` NO llegan del formulario y no deben llegar: la rejilla marca
     * casillas del horario del ausente, así que la naturaleza de cada hora —clase o
     * guardia de receso, y en qué patio— ya está en `horarios` y pedírsela al cliente
     * solo abriría la puerta a que no coincidan. Se leen aquí, de una vez para todas las
     * horas. Sin esto toda hora nacía como 'clase' y las guardias se colaban en la cola
     * de "¿dejó trabajo?".
     */
    private static function guardarHoras(int $supId, array $post): int {
        $periodos = $post['periodo_id'] ?? [];
        if (!$periodos) return 0;

        $tipoDe = self::tipoHorasDeAusente($supId, array_map('intval', (array)$periodos));

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
            $h->tipo         = $tipoDe[$pid]['tipo'] ?? 'clase';
            $h->lugar_id     = $tipoDe[$pid]['lugar_id'] ?? null;
            $h->estado_hora  = 'pendiente';
            $h->guardar();
            $n++;
        }
        return $n;
    }

    /**
     * Para cada periodo, qué es esa hora en el horario del profesor ausente: una clase o
     * una guardia, y en ese caso dónde.
     *
     * @return array<int, array{tipo:string,lugar_id:?int}> periodo_id => …
     */
    private static function tipoHorasDeAusente(int $supId, array $periodoIds): array {
        $sup = Suplencia::find($supId);
        if (!$sup || !$sup->profesor_ausente_id) return [];

        $dow = (int)date('N', strtotime($sup->fecha));
        if ($dow < 1 || $dow > 5) return [];   // fin de semana: no hay jornada

        return Horario::tipoDePeriodos(
            (int)$sup->profesor_ausente_id, Horario::DIAS[$dow - 1], $periodoIds);
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

                    // Y la dirección del nivel, que hasta ahora se enteraba de la
                    // ausencia solo si entraba a mirar la agenda. Va después de
                    // guardarHoras(), que es lo que le da nivel a la suplencia.
                    self::avisarDireccion(
                        Suplencia::nivelesDeSuplencia($nuevaId), 'suplencia_registrada',
                        'Nueva ausencia de ' . ($suplencia->ausente_nombre
                            ?: (UsuarioBlog::find($ausenteId)->nombre ?? 'un profesor'))
                            . ' el ' . fecha_larga($suplencia->fecha) . '.',
                        $nuevaId, 'suplencia', 'suplencias', 'info',
                        '/dashboard/suplencias/agendar?id=' . $nuevaId
                    );

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

        // Y a la dirección del nivel de ESA hora —no de toda la suplencia—: es el reparto
        // que acaba de ocurrir, y una ausencia puede cruzar dos niveles.
        $suplente = UsuarioBlog::find($suplenteId);
        self::avisarDireccion(
            array_filter([$hora->periodo_nivel]), 'cobertura_asignada',
            ($suplente->nombre ?? 'Un profesor') . " cubrirá {$materia}{$donde} el {$cuando}.",
            (int)$suplencia->id, 'suplencia', 'suplencias', 'info',
            '/dashboard/suplencias/agendar?id=' . (int)$suplencia->id
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
     * Una suplencia deja de estar en pie: avisa a TODAS las partes.
     *
     * "Todas" es literal y es el punto de este método: el profesor ausente, **cada
     * suplente que ya tenía una hora asignada** y la dirección del nivel. El suplente es
     * el que más lo necesita y el que antes se quedaba sin enterarse — tenía la cobertura
     * apuntada y la clase desaparecía sin una palabra.
     *
     * Hay que llamarlo ANTES del DELETE en el caso de borrado: `suplencia_horas` cae por
     * CASCADE y con ella la lista de a quién avisar.
     *
     * @param string $como 'cancelar' (se conserva el registro) | 'eliminar' (se borró)
     */
    private static function avisarSuplenciaAnulada(Suplencia $sup, string $como, string $motivo = ''): void {
        $id     = (int)$sup->id;
        $cuando = fecha_larga($sup->fecha);
        $quien  = $sup->ausente_nombre ?: 'un profesor';
        $porque = $motivo !== '' ? ' Motivo: ' . $motivo : '';
        $yo     = (int)($_SESSION['blog_usuario']['id'] ?? 0);
        $verbo  = $como === 'cancelar' ? 'canceló' : 'eliminó';

        // Un registro cancelado se puede seguir abriendo; uno eliminado ya no existe, así
        // que su aviso no debe llevar a una pantalla que responderá "no existe".
        $enlace = $como === 'cancelar' ? '/dashboard/suplencias/agendar?id=' . $id : '';

        // 1. Los suplentes que ya tenían hora asignada. Se deduplica: un mismo profesor
        //    puede cubrir varias horas de la misma ausencia y recibiría un aviso por cada
        //    una, y como "marcar como leída = BORRAR" tendría que cerrarlos uno a uno.
        $avisados = [];
        foreach (SuplenciaHora::deSuplencia($id) as $h) {
            $sid = (int)($h->suplente_id ?? 0);
            if (!$sid || $sid === $yo || isset($avisados[$sid])) continue;
            $avisados[$sid] = true;
            Notificacion::nueva(
                $sid, 'cobertura_anulada',
                "Ya no tienes que cubrir la clase del {$cuando}: la suplencia de {$quien} se {$verbo}.{$porque}",
                $id, 'suplencia', 'suplencias', 'aviso',
                '/dashboard/suplencias/mis-coberturas'
            );
        }

        // 2. El profesor ausente, salvo que sea quien la está anulando.
        $ausenteId = (int)$sup->profesor_ausente_id;
        if ($ausenteId && $ausenteId !== $yo) {
            Notificacion::nueva(
                $ausenteId, 'suplencia_anulada',
                "Tu ausencia del {$cuando} se {$verbo}.{$porque}",
                $id, 'suplencia', 'suplencias', 'aviso', $enlace
            );
        }

        // 3. La dirección del nivel. Se calcula antes de que desaparezcan las horas, que
        //    es de donde sale el nivel (via periodos, el único camino fiable: grupo_id y
        //    materia_id van NULL en las guardias).
        self::avisarDireccion(
            Suplencia::nivelesDeSuplencia($id), 'suplencia_anulada',
            "La suplencia de {$quien} del {$cuando} se {$verbo}.{$porque}",
            $id, 'suplencia', 'suplencias', 'aviso', $enlace
        );
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

            // ⚠️ La hora tiene que ser DE ESTA suplencia. requireAlcance() validó el
            // `?id=`, no el `hora_id`, así que sin esto un coordinador con alcance sobre
            // la suplencia A podía desasignar o borrar una hora de la B mandando
            // `id=A&hora_id=<hora de B>`. Misma precaución que marcarTrabajo().
            if ($horaId && !SuplenciaHora::esDeSuplencia($horaId, $id)) {
                header('Location: /dashboard/suplencias/agendar?id=' . $id); exit;
            }

            // Las horas se fijan al abrir la suplencia desde el horario del ausente:
            // aquí solo se asigna o se retira al suplente.
            if ($accion === 'asignar' && $horaId) {
                $suplenteId = (int)($_POST['suplente_id'] ?? 0);
                // El JS no pinta el botón de confirmar sobre un candidato bloqueado,
                // pero eso es cortesía, no un guard: el POST llega igual desde una
                // pestaña vieja, el botón atrás o un segundo coordinador trabajando a la
                // vez sobre el mismo día. La regla se comprueba AQUÍ, contra sugerir().
                $bloqueo = SuplenciaHora::motivoBloqueo($horaId, $suplenteId, $id);
                if ($bloqueo !== null) {
                    header('Location: /dashboard/suplencias/agendar?id=' . $id
                           . '&nodisponible=' . urlencode($bloqueo));
                    exit;
                }
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
            // Escribir "¿dejó trabajo?" es más estrecho que agendar: solo prefectura.
            'marcaTrabajo' => self::puedeMarcarTrabajo(),
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
            'icono'  => match (true) {
                $ext === 'pdf' => 'fa-file-pdf',
                $ext === 'txt' => 'fa-file-lines',
                in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) => 'fa-file-image',
                default => 'fa-file',
            },
        ];
    }

    /** El profesor ausente sube su justificante (flujo sin aviso). */
    public static function justificarSuplencia(Router $router) {
        // Faltaban los dos guards de módulo y alcance: con solo `requireAuth()`, una
        // dirección de nivel podía adjuntar un archivo a una suplencia de otro nivel —
        // que después no vería en su cola ni podría descargar.
        $sesion = self::requireAuth();
        self::requireModulo('suplencias');
        $id = (int)($_POST['id'] ?? 0);
        $sup = Suplencia::find($id);
        if ($sup && $_SERVER['REQUEST_METHOD'] === 'POST') {
            // El propio ausente o dirección. Prefectura ya no sube el justificante de
            // otro: subirlo es tenerlo en la mano, y el documento no es suyo.
            // El alcance solo se exige a quien NO es el ausente: un profesor sube el
            // suyo sin que su nivel entre en juego.
            if ((int)$sup->profesor_ausente_id !== (int)$sesion['id']) self::requireAlcance($id);
            if ((int)$sup->profesor_ausente_id === (int)$sesion['id'] || self::puedeVerJustificante()) {
                $ruta = self::subirJustificante();
                if ($ruta) {
                    $r = Suplencia::getDB()->escape_string($ruta);
                    Suplencia::getDB()->query("UPDATE suplencias SET justificante='{$r}' WHERE id={$id} LIMIT 1");
                    // Sella la subida y limpia cualquier resolución previa: subir un
                    // archivo nuevo reinicia el ciclo de revisión.
                    Suplencia::marcarSubida($id);
                    Suplencia::recalcularEstado($id);

                    // Avisar a dirección: revisar el justificante es competencia SUYA
                    // (puedeVerJustificante() = admin o directivo), y el documento tiene
                    // plazo — a los DIAS_DESCARGA pasa a la cola y a los DIAS_PURGA se
                    // borra solo. Sin aviso, el reloj corría sin que nadie lo supiera.
                    $quien = UsuarioBlog::find((int)$sup->profesor_ausente_id);
                    self::avisarDireccion(
                        Suplencia::nivelesDeSuplencia($id), 'justificante_subido',
                        'Justificante de ' . ($quien->nombre ?? 'un profesor')
                            . ' para la ausencia del ' . fecha_larga($sup->fecha) . '.',
                        $id, 'suplencia', 'suplencias', 'info',
                        '/dashboard/suplencias/agendar?id=' . $id
                    );
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
        // Solo prefectura (y admin). Ver puedeMarcarTrabajo(): dirección lo lee, no lo escribe.
        if (!self::puedeMarcarTrabajo()) { header('Location: /dashboard/suplencias'); exit; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /dashboard/suplencias'); exit; }

        $horaId = (int)($_POST['hora_id'] ?? 0);
        $supId  = (int)($_POST['id'] ?? 0);
        self::requireAlcance($supId);

        // ⚠️ `requireAlcance()` valida la SUPLENCIA del POST, no la hora. Sin esta
        // comprobación, quien tuviera alcance sobre la suplencia A podía marcar una hora
        // de la B mandando `id=A&hora_id=<hora de B>`: el modelo solo exige $horaId > 0.
        $hora = SuplenciaHora::detalle($horaId);
        if (!$hora || (int)$hora->suplencia_id !== $supId) {
            header('Location: /dashboard/suplencias?sinacceso=nivel');
            exit;
        }

        $v    = (string)($_POST['dejo'] ?? '');
        $dejo = $v === '1' ? true : ($v === '0' ? false : null);
        SuplenciaHora::marcarTrabajo($horaId, $dejo, $_POST['notas'] ?? null,
                                     (int)($_SESSION['blog_usuario']['id'] ?? 0));

        // `volver=cola` devuelve a la cola de pendientes en vez de a la suplencia: es
        // el flujo de repaso, donde se marcan varias seguidas.
        $destino = ($_POST['volver'] ?? '') === 'cola'
            ? '/dashboard/suplencias/trabajo-pendiente?trabajo=1'
            : '/dashboard/suplencias/agendar?id=' . $supId . '&trabajo=1';
        header('Location: ' . $destino);
        exit;
    }

    /**
     * Cola de horas cuyo "¿dejó trabajo?" sigue sin revisar.
     *
     * Hasta ahora el único rastro de `dejo_trabajo IS NULL` era una cifra en el KPI del
     * tablero que no enlazaba a ningún sitio — y que prefectura, que es justo quien tiene
     * que rellenarlo, ni siquiera veía, porque el tablero es de admin y dirección. Para
     * marcar una hora había que recordar en qué suplencia estaba y abrirla una a una.
     */
    public static function trabajoPendiente(Router $router) {
        self::requireModulo('suplencias');
        if (!self::puedeMarcarTrabajo()) { header('Location: /dashboard/suplencias'); exit; }

        $router->renderAdmin('blog/suplencias/trabajo-pendiente', [
            'titulo'    => 'Trabajo por revisar',
            'pendientes'=> SuplenciaHora::pendientesTrabajo(self::nivelesVista()),
        ]);
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

    /**
     * Editar una suplencia ya abierta.
     *
     * Editable: motivo, notas y justificante. **La fecha y el profesor ausente NO**, y no
     * es una limitación de la pantalla sino del dato: las horas se fijaron leyendo el
     * horario de ESA persona en ESE día, así que cambiar cualquiera de los dos dejaría
     * unas horas que no corresponden a nada, con coberturas ya asignadas y notificadas
     * sobre ellas. Para eso está cancelar y volver a abrir.
     */
    public static function editarSuplencia(Router $router) {
        self::requireModulo('suplencias');
        if (!self::puedeAgendar()) { header('Location: /dashboard/suplencias'); exit; }

        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        self::requireAlcance($id);
        $suplencia = Suplencia::encontrarConDetalle($id);
        if (!$suplencia) { header('Location: /dashboard/suplencias?noexiste=1'); exit; }

        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $suplencia->motivo = Suplencia::motivoDesdePost($_POST);
            $suplencia->notas  = trim((string)($_POST['notas'] ?? ''));

            $alertas = $suplencia->validar();
            $justificante = self::subirJustificante();
            $alertas = Suplencia::getAlertas();

            if (empty($alertas['error'])) {
                // Reemplazar el archivo se lleva el anterior: vive fuera de public/ y no
                // lo borra ninguna FK, así que si no se quedaría huérfano para siempre.
                if ($justificante) {
                    $viejo = self::rutaJustificante($suplencia->justificante);
                    if ($viejo && is_file($viejo)) @unlink($viejo);
                    $suplencia->justificante = $justificante;
                    Suplencia::marcarSubida($id);
                }
                $suplencia->guardar();
                // Subir el comprobante puede sacarla de 'por_justificar'.
                Suplencia::recalcularEstado($id);
                header('Location: /dashboard/suplencias/agendar?id=' . $id . '&editada=1');
                exit;
            }
        }

        $router->renderAdmin('blog/suplencias/editar', [
            'titulo'     => 'Editar suplencia',
            'suplencia'  => $suplencia,
            'alertas'    => $alertas,
            // Mismo criterio que agendar: el nombre y el peso del archivo no se calculan
            // —ni llegan al HTML— para quien no puede abrirlo.
            'veJustif'   => self::puedeVerJustificante(),
            'justifInfo' => self::puedeVerJustificante()
                              ? self::infoJustificante($suplencia->justificante) : null,
        ]);
    }

    /**
     * Cancelar una suplencia que ya no hace falta. NO la borra: el registro se conserva
     * con `estado = 'cancelada'` (ver Suplencia::cancelar()) y se avisa a todas las
     * partes — al ausente, a cada suplente que ya tenía hora asignada y a la dirección.
     */
    public static function cancelarSuplencia(Router $router) {
        self::requireModulo('suplencias');
        if (!self::puedeAgendar()) { header('Location: /dashboard/suplencias'); exit; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /dashboard/suplencias'); exit; }

        $id = (int)($_POST['id'] ?? 0);
        self::requireAlcance($id);
        $sup = Suplencia::encontrarConDetalle($id);
        if (!$sup) { header('Location: /dashboard/suplencias?noexiste=1'); exit; }

        $motivo = trim((string)($_POST['motivo_cancelacion'] ?? ''));

        // Se avisa ANTES de cancelar, mientras las horas siguen diciendo quién cubría.
        // Aquí no se borra nada, pero el orden se mantiene igual que en eliminar para
        // que las dos rutas se lean igual y nadie lo invierta "porque da lo mismo".
        self::avisarSuplenciaAnulada($sup, 'cancelar', $motivo);
        Suplencia::cancelar($id, $motivo);

        header('Location: /dashboard/suplencias/agendar?id=' . $id . '&cancelada=1');
        exit;
    }

    /**
     * Borrado DURO de una suplencia. Para dejar de necesitarla sin perder el registro
     * está `cancelarSuplencia()`, que es lo que hay que usar casi siempre: esto es para
     * la que no debería haberse abierto nunca.
     */
    public static function eliminarSuplencia(Router $router) {
        self::requireModulo('suplencias');
        if (!self::puedeAgendar()) { header('Location: /dashboard/suplencias'); exit; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            // Faltaba: filtrar el listado por nivel no sirve de nada si por ?id= se
            // puede borrar la suplencia de otro nivel. El resto de POST del módulo
            // (trabajo, validar-prefectura, reabrir-hora, resolver) ya lo hacían.
            self::requireAlcance($id);
            $s = Suplencia::find($id);
            if ($s) {
                // Avisar antes del DELETE: después no queda a quién. `suplencia_horas`
                // cae por ON DELETE CASCADE y con ella la lista de suplentes.
                self::avisarSuplenciaAnulada($s, 'eliminar');
                // El parte médico vive fuera de public/ y no lo borra ninguna FK: sin
                // esto se quedaba huérfano en disco para siempre.
                $ruta = self::rutaJustificante($s->justificante);
                if ($ruta && is_file($ruta)) @unlink($ruta);
                $s->eliminar();
            }
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
     * Listado de swaps. Dos públicos, como en Suplencias:
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
        // swap: le compete si cualquiera de las dos clases es de su nivel.
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
     * Alta de un swap. Dos caminos según quién lo abre:
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
            // primero, y anuncia que el swap nacerá ya validado.
            'coordina'    => $coordina,
            'uid'         => $uid,
        ]);
    }

    /**
     * Un swap validado llega a la dirección de los niveles que toca.
     * Son uno o dos: un swap cruza dos clases y pueden ser de niveles distintos.
     */
    private static function avisarSwapDireccion(int $swapId, string $tipo, string $texto): void {
        $sw = \Model\Swap::encontrar($swapId);
        if (!$sw) return;
        self::avisarDireccion($sw->niveles(), $tipo, $texto,
            $swapId, 'swap', 'horarios', 'info', '/dashboard/swaps');
    }

    /**
     * Busca al compañero con quien cambiar la clase. Solo personal docente y nunca uno
     * mismo: cambiarla con quien no da clase no significa nada, y consigo mismo lo
     * rechaza Swap::validar() después de haber dejado elegirlo.
     */
    public static function buscarProfesoresSwap(Router $router) {
        self::requireModulo('swaps');
        if (!self::imparte() && !self::puedeCoordinar()) self::json(['error' => 'Sin acceso'], 403);
        // Excluirse a uno mismo solo tiene sentido buscando al COMPAÑERO. Quien
        // coordina designa a los dos profesores y no es parte del swap, así que
        // filtrarlo dejaría fuera a un admin que además imparte.
        $excluir = self::puedeCoordinar() ? 0 : (int)($_SESSION['blog_usuario']['id'] ?? 0);
        self::json(UsuarioBlog::buscarProfesores((string)($_GET['q'] ?? ''), $excluir));
    }

    /**
     * Clases de un profesor en un rango de fechas concreto, para el selector del
     * swap. Devuelve una entrada por (clase × día del rango en que se imparte),
     * porque lo que se cambia es una clase EN UN DÍA, no la clase en abstracto.
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
                if (($h->tipo ?? 'clase') === 'guardia') continue;   // una guardia no se cambia
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
            // El swap efectivo llega a la dirección de su nivel.
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
            'titulo'      => 'Eventos',
            'eventos'     => Evento::todos(),
            // Interruptor del botón de descarga en Comunidad › Familias. Vive aquí
            // —y no en una pantalla de ajustes que no existe— porque lo que habilita
            // es el calendario que se llena desde este módulo.
            'calendarioPdf' => Ajuste::bool(Ajuste::CALENDARIO_PDF, false),
            'ciclo'         => Evento::ciclo(),
        ]);
    }

    /**
     * Interruptor «el calendario del ciclo se puede descargar en PDF desde la web».
     *
     * Mismo guard que el resto del módulo (`requireModulo('eventos')`) y no
     * `requireAdmin()`: quien puede crear un evento con audiencia `familias` ya está
     * publicando en esa misma página, así que exigir más aquí sería una frontera
     * inventada. El valor vive en `ajustes`, no en una columna de `eventos`: no es
     * propiedad de ningún evento sino del sitio.
     */
    public static function ajustesEventos(Router $router) {
        self::requireModulo('eventos');
        $ok = true;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Una casilla desmarcada no se envía: la ausencia del campo es el "no".
            $ok = Ajuste::guardarBool(Ajuste::CALENDARIO_PDF, !empty($_POST['calendario_pdf']));
        }
        // Se distingue el fallo porque el interruptor vuelve solo a su posición
        // anterior al recargar: sin avisar, eso se lee como que el panel lo ignora.
        header('Location: /dashboard/eventos?ajuste=' . ($ok ? '1' : '0'));
        exit;
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

    /**
     * Guard de la ficha de un colaborador AJENO: sesión → existe → **coordinar** (admin,
     * prefecto o directivo). La ficha expone motivos de ausencia y horarios de terceros,
     * que es la misma frontera que separa la agenda del histórico del plantel.
     *
     * Vive extraído porque lo comparten `detalleUsuario()` y `horarioUsuarioPdf()`: el
     * PDF enseña exactamente el mismo horario ajeno que la ficha, así que tiene que
     * pedir exactamente lo mismo. Copiarlo en los dos sitios es como se desincronizan
     * dos puertas que dan al mismo cuarto.
     *
     * ⚠️ Hubo una tercera puerta —módulo `usuarios` **o** el directorio del tipo de la
     * persona mirada, vía un mapa `DIRECTORIO_DE_TIPO`— que acotaba a un prefecto a las
     * fichas de los tipos cuyo directorio tuviera asignado. Se retiró al volverse
     * transversales los cuatro directorios (`UsuarioBlog::MODULOS_TRANSVERSALES`): con
     * `puede('profesores')` devolviendo siempre `true`, la condición era ya un `true`
     * constante. Dejarla escrita habría aparentado decidir algo que no podía decidir.
     *
     * `/dashboard/perfil` NO pasa por aquí: son los datos de uno mismo y le basta
     * `requireAuth()`.
     *
     * Sale por `exit` en los tres cortes; devuelve el usuario si pasa.
     */
    private static function requireFichaColaborador(int $id): UsuarioBlog {
        self::requireAuth();

        $u = $id > 0 ? UsuarioBlog::findConArticulos($id) : null;
        if (!$u) { header('Location: /dashboard'); exit; }

        /* ⚠️ Ya NO se exige puedeCoordinar(). La ficha es la guía de personal del
           claustro —quién es, qué imparte, cuándo está en clase y dónde—, y eso lo
           necesita cualquiera que trabaje aquí: saber si puede interrumpir a alguien
           ahora mismo no es un dato de gestión.

           Lo que sí sigue cerrado es lo que se ve DENTRO: datosFicha() entrega las
           ausencias, sus motivos, las coberturas y los intercambios solo a quien coordina
           o al propio interesado (ver `$puedeVerHistorial`). Los justificantes siguen
           siendo de dirección y no pasan por aquí en ningún caso. */
        return $u;
    }

    /**
     * ¿Quien mira puede ver el HISTORIAL de esta persona (ausencias con su motivo,
     * coberturas e intercambios), o solo su identidad y su horario?
     *
     * Es la frontera que antes marcaba el guard de entrada. Al abrirse la ficha a todo el
     * claustro hubo que bajarla un nivel: la pantalla es de todos, el historial no.
     */
    private static function puedeVerHistorial(int $idFicha): bool {
        return self::puedeCoordinar()
            || (int)($_SESSION['blog_usuario']['id'] ?? 0) === $idFicha;
    }

    /**
     * Datos de la ficha de UNA persona: identidad, horario, ausencias, coberturas e
     * intercambios. Devuelve el array de render, sin decidir quién puede verlo.
     *
     * **No ejecuta ni una consulta nueva.** Reutiliza `datosHorarioProfesor()` —la misma
     * fuente que "Mi horario" y su PDF, así que las tres no pueden pintar semanas
     * distintas— y los métodos que ya sabían ceñirse a UNA persona (`listar(ausente_id)`,
     * `conteos($id)`, `historicoDeSuplente()`, `deProfesor()`). Las estadísticas se
     * calculan en PHP sobre esos arrays.
     *
     * Extraído porque lo consumen las DOS puertas a esta pantalla —`detalleUsuario()`
     * (una persona ajena) y `perfil()` (uno mismo)—, por el mismo motivo que
     * `datosHorarioVista()`: dos vistas del mismo dato que se calculan por separado
     * acaban divergiendo.
     *
     * `$niveles` acota por el alcance de una dirección de nivel; `[]` no filtra nada.
     */
    private static function datosFicha(UsuarioBlog $u, array $niveles = []): array {
        $id        = (int)$u->id;
        $tipos     = array_filter(array_map('trim', explode(',', (string)$u->tipo_personal)));
        $esDocente = in_array('profesor', $tipos, true);

        // Horario: aquí solo interesa si tiene clases. Un administrativo entra con
        // `tramos` vacío y la vista omite la sección en vez de pintar una rejilla en
        // blanco.
        $horario = $esDocente ? self::datosHorarioProfesor($id) : null;

        /* La ficha la abre cualquiera con sesión, pero el historial no es de todos: las
           ausencias llevan su motivo, y las coberturas e intercambios son el registro de
           cómo ha trabajado esta persona. Eso se queda en quien coordina y en el propio
           interesado. Sin el flag, abrir la ficha al claustro habría publicado de paso el
           motivo de cada baja médica. */
        $verHistorial = self::puedeVerHistorial($id);
        $conDatos     = $esDocente && $verHistorial;

        $ausencias  = $conDatos ? Suplencia::listar(['ausente_id' => $id, 'niveles' => $niveles]) : [];
        $conteos    = $conDatos ? Suplencia::conteos($id, $niveles) : [];
        $coberturas = $conDatos ? SuplenciaHora::historicoDeSuplente($id) : [];
        // FQN como el resto del módulo: `Swap` no está en los `use`.
        $swaps      = $conDatos ? \Model\Swap::deProfesor($id) : [];

        // Resúmenes en PHP sobre lo ya cargado: `topIncumplimientos()` y
        // `contarIncumplimientos()` son del plantel entero, forma equivocada aquí y una
        // consulta de más.
        $porEstadoHora = array_count_values(array_map(
            fn($h) => (string)$h->estado_hora, $coberturas));
        $porEstadoSwap = array_count_values(array_map(
            fn($sw) => (string)$sw->estado, $swaps));

        return [
            'titulo'        => $u->nombre,
            'u'             => $u,
            'tipos'         => $tipos,
            'esDocente'     => $esDocente,
            'contenido'     => UsuarioBlog::contenidoDeAutor($id),
            'ausencias'     => $ausencias,
            'conteos'       => $conteos,
            'coberturas'    => $coberturas,
            'porEstadoHora' => $porEstadoHora,
            'swaps'         => $swaps,
            'porEstadoSwap' => $porEstadoSwap,
            'acotado'       => !empty($niveles),
            // La vista lo usa para distinguir «no tiene ausencias» de «no puedes verlas»:
            // un empty state que miente es peor que una sección ausente.
            'verHistorial'  => $verHistorial,
            // Claves del horario (tramos, rejilla, ocupadoPorDia, totalClases,
            // discrepantes…). `profesor` se pisa con `$u`, que es el mismo registro con
            // `total_articulos` de propina.
            'horario'       => $horario,
            // La columna de hoy con la clase en curso. Va a TODO el que abra la ficha,
            // incluido quien no ve el historial: saber si esta persona está ahora mismo
            // en clase es justo lo que se viene a consultar, y no es un dato sensible.
            'hoy'           => $esDocente ? self::bloquesDeHoy($horario) : null,
        ];
    }

    /**
     * Ficha interna de un colaborador AJENO: identidad, horario, ausencias, coberturas
     * e intercambios en una sola pantalla.
     *
     * Hasta ahora lo más parecido a una ficha era el formulario de edición, que solo
     * abre un admin y que no dice nada de lo que esa persona hace: quien coordina tenía
     * que cruzar a mano el módulo de horarios, la agenda de suplencias y el listado de
     * intercambios para hacerse una idea.
     *
     * El guard vive en `requireFichaColaborador()`, compartido con el PDF del horario
     * (`horarioUsuarioPdf()`). La versión sobre uno mismo es `perfil()`, que comparte
     * plantilla y datos pero no guard.
     */
    public static function detalleUsuario(Router $router) {
        $id = (int)($_GET['id'] ?? 0);
        $u  = self::requireFichaColaborador($id);

        $router->renderAdmin('blog/usuarios/detalle',
            self::datosFicha($u, self::nivelesAlcance()) + ['esPropio' => false]);
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

        // El nivel no declarado no bloquea (es opcional por diseño), pero sí se AÑADE a
        // su ficha: si le acabas de dar una clase de Secundaria, imparte en Secundaria, y
        // hasta ahora eso solo lo escribía el importador CSV. Acota su rejilla y prioriza
        // sus suplencias, así que dejarlo desfasado tiene efectos reales.
        $declarados = UsuarioBlog::nivelesDe($profId);
        if ($declarados && !in_array($nivel, $declarados, true)) {
            self::anadirNivelDocente($profId, $nivel);
            $_SESSION['horario_editor'] = ['nivelAnadido' => $nivel];
        }
        header('Location: ' . $volver . '&ok=' . $n);
        exit;
    }

    /**
     * Añade un nivel a los declarados de un profesor, sin quitar ninguno.
     *
     * ⚠️ Es UNIÓN y no recálculo, a propósito. El recálculo completo desde `horarios` lo
     * hace el importador CSV, que reemplaza la semana entera y por tanto sabe lo que hay;
     * aquí solo se ha tocado un bloque. Recalcular desde una edición parcial borraría un
     * nivel declarado a mano para alguien que todavía no tiene horario cargado — que es
     * exactamente el caso que la columna vino a resolver (ver `usuarios.niveles` en
     * CLAUDE.md: la fuente declarativa existe porque deducirlo de las clases fallaba sin
     * horario). Para poner al día a todo el claustro está el UPDATE de database/CLAUDE.md.
     */
    private static function anadirNivelDocente(int $profId, string $nivel): void {
        if (!in_array($nivel, Materia::NIVELES, true)) return;
        $actuales = UsuarioBlog::nivelesDe($profId);
        if (in_array($nivel, $actuales, true)) return;

        // Se reordena por el orden académico y no por el de llegada: es el mismo criterio
        // con el que los pinta la ficha y con el que los guarda el importador.
        $union = array_values(array_intersect(Materia::NIVELES, array_merge($actuales, [$nivel])));
        UsuarioBlog::guardarNiveles($profId, $union);
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

    /**
     * Guard de las tres vistas del módulo Horarios y de su PDF.
     *
     * Exponen el horario de TODO el claustro, de todas las aulas y de todos los grupos:
     * el desplegable se llena con el catálogo completo y `?id=` no se compara con la
     * sesión. Las abre quien coordina; un profesor se queda con su propio horario en
     * /mi-horario, que es a donde se le manda.
     */
    private static function requireHorariosVista(): void {
        self::requireModulo('horarios');
        if (!self::puedeCoordinar()) {
            header('Location: /dashboard/horarios/mi-horario');
            exit;
        }
    }

    /**
     * Los datos de una vista de Horarios (por profesor, aula o grupo).
     *
     * Fuente única de la pantalla y del PDF, igual que `datosHorarioProfesor()` lo es de
     * "Mi horario" y el suyo: si cada una montara su rejilla podrían acabar pintando
     * semanas distintas del mismo grupo.
     *
     * `entidad` es el objeto seleccionado (UsuarioBlog | Aula | Grupo) o null; los tres
     * tienen `nombre`, que es lo único que la cabecera necesita de él.
     *
     * ⚠️ El selector solo lista lo ACTIVO. Un aula, un grupo o un profesor dados de baja
     * tienen la semana vacía por definición —la importación que los apagó vació antes la
     * rejilla—, así que ofrecerlos sería ofrecer pantallas en blanco.
     */
    private static function datosHorarioVista(string $vista, int $entidadId): array {
        if ($vista === 'aula') {
            $entidades = Aula::todas();
            $titulo    = 'Horarios por aula';
        } elseif ($vista === 'grupo') {
            $entidades = Grupo::todos();
            $titulo    = 'Horarios por grupo';
        } else {
            $vista     = 'profesor';
            $entidades = UsuarioBlog::porTipo('profesor', false);
            $titulo    = 'Horarios por profesor';
        }

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

        // Minutos de CLASE por día. Solo lo consume el PDF (la pantalla no pinta la
        // carga en estas tres vistas), pero se calcula aquí para no tener dos sitios
        // sumando lo mismo. Va en minutos y no en celdas: con el eje mezclado un
        // fragmento de 20 min contaría como una hora entera.
        $ocupadoPorDia = [];
        foreach (Horario::DIAS as $d) {
            $ocupadoPorDia[$d] = array_sum(array_map(
                fn($c) => Periodo::minutos($c['inicio'], $c['fin']),
                array_filter($rejilla['rejilla'][$d] ?? [], fn($c) => $c['tipo'] === 'clase')
            ));
        }

        $entidad = null;
        if ($entidadId) {
            $entidad = match ($vista) {
                'aula'  => Aula::find($entidadId),
                'grupo' => Grupo::find($entidadId),
                default => UsuarioBlog::find($entidadId),
            };
        }

        return [
            'titulo'        => $titulo,
            'vista'         => $vista,
            'entidades'     => $entidades,
            'entidadId'     => $entidadId,
            'entidad'       => $entidad,
            'niveles'       => $ambito['niveles'],
            'discrepantes'  => $ambito['discrepantes'],
            'tramos'        => $rejilla['tramos'],
            'rejilla'       => $rejilla['rejilla'],
            'ocupadoPorDia' => $ocupadoPorDia,
            'totalClases'   => count($filas),
        ];
    }

    private static function horariosVista(Router $router, string $vista): void {
        self::requireHorariosVista();
        $d = self::datosHorarioVista($vista, (int)($_GET['id'] ?? 0));

        $router->renderAdmin('blog/horarios/index', [
            'titulo'       => $d['titulo'],
            'vista'        => $d['vista'],
            'entidades'    => $d['entidades'],
            'entidadId'    => $d['entidadId'],
            'niveles'      => $d['niveles'],
            'discrepantes' => $d['discrepantes'],
            'tramos'       => $d['tramos'],
            'rejilla'      => $d['rejilla'],
        ]);
    }
    public static function horariosProfesor(Router $router) { self::horariosVista($router, 'profesor'); }
    public static function horariosAula(Router $router)     { self::horariosVista($router, 'aula'); }
    public static function horariosGrupo(Router $router)    { self::horariosVista($router, 'grupo'); }

    /**
     * La semana que se está viendo, en PDF. Sirve a las tres vistas del módulo
     * (`?vista=profesor|aula|grupo&id=N`) con el mismo guard que la pantalla.
     *
     * La plantilla es la misma que la de "Mi horario": solo necesita del sujeto su
     * `nombre`, y eso lo tienen igual un profesor, un aula y un grupo. `$subtitulo`
     * distingue de qué se trata en el encabezado, porque "3A Secundaria" a secas no
     * dice si es el horario del grupo o el del aula que se llama así.
     */
    public static function horariosPdf(Router $router) {
        self::requireHorariosVista();

        $vista = (string)($_GET['vista'] ?? 'profesor');
        $d     = self::datosHorarioVista($vista, (int)($_GET['id'] ?? 0));
        if (!$d['entidad']) { header('Location: /dashboard/horarios/' . $d['vista']); exit; }

        self::emitirHorarioPdf([
            'profesor'      => $d['entidad'],
            'subtitulo'     => match ($d['vista']) {
                'aula'  => 'Horario del aula',
                'grupo' => 'Horario del grupo',
                default => 'Horario del profesor',
            },
            'niveles'       => $d['niveles'],
            'discrepantes'  => $d['discrepantes'],
            'tramos'        => $d['tramos'],
            'rejilla'       => $d['rejilla'],
            'ocupadoPorDia' => $d['ocupadoPorDia'],
            'totalClases'   => $d['totalClases'],
        ]);
    }

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
     * La columna de HOY, aplanada para `views/blog/_horario-ahora.php`.
     *
     * Reutiliza `datosHorarioProfesor()` en vez de consultar por su cuenta: es la misma
     * fuente que «Mi horario», su PDF y la ficha, así que las cuatro no pueden acabar
     * pintando días distintos. Aquí solo se traduce la celda de `rejilla()` a lo que la
     * tarjeta necesita — hora, qué es y de qué color.
     *
     * @return array{bloques: array, dia: string}  `dia` vacío = hoy no hay jornada
     */
    private static function bloquesDeHoy(?array $horario): array {
        $dow = (int)date('N');
        // Sábado y domingo: no hay jornada que pintar y la tarjeta lo dice.
        if ($dow < 1 || $dow > 5 || !$horario) return ['bloques' => [], 'dia' => ''];

        $dia    = Horario::DIAS[$dow - 1];
        $celdas = $horario['rejilla'][$dia] ?? [];

        $out = [];
        foreach ($celdas as $c) {
            if ($c['tipo'] === 'clase') {
                $h = $c['horario'];
                $out[] = [
                    'ini'     => $c['inicio'],
                    'fin'     => $c['fin'],
                    'materia' => $h->tipo === 'guardia' ? 'Guardia' : ($h->materia ?: 'Clase'),
                    'grupo'   => $h->tipo === 'guardia' ? ($h->lugar_nombre ?? '') : ($h->grupo_nombre ?? ''),
                    'aula'    => $h->tipo === 'guardia' ? '' : ($h->aula_nombre ?? ''),
                    // Mismo color que la rejilla y que el PDF: lo calcula colorMateria(),
                    // que es la fuente única desde que había tres copias del crc32().
                    'color'   => self::colorMateria($h->materia ?? '', $h->color ?? null)['hex'],
                    'receso'  => false,
                ];
            } elseif ($c['tipo'] === 'receso') {
                $out[] = ['ini' => $c['inicio'], 'fin' => $c['fin'], 'materia' => '',
                          'grupo' => '', 'aula' => '', 'color' => '#f5b400', 'receso' => true];
            } else {
                $out[] = ['ini' => $c['inicio'], 'fin' => $c['fin'], 'materia' => '',
                          'grupo' => '', 'aula' => '', 'color' => '#cbd5e1', 'receso' => false];
            }
        }

        return ['bloques' => $out, 'dia' => $dia];
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
        self::emitirHorarioPdf($datos);
    }

    /**
     * El horario de OTRO colaborador en PDF, desde su ficha.
     *
     * Ruta aparte de `mi-horario.pdf` a propósito, y no un `?id=` opcional sobre ella:
     * aquella tiene guard `requireAuth()` a secas porque el horario que sirve es el de
     * quien pide. Un único endpoint con dos niveles de autorización decididos dentro de
     * un `if` es la forma que alguien "simplifica" seis meses después, y el fallo sería
     * una fuga de horarios ajenos. La frontera ya existe en el panel: `/horarios/mi-horario`
     * es lo propio, `/usuarios/horario?id=` es lo de otro.
     *
     * Mismo guard que la ficha, literalmente el mismo código: ambas enseñan el horario
     * de un tercero.
     */
    public static function horarioUsuarioPdf(Router $router) {
        $id = (int)($_GET['id'] ?? 0);
        self::requireFichaColaborador($id);

        $datos = self::datosHorarioProfesor($id);
        if ($datos === null) { header('Location: /dashboard/usuarios/detalle?id=' . $id); exit; }
        self::emitirHorarioPdf($datos);
    }

    /**
     * Render + descarga del PDF de un horario. No decide QUÉ horario ni QUIÉN puede
     * verlo: eso lo resuelve quien la llama, que es donde vive el guard.
     *
     * El nombre del archivo sale de `$datos['profesor']`, así que sirve igual al horario
     * propio y al de un tercero sin ninguna rama.
     */
    private static function emitirHorarioPdf(array $datos): void {
        // El CSS vive en src/scss como todo lo demás (aquí no vale una hoja enlazada:
        // Dompdf no resuelve URLs del sitio). Se compila a este archivo y se inyecta,
        // precedido de las @font-face de Outfit, que necesitan rutas absolutas de
        // disco y por eso las arma PHP y no el SCSS.
        ob_start();
        $pdfCss   = Pdf::hojaCss('horario-pdf.css');
        $logoData = Pdf::logo();
        extract($datos);
        require __DIR__ . '/../views/blog/horarios/pdf.php';
        $html = ob_get_clean();

        Pdf::emitir($html, 'horario-' . Pdf::slug($datos['profesor']->nombre) . '.pdf', 'landscape');
    }

    // ── Importación de horarios por CSV (módulo horarios + admin) ───────────────
    //
    // El archivo lo exporta el sistema del colegio y es **el horario completo del
    // plantel**, no el de unos cuantos profesores. De ahí las dos decisiones que
    // gobiernan todo lo que sigue:
    //
    //   · Importar REEMPLAZA la rejilla entera (`Horario::borrarTodo()`). Quien no
    //     venga en el archivo se queda sin clases.
    //   · El archivo es también el censo docente y de catálogos: los profesores,
    //     grupos, aulas y materias que no existan **se dan de alta**, y los que deje de
    //     mencionar **se dan de baja lógica** (`activo = 0`) — nunca se borran, así que
    //     el histórico queda intacto y volver a nombrarlos los reactiva.
    //     Administradores, administrativos, prefectura y dirección no se tocan nunca.
    //
    // Formato (8 columnas, SIN cabecera):
    //
    //     Pablo Benlliure,L,1,B Arte,6°A Bach,Arte,LEC,1
    //     Nancy G,L,B1,P Lectura,Prim 1°A,Biblioteca,LEC,1
    //     Nieves,L,C1,K Esp,Kinder 1,K1,LEC,1
    //
    //   0 profesor   nombre de sala de maestros, SIN correo (se deriva, ver correoDocente())
    //   1 día        L M X J V
    //   2 periodo    N (Secundaria/Bachillerato) · B<N> (Primaria) · C<N> (Kinder)
    //   3 materia    «<prefijo de nivel> <nombre>» — B S P K M
    //   4 grupo      «6°A Bach», «Prim 1°A», «Kinder 1»
    //   5 aula       opcional (puede venir vacía)
    //   6 tipo       LEC
    //   7 —          columna del sistema de origen; se ignora (ver parsearCsvHorarios)

    /** Columnas exactas del archivo. Ni una más ni una menos: una fila corta es un error. */
    private const CSV_COLUMNAS = 8;

    /** Dominio con el que se construye el correo de un profesor dado de alta por archivo. */
    private const CSV_DOMINIO = 'bilbao.edu.mx';

    /**
     * Contraseña con la que nacen las cuentas creadas por el archivo.
     *
     * ⚠️ Es la misma que siembra `database/deploy/deploy.sql`, y por el mismo motivo:
     * el CSV no trae correos ni contraseñas, así que no hay forma de generar una
     * distinta por persona sin un canal para comunicársela. Es una contraseña
     * **inicial**: hay que rotarla antes de abrir el panel. La previa lo dice.
     */
    private const CSV_PASSWORD_INICIAL = 'password123';

    /** Día del archivo → ENUM de `horarios.dia`. */
    private const CSV_DIAS = [
        'l' => 'lunes', 'm' => 'martes', 'x' => 'miercoles', 'j' => 'jueves', 'v' => 'viernes',
    ];

    /** Prefijo de la materia → nivel académico. Es de donde sale el nivel de la fila. */
    private const CSV_NIVEL_MATERIA = [
        'm' => 'Maternal', 'k' => 'Kinder', 'p' => 'Primaria',
        's' => 'Secundaria', 'b' => 'Bachillerato',
    ];

    /**
     * Prefijo del código de periodo → niveles cuya jornada numera.
     *
     * ⚠️ `B` está en los dos mapas y significa cosas distintas: en la materia es
     * Bachillerato, en el periodo es Primaria. Por eso el nivel lo decide SIEMPRE la
     * materia y el código de periodo solo se comprueba contra él — al revés, «B Arte»
     * en «B3» se leería como Bachillerato y Primaria a la vez.
     *
     * Secundaria y Bachillerato comparten numeración desnuda porque comparten jornada.
     * Maternal no tiene código: si aparece una fila suya, el error lo dice en vez de
     * inventarse una letra.
     */
    private const CSV_NIVEL_PERIODO = [
        ''  => ['Secundaria', 'Bachillerato'],
        'b' => ['Primaria'],
        'c' => ['Kinder'],
    ];

    /** Tipos de sesión conocidos de la columna 6. */
    private const CSV_TIPOS = ['lec' => 'clase'];

    /** Normaliza para comparar catálogos: minúsculas, sin acentos ni espacios de sobra. */
    private static function claveCatalogo(string $v): string {
        $v = trim(mb_strtolower($v, 'UTF-8'));
        $v = strtr($v, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
        return (string)preg_replace('/\s+/', ' ', $v);
    }

    /**
     * Clave canónica de un grupo, para que el archivo y el catálogo se reconozcan.
     *
     * El colegio escribe «Prim 1°A», «1°A Sec» y «6°A Bach»; el catálogo guarda
     * «1A Primaria», «1A Secundaria» y «6A Bachillerato». Es el mismo grupo, y sin esta
     * clave la importación crearía un duplicado por cada uno —dos filas para el mismo
     * alumnado, con el horario repartido entre las dos—.
     *
     * La receta: fuera el nombre del nivel (venga como venga escrito), fuera el
     * ordinal y todo lo que no sea letra o dígito. Queda el grado y la sección, que es
     * lo único que de verdad identifica al grupo **dentro de su nivel**; el nivel va
     * delante en la clave para que «Kinder 1» y un hipotético «1A Primaria» no choquen.
     */
    private static function claveGrupo(string $nombre, string $nivel): string {
        $v = self::claveCatalogo($nombre);
        $v = (string)preg_replace('/\b(maternal|kinder|prim|primaria|sec|secundaria|bach|bachillerato)\b/u', ' ', $v);
        $v = (string)preg_replace('/[^a-z0-9]+/', '', str_replace(['°', 'º'], '', $v));
        return self::claveCatalogo($nivel) . '|' . $v;
    }

    /**
     * Correo derivado del nombre, porque el archivo no trae ninguno.
     *
     * «José Antonio» → `jose.antonio@bilbao.edu.mx`. Si ya está cogido se numera
     * (`jose.antonio2@`): dos personas distintas con el mismo nombre de pila existen, y
     * reventar el `UNIQUE uq_email` a mitad de la transacción no ayudaría a nadie.
     *
     * @param array<string,true> $usados Correos ya ocupados (BD + generados en esta pasada).
     */
    private static function correoDocente(string $nombre, array $usados): string {
        $base = trim((string)preg_replace('/[^a-z0-9]+/', '.', self::claveCatalogo($nombre)), '.');
        if ($base === '') $base = 'profesor';
        $correo = $base . '@' . self::CSV_DOMINIO;
        for ($i = 2; isset($usados[$correo]); $i++) {
            $correo = $base . $i . '@' . self::CSV_DOMINIO;
        }
        return $correo;
    }

    /** Cuántos candidatos a «es la misma persona» se enseñan por nombre del archivo. */
    private const CSV_MAX_PARECIDOS = 3;

    /**
     * ¿Hay ya alguien que se llame casi igual? Devuelve sus nombres (como mucho tres).
     *
     * El archivo trae el nombre de sala de maestros («Ana Lau», «Fer Uribe», «Nancy G») y
     * la BD el nombre completo del expediente («Ana Laura Castro», «María Fernanda Uribe
     * Barrios», «Nancy González de la Rosa»). **No se fusionan solos**: «Fernanda So»
     * encaja igual de bien con dos personas distintas, y elegir mal le da a alguien el
     * horario de otra. Pero callarlo deja dos cuentas para la misma persona sin que nadie
     * lo note, y en el claustro real eso es la norma y no la excepción.
     *
     * ⚠️ La comparación es **token a token, por prefijo y en orden**, no un
     * `str_starts_with` sobre la cadena entera. Con la cadena entera «Ana Lau» no casaba
     * con «Ana Laura Castro» (el espacio cae donde «Laura» sigue teniendo letras), y
     * justo esos —nombre de pila abreviado + apellido abreviado— son la mayoría de los
     * casos reales. Se permite saltar tokens del nombre largo, que es lo que hace falta
     * para que «Fer Uribe» encuentre a «María Fernanda Uribe Barrios».
     *
     * Lo que NO caza, y es deliberado: los apodos que no son prefijo («Gaby» de
     * «Gabriela», «Malena» de «María Elena»). Salen como alta limpia; cazarlos pediría
     * distancia de edición, que empieza a proponer parecidos falsos y convierte el aviso
     * en ruido.
     *
     * @return string[] nombres de los candidatos, el más corto primero
     */
    private static function docentesParecidos(string $nombre, array $existentes): array {
        $n = self::claveCatalogo($nombre);
        if ($n === '') return [];

        $out = [];
        foreach ($existentes as $u) {
            $e = self::claveCatalogo((string)$u->nombre);
            if ($e === $n) continue;                     // ese ya casó por nombre exacto
            if (self::nombresCompatibles($n, $e)) $out[] = (string)$u->nombre;
        }
        usort($out, fn($a, $b) => mb_strlen($a) <=> mb_strlen($b));
        return array_slice($out, 0, self::CSV_MAX_PARECIDOS);
    }

    /**
     * ¿Pueden estos dos nombres ser de la misma persona?
     *
     * Token a token, **por prefijo y en orden**, saltando tokens del nombre largo. Con
     * la cadena entera «Ana Lau» no casaba con «Ana Laura Castro» (el espacio cae donde
     * «Laura» sigue teniendo letras), y ese patrón —pila abreviada + apellido
     * abreviado— es la mayoría del claustro real. Se prueba en los dos sentidos porque
     * cualquiera de los dos puede ser el corto.
     *
     * Lo usan dos cosas distintas: proponer parecidos (`docentesParecidos()`) y
     * desconfiar de un casado por correo (`resolverDocente()`). Es la misma pregunta,
     * así que es la misma función: dos recetas se desincronizan.
     *
     * @param string $a,$b ya normalizados con `claveCatalogo()`
     */
    private static function nombresCompatibles(string $a, string $b): bool {
        $ta = array_values(array_filter(explode(' ', $a)));
        $tb = array_values(array_filter(explode(' ', $b)));
        if (!$ta || !$tb) return false;

        $encaja = function (array $cortos, array $largos): bool {
            $i = 0;
            foreach ($largos as $l) {
                if ($i < count($cortos) && str_starts_with($l, $cortos[$i])) $i++;
            }
            return $i === count($cortos);
        };
        return $encaja($ta, $tb) || $encaja($tb, $ta);
    }

    /**
     * El diccionario del claustro indexado por **todas** sus grafías.
     *
     * `Classes\Diccionario` devuelve el dato crudo y la normalización la pone aquí, con
     * la misma `claveCatalogo()` que el resto de catálogos: dos recetas distintas se
     * desincronizarían, y la primera vez que lo hicieran sería un profesor recibiendo el
     * horario de otro.
     *
     * Las tres grafías apuntan a la MISMA entrada porque el archivo de horarios puede
     * traer cualquiera de ellas. Si dos personas comparten una grafía —«Fernanda» como
     * versión corta de una y versión larga de otra— gana la primera y la segunda no
     * pisa: un alias ambiguo no puede decidir a quién se le carga el horario, y lo que
     * hace entonces el importador es lo de siempre (tratarlo como nombre a secas y, si
     * no casa con ninguna cuenta, avisar de los parecidos).
     *
     * @return array<string, array> clave normalizada → entrada del diccionario
     */
    private static function indiceDiccionario(): array {
        $out = [];
        foreach (Diccionario::entradas() as $e) {
            foreach (['corta', 'larga', 'real'] as $campo) {
                $k = self::claveCatalogo((string)$e[$campo]);
                if ($k !== '' && !isset($out[$k])) $out[$k] = $e;
            }
        }
        return $out;
    }

    /**
     * A quién se refiere este nombre del archivo de horarios.
     *
     * Es el punto que evita que importar duplique el claustro entero. El CSV trae el
     * nombre de sala de maestros («Gaby») y la base de datos el del expediente
     * («Gabriela Sánchez»); sin traducción, cada carga creaba una cuenta nueva por cada
     * persona cuyo nombre corto no coincidía — 38 de 39 con el archivo real.
     *
     * Se prueba en este orden, del identificador más fuerte al más débil:
     *
     *   1. **El nombre tal cual**, si ya hay una cuenta que se llama así.
     *   2. **El correo del diccionario**, que es el identificador único de verdad.
     *   3. **Las otras grafías del diccionario** (larga y real) contra el nombre.
     *   4. Nada casa → **cuenta nueva**, con el nombre y el correo del diccionario si
     *      está, y con el nombre del archivo y un correo derivado si no.
     *
     * ⚠️ Lo que devuelve en `clave` es la clave de la **persona de destino**, no la del
     * texto del archivo. Todo lo que viene después —los choques de horario, quién viene
     * en el archivo y quién se inhabilita, y la resolución de ids al escribir— se apoya
     * en esa clave, así que traducir aquí basta para que «Gaby» y «Gabriela Sánchez»
     * sean la misma persona en todas partes, incluido el choque de dos clases a la vez.
     *
     * ⚠️ El correo generado depende de los ya usados, así que `$correosUsados` se toca
     * por referencia: dos altas distintas no pueden salir con el mismo correo y reventar
     * el `UNIQUE` a mitad de la transacción.
     *
     * @param array<string,object> $profesores clave de nombre → usuario
     * @param array<string,object> $porCorreo  clave de correo → usuario
     * @param array<string,array>  $dic        índice del diccionario
     * @param object[]             $usuarios   claustro entero, para los parecidos
     * @return array{clave:string,nombre:string,id:int,email:string,email_dic:string,via:string,por:string,dic:bool,parecido:string[]}
     */
    private static function resolverDocente(
        string $nombre, array $profesores, array $porCorreo, array $dic, array $usuarios, array &$correosUsados
    ): array {
        $k = self::claveCatalogo($nombre);
        $e = $dic[$k] ?? null;

        // ⚠️ `email_dic` viaja también cuando la persona YA tiene cuenta, y se resuelve
        // antes que nada por eso: es el único dato que permite ver que el correo de la
        // ficha y el del diccionario no son el mismo. Antes solo sobrevivía en las
        // altas, así que la discrepancia no llegaba a saberse nunca.
        $correoDic = $e ? self::claveCatalogo((string)$e['email']) : '';
        $casado = function (object $u, string $via, string $por, bool $dudoso = false) use ($correoDic) {
            return ['clave' => self::claveCatalogo((string)$u->nombre), 'nombre' => (string)$u->nombre,
                    'id' => (int)$u->id, 'email' => (string)$u->email, 'email_dic' => $correoDic,
                    'via' => $via, 'por' => $por, 'dudoso' => $dudoso,
                    'dic' => $via === 'diccionario', 'parecido' => []];
        };

        // 1. El archivo escribe el nombre con el que ya está dada de alta.
        if (isset($profesores[$k])) return $casado($profesores[$k], 'directo', 'nombre');

        if ($e) {
            // 2. Por correo: es el identificador fuerte. Va antes que el nombre porque
            //    un correo no se repite y un nombre sí puede parecerse a varios.
            $kc = self::claveCatalogo((string)$e['email']);
            if ($kc !== '' && isset($porCorreo[$kc])) {
                // ⚠️ …pero un identificador fuerte con un dato malo es peor que uno
                // débil. Si el diccionario se equivoca de correo, el horario de esta
                // persona se escribe ENTERO en la cuenta de otra y la suya se inhabilita
                // por no aparecer — en silencio y sin nada que lo delate. Se comprueba
                // que la cuenta a la que lleva el correo se llame como alguna de las
                // grafías; si no, se casa igual (el correo manda) pero marcado para que
                // la previa lo enseñe y alguien decida.
                $u = $porCorreo[$kc];
                $cuenta = self::claveCatalogo((string)$u->nombre);
                $suena = false;
                foreach (['larga', 'real', 'corta'] as $campo) {
                    $g = self::claveCatalogo((string)$e[$campo]);
                    if ($g !== '' && ($g === $cuenta || self::nombresCompatibles($g, $cuenta))) { $suena = true; break; }
                }
                return $casado($u, 'diccionario', 'correo', !$suena);
            }

            // 3. Por cualquiera de las otras grafías.
            foreach (['larga', 'real', 'corta'] as $campo) {
                $kn = self::claveCatalogo((string)$e[$campo]);
                if ($kn !== '' && isset($profesores[$kn])) return $casado($profesores[$kn], 'diccionario', 'nombre');
            }
        }

        // 4. Alta. Con diccionario se crea con el nombre legible y el correo
        //    institucional de verdad; sin él, con lo que traiga el archivo.
        $nombreAlta = $e && $e['nombre'] !== '' ? (string)$e['nombre'] : $nombre;
        $correo     = $e ? self::claveCatalogo((string)$e['email']) : '';
        // Un correo del diccionario ya ocupado solo puede ser de otra cuenta que no
        // hemos casado: derivar uno propio es preferible a reventar el UNIQUE.
        if ($correo === '' || isset($correosUsados[$correo])) $correo = self::correoDocente($nombreAlta, $correosUsados);
        $correosUsados[self::claveCatalogo($correo)] = true;

        return [
            'clave'  => self::claveCatalogo($nombreAlta),
            'nombre' => $nombreAlta,
            'id'     => 0,
            'email'  => $correo,
            'email_dic' => $correoDic,
            'via'    => 'nuevo',
            'por'    => '',
            'dudoso' => false,
            'dic'    => (bool)$e,
            // Los parecidos solo tienen sentido en un alta: si ya casó, no hay nada que
            // decidir. Se comparan las DOS grafías —la del archivo y la del diccionario—
            // porque cualquiera de las dos puede ser la que se parezca a una cuenta.
            'parecido' => $e && $nombreAlta !== $nombre
                ? array_values(array_unique(array_merge(
                    self::docentesParecidos($nombre, $usuarios),
                    self::docentesParecidos($nombreAlta, $usuarios)
                  )))
                : self::docentesParecidos($nombre, $usuarios),
        ];
    }

    /**
     * ¿Hay que corregirle el correo a esta persona? Devuelve el nuevo, o `''`.
     *
     * El diccionario es la fuente de verdad de cómo se llama y cómo se escribe cada
     * quien, así que cuando su correo y el de la ficha no coinciden manda el del
     * diccionario. Pero **el correo es el usuario con el que se entra al panel**
     * (`login()` autentica por `findByEmail`), así que no se toca a la ligera: se
     * exigen tres cosas y cualquier duda deja la ficha como está.
     *
     *   1. Que el diccionario traiga correo y no sea el que ya tiene.
     *   2. Que sea un correo **válido**. La hoja de cálculo no valida nada, y una celda
     *      con el hipervínculo en vez del texto, o un `mailto:`, entrarían tal cual.
     *   3. Que **no sea el de otra cuenta**. `usuarios.email` es `UNIQUE`, así que un
     *      correo mal capturado en el Excel no puede reventar el UPDATE a mitad de la
     *      transacción — y además significa que el diccionario está mal, que es un
     *      hallazgo por derecho propio: por eso se recoge en `$conflictos` en vez de
     *      descartarse en silencio.
     *
     * @param array                $r          lo que devolvió `resolverDocente()`
     * @param array<string,object> $porCorreo  clave de correo → usuario
     * @param array                $conflictos se le añaden los correos ya ocupados
     */
    private static function correoACorregir(array $r, array $porCorreo, array &$conflictos): string {
        $nuevo = self::claveCatalogo((string)($r['email_dic'] ?? ''));
        if ($nuevo === '' || $nuevo === self::claveCatalogo((string)$r['email'])) return '';

        if (!filter_var($nuevo, FILTER_VALIDATE_EMAIL)) {
            $conflictos[] = ['nombre' => (string)$r['nombre'], 'correo' => $nuevo, 'de' => ''];
            return '';
        }
        // Si ese correo ya es de alguien, es de OTRA persona: si fuera de esta misma,
        // `resolverDocente()` habría casado por correo y no habría discrepancia.
        if (isset($porCorreo[$nuevo])) {
            $conflictos[] = ['nombre' => (string)$r['nombre'], 'correo' => $nuevo,
                             'de' => (string)$porCorreo[$nuevo]->nombre];
            return '';
        }
        return $nuevo;
    }

    /**
     * Lee el CSV subido y devuelve `[filas, resumen, plan]`.
     *
     * `plan` es lo que la confirmación hará con los catálogos, con las mismas cuatro
     * claves (`profesores|grupos|aulas|materias`) en los cuatro apartados:
     * `['altas' => …, 'match' => …, 'apagar' => …, 'encender' => …]`.
     * `match` es lo que el archivo RECONOCE y no toca —la mitad del resultado que la
     * previa no enseñaba—, `apagar` lo que deja de mencionar y `encender` lo que vuelve
     * a nombrar tras una baja. **Nada se borra**: son cambios de `activo`.
     *
     * A las personas las casa `resolverDocente()` con el diccionario del claustro, que
     * es lo que impide que «Gaby» se convierta en una segunda cuenta de «Gabriela
     * Sánchez». Los `alias` de cada registro son las grafías con las que el archivo lo
     * escribe cuando no coinciden con el nombre de la ficha.
     *
     * No escribe nada: resuelve cuanto puede contra los catálogos actuales, apunta lo
     * que habría que crear y deja cada fila con su estado ('ok' | 'aviso' | 'error') y
     * su motivo para que la vista previa lo enseñe antes de confirmar.
     *
     * Lo que NO se resuelve aquí son los ids de lo que todavía no existe: un grupo que
     * el archivo estrena no tiene id hasta que se confirma. Por eso cada fila viaja con
     * las **claves** de catálogo (`k_grupo`, `k_aula`, `k_materia`, `k_profesor`) y es
     * `importarHorarios()` quien las convierte en ids, ya dentro de la transacción y
     * después de dar de alta lo que faltaba. Los periodos son la excepción: la jornada
     * no se crea nunca desde un archivo, así que su id se resuelve ya —y hace falta,
     * porque los choques se comprueban por reloj.
     */
    private static function parsearCsvHorarios(string $ruta): array {
        $contenido = @file_get_contents($ruta);
        if ($contenido === false) return [[], ['error' => 'No se pudo leer el archivo.'], []];
        $contenido = self::csvAUtf8($contenido);

        // ── Índices de catálogo ──────────────────────────────────────────────────
        $usuarios = UsuarioBlog::todosParaImportar();
        $profesores = [];            // clave de nombre  → Usuario
        $porCorreo  = [];            // clave de correo  → Usuario
        $correosUsados = [];
        foreach ($usuarios as $u) {
            $profesores[self::claveCatalogo((string)$u->nombre)] = $u;
            $kc = self::claveCatalogo((string)$u->email);
            $porCorreo[$kc] = $u;
            $correosUsados[$kc] = true;
        }

        // El diccionario del claustro: es lo que traduce «Gaby» a la cuenta de
        // «Gabriela Sánchez» en vez de crear una segunda. Si no se puede leer, el
        // índice sale vacío y el importador se comporta como antes de que existiera.
        $dic = self::indiceDiccionario();
        // Nombre del archivo → a quién resuelve. Se calcula una vez por nombre
        // distinto y no una por fila: son ~40 personas y ~880 filas.
        $resueltos    = [];
        $porClave     = [];

        // La jornada es por nivel: 'Primaria' => [Periodo lectivo 1, 2, …]. El código
        // del archivo numera HORAS DE CLASE, no posiciones de la jornada: «4» es la 4ª
        // hora de Secundaria (orden 5), porque el orden 4 es un receso. Por eso el
        // índice se construye sobre los periodos lectivos y no sobre `orden`.
        $jornada = Periodo::porNivel(true);

        // ⚠️ Los tres van con `true` (incluir dados de baja). Es lo que permite que un
        // grupo apagado en la carga anterior se REACTIVE al volver a aparecer, en vez de
        // que el importador lo dé por inexistente e intente crearlo otra vez — cosa que
        // además reventaría el `UNIQUE uq_grupo` a mitad de la transacción.
        $grupos = [];
        foreach (Grupo::todos(true) as $g) $grupos[self::claveGrupo((string)$g->nombre, (string)$g->nivel)] = $g;
        $aulas = [];
        foreach (Aula::todas(true) as $a) $aulas[self::claveCatalogo((string)$a->nombre)] = $a;
        $materias = [];
        foreach (Materia::todas(true) as $m) {
            $materias[self::claveCatalogo((string)$m->nivel) . '|' . self::claveCatalogo((string)$m->nombre)] = $m;
        }

        // ── Altas pendientes: lo que el archivo estrena ──────────────────────────
        $altas = ['profesores' => [], 'grupos' => [], 'aulas' => [], 'materias' => []];

        $filas   = [];
        $n       = 0;
        $primera = true;
        foreach (preg_split('/\R/u', $contenido) as $linea) {
            $n++;                                  // número de línea FÍSICA: es lo que se enseña
            if (trim($linea) === '') continue;

            // El cuarto argumento («sin carácter de escape») va explícito por dos
            // razones: PHP 8.5 deprecia omitirlo, y el escape por defecto es `\`, que
            // en un CSV RFC-4180 no significa nada. Con él, `"Dulce\","Laura"` se lee
            // como UN campo corrupto (`Dulce\",Laura"`) en vez de como dos.
            $col = str_getcsv($linea, ',', '"', '');

            // Del formato viejo (7 columnas con cabecera) se avisa explícitamente: un
            // «faltan columnas» a secas mandaba a revisar el archivo equivocado. Se
            // mira la primera línea CON CONTENIDO, no la línea 1: un archivo que
            // empiece con un salto en blanco sigue siendo el formato viejo.
            $esPrimera = $primera;
            $primera   = false;
            if ($esPrimera && self::claveCatalogo((string)($col[0] ?? '')) === 'profesor_email') {
                return [[], ['error' =>
                    'Ese es el formato antiguo (7 columnas con cabecera «profesor_email,…»). '
                    . 'El nuevo son 8 columnas y SIN cabecera: profesor,día,periodo,materia,grupo,aula,tipo,—. '
                    . 'Descarga la plantilla para ver un ejemplo.'], []];
            }

            $fila = [
                'linea'    => $n,
                'profesor' => trim((string)($col[0] ?? '')),
                'dia_csv'  => trim((string)($col[1] ?? '')),
                'periodo'  => trim((string)($col[2] ?? '')),
                'materia'  => '',
                'grupo'    => trim((string)($col[4] ?? '')),
                'aula'     => trim((string)($col[5] ?? '')),
                'nivel'    => '',
                'dia'      => '',
                'hora'     => '',
                'estado'   => 'ok',
                'motivo'   => '',
                'periodo_id' => 0,
                'k_profesor' => '', 'k_grupo' => '', 'k_aula' => '', 'k_materia' => '',
                'division'   => 0,
                'rol_docente' => 'titular',
                // A quién resuelve el nombre del archivo: la cuenta de destino (`0` si
                // hay que crearla), cómo se llama de verdad y por qué camino se supo.
                'profesor_id'    => 0,
                'profesor_final' => '',
                'via_profesor'   => '',
            ];
            $marcar = function (string $estado, string $motivo) use (&$fila) {
                if ($fila['estado'] === 'error') return;   // un aviso posterior no degrada un error
                $fila['estado'] = $estado;
                $fila['motivo'] = $motivo;
            };

            if (count($col) !== self::CSV_COLUMNAS) {
                $marcar('error', 'La fila tiene ' . count($col) . ' columnas y deben ser ' . self::CSV_COLUMNAS . '.');
            }

            // ── Profesor ─────────────────────────────────────────────────────────
            //
            // ⚠️ Se resuelve AQUÍ, en la primera pasada, y no más tarde: de
            // `k_profesor` cuelga la detección de choques, y esa clave tiene que ser
            // ya la de la PERSONA. Si el archivo trae «Gaby» a primera hora y
            // «Gabriela Sánchez» a la misma hora, eso es la misma profesora en dos
            // clases a la vez; con la clave sin traducir pasaría por dos personas.
            if ($fila['profesor'] === '') {
                $marcar('error', 'Falta el nombre del profesor.');
            } else {
                $kCsv = self::claveCatalogo($fila['profesor']);
                $resueltos[$kCsv] ??= self::resolverDocente($fila['profesor'], $profesores, $porCorreo, $dic, $usuarios, $correosUsados);
                $r = $resueltos[$kCsv];
                // `??=`: dos grafías del archivo pueden resolver a la MISMA alta («Gaby» y
                // «Gabriela Sánchez Montes de Oca» si todavía no tiene cuenta). Manda la
                // primera resolución, que es la que se quedó con el correo del diccionario;
                // la segunda ya lo encontró ocupado y derivó uno del nombre.
                $porClave[$r['clave']] ??= $r;

                $fila['k_profesor']     = $r['clave'];
                $fila['profesor_id']    = $r['id'];
                $fila['profesor_final'] = $r['nombre'];
                $fila['via_profesor']   = $r['via'];
            }

            // ── Día ──────────────────────────────────────────────────────────────
            $fila['dia'] = self::CSV_DIAS[self::claveCatalogo($fila['dia_csv'])] ?? '';
            if ($fila['dia'] === '') {
                $marcar('error', "Día inválido «{$fila['dia_csv']}»: usa " . implode(' ', array_map('strtoupper', array_keys(self::CSV_DIAS))) . '.');
            }

            // ── Materia (y con ella el NIVEL de la fila) ─────────────────────────
            $matCruda = trim((string)($col[3] ?? ''));
            if ($matCruda === '') {
                $marcar('error', 'Falta la materia.');
            } elseif (!preg_match('/^(\pL)\s+(\S.*)$/u', $matCruda, $mm)) {
                $marcar('error', "La materia «{$matCruda}» no lleva prefijo de nivel (ej. «P Español 2»).");
            } else {
                $nivel = self::CSV_NIVEL_MATERIA[self::claveCatalogo($mm[1])] ?? null;
                if (!$nivel) {
                    $marcar('error', "Prefijo de nivel «{$mm[1]}» desconocido en «{$matCruda}»: usa "
                        . implode(' ', array_map('strtoupper', array_keys(self::CSV_NIVEL_MATERIA))) . '.');
                } else {
                    $fila['nivel']   = $nivel;
                    $fila['materia'] = trim($mm[2]);
                    $fila['k_materia'] = self::claveCatalogo($nivel) . '|' . self::claveCatalogo($fila['materia']);
                }
            }

            // ── Periodo: el código numera HORAS DE CLASE de la jornada de su nivel ──
            if ($fila['nivel'] !== '') {
                if (!preg_match('/^([A-Za-z]?)(\d{1,2})$/', $fila['periodo'], $pm)) {
                    $marcar('error', "Periodo «{$fila['periodo']}» ilegible: se espera 3, B3 o C3.");
                } else {
                    $pref = self::claveCatalogo($pm[1]);
                    $permitidos = self::CSV_NIVEL_PERIODO[$pref] ?? null;
                    if ($permitidos === null) {
                        $marcar('error', "Prefijo de periodo «{$pm[1]}» desconocido: usa N (Secundaria/Bachillerato), B (Primaria) o C (Kinder).");
                    } elseif (!in_array($fila['nivel'], $permitidos, true)) {
                        $esperado = array_search([$fila['nivel']], self::CSV_NIVEL_PERIODO, true);
                        $marcar('error', "«{$fila['periodo']}» numera la jornada de " . implode('/', $permitidos)
                            . ", pero la materia es de {$fila['nivel']}"
                            . ($esperado !== false ? " (sería «" . strtoupper((string)$esperado) . $pm[2] . "»)." : '.'));
                    } else {
                        $horas = $jornada[$fila['nivel']] ?? [];
                        $per   = $horas[(int)$pm[2] - 1] ?? null;
                        if (!$per) {
                            $marcar('error', "La jornada de {$fila['nivel']} tiene " . count($horas)
                                . " horas de clase, así que «{$fila['periodo']}» no existe.");
                        } else {
                            $fila['periodo_id'] = (int)$per->id;

                            $fila['ini']        = (string)$per->hora_inicio;
                            $fila['fin']        = (string)$per->hora_fin;
                            $fila['hora']       = substr($per->hora_inicio, 0, 5) . '–' . substr($per->hora_fin, 0, 5);
                        }
                    }
                }
            }

            // ── Grupo. La equivalencia de nombres la hace claveGrupo() ───────────
            if ($fila['grupo'] === '') {
                $marcar('error', 'Falta el grupo.');
            } elseif ($fila['nivel'] !== '') {
                $fila['k_grupo'] = self::claveGrupo($fila['grupo'], $fila['nivel']);
            }

            // ── Aula: opcional (la FK admite NULL) ───────────────────────────────
            if ($fila['aula'] !== '') {
                $fila['k_aula'] = self::claveCatalogo($fila['aula']);
            }

            // ── Tipo de sesión. Solo conocemos LEC; lo demás entra como clase y avisa ──
            $tipo = self::claveCatalogo((string)($col[6] ?? ''));
            if ($tipo !== '' && !isset(self::CSV_TIPOS[$tipo])) {
                $marcar('aviso', "Tipo de sesión «{$col[6]}» desconocido: se importa como clase normal.");
            }
            // La columna 7 del archivo va siempre a 1 y el sistema de origen no
            // documenta qué es. No se lee: inventarle un significado (¿duración?
            // ¿división?) sería peor que ignorarla, y la división real se deduce
            // más abajo de las materias que coinciden en la misma casilla.

            $filas[] = $fila;
        }

        if (!$filas) return [[], ['error' => 'El archivo no tiene filas de datos.'], []];

        // ── Materia dividida y coteaching ────────────────────────────────────────
        self::resolverDivisiones($filas);
        $avisosCoteaching = self::resolverCoteaching($filas);

        // ── Choques por RELOJ ────────────────────────────────────────────────────
        self::marcarChoquesCsv($filas);

        // ── Nombres de grupo que chocarían contra `uq_grupo` ─────────────────────
        self::marcarGruposAmbiguos($filas, $grupos);

        // ── Qué cataloga el archivo, y qué parte de eso hay que crear ────────────
        //
        // ⚠️ Esta pasada va DESPUÉS de marcar los choques, no dentro del bucle de
        // lectura. Recogiéndolas al vuelo, una fila que más tarde resultaba ser un
        // choque —y que por tanto no se importa— dejaba igualmente su grupo o su aula
        // en la lista de altas: se creaban filas de catálogo que después no usaba
        // ninguna clase.
        //
        // Se cuenta también el TOTAL de cada catálogo que el archivo menciona, no solo
        // lo que falta. Sin ese denominador la previa enseñaba «Grupos 4» sobre un
        // archivo con 22, y se lee como que el importador solo entendió cuatro.
        //
        // Y se recoge también lo que el archivo RECONOCE, no solo lo que estrena:
        // «ya existe» es la mitad del resultado y la pantalla no la enseñaba. Sin ella
        // no hay forma de distinguir un archivo que encajó con el colegio de uno que va
        // a duplicarlo entero, que es justo lo que el diccionario vino a evitar.
        $vistos = ['profesores' => [], 'grupos' => [], 'aulas' => [], 'materias' => []];
        $match  = ['profesores' => [], 'grupos' => [], 'aulas' => [], 'materias' => []];
        $nivelesDe   = [];
        $conflictos  = [];        // correos del diccionario que ya son de otra cuenta
        foreach ($filas as $f) {
            if ($f['estado'] === 'error') continue;

            if ($f['k_profesor'] !== '') {
                $k = $f['k_profesor'];
                $r = $porClave[$k] ?? null;
                $vistos['profesores'][$k] = true;
                if ($f['nivel'] !== '') $nivelesDe[$k][$f['nivel']] = true;

                if ($r && $r['id'] > 0) {
                    // Ya tiene cuenta. Se guarda CÓMO la escribe el archivo: si no
                    // coincide con su nombre, esa equivalencia es exactamente lo que el
                    // diccionario aporta y hay que poder revisarla antes de confirmar.
                    $match['profesores'][$k] ??= [
                        'id' => $r['id'], 'nombre' => $r['nombre'], 'email' => $r['email'],
                        'email_nuevo' => self::correoACorregir($r, $porCorreo, $conflictos),
                        'via' => $r['via'], 'por' => $r['por'], 'dudoso' => false,
                        'alias' => [], 'clases' => 0,
                    ];
                    if (self::claveCatalogo($f['profesor']) !== $k) $match['profesores'][$k]['alias'][$f['profesor']] = true;
                    $match['profesores'][$k]['clases']++;

                    // ⚠️ `dudoso` se lee de la resolución de ESTA fila y NO se queda con
                    // la primera, porque dos nombres distintos del archivo pueden
                    // resolver a la misma cuenta: uno legítimo y otro por un correo mal
                    // escrito en el diccionario. `$porClave` conserva la primera —que es
                    // lo correcto para el nombre y el correo del alta—, así que mirar
                    // solo ahí perdía justo el caso que hay que enseñar.
                    $rf = $resueltos[self::claveCatalogo($f['profesor'])] ?? null;
                    if (!empty($rf['dudoso'])) $match['profesores'][$k]['dudoso'] = true;
                } else {
                    // El correo generado depende de los ya generados, así que recorrer en
                    // orden de aparición lo hace reproducible entre importaciones.
                    $altas['profesores'][$k] ??= [
                        'nombre'   => $r['nombre']   ?? $f['profesor'],
                        'email'    => $r['email']    ?? '',
                        'alias'    => [],
                        'dic'      => (bool)($r['dic'] ?? false),
                        'niveles'  => [],
                        'clases'   => 0,
                        'parecido' => $r['parecido'] ?? [],
                    ];
                    if (self::claveCatalogo($f['profesor']) !== $k) $altas['profesores'][$k]['alias'][$f['profesor']] = true;
                    $altas['profesores'][$k]['clases']++;
                }
            }

            foreach ([
                ['grupos',   'k_grupo',   $grupos,   $f['grupo'],   ['nombre' => $f['grupo'],   'nivel' => $f['nivel']]],
                ['aulas',    'k_aula',    $aulas,    $f['aula'],    ['nombre' => $f['aula']]],
                ['materias', 'k_materia', $materias, $f['materia'], ['nombre' => $f['materia'], 'nivel' => $f['nivel']]],
            ] as [$tipo, $campo, $catalogo, $comoLoEscribe, $datos]) {
                $k = $f[$campo];
                if ($k === '') continue;
                $vistos[$tipo][$k] = true;
                if (isset($catalogo[$k])) {
                    $fila2 = $catalogo[$k];
                    $match[$tipo][$k] ??= [
                        'id' => (int)$fila2->id, 'nombre' => (string)$fila2->nombre,
                        'nivel' => $fila2->nivel ?? null, 'alias' => [], 'clases' => 0,
                    ];
                    // `Prim 1°A` reconocido como `1A Primaria` es un acierto de
                    // `claveGrupo()`, y enseñarlo es lo que deja comprobar que no se
                    // está fundiendo lo que no debe.
                    if (self::claveCatalogo($comoLoEscribe) !== self::claveCatalogo($match[$tipo][$k]['nombre'])) {
                        $match[$tipo][$k]['alias'][$comoLoEscribe] = true;
                    }
                    $match[$tipo][$k]['clases']++;
                    continue;
                }
                $altas[$tipo][$k] ??= $datos + ['clases' => 0];
                $altas[$tipo][$k]['clases']++;
            }
        }

        // Los alias se han ido acumulando como CLAVES de un mapa, que es lo que los
        // deduplica sin recorrer nada; a la vista van como lista, que es lo que sabe
        // pintar. Se hace aquí, una vez, y no dentro del bucle de filas.
        $aplanarAlias = function (array &$conjunto): void {
            foreach ($conjunto as &$porTipo) {
                foreach ($porTipo as &$item) {
                    if (isset($item['alias'])) $item['alias'] = array_keys($item['alias']);
                }
                unset($item);
            }
            unset($porTipo);
        };
        $aplanarAlias($match);
        $aplanarAlias($altas);

        // Niveles declarados de cada docente: salen del archivo, que es ahora la fuente.
        // Acotan el eje de su rejilla y priorizan a los candidatos de las suplencias.
        foreach ($nivelesDe as $k => $set) {
            $lista = array_values(array_intersect(Materia::NIVELES, array_keys($set)));
            if (isset($altas['profesores'][$k])) $altas['profesores'][$k]['niveles'] = $lista;
        }

        // ── Bajas y reactivaciones: el archivo manda, pero no destruye ───────────
        //
        // El CSV es el censo, así que lo que deja de mencionar deja de ofrecerse: si no,
        // cada carga deja sedimento y a los tres cursos el desplegable de grupos tiene
        // el doble de opciones que el colegio.
        //
        // ⚠️ Pero se APAGA (`activo = 0`), no se borra. Esto era un DELETE y tenía dos
        // problemas que la baja lógica resuelve de un golpe:
        //
        //   · Las FK de `suplencia_horas` son ON DELETE SET NULL, así que borrar el aula
        //     de una cobertura de marzo no daba error: le vaciaba el dato al histórico en
        //     silencio. Había que ir salvando una por una las filas citadas («retenidas»)
        //     y volver a comprobarlo al confirmar, por si entremedias prefectura agendaba
        //     una suplencia sobre algo que la previa dio por prescindible. Nada de eso
        //     hace falta ya: apagar no toca ni una fila de lo ya ocurrido.
        //   · Un export incompleto —al que le falta un nivel, o que se generó a medias—
        //     destruía catálogo que cuesta meses reconstruir. Ahora basta con volver a
        //     subir el archivo bueno: lo que reaparece se enciende solo.
        //
        // Los PROFESORES entran aquí ahora, con dos salvedades que no se negocian: una
        // cuenta no se BORRA nunca (arrastraría sus suplencias, sus intercambios, sus
        // artículos y sus notificaciones), y el archivo es el censo DOCENTE, así que
        // administradores, administrativos, prefectura y dirección quedan fuera de esta
        // poda — lo decide `esDocenteDelCenso()`.
        $apagar   = ['profesores' => [], 'grupos' => [], 'aulas' => [], 'materias' => []];
        $encender = ['profesores' => [], 'grupos' => [], 'aulas' => [], 'materias' => []];

        foreach ([['grupos', $grupos], ['aulas', $aulas], ['materias', $materias]] as [$tipo, $catalogo]) {
            foreach ($catalogo as $k => $fila) {
                $activo = (int)($fila->activo ?? 1) === 1;
                $item   = ['id' => (int)$fila->id, 'nombre' => (string)$fila->nombre, 'nivel' => $fila->nivel ?? null];
                if (isset($vistos[$tipo][$k])) {
                    if (!$activo) $encender[$tipo][$k] = $item;   // vuelve al archivo
                } elseif ($activo) {
                    $apagar[$tipo][$k] = $item;                   // el archivo ya no lo nombra
                }
            }
        }

        foreach ($profesores as $k => $u) {
            $activo = (int)($u->activo ?? 1) === 1;
            $item   = ['id' => (int)$u->id, 'nombre' => (string)$u->nombre, 'nivel' => null];
            if (isset($vistos['profesores'][$k])) {
                if (!$activo) $encender['profesores'][$k] = $item;
            } elseif ($activo && self::esDocenteDelCenso($u)) {
                $apagar['profesores'][$k] = $item;
            }
        }

        $catalogos = [];
        foreach ($vistos as $tipo => $set) {
            $catalogos[$tipo] = [
                'archivo'  => count($set),
                'match'    => count($match[$tipo]),
                'nuevos'   => count($altas[$tipo]),
                'apagar'   => count($apagar[$tipo]),
                'encender' => count($encender[$tipo]),
            ];
        }

        // ── Resumen ──────────────────────────────────────────────────────────────
        $errores = 0; $avisos = 0; $divididas = 0;
        $casillasDivididas = [];
        foreach ($filas as $f) {
            if ($f['estado'] === 'error') { $errores++; continue; }
            if ($f['estado'] === 'aviso') $avisos++;
            if ($f['division'] > 0) {
                $divididas++;
                $casillasDivididas[$f['dia'] . '|' . $f['periodo_id'] . '|' . $f['k_grupo']] = true;
            }
        }
        $profesIds = $vistos['profesores'];

        // ── A quién afecta quedarse fuera del archivo ────────────────────────────
        //
        // Son DOS consecuencias distintas y antes se enseñaban en dos sitios: «pierde
        // su horario» (tiene clases hoy y el archivo no lo trae) y «se da de baja» (es
        // docente del censo y el archivo no lo trae). No coinciden —un prefecto con
        // clases pierde la rejilla y conserva el acceso; un profesor sin horario
        // cargado se da de baja sin perder nada— así que van en UNA lista con la
        // etiqueta de lo que le pasa a cada uno. Dos paneles para el mismo grupo de
        // gente obligaban a cotejar nombres a mano para saber quién estaba en los dos.
        $conHorario = array_flip(array_map('intval', Horario::profesoresConHorario()));
        $fuera = [];
        foreach ($usuarios as $u) {
            $k = self::claveCatalogo((string)$u->nombre);
            if (isset($profesIds[$k])) continue;              // sí viene en el archivo
            $pierdeHorario = isset($conHorario[(int)$u->id]);
            $seDaDeBaja    = isset($apagar['profesores'][$k]);
            if (!$pierdeHorario && !$seDaDeBaja) continue;    // no le pasa nada
            $fuera[] = [
                'nombre'  => (string)$u->nombre,
                'horario' => $pierdeHorario,
                'baja'    => $seDaDeBaja,
            ];
        }
        // `claveCatalogo()` y no `strcoll()`: el orden tiene que ser el mismo en
        // cualquier máquina, y aquí ya hay una normalización sin acentos a mano.
        usort($fuera, fn($a, $b) => strcmp(self::claveCatalogo($a['nombre']), self::claveCatalogo($b['nombre'])));

        // ── Qué hizo el diccionario ──────────────────────────────────────────────
        // Se cuenta cuántas personas casaron **gracias a él** y cuántos nombres del
        // archivo no figuran. Lo segundo es el aviso útil: un nombre que el diccionario
        // no conoce acaba en cuenta nueva, así que o falta en el diccionario o el
        // archivo lo escribe de una forma que nadie más usa.
        $viaDic = 0;
        foreach ($match['profesores'] as $m) if ($m['via'] === 'diccionario') $viaDic++;
        $sinDic = [];
        foreach ($altas['profesores'] as $p) {
            if (!$p['dic']) $sinDic[] = (string)$p['nombre'];
        }
        sort($sinDic);

        // Los correos que la confirmación va a corregir, ya filtrados por
        // `correoACorregir()`. Van en el plan y no solo en el resumen porque
        // `escribirImportacion()` los ejecuta.
        $correos = [];
        $dudosos = [];
        foreach ($match['profesores'] as $m) {
            if (!empty($m['dudoso'])) {
                // El archivo escribe un nombre, el diccionario le da un correo, y ese
                // correo es de una cuenta que se llama de otra forma. Es el fallo más
                // caro de esta herramienta y el único que no se ve venir.
                // `alias` ya viene aplanado a lista por `$aplanarAlias()`, más arriba.
                $dudosos[] = ['cuenta' => (string)$m['nombre'], 'correo' => (string)$m['email'],
                              'archivo' => implode(' · ', (array)($m['alias'] ?: [])),
                              'clases' => (int)$m['clases']];
            }
            if (($m['email_nuevo'] ?? '') === '') continue;
            $correos[] = ['id' => (int)$m['id'], 'nombre' => (string)$m['nombre'],
                          'antes' => (string)$m['email'], 'despues' => (string)$m['email_nuevo']];
        }
        usort($correos, fn($a, $b) => strcmp(self::claveCatalogo($a['nombre']), self::claveCatalogo($b['nombre'])));

        return [$filas, [
            'total'       => count($filas),
            'errores'     => $errores,
            'avisos'      => $avisos,
            'profesores'  => count($profesIds),
            'divididas'   => $divididas,
            'casillas_divididas' => count($casillasDivididas),
            'coteaching'  => $avisosCoteaching,
            'fuera'       => $fuera,
            'catalogos'   => $catalogos,
            'password'    => self::CSV_PASSWORD_INICIAL,
            'diccionario' => Diccionario::estado() + ['traducidos' => $viaDic, 'sin_entrada' => $sinDic],
            'correos_conflicto' => $conflictos,
            'casados_dudosos'   => $dudosos,
        ], ['altas' => $altas, 'match' => $match, 'correos' => $correos,
            'apagar' => $apagar, 'encender' => $encender]];
    }

    /**
     * ¿A esta persona la alcanza el censo docente del archivo?
     *
     * Solo a quien es **únicamente** profesor. Quedan fuera:
     *
     *   · los `administrador`, por la regla de siempre del importador;
     *   · quien además es `administrativo` —su puesto no depende de dar clase, así que
     *     desaparecer del horario no significa que se haya ido del colegio—;
     *   · `prefecto` y `directivo`, que son tipos excluyentes y nunca aparecen en un
     *     horario, así que aplicarles esta regla los apagaría a todos en la primera
     *     importación.
     *
     * @param object $u Fila de UsuarioBlog::todosParaImportar() (trae `rol` y `tipo_personal`).
     */
    private static function esDocenteDelCenso(object $u): bool {
        if ((string)($u->rol ?? '') === 'administrador') return false;
        $tipos = array_filter(array_map('trim', explode(',', (string)($u->tipo_personal ?? ''))));
        if (!in_array('profesor', $tipos, true)) return false;
        return !array_intersect($tipos, ['administrativo', 'prefecto', 'directivo']);
    }

    /**
     * Deja el contenido del CSV en UTF-8 venga como venga.
     *
     * El archivo del colegio sale de Excel en Windows-1252, así que «Español» llega
     * como bytes sueltos: sin esto, `claveCatalogo()` no casaba ni una materia con
     * acento y el importador rechazaba media Primaria por «no existe la materia». Se
     * decide por validez, no por confianza: si ya es UTF-8 válido se deja intacto
     * —convertir dos veces rompe lo que estaba bien—, y si no, se traduce desde
     * Windows-1252, que es el superconjunto de Latin-1 que usa Excel.
     */
    private static function csvAUtf8(string $s): string {
        if (str_starts_with($s, "\xEF\xBB\xBF")) $s = substr($s, 3);   // BOM de Excel
        if (mb_check_encoding($s, 'UTF-8')) return $s;
        return (string)mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
    }

    /**
     * Reparte `division` cuando un grupo tiene varias materias a la misma hora.
     *
     * Es el caso «materia dividida» que el esquema ya contempla: 1ºA de Secundaria
     * tiene Arte y Música a la vez y el alumnado se reparte. En el archivo se ve como
     * dos filas del mismo (día, periodo, grupo) con materias distintas, y sin este
     * paso la comprobación de choques las rechazaría como «ese grupo ya tiene clase»
     * —eran 65 filas del horario real, todas legítimas—.
     *
     * `division` 0 significa «la clase es para todo el grupo», así que solo se numera
     * cuando de verdad hay más de una materia. El orden es el de aparición en el
     * archivo, que es estable entre importaciones del mismo fichero.
     */
    private static function resolverDivisiones(array &$filas): void {
        $casillas = [];
        foreach ($filas as $i => $f) {
            if ($f['estado'] === 'error' || !$f['periodo_id'] || $f['k_grupo'] === '') continue;
            $casillas[$f['dia'] . '|' . $f['periodo_id'] . '|' . $f['k_grupo']][] = $i;
        }
        foreach ($casillas as $indices) {
            $orden = [];
            foreach ($indices as $i) {
                $m = $filas[$i]['k_materia'];
                if ($m !== '' && !isset($orden[$m])) $orden[$m] = count($orden) + 1;
            }
            if (count($orden) < 2) continue;              // una sola materia → sin dividir
            foreach ($indices as $i) {
                $filas[$i]['division'] = $orden[$filas[$i]['k_materia']] ?? 0;
            }
        }
    }

    /**
     * Marca titular y acompañantes cuando varios profesores comparten la misma clase.
     *
     * Una clase con dos docentes son N filas en `horarios`, una por persona, con el
     * mismo (día, periodo, grupo, materia, división) y `rol_docente` distinguiéndolas.
     * Que cada uno tenga su fila es lo que hace que su horario, su disponibilidad y sus
     * suplencias funcionen sin ningún caso especial.
     *
     * El titular es el primero que aparece en el archivo. Pasado el tope de
     * acompañantes la fila es un error y no un recorte silencioso: si el archivo mete
     * cuatro docentes en una clase, o el tope se queda corto o el archivo está mal, y
     * las dos cosas hay que verlas.
     *
     * @return int cuántas clases llevan más de un docente (para el resumen).
     */
    private static function resolverCoteaching(array &$filas): int {
        $bloques = [];
        foreach ($filas as $i => $f) {
            if ($f['estado'] === 'error' || !$f['periodo_id'] || $f['k_materia'] === '') continue;
            $bloques[implode('|', [$f['dia'], $f['periodo_id'], $f['k_grupo'], $f['k_materia'], $f['division']])][] = $i;
        }
        $conVarios = 0;
        foreach ($bloques as $indices) {
            if (count($indices) < 2) continue;
            $conVarios++;
            foreach ($indices as $pos => $i) {
                if ($pos === 0) continue;
                if ($pos > self::MAX_ACOMPANANTES) {
                    $filas[$i]['estado'] = 'error';
                    $filas[$i]['motivo'] = 'Esa clase ya tiene titular y ' . self::MAX_ACOMPANANTES
                        . ' acompañantes, que es el máximo.';
                    continue;
                }
                $filas[$i]['rol_docente'] = 'acompanante';
                if ($filas[$i]['estado'] === 'ok') {
                    $filas[$i]['motivo'] = 'Acompaña a ' . $filas[$indices[0]]['profesor'] . ' en esta clase.';
                }
            }
        }
        return $conVarios;
    }

    /**
     * Choques POR RELOJ dentro del archivo: profesor, grupo y aula.
     *
     * Por reloj y no por `periodo_id` porque la jornada es por nivel: la 3ª hora de
     * Primaria y la 3ª de Secundaria son periodos distintos que se pisan en el tiempo,
     * y un profesor que da clase en los dos niveles no puede estar en las dos.
     *
     * Tres convivencias son legítimas y no se reportan:
     *   · clase conjunta  mismo profesor, misma hora y materia, dos grupos (6ºA+6ºB)
     *   · coteaching      mismo grupo, misma hora y materia, dos profesores
     *   · materia dividida mismo grupo y hora, materias distintas (ya con `division`)
     *
     * El aula es **aviso** y no error: el patio o el salón de usos múltiples reciben a
     * dos grupos a la vez sin que eso sea un fallo del archivo.
     *
     * No hay comprobación contra el horario ya cargado, y es deliberado: importar
     * reemplaza la rejilla entera, así que no queda nada con lo que chocar.
     */
    private static function marcarChoquesCsv(array &$filas): void {
        $ocupa = ['prof' => [], 'grupo' => [], 'aula' => []];

        foreach ($filas as $i => $f) {
            if ($f['estado'] === 'error' || !$f['periodo_id']) continue;

            $buscar = function (string $tipo, string $id, bool $mismaMateria) use (&$ocupa, $f) {
                foreach ($ocupa[$tipo][$f['dia'] . '|' . $id] ?? [] as $x) {
                    // Mismo bloque real: misma casilla de reloj y, donde toca, misma materia
                    if ($x['periodo_id'] === $f['periodo_id'] && (!$mismaMateria || $x['k_materia'] === $f['k_materia'])) continue;
                    if (Periodo::solapan($f['ini'], $f['fin'], $x['ini'], $x['fin'])) return $x;
                }
                return null;
            };
            $marca = ['ini' => $f['ini'], 'fin' => $f['fin'], 'hora' => $f['hora'],
                      'periodo_id' => $f['periodo_id'], 'k_materia' => $f['k_materia'],
                      'linea' => $f['linea'], 'materia' => $f['materia']];

            if ($f['k_profesor'] !== '' && ($ch = $buscar('prof', $f['k_profesor'], true))) {
                $filas[$i]['estado'] = 'error';
                $filas[$i]['motivo'] = "{$f['profesor']} ya da {$ch['materia']} de {$ch['hora']} (línea {$ch['linea']}), que se pisa con {$f['hora']}.";
                continue;
            }
            if ($f['k_grupo'] !== '' && ($ch = $buscar('grupo', $f['k_grupo'], false))) {
                $filas[$i]['estado'] = 'error';
                $filas[$i]['motivo'] = "El grupo {$f['grupo']} ya tiene {$ch['materia']} de {$ch['hora']} (línea {$ch['linea']}), que se pisa con {$f['hora']}.";
                continue;
            }
            if ($f['k_aula'] !== '' && ($ch = $buscar('aula', $f['k_aula'], true)) && $filas[$i]['estado'] === 'ok') {
                $filas[$i]['estado'] = 'aviso';
                $filas[$i]['motivo'] = "El aula {$f['aula']} ya está ocupada de {$ch['hora']} (línea {$ch['linea']}).";
            }

            $ocupa['prof'][$f['dia'] . '|' . $f['k_profesor']][] = $marca;
            if ($f['k_grupo'] !== '') $ocupa['grupo'][$f['dia'] . '|' . $f['k_grupo']][] = $marca;
            if ($f['k_aula']  !== '') $ocupa['aula'][$f['dia'] . '|' . $f['k_aula']][]   = $marca;
        }
    }

    /**
     * Grupos que el archivo estrenaría con un nombre ya ocupado por otro nivel.
     *
     * `grupos` tiene `UNIQUE (nombre)` a secas, pero la clave con la que el importador
     * los reconoce es `nivel|nombre` (`claveGrupo()`). Los dos criterios no coinciden, y
     * cuando se separan el INSERT de la confirmación moría con un **`Duplicate entry`
     * de MySQL en crudo, a mitad de la transacción**: se perdía la importación entera y
     * el mensaje no decía qué fila del archivo lo había provocado.
     *
     * Pasa cuando el nivel que el archivo deduce de la materia no es el del grupo que
     * nombra —un prefijo de materia equivocado basta: «K Esp» en el grupo «Maternal»
     * pide un grupo de Kinder llamado «Maternal», y ese nombre ya es del de Maternal—.
     *
     * Aquí es un **error de fila**: se omite esa clase, se dice por qué y el resto del
     * archivo entra. Reutilizar el grupo existente sería peor que no importar la fila,
     * porque metería a un grupo de Kinder las clases de otro nivel sin decirlo.
     *
     * No hace falta para aulas (su clave ES el nombre) ni para materias (su UNIQUE ya
     * es `(nombre, nivel)`, lo mismo que su clave).
     *
     * @param array<string,object> $grupos catálogo actual, indexado por `claveGrupo()`
     */
    private static function marcarGruposAmbiguos(array &$filas, array $grupos): void {
        // Nombre normalizado → nivel de quien ya lo tiene. Se arranca con la BD y se va
        // ampliando con los grupos que el propio archivo estrena: dos filas del archivo
        // pidiendo el mismo nombre en dos niveles chocarían igual entre ellas.
        $duenno = [];
        foreach ($grupos as $g) $duenno[self::claveCatalogo((string)$g->nombre)] = (string)$g->nivel;

        foreach ($filas as $i => $f) {
            if ($f['estado'] === 'error' || $f['k_grupo'] === '' || $f['nivel'] === '') continue;
            if (isset($grupos[$f['k_grupo']])) continue;          // el grupo exacto ya existe

            $kn  = self::claveCatalogo($f['grupo']);
            $ya  = $duenno[$kn] ?? null;
            if ($ya === null) { $duenno[$kn] = $f['nivel']; continue; }
            if (self::claveCatalogo($ya) === self::claveCatalogo($f['nivel'])) continue;

            $filas[$i]['estado'] = 'error';
            $filas[$i]['motivo'] = "El grupo «{$f['grupo']}» ya existe en {$ya} y aquí la materia lo sitúa en "
                . "{$f['nivel']}. Dos grupos no pueden llamarse igual: revisa el prefijo de la materia "
                . "«{$f['materia']}» o renombra el grupo.";
        }
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
     * Interruptor de baja lógica de un aula o un grupo, desde su listado.
     *
     * Es la vuelta atrás de lo que hace la importación: un archivo incompleto puede
     * apagar un aula que sí existe, y sin este botón la única forma de encenderla otra
     * vez sería volver a subir un CSV que la mencione. Vive aquí y no en el formulario
     * de edición porque es una acción de fila, no una propiedad que se rellena.
     *
     * A diferencia de eliminar, NO comprueba dependencias: apagar no rompe nada — deja
     * de ofrecerse para lo nuevo y ya está.
     */
    public static function cambiarActivoCatalogo(Router $router) {
        $tipo = ($_POST['tipo'] ?? '') === 'grupos' ? 'grupos' : 'aulas';
        self::requireEscritura($tipo);

        $modelo  = $tipo === 'grupos' ? Grupo::class : Aula::class;
        $destino = '/dashboard/' . $tipo;
        $id      = (int)($_POST['id'] ?? 0);
        if (!$id || !$modelo::find($id)) { header("Location: {$destino}"); exit; }

        $activo = !empty($_POST['activo']);
        $modelo::cambiarActivo([$id], $activo);
        header("Location: {$destino}?" . ($activo ? 'reactivado=1' : 'inhabilitado=1'));
        exit;
    }

    /**
     * Carga de horarios por CSV. Dos pasos: subir → vista previa → confirmar.
     *
     * ⚠️ Pide `requireAdmin()` además del módulo. Confirmar hace cuatro cosas:
     *
     *   1. Da de alta a los profesores, grupos, aulas y materias que el archivo
     *      estrena, y reactiva los que vuelve a nombrar tras una baja.
     *   2. Vacía la rejilla ENTERA (`Horario::borrarTodo()`), no solo la de los
     *      profesores del archivo: el CSV es el horario completo del plantel. **Este
     *      es el único paso irreversible**, y por eso la casilla lo nombra a él.
     *   3. Inserta las filas válidas y avisa por la campana a cada profesor.
     *   4. Da de BAJA LÓGICA (`activo = 0`) lo que el archivo ya no menciona. No borra
     *      nada: el histórico queda intacto y volver a subir un archivo que lo nombre
     *      lo enciende otra vez. Administradores, administrativos, prefectura y
     *      dirección quedan fuera de esa poda.
     *
     * Todo va en una transacción: si falla una fila no se escribe ninguna. Y la vista
     * previa enseña las consecuencias —altas, bajas, quién se queda sin horario, qué
     * filas se omiten— antes de que haya un botón que pulsar.
     */
    public static function importarHorarios(Router $router) {
        self::requireModulo('horarios');
        self::requireAdmin();

        // Descarga de la plantilla de ejemplo
        if (isset($_GET['plantilla'])) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="horarios-plantilla.csv"');
            echo "\xEF\xBB\xBF";
            // Sin cabecera, igual que el archivo real: la primera línea ya es una clase.
            // Las tres de ejemplo cubren las tres numeraciones de jornada que existen.
            echo "Pablo Benlliure,L,1,B Arte,6°A Bach,Arte,LEC,1\n";
            echo "Nancy G,L,B1,P Lectura,Prim 1°A,Biblioteca,LEC,1\n";
            echo "Nieves,L,C1,K Esp,Kinder 1,K1,LEC,1\n";
            exit;
        }

        $filas    = $_SESSION['horarios_import']['filas'] ?? [];
        $resumen  = $_SESSION['horarios_import']['resumen'] ?? [];
        $plan     = $_SESSION['horarios_import']['plan'] ?? [];
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
                $filas   = $_SESSION['horarios_import']['filas'] ?? [];
                $plan    = $_SESSION['horarios_import']['plan'] ?? [];
                $validas = array_values(array_filter($filas, fn($f) => $f['estado'] !== 'error'));

                // El guard real de la casilla «entiendo que reemplaza el horario
                // completo»: el `required` del formulario es solo la ayuda.
                if (empty($_POST['confirmo'])) {
                    $alertas['error'][] = 'Marca la casilla de confirmación: la importación reemplaza el horario de todo el colegio.';
                } elseif (!$validas) {
                    $alertas['error'][] = 'No hay ninguna fila válida que importar.';
                } else {
                    try {
                        $guardado = self::escribirImportacion($validas, $plan);
                        unset($_SESSION['horarios_import']);
                        header('Location: /dashboard/horarios/importar?ok=' . $guardado);
                        exit;
                    } catch (\Throwable $e) {
                        $alertas['error'][] = 'No se pudo importar (no se ha escrito nada): ' . $e->getMessage();
                    }
                }
            }

            if ($accion === 'previsualizar') {
                // ⚠️ El diccionario se guarda PRIMERO, no después: si se sube junto al
                // horario es precisamente para que el horario se lea contra él. Al revés
                // la previa hablaría del diccionario viejo y la siguiente carga saldría
                // distinta sin que nada lo explicara.
                $dicSubido = self::guardarDiccionarioSubido($alertas);

                $archivo = $_FILES['csv'] ?? null;
                $hayHorario = $archivo && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;

                if (!$hayHorario) {
                    // Actualizar solo la tabla de nombres es una tarea legítima por sí
                    // sola, y obligar a elegir además un horario para poder hacerla
                    // llevaba a cargar uno cualquiera «para que dejara pasar».
                    if (!$dicSubido && empty($alertas['error'])) {
                        $alertas['error'][] = 'Elige el CSV del horario, el del diccionario, o los dos.';
                    }
                } elseif ($archivo['size'] > 2 * 1024 * 1024) {
                    $alertas['error'][] = 'El archivo del horario supera los 2 MB.';
                } else {
                    [$filas, $resumen, $plan] = self::parsearCsvHorarios($archivo['tmp_name']);
                    if (!empty($resumen['error'])) {
                        $alertas['error'][] = $resumen['error'];
                        $filas = []; $resumen = []; $plan = [];
                    } else {
                        $_SESSION['horarios_import'] = ['filas' => $filas, 'resumen' => $resumen, 'plan' => $plan];
                    }
                }
            }
        }

        $router->renderAdmin('blog/horarios/importar', [
            'titulo'    => 'Importar horarios',
            'filas'     => $filas,
            'resumen'   => $resumen,
            'plan'      => $plan,
            'alertas'   => $alertas,
            'importado' => (int)($_GET['ok'] ?? 0),
            // El paso 1 también lo necesita —dice con qué se va a cotejar el archivo—, y
            // ahí todavía no hay `$resumen`. Va como dato propio para que la vista no
            // tenga que preguntarle nada al modelo.
            'diccionario' => Diccionario::estado(),
        ]);
    }

    /** Tope del diccionario subido. Son ~40 filas de texto: 1 MB sobra de lejos. */
    private const DIC_MAX_MB = 1;

    /**
     * Guarda el diccionario que venga en el POST. Devuelve `true` si se escribió.
     *
     * ⚠️ Se **valida antes de mover**, con el propio lector (`Diccionario::comprobar()`)
     * y exigiendo cabecera: un archivo que no se entiende no puede pisar al que
     * funciona, y sin cabecera cualquier CSV de cuatro columnas —una lista de aulas, un
     * export de otra cosa— se leería como si fuera el claustro. La siguiente
     * importación duplicaría el colegio entero sin que nadie hubiera visto un error.
     *
     * Sobrescribe siempre el mismo archivo en vez de acumular versiones: con la regla
     * de «gana el más reciente», un historial de subidas sería un montón de candidatos
     * compitiendo por fecha. El suelo al que se vuelve es la copia de `diccionario/`,
     * que está versionada.
     *
     * @param array $alertas se le añaden los errores y el aviso de éxito
     */
    private static function guardarDiccionarioSubido(array &$alertas): bool {
        $f = $_FILES['diccionario'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return false;

        if (($f['error'] ?? 0) !== UPLOAD_ERR_OK) {
            $alertas['error'][] = 'No se pudo subir el diccionario (error ' . (int)$f['error'] . ').';
            return false;
        }
        if (($f['size'] ?? 0) > self::DIC_MAX_MB * 1024 * 1024) {
            $alertas['error'][] = 'El diccionario supera ' . self::DIC_MAX_MB . ' MB.';
            return false;
        }

        // ⚠️ Solo CSV, y no por comodidad: el destino se llama `claustro.csv` y el
        // lector decide por extensión. Un `.xlsx` guardado con ese nombre iría al
        // lector de CSV y saldría ilegible, o peor, como una fila de basura.
        // Desde Excel es «Guardar como → CSV UTF-8», que es lo que dice la pantalla.
        $ext = strtolower((string)pathinfo((string)$f['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $alertas['error'][] = 'El diccionario tiene que ser un CSV. En Excel: Guardar como → CSV UTF-8.';
            return false;
        }
        [$entradas, $error] = Diccionario::comprobar((string)$f['tmp_name'], $ext, true);
        if ($error !== null || !$entradas) {
            $alertas['error'][] = 'El diccionario no se guardó: ' . ($error ?: 'no tiene ninguna fila con nombres.');
            return false;
        }

        $destino = Diccionario::DESTINO;
        if (!is_dir(dirname($destino))) @mkdir(dirname($destino), 0775, true);
        if (!@move_uploaded_file((string)$f['tmp_name'], $destino)) {
            // En un arnés de pruebas el archivo no viene de una subida real.
            if (!@copy((string)$f['tmp_name'], $destino)) {
                $alertas['error'][] = 'No se pudo escribir en «storage/diccionario/». Revisa los permisos de la carpeta.';
                return false;
            }
        }

        Diccionario::olvidar();
        $alertas['exito'][] = 'Diccionario actualizado: ' . count($entradas) . ' personas.';
        return true;
    }

    /**
     * Escribe la importación ya validada. Todo o nada.
     *
     * El orden importa y no es arbitrario: primero los catálogos, porque las filas
     * viajan con **claves** de catálogo y no con ids —un grupo que el archivo estrena
     * no tiene id hasta este momento—; después el borrado; y al final los INSERT.
     *
     * Los colores se fotografían ANTES de borrar: el archivo no trae ninguna columna
     * de color, así que sin esa foto reimportar revertía en silencio cada color
     * elegido a mano en el editor. Es el único dato que el CSV no sabe expresar.
     *
     * Las BAJAS LÓGICAS van al final, después de insertar la rejilla nueva: así se
     * apaga sobre el estado definitivo y no sobre uno intermedio.
     *
     * @param array $validas Filas sin error, tal y como las dejó parsearCsvHorarios().
     * @param array $plan    `['altas' => …, 'apagar' => …, 'encender' => …]`. `match` no
     *                       se lee aquí: es lo que el archivo reconoce y no hay que tocar.
     * @return int filas insertadas en `horarios`.
     */
    private static function escribirImportacion(array $validas, array $plan): int {
        $altas    = $plan['altas']    ?? [];
        $apagar   = $plan['apagar']   ?? [];
        $encender = $plan['encender'] ?? [];

        $db = UsuarioBlog::getDB();
        $db->begin_transaction();
        try {
            // ── 1. Altas de catálogo ─────────────────────────────────────────────
            // Los tres catálogos, con los dados de baja incluidos: lo que reaparece se
            // reconoce y se reactiva más abajo, en vez de intentar crearlo otra vez
            // contra su UNIQUE.
            $profesores = [];
            $vivos      = [];
            foreach (UsuarioBlog::todosParaImportar() as $u) {
                $profesores[self::claveCatalogo((string)$u->nombre)] = (int)$u->id;
                $vivos[(int)$u->id] = true;
            }
            $grupos = [];
            foreach (Grupo::todos(true) as $g) $grupos[self::claveGrupo((string)$g->nombre, (string)$g->nivel)] = (int)$g->id;
            $aulas = [];
            foreach (Aula::todas(true) as $a) $aulas[self::claveCatalogo((string)$a->nombre)] = (int)$a->id;
            $materias = [];
            foreach (Materia::todas(true) as $m) {
                $materias[self::claveCatalogo((string)$m->nivel) . '|' . self::claveCatalogo((string)$m->nombre)] = (int)$m->id;
            }

            foreach ($altas['grupos'] ?? [] as $k => $g) {
                if (isset($grupos[$k])) continue;
                $nuevo = new Grupo();
                $nuevo->sincronizar(['nombre' => $g['nombre'], 'nivel' => $g['nivel']]);
                $r = $nuevo->guardar();
                $grupos[$k] = (int)($r['id'] ?? 0);
            }
            foreach ($altas['aulas'] ?? [] as $k => $a) {
                if (isset($aulas[$k])) continue;
                $nuevo = new Aula();
                $nuevo->sincronizar(['nombre' => $a['nombre']]);
                $r = $nuevo->guardar();
                $aulas[$k] = (int)($r['id'] ?? 0);
            }
            foreach ($altas['materias'] ?? [] as $k => $m) {
                if (isset($materias[$k])) continue;
                $nuevo = new Materia();
                $nuevo->sincronizar(['nombre' => $m['nombre'], 'nivel' => $m['nivel']]);
                $r = $nuevo->guardar();
                $materias[$k] = (int)($r['id'] ?? 0);
            }
            $creados = [];
            foreach ($altas['profesores'] ?? [] as $k => $p) {
                if (isset($profesores[$k])) continue;
                $id = UsuarioBlog::altaDocente($p['nombre'], $p['email'], self::CSV_PASSWORD_INICIAL, $p['niveles'] ?? []);
                if ($id <= 0) throw new \RuntimeException("No se pudo dar de alta a «{$p['nombre']}».");
                $profesores[$k] = $id;
                $creados[$id]   = true;
            }

            // ── 2. Foto de los colores y borrado total ───────────────────────────
            // `coloresDeProfesores()` sin argumentos devuelve los de toda la tabla, que
            // es lo que hace falta aquí: el reemplazo es completo.
            $colores = Horario::coloresDeProfesores();
            Horario::borrarTodo();

            // ── 3. Inserción ─────────────────────────────────────────────────────
            $n = 0;
            $idDe = [];                 // clave de persona → id, para el paso 4
            foreach ($validas as $f) {
                // El id resuelto en la previa manda sobre la clave de nombre: si entre
                // revisar el archivo y confirmar alguien renombró a esa persona en
                // Usuarios, su clave ya no casaría y la importación abortaría entera por
                // un cambio que no tiene nada que ver. Se comprueba que la cuenta siga
                // existiendo, que es lo único que el id no garantiza por sí solo.
                $profesorId = (int)($f['profesor_id'] ?? 0);
                if ($profesorId > 0 && !isset($vivos[$profesorId])) $profesorId = 0;
                if ($profesorId <= 0) $profesorId = $profesores[$f['k_profesor']] ?? 0;
                if ($profesorId <= 0) throw new \RuntimeException("Línea {$f['linea']}: no se resolvió al profesor «{$f['profesor']}».");
                $idDe[$f['k_profesor']] = $profesorId;
                $grupoId   = $f['k_grupo']   !== '' ? ($grupos[$f['k_grupo']]     ?? 0) : 0;
                $aulaId    = $f['k_aula']    !== '' ? ($aulas[$f['k_aula']]       ?? 0) : 0;
                $materiaId = $f['k_materia'] !== '' ? ($materias[$f['k_materia']] ?? 0) : 0;

                $h = new Horario();
                $h->sincronizar([
                    'dia'         => $f['dia'],
                    'periodo_id'  => $f['periodo_id'],
                    'profesor_id' => $profesorId,
                    'tipo'        => 'clase',
                    'grupo_id'    => $grupoId   ?: null,
                    'aula_id'     => $aulaId    ?: null,
                    'materia_id'  => $materiaId ?: null,
                    'rol_docente' => $f['rol_docente'],
                    'division'    => (int)$f['division'],
                    'color'       => $colores[$profesorId . '|' . $f['dia'] . '|' . $f['periodo_id']] ?? null,
                ]);
                $h->guardar();
                $n++;
            }

            // ── 4. Niveles declarados de los docentes que ya existían ────────────
            // Para los nuevos ya los fijó altaDocente(). El archivo es la fuente
            // declarativa del nivel de un profesor, así que se recalcula en cada carga.
            // `$idDe` y no el índice por nombre: es el mismo id con el que se acaba de
            // escribir cada clase, así que los niveles no pueden acabar en otra cuenta.
            $nivelesDe = [];
            foreach ($validas as $f) {
                if ($f['nivel'] === '') continue;
                $nivelesDe[$idDe[$f['k_profesor']] ?? 0][$f['nivel']] = true;
            }
            foreach ($nivelesDe as $pid => $set) {
                if ($pid <= 0 || isset($creados[$pid])) continue;
                UsuarioBlog::guardarNiveles((int)$pid, array_keys($set));
            }

            // ── 4b. Correos que el diccionario corrige ───────────────────────────
            // Los recién creados no entran: `altaDocente()` ya les puso el correo del
            // diccionario. `guardarEmail()` revalida la unicidad DENTRO de la
            // transacción —el plan se calculó minutos antes y viaja en sesión— y
            // devuelve `false` en vez de dejar reventar el UNIQUE: una cuenta que se
            // salta no puede tumbar la importación del horario de todo el colegio.
            foreach ($plan['correos'] ?? [] as $c) {
                $pid = (int)($c['id'] ?? 0);
                if ($pid <= 0 || isset($creados[$pid]) || !isset($vivos[$pid])) continue;
                UsuarioBlog::guardarEmail($pid, (string)$c['despues']);
            }

            // ── 5. Bajas lógicas y reactivaciones ────────────────────────────────
            // Lo que el archivo ya no menciona se apaga (`activo = 0`) y lo que vuelve
            // a nombrar se enciende, para que el catálogo ofrecible sea el del colegio
            // y no el sedimento de todas las cargas anteriores.
            //
            // ⚠️ Nada se borra, y por eso este bloque ya no necesita la salvaguarda que
            // tenía (`usosEnSuplencias()` + recomprobación anti-TOCTOU al confirmar):
            // apagar una fila no toca ni un registro del histórico, así que da igual lo
            // que prefectura haya agendado entre la previa y este momento.
            //
            // Va DESPUÉS de insertar la rejilla nueva para decidir sobre el estado
            // definitivo, y ENCENDER va antes que APAGAR: son conjuntos disjuntos por
            // construcción (una clave está en el archivo o no lo está), pero el orden
            // deja la intención clara si alguien los cruza alguna vez.
            foreach ([
                ['profesores', UsuarioBlog::class],
                ['grupos',     Grupo::class],
                ['aulas',      Aula::class],
                ['materias',   Materia::class],
            ] as [$tipo, $modelo]) {
                $modelo::cambiarActivo(array_column($encender[$tipo] ?? [], 'id'), true);
                $modelo::cambiarActivo(array_column($apagar[$tipo]   ?? [], 'id'), false);
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }

        // ⚠️ Las notificaciones van FUERA del try, no solo fuera de la transacción.
        // Dentro, una que fallara entraba en el `catch`, hacía un `rollback()` que a
        // estas alturas ya no deshace nada y relanzaba — y el llamador le decía al
        // admin «no se ha escrito nada» sobre una importación completada. Avisar es
        // accesorio; escribir el horario, no.
        foreach (array_keys($nivelesDe) as $pid) {
            if ($pid <= 0) continue;
            Notificacion::nueva(
                (int)$pid,
                'horario_actualizado',
                'Tu horario se actualizó tras una importación. Revísalo por si algo no cuadra.',
                null, null, 'horarios', 'aviso',
                '/dashboard/horarios/mi-horario'
            );
        }
        return $n;
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

    /**
     * Restaura en `$usuario` los campos que quien guarda NO puede tocar, copiándolos de
     * la fila que hay en base de datos.
     *
     * ⚠️ Esto cierra una escalada de privilegios real. `ActiveRecord::sincronizar()`
     * asigna **cualquier** clave del POST que exista como propiedad, y `modulos` y
     * `puede_suplir` están en `$columnasDB`, así que se persisten. Hasta ahora solo `rol`
     * se blindaba a mano en perfil(): un POST a /dashboard/perfil con
     * `modulos=usuarios,horarios,suplencias` se guardaba tal cual y surtía efecto en el
     * siguiente login. El formulario no pinta esos campos, pero eso es un guard de vista,
     * no de servidor.
     *
     * El `nombre` entra en la misma lista por otra razón: no es un dato personal sino la
     * identidad con la que el resto del claustro reconoce a esta persona en horarios,
     * suplencias e intercambios. Lo cambia un administrador.
     *
     * @param UsuarioBlog $usuario objeto ya sincronizado con el POST
     * @param int         $id      su id, para releer la fila original
     */
    private static function blindarCamposPrivilegiados(UsuarioBlog $usuario, int $id): void {
        if (($_SESSION['blog_usuario']['rol'] ?? '') === 'administrador') return;

        $orig = UsuarioBlog::find($id);
        if (!$orig) return;

        $usuario->rol          = $orig->rol;
        $usuario->nombre       = $orig->nombre;
        $usuario->modulos      = $orig->modulos;
        $usuario->puede_suplir = $orig->puede_suplir;
    }

    /**
     * ¿La confirmación de contraseña casa con la contraseña?
     *
     * Se comprobaba **solo en el cliente** (blog-perfil.js), así que un POST sin JS
     * guardaba lo que viniera en `password` y el usuario se quedaba fuera de su cuenta
     * con una contraseña que no era la que creía haber escrito.
     */
    private static function passwordConfirmada(array $post): bool {
        $p = (string)($post['password'] ?? '');
        if ($p === '') return true;                 // no se está cambiando
        if (!array_key_exists('password_confirm', $post)) return true;  // formulario sin campo
        return $p === (string)$post['password_confirm'];
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
                // El formulario no pinta esos campos para un editor, pero el POST puede
                // traerlos igual: `rol` ya se forzaba, y `nombre`, `modulos` y
                // `puede_suplir` viajaban sin que nadie los mirara.
                self::blindarCamposPrivilegiados($usuario, $id);
            } else {
                $usuario->puede_suplir = self::resolverPuedeSuplir($_POST);
            }

            // Después de validarEdicion(), no antes: esa función arranca vaciando
            // static::$alertas, así que un aviso puesto antes se perdería en silencio.
            $alertas = $usuario->validarEdicion();
            if (!self::passwordConfirmada($_POST)) {
                UsuarioBlog::setAlerta('error', 'Las contraseñas no coinciden');
                $alertas = UsuarioBlog::getAlertas();
            }

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

    /**
     * Da de baja o reactiva una cuenta, desde el listado de Usuarios.
     *
     * Es la alternativa **reversible** a eliminar, y la vuelta atrás de lo que hace la
     * importación de horarios con quien deja de aparecer en el archivo: la persona no
     * entra al panel ni sale como candidata a suplir, pero conserva sus suplencias, sus
     * intercambios, sus artículos y sus notificaciones.
     *
     * ⚠️ Nadie puede darse de baja a sí mismo: dejaría la sesión viva sobre una cuenta
     * que ya no puede volver a entrar, y si fuera el único admin el panel se queda sin
     * quien lo revierta.
     */
    public static function cambiarActivoUsuario(Router $router) {
        $sesion = self::requireAuth();
        self::requireAdmin();

        $id      = (int)($_POST['id'] ?? 0);
        $activo  = !empty($_POST['activo']);
        $usuario = $id ? UsuarioBlog::find($id) : null;

        if (!$usuario) { header('Location: /dashboard/usuarios'); exit; }
        if (!$activo && $id === (int)$sesion['id']) {
            header('Location: /dashboard/usuarios?nobaja=propia');
            exit;
        }

        UsuarioBlog::cambiarActivo([$id], $activo);
        header('Location: /dashboard/usuarios?' . ($activo ? 'reactivado=1' : 'inhabilitado=1'));
        exit;
    }

    // ── PERFIL ────────────────────────────────────────────────────────────────

    /**
     * Mi perfil = **mi propia ficha**, con los campos editables encima.
     *
     * Comparte plantilla (`blog/usuarios/detalle`) y datos (`datosFicha()`) con la ficha
     * de un colaborador ajeno, así que las dos pantallas no pueden desincronizarse. Lo
     * único que cambia es el flag `esPropio`, que añade la sección editable y cambia el
     * título; y el guard.
     *
     * Guard: `requireAuth()` **a secas**, sin `puedeCoordinar()` ni módulo. Es el punto
     * del cambio: la ficha ajena expone datos de terceros y por eso está restringida,
     * pero el horario, las ausencias y los intercambios de uno mismo son suyos. Antes un
     * profesor raso no podía ver esto en ningún sitio del panel.
     *
     * `niveles = []` a propósito: una dirección de nivel no debe verse **su propia**
     * ficha recortada por su alcance de gestión.
     */
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

            // Rol, nombre, módulos y puede_suplir no se tocan desde el perfil. Antes solo
            // se blindaba `rol`, y como `modulos` y `puede_suplir` están en $columnasDB un
            // POST manipulado se concedía módulos a sí mismo. Ver el docblock del guard.
            $usuario->rol = $sesion['rol'];
            self::blindarCamposPrivilegiados($usuario, (int)$sesion['id']);

            $alertas = $usuario->validarPerfil();
            if (!self::passwordConfirmada($_POST)) {
                UsuarioBlog::setAlerta('error', 'Las contraseñas no coinciden');
                $alertas = UsuarioBlog::getAlertas();
            }

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
                            // El cumpleaños va aparte: el ORM base envuelve todo en
                            // comillas y no sabe escribir NULL real, así que vaciar el
                            // campo tiene que pasar por aquí. Crear y editar usuario ya lo
                            // hacían; el perfil no, y por eso el campo no existía aquí.
                            UsuarioBlog::guardarFechaNacimiento(
                                (int)$sesion['id'], $_POST['fecha_nacimiento'] ?? null);
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

        // `findConArticulos()` para que la sección de Redacción cuente igual que en la
        // ficha ajena; si fallara, se sigue con el registro que ya tenemos.
        $ficha = self::datosFicha(UsuarioBlog::findConArticulos((int)$sesion['id']) ?: $usuario);

        // ⚠️ `$usuario` pisa a la clave `u` SOLO cuando el POST no pasó la validación:
        // lleva lo que la persona acababa de escribir, y recargar la fila de la BD le
        // borraría el nombre o el correo corregido justo al enseñarle el error.
        if (!empty($alertas['error'])) $ficha['u'] = $usuario;

        $router->renderAdmin('blog/usuarios/detalle', array_merge($ficha, [
            'titulo'   => 'Mi perfil',
            'esPropio' => true,
            'usuario'  => $usuario,
            'alertas'  => $alertas,
            'guardado' => $guardado,
        ]));
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

    // ══════════════════════════════════════════════════════════════════════════════
    // MÓDULO ACTUALIZACIONES (anuncios de versión)
    // ══════════════════════════════════════════════════════════════════════════════

    /**
     * Historial de novedades. Lo ve cualquiera con sesión —es el registro de lo que ha
     * cambiado en su herramienta de trabajo—; publicar es otra cosa y pide admin.
     */
    public static function actualizaciones(Router $router) {
        $sesion = self::requireAuth();
        $esAdmin = self::esAdmin();

        $router->renderAdmin('blog/actualizaciones/index', [
            'titulo'   => 'Actualizaciones',
            'lista'    => $esAdmin ? Actualizacion::todas() : Actualizacion::publicadas(),
            'esAdmin'  => $esAdmin,
            'total'    => $esAdmin ? Actualizacion::totalUsuarios() : 0,
        ]);
    }

    /** Alta y edición comparten formulario: la única diferencia es si hay `?id=`. */
    public static function crearActualizacion(Router $router) {
        self::requireAuth();
        self::requireAdmin();

        $id  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $act = $id ? Actualizacion::encontrar($id) : new Actualizacion();
        if (!$act) { header('Location: /dashboard/actualizaciones'); exit; }

        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act->sincronizar($_POST);
            if (!$id) $act->creado_por = (int)$_SESSION['blog_usuario']['id'];
            // El estado no se elige en el formulario: se guarda como borrador y se publica
            // con su propio botón. Publicar bloquea a todo el claustro, así que no puede
            // ser un `<select>` que se marca sin querer.
            $act->estado = $act->estado === 'publicada' ? 'publicada' : 'borrador';

            $alertas = $act->validar();

            if (empty($alertas['error'])) {
                $img = self::subirImagenActualizacion();
                if ($img) $act->imagen = $img;
                $r = $act->guardar();
                if ($r['resultado']) {
                    header('Location: /dashboard/actualizaciones?' . ($id ? 'editada=1' : 'creada=1'));
                    exit;
                }
                Actualizacion::setAlerta('error', 'No se pudo guardar el anuncio. Intenta de nuevo.');
                $alertas = Actualizacion::getAlertas();
            }
        }

        $router->renderAdmin('blog/actualizaciones/form', [
            'titulo'  => $id ? 'Editar actualización' : 'Nueva actualización',
            'act'     => $act,
            'esNuevo' => !$id,
            'alertas' => $alertas,
        ]);
    }

    /** Imagen opcional del anuncio (una captura de la novedad). */
    private static function subirImagenActualizacion(): ?string {
        if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) return null;

        $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) return null;
        if ($_FILES['imagen']['size'] > 4 * 1024 * 1024) return null;

        $dir = __DIR__ . '/../public/build/assets/actualizaciones/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $nombre = uniqid('act_', true) . '.' . $ext;
        if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $dir . $nombre)) return null;

        return '/build/assets/actualizaciones/' . $nombre;
    }

    /** Publicar / despublicar. Publicar es lo que dispara el modal bloqueante. */
    public static function publicarActualizacion(Router $router) {
        self::requireAuth();
        self::requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /dashboard/actualizaciones'); exit; }

        $id = (int)($_POST['id'] ?? 0);
        $volver = 'publicada=1';
        if (!empty($_POST['despublicar'])) {
            Actualizacion::despublicar($id);
            $volver = 'despublicada=1';
        } else {
            Actualizacion::publicar($id);
        }
        header('Location: /dashboard/actualizaciones?' . $volver);
        exit;
    }

    public static function eliminarActualizacion(Router $router) {
        self::requireAuth();
        self::requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = Actualizacion::encontrar((int)($_POST['id'] ?? 0));
            if ($act) {
                // La imagen vive en public/ y no la borra ninguna FK.
                if ($act->imagen) {
                    $f = __DIR__ . '/../public' . $act->imagen;
                    if (is_file($f)) @unlink($f);
                }
                $act->eliminar();
            }
        }
        header('Location: /dashboard/actualizaciones?eliminada=1');
        exit;
    }

    /**
     * Acuse de recibo del modal bloqueante.
     *
     * Devuelve al usuario a donde estaba: el modal se interpone en CUALQUIER pantalla, así
     * que mandarlo siempre al home le costaría volver a navegar. `$_POST['volver']` se
     * valida como ruta interna del panel — sin eso sería un redirect abierto.
     */
    public static function verActualizacion(Router $router) {
        $sesion = self::requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /dashboard'); exit; }

        Actualizacion::marcarVista((int)($_POST['id'] ?? 0), (int)$sesion['id']);

        $volver = (string)($_POST['volver'] ?? '');
        if ($volver === '' || !preg_match('#^/dashboard(/|$|\?)#', $volver)) $volver = '/dashboard';
        header('Location: ' . $volver);
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════════════
    // RESTABLECIMIENTO DE CONTRASEÑA
    // ══════════════════════════════════════════════════════════════════════════════

    /**
     * Formulario público: nombre, correo y confirmación del correo.
     *
     * ⚠️ **La respuesta es siempre la misma**, exista o no el correo. Un mensaje distinto
     * convertiría esta pantalla en un verificador de qué direcciones pertenecen al
     * claustro, que es justo lo que no puede ofrecer una pantalla sin autenticar.
     */
    public static function recuperarPassword(Router $router) {
        $alertas = [];
        $enviado = isset($_GET['enviado']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sol = new SolicitudPassword();
            $sol->nombre = trim((string)($_POST['nombre'] ?? ''));
            $sol->email  = trim((string)($_POST['email'] ?? ''));
            $confirm     = trim((string)($_POST['email_confirm'] ?? ''));

            $alertas = $sol->validar();

            // La confirmación se compara EN SERVIDOR: si solo la valida el JS, un envío
            // sin JS deja una solicitud con el correo mal escrito y nadie la puede casar
            // con ninguna cuenta.
            if (strcasecmp($sol->email, $confirm) !== 0) {
                SolicitudPassword::setAlerta('error', 'Los dos correos no coinciden');
                $alertas = SolicitudPassword::getAlertas();
            }

            if (empty($alertas['error'])) {
                if (!SolicitudPassword::demasiadasRecientes($sol->email)) {
                    // Si el correo existe se enlaza la cuenta, y si no la solicitud se
                    // guarda igual: al admin le sirve para detectar a quien se equivoca de
                    // dirección, y el usuario recibe la misma respuesta en los dos casos.
                    $u = UsuarioBlog::findByEmail($sol->email);
                    $sol->usuario_id = $u ? (int)$u->id : null;
                    $sol->ip = $_SERVER['REMOTE_ADDR'] ?? null;
                    $sol->guardar();

                    foreach (UsuarioBlog::administradores() as $adminId) {
                        Notificacion::nueva(
                            $adminId, 'password_solicitada',
                            $sol->nombre . ' pidió restablecer su contraseña.',
                            (int)$sol->id, 'solicitud_password', 'usuarios', 'aviso',
                            '/dashboard/usuarios/solicitudes'
                        );
                    }
                }
                // Se redirige igual aunque se haya frenado por límite: decir "demasiadas
                // solicitudes" también confirmaría que el correo es real.
                header('Location: /recuperar?enviado=1');
                exit;
            }
        }

        $router->renderAdmin('blog/recuperar', [
            'titulo'     => 'Recuperar contraseña',
            'alertas'    => $alertas,
            'enviado'    => $enviado,
            'extra_head' => three_js_tag(),
        ]);
    }

    /** Cola de solicitudes (admin). */
    public static function solicitudesPassword(Router $router) {
        self::requireAuth();
        self::requireModulo('usuarios');
        self::requireAdmin();

        $router->renderAdmin('blog/usuarios/solicitudes', [
            'titulo'   => 'Solicitudes de contraseña',
            'lista'    => SolicitudPassword::todas(),
            // La temporal recién generada viaja por sesión y NO por query string: una URL
            // con la contraseña dentro acaba en el historial del navegador y en los logs
            // del servidor. Se consume y se borra al pintarla.
            'generada' => self::consumirPasswordGenerada(),
        ]);
    }

    /** Saca de la sesión la contraseña temporal recién generada y la borra. */
    private static function consumirPasswordGenerada(): ?array {
        $g = $_SESSION['password_generada'] ?? null;
        unset($_SESSION['password_generada']);
        return $g;
    }

    /**
     * Resolver una solicitud: generar contraseña temporal, o descartarla.
     *
     * La temporal se muestra UNA sola vez, en la recarga siguiente. No se guarda en claro
     * en ningún sitio: lo que queda en base de datos es el hash, como cualquier otra.
     */
    public static function resolverSolicitudPassword(Router $router) {
        $sesion = self::requireAuth();
        self::requireModulo('usuarios');
        self::requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /dashboard/usuarios/solicitudes'); exit; }

        $id  = (int)($_POST['id'] ?? 0);
        $sol = SolicitudPassword::encontrar($id);
        if (!$sol || $sol->estado !== 'pendiente') {
            header('Location: /dashboard/usuarios/solicitudes'); exit;
        }

        if (!empty($_POST['descartar'])) {
            SolicitudPassword::resolver($id, (int)$sesion['id'], 'descartada');
            header('Location: /dashboard/usuarios/solicitudes?descartada=1');
            exit;
        }

        // Sin cuenta enlazada no hay contraseña que cambiar: la solicitud se cierra como
        // descartada y el admin ya sabe, por la propia fila, que el correo no existe.
        if (!$sol->usuario_id) {
            SolicitudPassword::resolver($id, (int)$sesion['id'], 'descartada');
            header('Location: /dashboard/usuarios/solicitudes?sincuenta=1');
            exit;
        }

        $u = UsuarioBlog::find((int)$sol->usuario_id);
        if (!$u) { header('Location: /dashboard/usuarios/solicitudes'); exit; }

        $temporal = SolicitudPassword::generarTemporal();
        $u->password = password_hash($temporal, PASSWORD_BCRYPT);
        $u->guardar();

        SolicitudPassword::resolver($id, (int)$sesion['id'], 'resuelta');

        Notificacion::nueva(
            (int)$u->id, 'password_restablecida',
            'Se restableció tu contraseña. Entra con la temporal que te dieron y cámbiala desde tu perfil.',
            null, null, 'usuarios', 'aviso', '/dashboard/perfil'
        );

        $_SESSION['password_generada'] = [
            'nombre' => $u->nombre,
            'email'  => $u->email,
            'clave'  => $temporal,
        ];
        header('Location: /dashboard/usuarios/solicitudes?resuelta=1');
        exit;
    }
}
