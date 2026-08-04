<?php
/**
 * Catálogo y agrupación de módulos del panel — fuente única compartida.
 *
 * Lo consumen el home de módulos (views/blog/home.php) y el sidebar
 * (views/blog/_sidebar.php). Antes cada uno tenía su propia copia y se
 * desincronizaban al añadir un módulo.
 *
 * Solo define funciones: no emite markup ni deja variables sueltas, así puede
 * incluirse más de una vez en la misma petición sin efectos.
 */

if (!function_exists('blog_modulos_catalogo')) {

    /**
     * ¿Coordina la operación académica? (admin o prefecto)
     *
     * Fuente canónica de la regla en la capa de vistas; espeja
     * BlogController::puedeCoordinar(). _sidebar.php la reexporta como
     * _blog_coordina() para no tener dos copias del mismo criterio.
     */
    function blog_modulos_coordina(): bool {
        $u = $_SESSION['blog_usuario'] ?? null;
        if (!$u) return false;
        if (($u['rol'] ?? '') === 'administrador') return true;
        $tipos = array_filter(array_map('trim', explode(',', (string)($u['tipo_personal'] ?? ''))));
        return in_array('prefecto', $tipos, true);
    }

    /**
     * Todos los módulos del panel, indexados por su clave.
     *
     * Sensible al rol: quien NO coordina (un profesor sin prefectura) solo puede
     * abrir su propio horario, así que su tarjeta de Horarios apunta a
     * /mi-horario y lo dice. Antes todos veían el texto y el destino del admin,
     * y el profesor aterrizaba en una vista que no podía usar.
     *
     * @param bool|null $coordina null = deducirlo de la sesión. Pásalo en `true`
     *        cuando el catálogo describa módulos de OTRA persona (el formulario
     *        de permisos de usuarios), no los del usuario en sesión.
     */
    function blog_modulos_catalogo(?bool $coordina = null): array {
        $coordina = $coordina ?? blog_modulos_coordina();

        return [
            'usuarios'        => ['nombre' => 'Usuarios',        'desc' => 'Colaboradores, permisos y calendario de cumpleaños.', 'icon' => 'fa-users-gear',      'url' => '/dashboard/usuarios'],
            'profesores'      => ['nombre' => 'Profesores',      'desc' => 'Directorio del personal docente.',                    'icon' => 'fa-chalkboard-user', 'url' => '/dashboard/profesores'],
            'prefectura'      => ['nombre' => 'Prefectura',      'desc' => 'Directorio de prefectura y coordinación.',            'icon' => 'fa-user-shield',     'url' => '/dashboard/prefectura'],
            'administrativos' => ['nombre' => 'Administrativos', 'desc' => 'Directorio del personal administrativo.',             'icon' => 'fa-user-tie',        'url' => '/dashboard/administrativos'],

            'suplencias'      => $coordina
                ? ['nombre' => 'Suplencias', 'desc' => 'Organiza y consulta las suplencias del personal.',      'icon' => 'fa-user-clock',  'url' => '/dashboard/suplencias']
                : ['nombre' => 'Suplencias', 'desc' => 'Solicita tus ausencias y confirma tus coberturas.',     'icon' => 'fa-user-clock',  'url' => '/dashboard/suplencias'],

            'horarios'        => $coordina
                ? ['nombre' => 'Horarios',   'desc' => 'Horarios por profesor, aula y grupo de alumnos.',       'icon' => 'fa-table-cells', 'url' => '/dashboard/horarios']
                : ['nombre' => 'Mi horario', 'desc' => 'Consulta tu horario semanal de clases.',                'icon' => 'fa-table-cells', 'url' => '/dashboard/horarios/mi-horario'],

            'eventos'         => ['nombre' => 'Eventos',         'desc' => 'Calendario institucional y avisos para familias.',    'icon' => 'fa-calendar-day',    'url' => '/dashboard/eventos'],
            'aulas'           => ['nombre' => 'Aulas',           'desc' => 'Catálogo de espacios donde se imparte clase.',        'icon' => 'fa-door-open',       'url' => '/dashboard/aulas'],
            'grupos'          => ['nombre' => 'Grupos',          'desc' => 'Catálogo de grupos por nivel académico.',             'icon' => 'fa-layer-group',     'url' => '/dashboard/grupos'],

            'redaccion'       => ['nombre' => 'Redacción',       'desc' => 'Blog, noticias y contenido editorial del colegio.',   'icon' => 'fa-pen-nib',         'url' => '/dashboard/redaccion'],
        ];
    }

    /**
     * Agrupación del panel. El orden manda: se respeta igual en el home y en el
     * sidebar, y en el home además fija la secuencia cromática de las tarjetas.
     */
    function blog_modulos_categorias(): array {
        return [
            ['label' => 'Personal y accesos',  'icon' => 'fa-users',          'claves' => ['usuarios', 'profesores', 'prefectura', 'administrativos']],
            ['label' => 'Operación académica', 'icon' => 'fa-graduation-cap', 'claves' => ['eventos', 'horarios', 'aulas', 'grupos', 'suplencias']],
            ['label' => 'Contenido',           'icon' => 'fa-pen-nib',        'claves' => ['redaccion']],
        ];
    }

    /**
     * Subnavegación de cada módulo — lo que el sidebar despliega al abrirlo.
     *
     * Antes esto eran ocho ramas `elseif ($_modActivo === ...)` de markup a mano en
     * _sidebar.php, y solo se pintaba la del módulo activo. Como datos, el sidebar
     * es un único bucle y añadir una subopción vuelve a ser tocar un solo sitio.
     *
     * Cada entrada:
     *   label   texto del enlace
     *   icon    clase de Font Awesome
     *   url     destino
     *   prefijo true = marcar activo por prefijo de ruta (listado + sus subpáginas)
     *   ver     false = el usuario no puede abrirla, no se pinta
     *
     * Un módulo sin entradas visibles se pinta como enlace directo, sin acordeón.
     *
     * @return array<int, array{label:string, icon:string, url:string, prefijo?:bool, ver?:bool}>
     */
    function blog_modulos_subnav(string $clave): array {
        $u        = $_SESSION['blog_usuario'] ?? null;
        $esAdmin  = ($u['rol'] ?? '') === 'administrador';
        $coordina = blog_modulos_coordina();
        // Revisor editorial: mismo criterio que _blog_puede_revisar() en _sidebar.php
        $revisa   = $esAdmin || ($u['rol_redaccion'] ?? '') === 'revisor';

        $mapa = [
            'usuarios' => [
                ['label' => 'Todos los usuarios', 'icon' => 'fa-users',        'url' => '/dashboard/usuarios'],
                ['label' => 'Nuevo usuario',      'icon' => 'fa-user-plus',    'url' => '/dashboard/usuarios/crear',      'ver' => $esAdmin],
                ['label' => 'Cumpleaños',         'icon' => 'fa-cake-candles', 'url' => '/dashboard/usuarios/cumpleanos'],
            ],

            'suplencias' => [
                // Un profesor raso solo ve sus propias suplencias: la etiqueta lo dice
                ['label' => $coordina ? 'Agenda' : 'Mis suplencias', 'icon' => 'fa-user-clock',      'url' => '/dashboard/suplencias'],
                ['label' => 'Solicitar',                             'icon' => 'fa-hand',            'url' => '/dashboard/suplencias/solicitar'],
                ['label' => 'Mis coberturas',                        'icon' => 'fa-clipboard-check', 'url' => '/dashboard/suplencias/mis-coberturas'],
                ['label' => 'Tablero',                               'icon' => 'fa-chart-line',      'url' => '/dashboard/suplencias/dashboard', 'ver' => $esAdmin],
            ],

            'horarios' => [
                // Las vistas generales exponen horarios de terceros: solo quien coordina
                ['label' => 'Por profesor',  'icon' => 'fa-chalkboard-user',        'url' => '/dashboard/horarios/profesor',   'prefijo' => true, 'ver' => $coordina],
                ['label' => 'Por aula',      'icon' => 'fa-door-open',              'url' => '/dashboard/horarios/aula',       'prefijo' => true, 'ver' => $coordina],
                ['label' => 'Por grupo',     'icon' => 'fa-users-rectangle',        'url' => '/dashboard/horarios/grupo',      'prefijo' => true, 'ver' => $coordina],
                ['label' => 'Mi horario',    'icon' => 'fa-regular fa-calendar-check', 'url' => '/dashboard/horarios/mi-horario'],
                // Reemplaza el horario completo de los profesores del CSV: destructivo
                ['label' => 'Importar CSV',  'icon' => 'fa-file-csv',               'url' => '/dashboard/horarios/importar',   'ver' => $esAdmin],
            ],

            'eventos' => [
                ['label' => 'Calendario',    'icon' => 'fa-calendar-day',  'url' => '/dashboard/eventos'],
                ['label' => 'Nuevo evento',  'icon' => 'fa-calendar-plus', 'url' => '/dashboard/eventos/crear'],
            ],

            'aulas' => [
                ['label' => 'Todas las aulas', 'icon' => 'fa-door-open', 'url' => '/dashboard/aulas'],
                ['label' => 'Nueva aula',      'icon' => 'fa-plus',      'url' => '/dashboard/aulas/crear'],
            ],

            'grupos' => [
                ['label' => 'Todos los grupos', 'icon' => 'fa-layer-group', 'url' => '/dashboard/grupos'],
                ['label' => 'Nuevo grupo',      'icon' => 'fa-plus',        'url' => '/dashboard/grupos/crear'],
            ],

            'redaccion' => [
                ['label' => 'Resumen',            'icon' => 'fa-gauge-high',           'url' => '/dashboard/redaccion'],
                ['label' => 'Artículos',          'icon' => 'fa-regular fa-newspaper', 'url' => '/dashboard/articulos'],
                ['label' => 'Nuevo artículo',     'icon' => 'fa-pen-to-square',        'url' => '/dashboard/articulos/crear'],
                ['label' => 'Categorías',         'icon' => 'fa-tags',                 'url' => '/dashboard/categorias',          'prefijo' => true],
                ['label' => 'Noticias',           'icon' => 'fa-regular fa-bell',      'url' => '/dashboard/noticias'],
                ['label' => 'Nueva noticia',      'icon' => 'fa-bullhorn',             'url' => '/dashboard/noticias/crear'],
                ['label' => 'Categorías noticias','icon' => 'fa-folder-tree',          'url' => '/dashboard/noticias/categorias', 'prefijo' => true],
                ['label' => 'Por autor',          'icon' => 'fa-users-between-lines',  'url' => '/dashboard/autores',             'ver' => $esAdmin],
                ['label' => 'Revisiones',         'icon' => 'fa-clipboard-check',      'url' => '/dashboard/revisiones',          'ver' => $revisa],
                ['label' => 'Testimoniales',      'icon' => 'fa-comment-dots',         'url' => '/dashboard/testimoniales',       'ver' => $revisa],
                ['label' => 'Mis revisiones',     'icon' => 'fa-rotate-left',          'url' => '/dashboard/mis-revisiones',      'ver' => !$esAdmin],
            ],

            // profesores / prefectura / administrativos son listados sin subpáginas:
            // se pintan como enlace directo.
        ];

        $items = $mapa[$clave] ?? [];
        return array_values(array_filter($items, fn($i) => $i['ver'] ?? true));
    }

    /**
     * Categorías con al menos un módulo permitido, ya filtradas.
     * @return array<int, array{label:string, icon:string, claves:string[]}>
     */
    function blog_modulos_visibles(array $disponibles): array {
        $cat    = blog_modulos_catalogo();
        $salida = [];
        foreach (blog_modulos_categorias() as $c) {
            $claves = array_values(array_filter(
                $c['claves'],
                fn($k) => isset($cat[$k]) && in_array($k, $disponibles, true)
            ));
            if ($claves) $salida[] = ['label' => $c['label'], 'icon' => $c['icon'], 'claves' => $claves];
        }
        return $salida;
    }
}
