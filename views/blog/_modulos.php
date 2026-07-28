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

    /** Todos los módulos del panel, indexados por su clave. */
    function blog_modulos_catalogo(): array {
        return [
            'usuarios'        => ['nombre' => 'Usuarios',        'desc' => 'Colaboradores, permisos y calendario de cumpleaños.', 'icon' => 'fa-users-gear',      'url' => '/dashboard/usuarios'],
            'profesores'      => ['nombre' => 'Profesores',      'desc' => 'Directorio del personal docente.',                    'icon' => 'fa-chalkboard-user', 'url' => '/dashboard/profesores'],
            'prefectura'      => ['nombre' => 'Prefectura',      'desc' => 'Personal de prefectura y coordinación.',              'icon' => 'fa-user-shield',     'url' => '/dashboard/prefectura'],
            'administrativos' => ['nombre' => 'Administrativos', 'desc' => 'Directorio del personal administrativo.',             'icon' => 'fa-user-tie',        'url' => '/dashboard/administrativos'],

            'suplencias'      => ['nombre' => 'Suplencias',      'desc' => 'Organiza y consulta las suplencias del personal.',    'icon' => 'fa-user-clock',      'url' => '/dashboard/suplencias'],
            'horarios'        => ['nombre' => 'Horarios',        'desc' => 'Horarios por profesor, aula y grupo de alumnos.',     'icon' => 'fa-table-cells',     'url' => '/dashboard/horarios'],
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
            ['label' => 'Operación académica', 'icon' => 'fa-graduation-cap', 'claves' => ['suplencias', 'horarios', 'eventos', 'aulas', 'grupos']],
            ['label' => 'Contenido',           'icon' => 'fa-pen-nib',        'claves' => ['redaccion']],
        ];
    }

    /** Módulos que solo ve un superadmin (no se conceden por el CSV `modulos`). */
    function blog_modulos_superadmin(): array {
        return ['profesores', 'prefectura', 'administrativos', 'aulas', 'grupos'];
    }

    /**
     * Claves que el usuario puede abrir: las de su CSV `modulos` (o todas si es
     * admin) más las exclusivas de superadmin cuando corresponde.
     */
    function blog_modulos_disponibles(array $modulos, bool $esSuper): array {
        return $esSuper ? array_merge($modulos, blog_modulos_superadmin()) : $modulos;
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
