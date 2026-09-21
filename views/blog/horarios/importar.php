<?php $paginaVista = 'blog-horarios-importar'; ?>
<?php
/**
 * Importar el horario del colegio desde el CSV de Peñalara.
 *
 * DOS PASOS, un solo archivo, distinguidos por `$hayPrevia`:
 *   1. subir  — el aviso de qué hace la herramienta + el campo + el formato esperado
 *   2. previa — un ASISTENTE que recorre las consecuencias antes de dejar confirmar
 *
 * ── Por qué asistente y no una página larga ──
 * El paso 2 volcaba de golpe el bloque de impacto, cuatro tarjetas de cifra, la tabla de
 * catálogo, la nota, el aviso de duplicados, hasta doce listas de nombres, la leyenda,
 * el filtro y las 878 filas del archivo sin paginar. Todo competía por la misma primera
 * pantalla, así que lo irreversible se leía al mismo peso que lo anecdótico. Ahora es una
 * consecuencia por pantalla y la confirmación al final, como el alta de un swap
 * (`views/blog/swaps/crear.php`).
 *
 * ⚠️ DIVERGENCIA respecto a aquel asistente: aquí el índice deja saltar a CUALQUIER paso,
 * también hacia adelante. Allí cada paso recoge un dato que el siguiente necesita; aquí
 * todos son de lectura y el único requisito es la casilla del último. Bloquear el avance
 * obligaría a pulsar «Siguiente» cuatro veces para reimportar un archivo ya revisado. Para
 * que ese salto sea seguro, el paso «Confirmar» lleva el recap completo: es autosuficiente
 * aunque no se haya visitado ningún otro.
 *
 * ⚠️ NADA SE BORRA. Lo que el archivo deja de mencionar se INHABILITA (`activo = 0`) y lo
 * que vuelve a nombrar se reactiva solo. Esta vista estuvo prometiendo un borrado que el
 * controlador ya no hacía —leía `$plan['bajas']`, que no existe— y por eso la previa
 * afirmaba «No se elimina ningún grupo, aula ni materia» mientras iba a apagar decenas.
 * Las claves buenas son `match` / `altas` / `apagar` / `encender` / `fuera`; ver
 * `parsearCsvHorarios()`.
 *
 * ⚠️ Y **`match` es la mitad que faltaba**: lo que el archivo RECONOCE. Sin él, la previa
 * solo sabía hablar de lo que cambia, así que un archivo que encajó con el colegio y uno
 * que va a duplicarlo entero se leían igual. Los tres verbos —reconoce, crea, inhabilita—
 * van juntos en todas las superficies de esta pantalla, y por eso mismo el paso
 * «Catálogo» se pinta aunque no haya nada que crear ni que apagar.
 *
 * Sin JS los cinco paneles se pintan seguidos y el formulario de confirmar sigue en el
 * DOM: más largo de leer, pero nadie se queda sin poder importar.
 *
 * @var array $filas       una por línea del archivo, ya validada
 * @var array $resumen     cifras del parseo + `fuera` + `catalogos` + `diccionario`
 * @var array $plan        ['altas' => …, 'match' => …, 'apagar' => …, 'encender' => …]
 * @var array $alertas
 * @var int   $importado
 * @var array $diccionario estado del diccionario del claustro (archivo, total, error)
 */
$hayPrevia  = !empty($filas);
$iconoFila  = ['ok' => 'fa-circle-check', 'aviso' => 'fa-triangle-exclamation', 'error' => 'fa-circle-xmark'];

/* El estado del diccionario se lee en LOS DOS pasos —en el 1 para decir con qué se va a
   cotejar el archivo, en el 2 para decir además qué tradujo—, así que va antes del corte.
   El del resumen es el de ESTA carga y trae los contadores; el suelto es el de ahora y es
   el único que hay cuando aún no se ha subido nada. */
$dic = ($resumen['diccionario'] ?? []) ?: ($diccionario ?? []);

/* ⚠️ Todo lo que sigue son cifras DE LA PREVIA y solo existe con archivo cargado. Iba
   suelto y el paso 1 reventaba con «Undefined array key "total"» al construir el
   catálogo de pasos, que se arma siempre. */
$toast = null;
if ($importado > 0) {
    /* El éxito llega por `?ok=N` tras el redirect. Toast de Alex, que es lo que pide el
       proyecto para lo nuevo; `.admin-alerta` se queda para los errores de validación,
       que son varios a la vez y hay que poder releerlos. */
    $toast = ['title' => 'Horario importado',
              'msg'   => 'Se escribieron ' . $importado . ' clases. Los profesores afectados ya tienen el aviso en su campana.',
              'icon'  => 'fa-file-import', 'color' => '#34a853'];
}

if ($hayPrevia):

$importable = (int)$resumen['total'] - (int)$resumen['errores'];

/* ── El plan, tal como lo devuelve el controlador ──────────────────────────────
   `match`    = lo que el archivo reconoce y no toca      → nada que hacer
   `apagar`   = lo que el archivo deja de mencionar       → activo = 0
   `encender` = lo que vuelve a nombrar tras una baja     → activo = 1
   Los cuatro llegan indexados por clave de catálogo; aquí solo interesan los valores. */
$altas    = $plan['altas']    ?? [];
$match    = $plan['match']    ?? [];
$apagar   = $plan['apagar']   ?? [];
$encender = $plan['encender'] ?? [];
$cat      = $resumen['catalogos'] ?? [];

$cuenta = function (array $mapa): int {
    $n = 0;
    foreach ($mapa as $lista) $n += count($lista);
    return $n;
};
$totalMatch    = $cuenta($match);
$totalAltas    = $cuenta($altas);
$totalApagar   = $cuenta($apagar);
$totalEncender = $cuenta($encender);
$catLeidos     = array_sum(array_column($cat, 'archivo'));

/* Los cuatro catálogos, en el orden en que importan. Los PROFESORES entran en la poda
   como los demás —`esDocenteDelCenso()` los apaga— y lo que no ocurre nunca es que una
   cuenta se BORRE: arrastraría sus suplencias, sus intercambios y sus notificaciones. */
$CATALOGOS = [
    ['tipo' => 'profesores', 'label' => 'Profesores', 'icono' => 'fa-chalkboard-user', 'nivel' => false],
    ['tipo' => 'grupos',     'label' => 'Grupos',     'icono' => 'fa-user-group',      'nivel' => true],
    ['tipo' => 'aulas',      'label' => 'Aulas',      'icono' => 'fa-door-open',       'nivel' => false],
    ['tipo' => 'materias',   'label' => 'Materias',   'icono' => 'fa-book',            'nivel' => true],
];
// El paso «Catálogo» son las cosas; las personas tienen el suyo.
$COSAS = array_values(array_filter($CATALOGOS, fn($c) => $c['tipo'] !== 'profesores'));

/* ⚠️ La consecuencia más grave de la pantalla no es ninguna fila: es que el archivo deje
   de mencionar UN NIVEL ENTERO (pasa con Maternal, que el colegio no manda en el CSV).
   Repartido en 34 nombres sueltos no se ve, y es justo la conclusión que hay que sacar
   antes de confirmar: ¿está incompleto el export? Un nivel está fuera si algo suyo se
   apaga y el archivo no trae ni una sola clase de ese nivel. */
$nivelesArchivo = [];
foreach ($filas as $f) {
    if ($f['estado'] !== 'error' && $f['nivel'] !== '') $nivelesArchivo[$f['nivel']] = true;
}
$fueraSet = [];
foreach ($apagar as $lista) {
    foreach ($lista as $x) {
        $n = (string)($x['nivel'] ?? '');
        if ($n !== '' && !isset($nivelesArchivo[$n])) $fueraSet[$n] = true;
    }
}
// Ordenados como el colegio los nombra (Maternal → Bachillerato), no por aparición.
$nivelesFuera = array_values(array_intersect(\Model\Materia::NIVELES, array_keys($fueraSet)));

/* ── Personas ──────────────────────────────────────────────────────────────────
   `fuera` trae ya las DOS consecuencias separadas por persona, que no coinciden: un
   prefecto con clases pierde la rejilla y conserva el acceso; un profesor sin horario
   cargado se inhabilita sin perder rejilla. Se pinta la bandera de cada uno. */
$altaProf     = array_values($altas['profesores'] ?? []);
$matchProf    = array_values($match['profesores'] ?? []);
$profEncender = array_values($encender['profesores'] ?? []);
$fuera        = $resumen['fuera'] ?? [];
$pierdeHorario = count(array_filter($fuera, fn($p) => !empty($p['horario'])));
$seInhabilitan = count(array_filter($fuera, fn($p) => !empty($p['baja'])));
$parecidos     = array_values(array_filter($altaProf, fn($p) => !empty($p['parecido'])));

/* ── Qué hizo el diccionario ───────────────────────────────────────────────────
   `traducidos` son las personas que casaron GRACIAS a él: sin diccionario cada una
   habría sido una cuenta nueva duplicada, que es el fallo que vino a cerrar.
   `sin_entrada` son los nombres del archivo que no figuran en él, y es el aviso que
   de verdad hay que leer: o falta esa persona en el diccionario, o el archivo la
   escribe de una forma que nadie más usa. */
$traducidos = (int)($dic['traducidos'] ?? 0);
$sinDic     = $dic['sin_entrada'] ?? [];
// Los reconocidos gracias al diccionario van los primeros de la lista: son los que
// hay que comprobar, porque son los únicos en los que se ha traducido un nombre.
usort($matchProf, fn($a, $b) => [$a['via'] === 'directo', $a['nombre']] <=> [$b['via'] === 'directo', $b['nombre']]);

/* Correos que el diccionario corrige, y los que no se pueden corregir porque ya son de
   otra cuenta. Lo segundo significa que el diccionario está mal y hay que ir a mirarlo. */
$correos     = $plan['correos'] ?? [];
$corConflicto = $resumen['correos_conflicto'] ?? [];
/* Y lo más caro de todo: el diccionario da un correo que es de una cuenta con OTRO
   nombre, así que el horario de una persona se escribe entero en la ficha de otra. */
$dudosos     = $resumen['casados_dudosos'] ?? [];

$coteaching  = (int)($resumen['coteaching'] ?? 0);
$divididas   = (int)($resumen['casillas_divididas'] ?? 0);
$conProblema = (int)($resumen['avisos'] ?? 0) + (int)($resumen['errores'] ?? 0);

/* ── El catálogo de PASOS son datos, no markup repetido ────────────────────────
   De aquí salen a la vez el índice lateral, la barra de progreso y la frase de Alex, así
   que no pueden desincronizarse. Un paso que no tiene nada que contar no se pinta: un
   panel vacío con un empty state es una parada más en un recorrido que ya es largo. */
$PASOS = [];
$PASOS[] = ['clave' => 'impacto', 'corto' => 'Qué pasará', 'titulo' => 'Esto es lo que pasará al confirmar',
            'sub'   => 'Todavía no se ha escrito nada. Revisa las consecuencias antes de seguir.',
            'alex'  => 'alex-espera',
            'dice'  => 'Léelo con calma: el archivo sustituye el horario de todo el colegio. Nada se borra, pero sí cambia mucho.'];
/* ⚠️ El paso se pinta también cuando SOLO hay reconocidos. Antes colgaba de que hubiera
   algo que crear, apagar o reactivar, así que la mejor noticia posible —«el archivo
   encaja entero con el catálogo»— era exactamente la que no se podía comprobar. */
if ($totalAltas || $totalApagar || $totalEncender || $cuenta(array_diff_key($match, ['profesores' => 1]))) {
    $PASOS[] = ['clave' => 'catalogo', 'corto' => 'Catálogo', 'titulo' => 'Cómo quedan grupos, aulas y materias',
                'sub'   => 'Qué reconoce el archivo, qué estrena y qué deja de ofrecerse.',
                'alex'  => 'alex-lee',
                'dice'  => 'Si aquí ves un nivel entero apagándose, seguramente al export le falta ese nivel. Mejor arreglarlo ahora.'];
}
if ($altaProf || $matchProf || $fuera || $profEncender) {
    $PASOS[] = ['clave' => 'personal', 'corto' => 'Personal', 'titulo' => 'A quién afecta este archivo',
                'sub'   => 'A quién reconoce, a quién da de alta y a quién inhabilita. Ninguna cuenta se borra.',
                'alex'  => 'alex-dice',
                'dice'  => $sinDic
                    ? 'Mira primero los nombres que no están en el diccionario: cada uno será una cuenta nueva, y si ya tenían una, es un duplicado.'
                    : 'Cada nombre del archivo lleva al lado la cuenta a la que irá su horario. Si alguna pareja no es la misma persona, para aquí: el horario acabaría en la cuenta equivocada.'];
}
$PASOS[] = ['clave' => 'filas', 'corto' => 'Las filas', 'titulo' => 'Las ' . (int)$resumen['total'] . ' filas del archivo',
            'sub'   => 'Cada línea, con lo que el importador entendió de ella.',
            'alex'  => 'alex-point',
            'dice'  => 'Ordena por columna para agrupar, o filtra a las filas con problema si las hay. Las que dan error se omiten.'];
$PASOS[] = ['clave' => 'confirmar', 'corto' => 'Confirmar', 'titulo' => 'Confirmar la importación',
            'sub'   => 'Último paso. Marca la casilla para poder enviar.',
            'alex'  => 'alex-cientifico',
            'dice'  => 'Aquí tienes el resumen completo, por si has saltado directo. Al confirmar se escribe todo de una vez.'];
$TOTAL = count($PASOS);
$iPaso = array_flip(array_column($PASOS, 'clave'));   // clave → índice, para pintar cada panel

/* ── Los HALLAZGOS, también datos ──────────────────────────────────────────────
   Lo que hay que mirar antes de confirmar estaba repartido por cuatro pasos y el
   último los ponía todos al mismo nivel que las cifras, así que no se distinguía lo
   que pide una decisión de lo que solo informa. Aquí cada uno dice su cifra, su
   consecuencia en una frase, y **a qué paso ir a verlo** — el índice deja saltar, así
   que el hallazgo puede llevar de la mano en vez de describir un sitio.

   `grave` reserva el rojo para lo que se pierde o se hace mal; lo demás es ámbar,
   que en esta pantalla significa reversible. Si todo esto se pintara igual, el color
   dejaría de señalar nada.

   ⚠️ `que` va en pareja [singular, plural] y no con una «s» pegada al final: con un
   archivo casi limpio media pantalla sale en singular («1 nombre del archivo no
   está…»), y ahí el verbo también concuerda. Un plural mal puesto justo en la pantalla
   que pide confianza antes de reescribir el horario del colegio se nota. */
$H = function (string $paso) use ($iPaso): ?int { return $iPaso[$paso] ?? null; };
$HALLAZGOS = [];
if ($dudosos) {
    // El primero de la lista, y en rojo: es el único hallazgo que, si nadie lo mira,
    // acaba con el horario de una persona escrito entero en la cuenta de otra.
    $HALLAZGOS[] = ['grave' => true, 'icono' => 'fa-user-xmark', 'paso' => $H('personal'),
        'n' => count($dudosos),
        'que' => ['nombre del archivo apunta a una cuenta que se llama distinto',
                  'nombres del archivo apuntan a cuentas que se llaman distinto'],
        'por' => 'El diccionario les da un correo que es de otra persona. Su horario se escribiría en esa cuenta. Revísalo antes que nada.'];
}
if ((int)$resumen['errores'] > 0) {
    $HALLAZGOS[] = ['grave' => true, 'icono' => 'fa-circle-exclamation', 'paso' => $H('filas'),
        'n' => (int)$resumen['errores'],
        'que' => ['fila del archivo no se importa', 'filas del archivo no se importan'],
        'por' => 'El importador no las entendió. Esas clases se quedan sin escribir; el resto entra igual.'];
}
if ($nivelesFuera) {
    $HALLAZGOS[] = ['grave' => true, 'icono' => 'fa-layer-group', 'paso' => $H('impacto'),
        'n' => null, 'que' => 'El archivo no trae ni una clase de ' . implode(' ni de ', $nivelesFuera),
        'por' => 'Ese nivel se queda sin horario y sus grupos y materias se inhabilitan. Si sigue existiendo en el colegio, al export le falta.'];
}
if ($corConflicto) {
    $HALLAZGOS[] = ['grave' => true, 'icono' => 'fa-envelope-circle-check', 'paso' => $H('personal'),
        'n' => count($corConflicto),
        'que' => ['correo del diccionario ya es de otra cuenta', 'correos del diccionario ya son de otra cuenta'],
        'por' => 'No se van a tocar, pero significa que el diccionario tiene un correo mal escrito.'];
}
if ($sinDic && !empty($dic['total'])) {
    $HALLAZGOS[] = ['grave' => false, 'icono' => 'fa-address-book', 'paso' => $H('personal'),
        'n' => count($sinDic),
        'que' => ['nombre del archivo no está en el diccionario', 'nombres del archivo no están en el diccionario'],
        'por' => 'Se darán de alta como personas nuevas. Si alguno ya tenía cuenta, quedará duplicado.'];
}
if ($parecidos) {
    $HALLAZGOS[] = ['grave' => false, 'icono' => 'fa-user-large', 'paso' => $H('personal'),
        'n' => count($parecidos),
        'que' => ['cuenta nueva se parece a alguien que ya existe', 'cuentas nuevas se parecen a alguien que ya existe'],
        'por' => 'No se fusionan solas. Si es la misma persona, renómbrala en Usuarios antes de importar.'];
}
if ($correos) {
    $HALLAZGOS[] = ['grave' => false, 'icono' => 'fa-at', 'paso' => $H('personal'),
        'n' => count($correos),
        'que' => ['correo se corrige con el del diccionario', 'correos se corrigen con el del diccionario'],
        'por' => 'Es el usuario con el que esas personas entran al panel: tendrán que usar el nuevo.'];
}
if ($fuera) {
    $HALLAZGOS[] = ['grave' => false, 'icono' => 'fa-user-slash', 'paso' => $H('personal'),
        'n' => count($fuera),
        'que' => ['persona no viene en el archivo', 'personas no vienen en el archivo'],
        'por' => 'Pierden su horario y, si solo daban clase, su cuenta se inhabilita. Nada se borra.'];
}
// Un paso que no existe no se puede visitar: el enlace se cae solo, el hallazgo no.
foreach ($HALLAZGOS as &$_h) { if ($_h['paso'] === null) unset($_h['paso']); }
unset($_h);

endif;   // $hayPrevia
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">
                    <i class="fa-solid fa-file-csv"></i> Importar horarios
                    <span class="hoi-super"><i class="fa-solid fa-shield-halved"></i> Admin</span>
                </span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">

            <?php if (!empty($alertas['error'])): ?>
            <div class="admin-alerta admin-alerta--error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul class="admin-alerta__list"><?php foreach ($alertas['error'] as $e): ?><li><?= s($e) ?></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>

            <?php /* El diccionario puede actualizarse SIN importar ningún horario, así
                     que su confirmación no puede vivir dentro de la previa: sería un
                     éxito que no se llega a ver. Va aquí arriba, donde está el resto
                     de lo que el servidor tiene que decir de este envío. */ ?>
            <?php if (!empty($alertas['exito'])): ?>
            <div class="admin-alerta admin-alerta--success">
                <i class="fa-solid fa-circle-check"></i>
                <ul class="admin-alerta__list"><?php foreach ($alertas['exito'] as $e): ?><li><?= s($e) ?></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>

            <?php if (!$hayPrevia): ?>
            <!-- ══════════ Paso 1: subir el archivo ══════════ -->

            <?php /* El archivo del colegio es el horario COMPLETO del plantel, así que
                     cargarlo no añade: sustituye. Conviene decirlo ANTES de elegir archivo,
                     que es cuando todavía no hay ninguna cifra que enseñar.
                     ⚠️ Vive SOLO en el paso 1: en la previa lo sustituye el paso «Qué
                     pasará», que dice lo mismo con las cifras reales de esta carga. Dos
                     bloques de alarma seguidos se anulan entre ellos y el que acaba sin
                     leerse es justo el que trae los datos.
                     Eran siete líneas de prosa corrida que nadie terminaba; ahora es un
                     titular y tres hechos. */ ?>
            <div class="hoi-aviso">
                <img class="hoi-aviso__alex" src="/build/assets/img/alex/alex-cientifico.png" alt="">
                <div class="hoi-aviso__body">
                    <h2 class="hoi-aviso__t">El archivo sustituye el horario de todo el colegio</h2>
                    <ul class="hoi-aviso__list">
                        <li><strong>La rejilla se reescribe entera.</strong> Quien no venga en el
                            archivo se queda sin clases; su cuenta y su histórico siguen ahí.</li>
                        <li><strong>Nada se borra.</strong> Lo que el archivo deja de mencionar se
                            <em>inhabilita</em>: deja de ofrecerse, pero se conserva y se reactiva
                            solo en cuanto vuelva a aparecer.</li>
                        <li><strong>Verás la previa completa</strong> antes de que haya nada que
                            confirmar. Administradores y administrativos no se tocan nunca.</li>
                    </ul>
                </div>
            </div>

            <?php /* ── Con qué se va a cotejar ───────────────────────────────────────
                     El diccionario es lo que traduce «Gaby» a la cuenta de «Gabriela
                     Sánchez». Se dice ANTES de elegir archivo porque es el momento de
                     arreglarlo: si está desactualizado, cada nombre que le falte acaba
                     en una cuenta duplicada, y eso ya no se deshace desde aquí.
                     Que esté cargado también hay que decirlo — un componente que solo
                     habla cuando falla no se distingue de uno que no existe. */ ?>
            <div class="hoi-dicc<?= empty($dic['total']) ? ' hoi-dicc--falta' : '' ?>">
                <span class="hoi-dicc__ico"><i class="fa-solid fa-book-open-reader"></i></span>
                <div class="hoi-dicc__body">
                    <?php if (!empty($dic['total'])): ?>
                    <p class="hoi-dicc__t">
                        Diccionario del claustro cargado ·
                        <strong><?= (int)$dic['total'] ?></strong> personas
                    </p>
                    <p class="hoi-dicc__d">
                        Los nombres del archivo se casan contra él antes de crear ninguna cuenta,
                        así que «Gaby» va al horario de <strong>Gabriela Sánchez</strong> en vez de
                        abrir una segunda ficha. Sale de
                        <code><?= s((string)$dic['archivo']) ?></code><?php
                        /* De dónde: no es lo mismo estar leyendo lo que alguien cargó
                           la semana pasada que la copia que vino con el proyecto. */
                        echo ($dic['origen'] ?? '') === 'subido'
                            ? ', que se subió desde aquí'
                            : ', la copia que viene con el sistema'; ?>.
                        Para actualizarlo, súbelo en el campo de abajo.
                    </p>
                    <?php if (!empty($dic['otros'])): ?>
                    <?php /* Se usa el más reciente. Decir cuál, porque leer el archivo
                             equivocado en silencio es peor que no leer ninguno. */ ?>
                    <p class="hoi-dicc__d">
                        Hay más archivos disponibles
                        (<?= s(implode(', ', (array)$dic['otros'])) ?>): se usa siempre el más reciente.
                    </p>
                    <?php endif; ?>
                    <?php else: ?>
                    <p class="hoi-dicc__t">No hay diccionario del claustro</p>
                    <p class="hoi-dicc__d">
                        <?= s((string)($dic['error'] ?? '')) ?>
                        Se puede importar igual, pero cada nombre del archivo que no coincida
                        <em>exactamente</em> con el de una cuenta creará una cuenta nueva.
                        Súbelo en el campo de abajo: un CSV con cuatro columnas nombradas en la
                        primera fila — versión corta, versión larga, nombre real y correo.
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="hoi-grid">
                <div class="admin-panel">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title"><i class="fa-solid fa-file-csv"></i> Sube el archivo</h2>
                        <?php /* La plantilla, junto al campo que la pide: es lo primero que hace
                                 falta cuando no se sabe qué columnas lleva el CSV. */ ?>
                        <div class="admin-panel__tools">
                            <a href="/dashboard/horarios/importar?plantilla=1" class="admin-btn admin-btn--ghost admin-btn--sm">
                                <i class="fa-solid fa-download"></i> Plantilla CSV
                            </a>
                        </div>
                    </div>
                    <div class="admin-form-section">
                        <?php /* ⚠️ DOS zonas de subida en el mismo formulario, que es la
                                 primera vez que pasa en el panel. `admin-file.js` lo
                                 soporta sin tocarlo —recorre `[data-file]` y guarda todo
                                 su estado en el closure de cada zona—, pero cada una
                                 necesita sus propios `[data-file-title]`/`[data-file-hint]`
                                 DENTRO de su `[data-file]`, o las dos escribirían sobre
                                 el mismo rótulo. */ ?>
                        <form method="POST" action="/dashboard/horarios/importar" enctype="multipart/form-data">
                            <input type="hidden" name="_accion" value="previsualizar">

                            <?php /* Sin `required`: el servidor acepta el horario, el
                                     diccionario, o los dos. Actualizar solo la tabla de
                                     nombres es una tarea por sí sola, y exigir además un
                                     horario llevaba a cargar uno cualquiera para que el
                                     formulario dejara pasar. */ ?>
                            <div class="admin-form__group">
                                <span class="hoi-campo__t">
                                    <i class="fa-solid fa-table-cells"></i> Horario del colegio
                                </span>
                                <label class="admin-file" data-file data-file-max="2">
                                    <input type="file" name="csv" accept=".csv,text/csv">
                                    <span class="admin-file__ico"><i class="fa-solid fa-file-arrow-up"></i></span>
                                    <span class="admin-file__text">
                                        <span class="admin-file__title" data-file-title>Elige el CSV del horario</span>
                                        <span class="admin-file__hint" data-file-hint>Tal como lo exporta el colegio · máx. 2 MB</span>
                                    </span>
                                </label>
                            </div>

                            <div class="admin-form__group">
                                <span class="hoi-campo__t">
                                    <i class="fa-solid fa-book-open-reader"></i> Diccionario del claustro
                                    <em>opcional</em>
                                </span>
                                <label class="admin-file" data-file data-file-max="1">
                                    <input type="file" name="diccionario" accept=".csv,text/csv">
                                    <span class="admin-file__ico"><i class="fa-solid fa-address-book"></i></span>
                                    <span class="admin-file__text">
                                        <span class="admin-file__title" data-file-title>Elige el CSV del diccionario</span>
                                        <span class="admin-file__hint" data-file-hint>Reemplaza el actual · se aplica antes de leer el horario</span>
                                    </span>
                                </label>
                            </div>

                            <div class="hoi-actions">
                                <button type="submit" class="admin-btn admin-btn--primary">
                                    <i class="fa-solid fa-magnifying-glass"></i> Revisar archivo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="admin-panel">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title"><i class="fa-solid fa-list-check"></i> Formato esperado</h2>
                    </div>
                    <div class="admin-form-section">
                        <p class="hoi-help">
                            Una fila por clase, <strong>8 columnas</strong> y <strong>sin cabecera</strong>:
                            la primera línea ya es una clase.
                        </p>
                        <pre class="hoi-code">Pablo Benlliure,L,1,B Arte,6°A Bach,Arte,LEC,1
Nancy G,L,B1,P Lectura,Prim 1°A,Biblioteca,LEC,1
Nieves,L,C1,K Esp,Kinder 1,K1,LEC,1</pre>
                        <ul class="hoi-rules">
                            <li><strong>profesor</strong> — el nombre con el que se le conoce, sin correo.
                                Se busca en el <em>diccionario del claustro</em> y de ahí a su cuenta;
                                solo si no aparece por ningún lado <em>se da de alta</em>, con el correo
                                del diccionario o, a falta de él, uno derivado del nombre
                                (<code>pablo.benlliure@bilbao.edu.mx</code>).</li>
                            <li><strong>día</strong> — <code>L</code> <code>M</code> <code>X</code> <code>J</code> <code>V</code>.</li>
                            <li><strong>periodo</strong> — numera las <em>horas de clase</em> de su jornada,
                                saltándose los recesos: <code>3</code> en Secundaria y Bachillerato,
                                <code>B3</code> en Primaria, <code>C3</code> en Kinder.</li>
                            <li><strong>materia</strong> — con prefijo de nivel: <code>B</code> Bachillerato ·
                                <code>S</code> Secundaria · <code>P</code> Primaria · <code>K</code> Kinder ·
                                <code>M</code> Maternal. <strong>El nivel de la fila sale de aquí.</strong></li>
                            <li><strong>grupo</strong> y <strong>aula</strong> — el aula puede ir vacía.
                                <code>Prim 1°A</code> y <code>1A Primaria</code> se reconocen como el mismo grupo.</li>
                            <li><strong>tipo</strong> — <code>LEC</code>. La octava columna no se usa.</li>
                        </ul>

                        <?php /* Referencia, no lo que hace falta para elegir un archivo: plegado
                                 para que el panel quepa junto al campo de subida sin scroll. */ ?>
                        <details class="hoi-detalle">
                            <summary>Qué reconoce el importador por su cuenta</summary>
                            <p class="hoi-help">
                                Las tres convivencias del horario real: <strong>clase conjunta</strong>
                                (un profesor con dos grupos a la vez), <strong>coteaching</strong> (dos
                                docentes en la misma clase; el primero del archivo es el titular) y
                                <strong>materia dividida</strong> (un grupo con Arte y Música a la misma
                                hora, repartido).
                            </p>
                            <p class="hoi-help">
                                Los choques se comprueban <strong>por hora del reloj</strong>, no por número
                                de periodo: con jornadas distintas por nivel, dos periodos con distinto
                                número pueden ser la misma hora.
                            </p>
                        </details>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- ══════════ Paso 2: la previa, como asistente ══════════ -->

            <?php /* El veredicto del archivo va FUERA del asistente, sobre la barra de
                     progreso: son las cifras con las que se decide y tienen que verse en
                     todos los pasos, no solo en el primero. La dominante es la que va
                     escrita en el botón rojo; «filas leídas» es su denominador, no otra
                     tarjeta. Coteaching y materias divididas viven en la leyenda de la
                     tabla, que es donde explican algo. */ ?>
            <div class="hoi-summary">
                <?php /* ⚠️ La cifra de cabecera va en TINTA, no en verde. El verde de esta
                         pantalla significa «se crea» y lo lleva la tarjeta de al lado: teñir
                         también la dominante le quitaba a ese verde su único significado.
                         Aquí manda el tamaño (2.6rem contra 1.6rem), que es jerarquía que no
                         gasta ningún color del sistema. */ ?>
                <div class="hoi-stat hoi-stat--hero">
                    <span class="hoi-stat__n"><?= $importable ?></span>
                    <span class="hoi-stat__l">clases se importarán</span>
                    <?php /* Los errores perdieron su tarjeta —eran cinco compitiendo por una
                             fila— pero NO su señal: van dentro del denominador, que es donde
                             significan algo («de 878, N no entran»), y en rojo. En el gris del
                             resto del subtítulo se leían al mismo peso que «filas leídas», y
                             son la única cifra de esta barra que pide arreglar el archivo. */ ?>
                    <span class="hoi-stat__sub">de <?= (int)$resumen['total'] ?> filas leídas en el archivo<?php
                        if ((int)$resumen['errores']): ?> · <span class="hoi-stat__alert"><i
                            class="fa-solid fa-circle-exclamation"></i><?= (int)$resumen['errores'] ?>
                            con errores se omiten</span><?php endif; ?></span>
                </div>
                <?php /* Los TRES verbos, siempre los tres y en este orden: lo que el
                         archivo reconoce, lo que estrena y lo que apaga. Faltaba el
                         primero, y sin él las otras dos cifras no tienen denominador:
                         «17 se crean» se lee igual en un archivo que encaja y en uno que
                         va a duplicar medio claustro. */ ?>
                <div class="hoi-stat<?= $totalMatch ? ' hoi-stat--match' : '' ?>">
                    <span class="hoi-stat__n"><?= $totalMatch ?></span>
                    <span class="hoi-stat__l">ya existen</span>
                    <span class="hoi-stat__sub">de <?= $catLeidos ?> que menciona el archivo<?php
                        if ($traducidos) echo ' · ' . $traducidos . ' por el diccionario'; ?></span>
                </div>
                <div class="hoi-stat<?= $totalAltas ? ' hoi-stat--new' : '' ?>">
                    <span class="hoi-stat__n"><?= $totalAltas ?></span>
                    <span class="hoi-stat__l">se crean</span>
                    <span class="hoi-stat__sub"><?= $totalAltas ? 'no estaban en ningún catálogo' : 'el archivo no estrena nada' ?></span>
                </div>
                <?php /* La cifra que esta pantalla no enseñaba en absoluto. Va en ámbar y no
                         en rojo a propósito: inhabilitar es reversible —el propio archivo lo
                         deshace— y pintarla como una destrucción sería mentir hacia el otro
                         lado, que es lo que hacía la versión anterior con su papelera. */ ?>
                <div class="hoi-stat<?= $totalApagar ? ' hoi-stat--warn' : '' ?>">
                    <span class="hoi-stat__n"><?= $totalApagar ?></span>
                    <span class="hoi-stat__l">se inhabilitan</span>
                    <span class="hoi-stat__sub">no se borran: se reactivan solos</span>
                </div>
            </div>

            <?php /* ⚠️ UN SOLO `<form>` para todo el asistente, y las dos acciones son dos
                     submit con `name="_accion"`: anidar formularios es ilegal y el pie
                     necesita Cancelar junto a Importar. «Cancelar» lleva `formnovalidate`
                     o el `required` de la casilla le impediría salir.
                     Los campos del POST siguen en el DOM aunque su panel esté oculto
                     —`hidden` no desactiva un input—, así que el contrato con
                     `importarHorarios()` es idéntico al de antes. */ ?>
            <form method="POST" action="/dashboard/horarios/importar" class="hoi-wiz" data-hoi-wiz>

                <!-- ── Acompañamiento: Alex + índice del recorrido ───────────────── -->
                <aside class="hoi-wiz__aside">
                    <?php /* Los <img> se emiten todos de una vez y el JS alterna cuál se ve:
                             así no hay parpadeo de carga al avanzar. La FRASE viaja en
                             `data-dice`, no copiada en el JS — el catálogo de pasos es uno
                             solo y vive arriba, en PHP. */ ?>
                    <div class="hoi-wiz__alex">
                        <div class="hoi-wiz__alex-img">
                            <?php foreach ($PASOS as $i => $ps): ?>
                            <img src="/build/assets/img/alex/<?= s($ps['alex']) ?>.png" alt=""
                                 data-alex-paso="<?= $i ?>" data-dice="<?= s($ps['dice']) ?>"<?= $i === 0 ? '' : ' hidden' ?>>
                            <?php endforeach; ?>
                        </div>
                        <p class="hoi-wiz__dice" data-hoi-dice><?= s($PASOS[0]['dice']) ?></p>
                    </div>

                    <?php /* Índice navegable en los DOS sentidos (ver la cabecera del archivo).
                             Son <button> y no <li> a secas: se pulsan, así que tienen que
                             alcanzarse con el teclado. */ ?>
                    <ol class="hoi-wiz__pasos" data-hoi-indice>
                        <?php foreach ($PASOS as $i => $ps): ?>
                        <li>
                            <button type="button" class="hoi-wiz__paso<?= $i === 0 ? ' is-actual' : '' ?>"
                                    data-indice="<?= $i ?>"<?= $i === 0 ? ' aria-current="step"' : '' ?>>
                                <span class="hoi-wiz__paso-n"><?= $i + 1 ?></span>
                                <span class="hoi-wiz__paso-txt">
                                    <span class="hoi-wiz__paso-corto"><?= s($ps['corto']) ?></span>
                                    <span class="hoi-wiz__paso-val"><?= s($ps['sub']) ?></span>
                                </span>
                            </button>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                </aside>

                <!-- ── Panel del paso activo ─────────────────────────────────────── -->
                <div class="hoi-wiz__main">

                    <div class="hoi-wiz__bar">
                        <div class="hoi-wiz__bar-txt">
                            <span>Paso <strong data-hoi-actual>1</strong> de <?= $TOTAL ?></span>
                            <span data-hoi-restante></span>
                        </div>
                        <div class="hoi-wiz__bar-riel">
                            <span class="hoi-wiz__bar-fill" data-hoi-fill style="width:<?= round(100 / $TOTAL) ?>%"></span>
                        </div>
                    </div>

                    <?php /* ⚠️ `tabindex="-1"` en los cinco: al cambiar de paso, el botón que se
                             acaba de pulsar se va a `hidden` —«Ver» de un hallazgo vive DENTRO
                             del panel que se oculta, y «Siguiente»/«Atrás» se esconden al llegar
                             al extremo—, así que el foco se caía al `<body>` y quien navega con
                             teclado perdía el sitio en una pantalla de cinco pasos. El módulo
                             enfoca el panel de destino, que además hace que un lector de pantalla
                             lea su título al llegar. */ ?>
                    <?php /* ⚠️ Los paneles NO nacen con `hidden`: sin JS se pintan los cinco
                             seguidos y el formulario de confirmar sigue ahí, que es lo que
                             hace falta en una pantalla que solo abre un admin y que reescribe
                             el horario del colegio. Quien los pliega es la cascada: el
                             `<head>` síncrono de `layout-admin.php` estampa `html.js` antes
                             del primer pintado, así que `html.js .hoi-wiz:not(.is-wiz)` oculta
                             todo menos `.is-activo` sin ningún flash. En cuanto el bundle
                             monta, el JS añade `.is-wiz` y a partir de ahí manda `hidden`,
                             como en el resto del panel. */ ?>
                    <!-- ══ Paso · Qué pasará ══ -->
                    <section class="hoi-panel is-activo" data-hoi-panel tabindex="-1">
                        <div class="hoi-panel__head">
                            <h2 class="hoi-panel__title"><?= s($PASOS[$iPaso['impacto']]['titulo']) ?></h2>
                            <p class="hoi-panel__sub"><?= s($PASOS[$iPaso['impacto']]['sub']) ?></p>
                        </div>
                        <div class="hoi-panel__body">

                            <?php /* Las consecuencias, de más a menos grave. Cada una dice su
                                     cifra real: un «se actualizan N registros» escondería justo
                                     la mitad que no es una creación. */ ?>
                            <ol class="hoi-impacto">
                                <li class="hoi-impacto__i hoi-impacto__i--alta">
                                    <span class="hoi-impacto__ico"><i class="fa-solid fa-table-cells"></i></span>
                                    <div>
                                        <p class="hoi-impacto__t">Se reescribe el horario completo del colegio</p>
                                        <p class="hoi-impacto__d">
                                            La rejilla actual se vacía y se vuelve a escribir con las
                                            <strong><?= $importable ?></strong> filas válidas de este archivo<?php
                                            if ((int)$resumen['errores']) echo '; las ' . (int)$resumen['errores'] . ' con error se omiten'; ?>.
                                        </p>
                                    </div>
                                </li>

                                <?php /* Lo que NO cambia va antes que lo que cambia, y es
                                         deliberado: es la comprobación de que el archivo
                                         habla del mismo colegio. Si esta cifra sale baja y
                                         la de altas alta, el archivo no encaja y hay que
                                         parar — antes eso no se veía por ningún lado. */ ?>
                                <li class="hoi-impacto__i hoi-impacto__i--match">
                                    <span class="hoi-impacto__ico"><i class="fa-solid fa-link"></i></span>
                                    <div>
                                        <p class="hoi-impacto__t">
                                            Se reconocen <strong><?= $totalMatch ?></strong> de los
                                            <strong><?= $catLeidos ?></strong> nombres del archivo
                                        </p>
                                        <p class="hoi-impacto__d">
                                            Ya existen en el catálogo, así que no se crea ni se toca nada:
                                            conservan su id y todo lo que los cita.
                                            <?php if ($traducidos): ?>
                                            <strong><?= $traducidos ?></strong>
                                            persona<?= $traducidos === 1 ? '' : 's' ?> casaron gracias al
                                            <strong>diccionario del claustro</strong>
                                            (<?= s((string)($dic['archivo'] ?? '')) ?>): sin él habrían
                                            sido cuentas nuevas duplicadas.
                                            <?php endif; ?>
                                        </p>
                                        <?php /* ⚠️ Los dos avisos se excluyen: sin diccionario, «no
                                                 está en el diccionario» sería cierto de todos y no
                                                 señalaría nada. Lo que hay que decir entonces es que
                                                 no hay con qué cotejar. */ ?>
                                        <?php if (empty($dic['total'])): ?>
                                        <p class="hoi-impacto__alerta">
                                            <i class="fa-solid fa-address-book"></i>
                                            <strong>No hay diccionario del claustro</strong>, así que las
                                            personas solo se reconocen por su nombre exacto. Cualquiera que
                                            el archivo escriba de otra forma abrirá una cuenta nueva aunque
                                            ya tenga la suya.
                                        </p>
                                        <?php elseif ($sinDic): ?>
                                        <p class="hoi-impacto__alerta">
                                            <i class="fa-solid fa-address-book"></i>
                                            <strong><?= count($sinDic) ?></strong>
                                            nombre<?= count($sinDic) === 1 ? '' : 's' ?> del archivo no
                                            está<?= count($sinDic) === 1 ? '' : 'n' ?> en el diccionario:
                                            <?= s(implode(' · ', array_slice($sinDic, 0, 6))) ?><?php
                                            if (count($sinDic) > 6) echo ' y ' . (count($sinDic) - 6) . ' más'; ?>.
                                            Si alguno ya tiene cuenta, se duplicará.
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </li>

                                <li class="hoi-impacto__i hoi-impacto__i--<?= $totalApagar ? 'baja' : 'nada' ?>">
                                    <span class="hoi-impacto__ico"><i class="fa-solid fa-power-off"></i></span>
                                    <div>
                                        <?php if ($totalApagar): ?>
                                        <p class="hoi-impacto__t">
                                            Se inhabilitan <strong><?= $totalApagar ?></strong>
                                            registro<?= $totalApagar === 1 ? '' : 's' ?> que el archivo ya no menciona
                                        </p>
                                        <p class="hoi-impacto__d">
                                            <strong>No se borra ninguno.</strong> Dejan de ofrecerse en los
                                            desplegables y en las suplencias, conservan todo su histórico, y
                                            se reactivan solos en cuanto vuelvan a aparecer en un archivo.
                                        </p>
                                        <?php else: ?>
                                        <p class="hoi-impacto__t">No se inhabilita nada</p>
                                        <p class="hoi-impacto__d">El archivo menciona todo lo que ya existe.</p>
                                        <?php endif; ?>

                                        <?php if ($nivelesFuera): ?>
                                        <?php /* La conclusión que 34 nombres sueltos no dejan sacar.
                                                 ⚠️ NO dice «entre ellos»: lo que se afirma es un hecho
                                                 del ARCHIVO —no trae ese nivel—, que es además la
                                                 pregunta útil: ¿está incompleto el export? */ ?>
                                        <p class="hoi-impacto__alerta">
                                            <i class="fa-solid fa-layer-group"></i>
                                            El archivo <strong>no trae ni una clase de
                                            <?= s(implode(' ni de ', $nivelesFuera)) ?></strong>.
                                            Si ese nivel sigue existiendo en el colegio, al export le falta.
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </li>

                                <?php if ($totalEncender): ?>
                                <li class="hoi-impacto__i hoi-impacto__i--reac">
                                    <span class="hoi-impacto__ico"><i class="fa-solid fa-rotate-left"></i></span>
                                    <div>
                                        <p class="hoi-impacto__t">
                                            Se reactivan <strong><?= $totalEncender ?></strong>
                                            registro<?= $totalEncender === 1 ? '' : 's' ?> que estaban de baja
                                        </p>
                                        <p class="hoi-impacto__d">
                                            El archivo vuelve a nombrarlos, así que vuelven a estar disponibles
                                            con su id de siempre: nada de lo que los citaba se quedó huérfano.
                                        </p>
                                    </div>
                                </li>
                                <?php endif; ?>

                                <li class="hoi-impacto__i hoi-impacto__i--<?= $totalAltas ? 'alta' : 'nada' ?>">
                                    <span class="hoi-impacto__ico"><i class="fa-solid fa-plus"></i></span>
                                    <div>
                                        <p class="hoi-impacto__t">
                                            <?php if ($totalAltas): ?>
                                            Se crean <strong><?= $totalAltas ?></strong>
                                            registro<?= $totalAltas === 1 ? '' : 's' ?> de catálogo que faltan
                                            <?php else: ?>
                                            No hace falta crear nada
                                            <?php endif; ?>
                                        </p>
                                        <p class="hoi-impacto__d">
                                            Solo lo que no encontró de ninguna forma: <code>Prim 1°A</code> y
                                            <code>1A Primaria</code> se reconocen como el mismo grupo, y los
                                            nombres de persona pasan antes por el diccionario, así que nada
                                            de eso llega hasta aquí.
                                        </p>
                                    </div>
                                </li>
                            </ol>

                            <?php /* Tabla de un vistazo: por catálogo, cuántos nombres trae el
                                     archivo y qué le pasa a cada grupo. Sin la columna «en el
                                     archivo», un «Grupos 4» se lee como que el importador solo
                                     entendió cuatro de los 22 que hay. */ ?>
                            <h3 class="hoi-sub">Registro a registro</h3>
                            <div class="admin-table-scroll">
                                <table class="admin-table hoi-cat">
                                    <thead>
                                        <tr>
                                            <th>Catálogo</th>
                                            <th class="hoi-cat__n">En el archivo</th>
                                            <th class="hoi-cat__n">Ya existen</th>
                                            <th class="hoi-cat__n">Se crean</th>
                                            <th class="hoi-cat__n">Se inhabilitan</th>
                                            <th class="hoi-cat__n">Se reactivan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($CATALOGOS as $c): ?>
                                        <?php
                                        $t  = $c['tipo'];
                                        $nM = count($match[$t]    ?? []);
                                        $nA = count($altas[$t]    ?? []);
                                        $nP = count($apagar[$t]   ?? []);
                                        $nE = count($encender[$t] ?? []);
                                        ?>
                                        <tr>
                                            <td class="hoi-cat__t">
                                                <i class="fa-solid <?= $c['icono'] ?>"></i> <?= $c['label'] ?>
                                            </td>
                                            <td class="hoi-cat__n"><?= (int)($cat[$t]['archivo'] ?? 0) ?></td>
                                            <?php /* «Ya existen» es la columna que faltaba, y va pegada a
                                                     «En el archivo» porque es su fracción: las dos juntas
                                                     dicen de un vistazo si el archivo habla del mismo
                                                     colegio que la base de datos. */ ?>
                                            <td class="hoi-cat__n">
                                                <span class="hoi-delta<?= $nM ? ' hoi-delta--ok' : '' ?>"><?= $nM ?: '—' ?></span>
                                            </td>
                                            <td class="hoi-cat__n">
                                                <span class="hoi-delta<?= $nA ? ' hoi-delta--mas' : '' ?>"><?= $nA ? '+' . $nA : '—' ?></span>
                                            </td>
                                            <td class="hoi-cat__n">
                                                <span class="hoi-delta<?= $nP ? ' hoi-delta--off' : '' ?>"><?= $nP ?: '—' ?></span>
                                            </td>
                                            <td class="hoi-cat__n">
                                                <span class="hoi-delta<?= $nE ? ' hoi-delta--reac' : '' ?>"><?= $nE ?: '—' ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php /* Los dos verbos que la tabla no puede separar en una columna:
                                     una cuenta docente SÍ se inhabilita, lo que no ocurre nunca es
                                     que se borre. Tenerlo como un «nunca» en la columna de bajas
                                     —que es lo que decía antes— era sencillamente falso. */ ?>
                            <p class="hoi-nota">
                                <i class="fa-solid fa-circle-info"></i>
                                <span>
                                    <strong>Nada de esto se elimina.</strong> Inhabilitar solo cambia una
                                    columna: el registro sigue en la base de datos con su id, así que las
                                    suplencias, los intercambios y el histórico que lo citen no pierden el
                                    dato. Grupos y aulas se reactivan a mano desde su catálogo; todo lo
                                    demás, volviendo a subir un archivo que lo mencione.
                                </span>
                            </p>
                        </div>
                    </section>

                    <?php if (isset($iPaso['catalogo'])): ?>
                    <!-- ══ Paso · Catálogo ══ -->
                    <section class="hoi-panel" data-hoi-panel tabindex="-1">
                        <div class="hoi-panel__head">
                            <h2 class="hoi-panel__title"><?= s($PASOS[$iPaso['catalogo']]['titulo']) ?></h2>
                            <p class="hoi-panel__sub"><?= s($PASOS[$iPaso['catalogo']]['sub']) ?></p>
                        </div>
                        <div class="hoi-panel__body">
                            <?php /* Un bloque cerrado por catálogo, y dentro sus listas por verbo.
                                     Eran encabezados hermanos al mismo nivel —«Grupos que se crean»,
                                     «Grupos que se eliminan»…—: la palabra «Grupos» se repetía tres
                                     veces y había que leerla las tres para saber que hablaban del
                                     mismo sujeto. Ahora el sujeto se dice UNA vez. */ ?>
                            <?php $algo = false; foreach ($COSAS as $c): ?>
                                <?php
                                $t   = $c['tipo'];
                                $lM  = array_values($match[$t]    ?? []);
                                $lA  = array_values($altas[$t]    ?? []);
                                $lP  = array_values($apagar[$t]   ?? []);
                                $lE  = array_values($encender[$t] ?? []);
                                if (!$lM && !$lA && !$lP && !$lE) continue;
                                $algo = true;
                                // Los que el archivo escribe distinto a como están en el catálogo:
                                // son los únicos reconocidos que hay algo que revisar.
                                $conAlias = array_values(array_filter($lM, fn($x) => !empty($x['alias'])));
                                ?>
                                <section class="hoi-bloc">
                                    <h3 class="hoi-bloc__t">
                                        <i class="fa-solid <?= $c['icono'] ?>"></i> <?= $c['label'] ?>
                                        <span class="hoi-bloc__meta"><?= (int)($cat[$t]['archivo'] ?? 0) ?> en el archivo</span>
                                    </h3>

                                    <?php if ($lM): ?>
                                    <?php /* Plegado, y es lo único de esta pantalla que lo está: son
                                             hasta 69 nombres que ya están bien y no hay nada que
                                             decidir sobre ellos. Lo que sí se dice sin abrir nada es
                                             CUÁNTOS son, que es la cifra con la que se comprueba que
                                             el archivo habla del mismo colegio. */ ?>
                                    <details class="hoi-detalle hoi-detalle--ok">
                                        <?php /* ⚠️ Cada trozo de texto va ENVUELTO en su <span>. El
                                                 <summary> es flex (lo hereda de `.hoi-detalle`), y un
                                                 texto suelto entre elementos se convierte en un ítem
                                                 anónimo que se encoge por su cuenta: la frase salía
                                                 partida en cachos de distinto alto. Es la misma trampa
                                                 que documenta `.hoi-impacto__t` (`.hoi-filtro`, el otro
                                                 ejemplo que citaba esto, ya no existe: lo relevaron el
                                                 buscador y las pills del paso «Las filas»). */ ?>
                                        <summary>
                                            <span>Ya existen <strong><?= count($lM) ?></strong></span>
                                            <?php if ($conAlias): ?>
                                            <?php /* En caja baja y en gris: es una salvedad del verbo,
                                                     no otro verbo. En versalitas competía con él. */ ?>
                                            <span class="hoi-detalle__meta"><?= count($conAlias) ?> con otro nombre en el archivo</span>
                                            <?php endif; ?>
                                        </summary>
                                        <ul class="hoi-lista hoi-lista--ok">
                                            <?php foreach ($lM as $x): ?>
                                            <li>
                                                <?php /* ⚠️ `title` con el nombre entero. La tarjeta
                                                         tiene alto fijo (52px) y recorta a dos
                                                         líneas, así que «Conservación de la energía
                                                         y sus interacciones con la materia» se queda
                                                         a medias y no hay ninguna otra forma de
                                                         leerla: ni modal, ni detalle, ni scroll. El
                                                         SCSS daba este atributo por hecho y no
                                                         estaba en ninguna de las cuatro listas. */ ?>
                                                <span class="hoi-lista__n" title="<?= s($x['nombre']) ?>"><?= s($x['nombre']) ?></span>
                                                <?php if (!empty($x['alias'])): ?>
                                                <?php /* `Prim 1°A` reconocido como `1A Primaria`:
                                                         enseñarlo es lo que deja comprobar que no se
                                                         está fundiendo lo que no debe. */ ?>
                                                <span class="hoi-alias"><i class="fa-solid fa-arrow-left-long"></i>
                                                    <?= s(implode(' · ', (array)$x['alias'])) ?></span>
                                                <?php endif; ?>
                                                <?php if ($c['nivel'] && !empty($x['nivel'])): ?>
                                                <span class="hoi-lista__meta"><?= s($x['nivel']) ?></span>
                                                <?php endif; ?>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </details>
                                    <?php endif; ?>

                                    <?php if ($lA): ?>
                                    <h4 class="hoi-lista__t hoi-lista__t--mas">
                                        <i class="fa-solid fa-plus"></i> Se crean <span><?= count($lA) ?></span>
                                    </h4>
                                    <ul class="hoi-lista">
                                        <?php foreach ($lA as $x): ?>
                                        <li>
                                            <span class="hoi-lista__n" title="<?= s($x['nombre']) ?>"><?= s($x['nombre']) ?></span>
                                            <?php if ($c['nivel']): ?><span class="hoi-lista__meta"><?= s($x['nivel'] ?? '') ?></span><?php endif; ?>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php endif; ?>

                                    <?php if ($lP): ?>
                                    <h4 class="hoi-lista__t hoi-lista__t--off">
                                        <i class="fa-solid fa-power-off"></i> Se inhabilitan <span><?= count($lP) ?></span>
                                    </h4>
                                    <?php /* ⚠️ Sin tachado. Un `line-through` distingue cuando lo
                                             tachado convive con lo que no; aquí la lista entera se
                                             apaga, así que no añade información y estorba justo en
                                             la tarea de esta pantalla, que es LEER los nombres uno a
                                             uno para decidir si alguno no debería estar ahí. */ ?>
                                    <ul class="hoi-lista hoi-lista--off">
                                        <?php foreach ($lP as $x): ?>
                                        <?php $esFuera = $c['nivel'] && in_array((string)($x['nivel'] ?? ''), $nivelesFuera, true); ?>
                                        <li>
                                            <span class="hoi-lista__n" title="<?= s($x['nombre']) ?>"><?= s($x['nombre']) ?></span>
                                            <?php if ($c['nivel'] && !empty($x['nivel'])): ?>
                                            <?php /* El nivel que desaparece entero, marcado en CADA fila:
                                                     con 34 materias seguidas, el rótulo repetido es lo que
                                                     hace ver «esto es Maternal completo» sin contar. */ ?>
                                            <span class="hoi-lista__meta<?= $esFuera ? ' hoi-lista__meta--fuera' : '' ?>"><?= s($x['nivel']) ?></span>
                                            <?php endif; ?>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php endif; ?>

                                    <?php if ($lE): ?>
                                    <h4 class="hoi-lista__t hoi-lista__t--reac">
                                        <i class="fa-solid fa-rotate-left"></i> Se reactivan <span><?= count($lE) ?></span>
                                    </h4>
                                    <ul class="hoi-lista hoi-lista--reac">
                                        <?php foreach ($lE as $x): ?>
                                        <li>
                                            <span class="hoi-lista__n" title="<?= s($x['nombre']) ?>"><?= s($x['nombre']) ?></span>
                                            <?php if ($c['nivel'] && !empty($x['nivel'])): ?><span class="hoi-lista__meta"><?= s($x['nivel']) ?></span><?php endif; ?>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php endif; ?>
                                </section>
                            <?php endforeach; ?>

                            <?php if (!$algo): ?>
                            <p class="hoi-vacio"><i class="fa-solid fa-circle-check"></i>
                                El archivo usa exactamente los grupos, aulas y materias que ya existen.</p>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if (isset($iPaso['personal'])): ?>
                    <!-- ══ Paso · Personal ══ -->
                    <section class="hoi-panel" data-hoi-panel tabindex="-1">
                        <div class="hoi-panel__head">
                            <h2 class="hoi-panel__title"><?= s($PASOS[$iPaso['personal']]['titulo']) ?></h2>
                            <p class="hoi-panel__sub"><?= s($PASOS[$iPaso['personal']]['sub']) ?></p>
                        </div>
                        <div class="hoi-panel__body">

                            <?php if ($dudosos): ?>
                            <?php /* ⚠️ Lo PRIMERO del paso, por delante incluso de los parecidos.
                                     El diccionario casa por correo antes que por nombre —es el
                                     identificador fuerte—, pero un identificador fuerte con un
                                     dato malo es peor que uno débil: si el correo está mal
                                     escrito, el horario entero de esta persona se escribe en la
                                     cuenta de otra y la suya se inhabilita por no aparecer. Sin
                                     este aviso no hay absolutamente nada que lo delate. */ ?>
                            <div class="hoi-dup hoi-dup--grave">
                                <span class="hoi-dup__ico"><i class="fa-solid fa-user-xmark"></i></span>
                                <div class="hoi-dup__body">
                                    <p class="hoi-dup__t">
                                        <strong><?= count($dudosos) ?></strong>
                                        nombre<?= count($dudosos) === 1 ? '' : 's' ?> del archivo
                                        apunta<?= count($dudosos) === 1 ? '' : 'n' ?> a una cuenta
                                        que se llama de otra forma
                                    </p>
                                    <p class="hoi-dup__d">
                                        El correo que el diccionario les asigna pertenece a esa cuenta, así
                                        que <strong>su horario se escribiría ahí</strong>. Si no son la misma
                                        persona, corrige el correo en el diccionario
                                        <em>antes</em> de confirmar.
                                    </p>
                                    <ul class="hoi-dup__list">
                                        <?php foreach ($dudosos as $d): ?>
                                        <li>
                                            <strong><?= s($d['archivo'] !== '' ? $d['archivo'] : $d['cuenta']) ?></strong>
                                            <span class="hoi-dup__eq">→</span>
                                            <?= s($d['cuenta']) ?>
                                            <code><?= s($d['correo']) ?></code>
                                            <span class="hoi-dup__meta"><?= (int)$d['clases'] ?> clases</span>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($sinDic && !empty($dic['total'])): ?>
                            <?php /* Lo PRIMERO cuando hay diccionario: un nombre que él no
                                     conoce es el único camino que queda hacia una cuenta
                                     duplicada, y se arregla fuera de aquí (añadiéndolo al
                                     Excel) — o sea, antes de confirmar, no después. */ ?>
                            <div class="hoi-dup hoi-dup--dicc">
                                <span class="hoi-dup__ico"><i class="fa-solid fa-address-book"></i></span>
                                <div class="hoi-dup__body">
                                    <p class="hoi-dup__t">
                                        <strong><?= count($sinDic) ?></strong>
                                        nombre<?= count($sinDic) === 1 ? '' : 's' ?> del archivo no
                                        está<?= count($sinDic) === 1 ? '' : 'n' ?> en el diccionario
                                    </p>
                                    <p class="hoi-dup__d">
                                        Al resto el diccionario ya le encontró su cuenta. Estos no, así que
                                        se darán de alta como personas nuevas: <strong>si alguno ya tiene
                                        cuenta, quedará duplicado</strong>. Añádelos a
                                        <code><?= s((string)($dic['archivo'] ?? 'diccionario/')) ?></code>
                                        y vuelve a subir el archivo.
                                    </p>
                                    <ul class="hoi-dup__list">
                                        <?php foreach ($sinDic as $n): ?>
                                        <li><strong><?= s($n) ?></strong></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($parecidos): ?>
                            <?php /* Lo primero del paso, porque es lo único que hay que arreglar
                                     ANTES de importar. No se fusionan solos: «Fernanda» podría ser
                                     tanto «Fernanda Covarrubias» como «María Fernanda Uribe», y
                                     elegir mal le da a alguien el horario de otra persona. Pero
                                     callarlo dejaría dos cuentas para la misma persona. */ ?>
                            <div class="hoi-dup">
                                <span class="hoi-dup__ico"><i class="fa-solid fa-user-large"></i></span>
                                <div class="hoi-dup__body">
                                    <p class="hoi-dup__t">
                                        <strong><?= count($parecidos) ?></strong>
                                        nombre<?= count($parecidos) === 1 ? '' : 's' ?> del archivo se
                                        parece<?= count($parecidos) === 1 ? '' : 'n' ?> a alguien que ya existe
                                    </p>
                                    <p class="hoi-dup__d">
                                        Se darán de alta igual, como cuentas nuevas. Si es la misma persona,
                                        renómbrala en Usuarios para que coincida con el archivo
                                        <em>antes</em> de importar.
                                    </p>
                                    <ul class="hoi-dup__list">
                                        <?php /* Puede haber más de un candidato —«Fernanda» encaja con
                                                 dos personas distintas— y enseñarlos todos es el punto:
                                                 quedarse con uno decidiría por quien mira. */ ?>
                                        <?php foreach ($parecidos as $p): ?>
                                        <li>
                                            <strong><?= s($p['nombre']) ?></strong>
                                            <span class="hoi-dup__eq">≈</span>
                                            <?= s(implode(' · ', (array)$p['parecido'])) ?>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($matchProf): ?>
                            <?php /* Los RECONOCIDOS, que es la lista que esta pantalla no tenía.
                                     Va primera de las tres —reconoce · crea · inhabilita— porque
                                     es la que responde «¿este archivo habla de mi claustro?», y
                                     las otras dos solo se interpretan después de esa. */ ?>
                            <?php /* ⚠️ Aquí el bloque ES el verbo, así que su icono lo lleva
                                     pintado (`--ok` · `--mas` · `--off` · `--reac`). En el paso
                                     «Catálogo» NO se hace: allí el título es el sujeto —Grupos,
                                     Aulas, Materias— y los verbos cuelgan de él en sus propios
                                     encabezados. Sin esto, «Personal» era el único sitio donde
                                     los tres verbos salían todos en gris, y el brief pide que se
                                     lean con el mismo peso relativo en todas las superficies. */ ?>
                            <section class="hoi-bloc">
                                <h3 class="hoi-bloc__t hoi-bloc__t--ok">
                                    <i class="fa-solid fa-user-check"></i> Ya tienen cuenta
                                    <span class="hoi-bloc__meta">
                                        <?= count($matchProf) ?> personas<?php
                                        if ($traducidos) echo ' · ' . $traducidos . ' por el diccionario'; ?>
                                    </span>
                                </h3>
                                <p class="hoi-help">
                                    No se crea ni se toca ninguna: conservan su cuenta, su histórico y sus
                                    permisos. Solo se les reescribe el horario.
                                    <?php if ($traducidos): ?>
                                    Las marcadas con <span class="hoi-mini hoi-mini--dicc">diccionario</span>
                                    vienen en el archivo con <strong>otro nombre</strong>, y es la
                                    equivalencia lo que hay que revisar aquí.
                                    <?php endif; ?>
                                </p>
                                <ul class="hoi-lista hoi-lista--prof">
                                    <?php foreach ($matchProf as $p): ?>
                                    <li>
                                        <span class="hoi-lista__n"><?= s($p['nombre']) ?></span>
                                        <?php if (!empty($p['alias'])): ?>
                                        <span class="hoi-alias"><i class="fa-solid fa-arrow-left-long"></i>
                                            <?= s(implode(' · ', (array)$p['alias'])) ?></span>
                                        <?php endif; ?>
                                        <?php if ($p['via'] === 'diccionario'): ?>
                                        <?php /* Por qué camino casó: el correo es el identificador
                                                 fuerte, el nombre es la otra grafía del diccionario. */ ?>
                                        <span class="hoi-mini hoi-mini--dicc">
                                            diccionario<?= $p['por'] === 'correo' ? ' · correo' : '' ?>
                                        </span>
                                        <?php endif; ?>
                                        <span class="hoi-lista__meta"><?= (int)$p['clases'] ?> clases</span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </section>
                            <?php endif; ?>

                            <?php if ($altaProf): ?>
                            <section class="hoi-bloc">
                                <h3 class="hoi-bloc__t hoi-bloc__t--mas">
                                    <i class="fa-solid fa-user-plus"></i> Cuentas nuevas
                                    <span class="hoi-bloc__meta"><?= count($altaProf) ?> personas</span>
                                </h3>
                                <ul class="hoi-lista hoi-lista--prof">
                                    <?php foreach ($altaProf as $p): ?>
                                    <li>
                                        <span class="hoi-lista__n"><?= s($p['nombre']) ?></span>
                                        <code><?= s($p['email']) ?></code>
                                        <?php if (!empty($p['dic'])): ?>
                                        <?php /* Con entrada en el diccionario: el colegio la conoce y
                                                 solo le falta la cuenta, así que el nombre y el correo
                                                 son los suyos de verdad. Sin ella, el correo es
                                                 inventado a partir del nombre del archivo. */ ?>
                                        <span class="hoi-mini hoi-mini--dicc">diccionario</span>
                                        <?php else: ?>
                                        <?php /* ⚠️ Chip de AUSENCIA (contorno discontinuo), no ámbar.
                                                 Llevaba `--mini--off`, que dos bloques más abajo
                                                 significa «se inhabilita»: una fila de «Cuentas nuevas»
                                                 con chip ámbar se leía como que a esa persona se le
                                                 está dando de baja, justo lo contrario. Aquí el chip
                                                 no es un verbo sino calidad del dato —el correo es
                                                 inventado—, así que es el negativo del turquesa de al
                                                 lado y no gasta ningún color del sistema. */ ?>
                                        <span class="hoi-mini hoi-mini--sindic">sin diccionario</span>
                                        <?php endif; ?>
                                        <span class="hoi-lista__meta">
                                            <?= $p['niveles'] ? s(implode(' · ', $p['niveles'])) : '—' ?> ·
                                            <?= (int)$p['clases'] ?> clases
                                        </span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <p class="hoi-nota hoi-nota--warn">
                                    <i class="fa-solid fa-key"></i>
                                    <span>
                                        Nacen con la contraseña <code><?= s($resumen['password'] ?? 'password123') ?></code>
                                        y los módulos de un profesor (Suplencias, Horarios, Intercambios).
                                        <strong>Es una contraseña inicial: hay que rotarla.</strong>
                                    </span>
                                </p>
                            </section>
                            <?php endif; ?>

                            <?php if ($fuera): ?>
                            <section class="hoi-bloc">
                                <h3 class="hoi-bloc__t hoi-bloc__t--off">
                                    <i class="fa-solid fa-user-slash"></i> No vienen en el archivo
                                    <span class="hoi-bloc__meta"><?= count($fuera) ?> personas</span>
                                </h3>
                                <?php /* Son DOS consecuencias distintas y no coinciden: un prefecto
                                         con clases pierde la rejilla y conserva el acceso; un profesor
                                         sin horario cargado se inhabilita sin perder rejilla. Cada
                                         quien lleva la suya, que es justo para lo que el controlador
                                         las separó — dos listas obligaban a cotejar nombres a mano. */ ?>
                                <p class="hoi-help">
                                    <?php if ($pierdeHorario): ?>
                                    <strong><?= $pierdeHorario ?></strong> pierde<?= $pierdeHorario === 1 ? '' : 'n' ?>
                                    su horario<?php endif; ?><?php if ($pierdeHorario && $seInhabilitan): ?> ·
                                    <?php endif; ?><?php if ($seInhabilitan): ?>
                                    <strong><?= $seInhabilitan ?></strong> se inhabilita<?= $seInhabilitan === 1 ? '' : 'n' ?>
                                    <?php endif; ?>.
                                    Ninguna cuenta se borra: conservan su histórico, sus suplencias y sus
                                    intercambios. Si alguno debería seguir dando clase,
                                    <strong>corrige el archivo antes de confirmar</strong>.
                                </p>
                                <ul class="hoi-lista hoi-lista--fuera">
                                    <?php foreach ($fuera as $p): ?>
                                    <li>
                                        <span class="hoi-lista__n"><?= s($p['nombre']) ?></span>
                                        <span class="hoi-lista__chips">
                                            <?php if (!empty($p['horario'])): ?>
                                            <span class="hoi-mini hoi-mini--hor">pierde horario</span>
                                            <?php endif; ?>
                                            <?php if (!empty($p['baja'])): ?>
                                            <span class="hoi-mini hoi-mini--off">se inhabilita</span>
                                            <?php endif; ?>
                                        </span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </section>
                            <?php endif; ?>

                            <?php if ($profEncender): ?>
                            <section class="hoi-bloc">
                                <h3 class="hoi-bloc__t hoi-bloc__t--reac">
                                    <i class="fa-solid fa-rotate-left"></i> Vuelven al claustro
                                    <span class="hoi-bloc__meta"><?= count($profEncender) ?> personas</span>
                                </h3>
                                <p class="hoi-help">
                                    Estaban dadas de baja y el archivo vuelve a nombrarlas, así que se
                                    reactivan con su cuenta de siempre.
                                </p>
                                <ul class="hoi-lista hoi-lista--reac">
                                    <?php foreach ($profEncender as $p): ?>
                                    <li><span class="hoi-lista__n"><?= s($p['nombre']) ?></span></li>
                                    <?php endforeach; ?>
                                </ul>
                            </section>
                            <?php endif; ?>

                            <?php if ($correos): ?>
                            <?php /* ⚠️ El correo NO es un dato de contacto: es el usuario con
                                     el que se entra al panel. Se dice con todas las letras
                                     porque el fallo es diferido —la sesión abierta sigue
                                     funcionando— y quien se quede fuera verá «No encontramos
                                     ninguna cuenta con ese correo», que se lee como una falta
                                     de ortografía propia y no como un cambio del sistema. */ ?>
                            <section class="hoi-bloc">
                                <h3 class="hoi-bloc__t hoi-bloc__t--ok">
                                    <i class="fa-solid fa-at"></i> Correos que se corrigen
                                    <span class="hoi-bloc__meta"><?= count($correos) ?> personas</span>
                                </h3>
                                <p class="hoi-help">
                                    En su ficha hay un correo y en el diccionario otro; manda el del
                                    diccionario. <strong>Es el usuario con el que entran al panel</strong>,
                                    así que a partir de la importación tendrán que usar el nuevo — la
                                    contraseña no cambia. Si alguno no debería cambiar,
                                    corrige el diccionario antes de confirmar.
                                </p>
                                <ul class="hoi-lista hoi-lista--correo">
                                    <?php foreach ($correos as $c): ?>
                                    <li>
                                        <?php /* `--correo` recorta el nombre a UNA línea para dejar
                                                 sitio a la pareja de correos: el `title` es la única
                                                 forma de leer entero un «María Fernanda Uribe
                                                 Castellanos». */ ?>
                                        <span class="hoi-lista__n" title="<?= s($c['nombre']) ?>"><?= s($c['nombre']) ?></span>
                                        <span class="hoi-correo">
                                            <code class="hoi-correo__antes"><?= s($c['antes']) ?></code>
                                            <i class="fa-solid fa-arrow-right-long"></i>
                                            <code class="hoi-correo__despues"><?= s($c['despues']) ?></code>
                                        </span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </section>
                            <?php endif; ?>

                            <?php if ($corConflicto): ?>
                            <?php /* El diccionario tiene un correo que ya es de otra persona.
                                     No se toca nada —`usuarios.email` es UNIQUE y además
                                     significa que el archivo está mal—, pero callarlo dejaría
                                     una corrección que no ocurre sin ninguna explicación. */ ?>
                            <section class="hoi-bloc">
                                <h3 class="hoi-bloc__t hoi-bloc__t--off">
                                    <i class="fa-solid fa-envelope-circle-check"></i> Correos que no se pueden corregir
                                    <span class="hoi-bloc__meta"><?= count($corConflicto) ?></span>
                                </h3>
                                <p class="hoi-help">
                                    El diccionario les asigna un correo que <strong>ya es de otra
                                    cuenta</strong>, o que no es un correo válido. Esas fichas se quedan
                                    como están; lo que hay que arreglar es el diccionario.
                                </p>
                                <ul class="hoi-lista hoi-lista--correo">
                                    <?php foreach ($corConflicto as $c): ?>
                                    <li>
                                        <span class="hoi-lista__n"><?= s($c['nombre']) ?></span>
                                        <span class="hoi-correo">
                                            <code class="hoi-correo__choque"><?= s($c['correo']) ?></code>
                                            <span class="hoi-correo__de"><?= $c['de'] !== ''
                                                ? 'ya es de ' . s($c['de'])
                                                : 'no es un correo válido' ?></span>
                                        </span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </section>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- ══ Paso · Las filas ══ -->
                    <section class="hoi-panel" data-hoi-panel tabindex="-1">
                        <div class="hoi-panel__head">
                            <h2 class="hoi-panel__title"><?= s($PASOS[$iPaso['filas']]['titulo']) ?></h2>
                            <p class="hoi-panel__sub"><?= s($PASOS[$iPaso['filas']]['sub']) ?></p>
                        </div>
                        <div class="hoi-panel__body hoi-panel__body--tabla">

                            <?php
                            /* Cuántas filas toca cada pill, para que el chip diga su cifra
                               antes de pulsarlo. Se cuenta aquí y no en el JS porque el JS
                               puede no cargar y el número sigue siendo información. */
                            $nNuevas = 0;
                            foreach ($filas as $f) if (($f['via_profesor'] ?? '') === 'nuevo') $nNuevas++;
                            $PILLS = [];
                            if ($coteaching > 0) $PILLS[] = ['clave' => 'acomp', 'tag' => 'acompaña',
                                'n' => $coteaching, 'que' => 'clases con más de un docente'];
                            if ($divididas > 0)  $PILLS[] = ['clave' => 'div', 'tag' => 'opción 2',
                                'n' => $divididas, 'que' => 'casillas con la materia dividida'];
                            if ($nNuevas > 0)    $PILLS[] = ['clave' => 'new', 'tag' => 'nueva',
                                'n' => $nNuevas, 'que' => 'filas de alguien sin cuenta todavía'];
                            ?>
                            <div class="hoi-barra">
                                <?php /* Buscador sobre las filas ya cargadas, con el contrato del
                                         resto del panel: el heno lo compone PHP en `data-buscar` y
                                         el JS solo quita acentos. Es lo que convierte 878 filas en
                                         algo que se puede comprobar: «¿salen todas las de Gaby?»,
                                         «¿qué pasa con Biblioteca?». */ ?>
                                <div class="hoi-buscar">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="search" class="hoi-buscar__input" data-hoi-buscar
                                           placeholder="Busca profesor, materia, grupo, aula, día…"
                                           aria-label="Buscar en las filas del archivo">
                                    <button type="button" class="hoi-buscar__x" data-hoi-limpiar hidden
                                            aria-label="Limpiar la búsqueda">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>

                                <?php /* Cuántas quedan a la vista. Sin esto, filtrar 878 filas a 12
                                         deja un paginador que dice «1 / 1» y ninguna pista de que
                                         la cifra grande de arriba ya no describe lo que se ve.
                                         ⚠️ Va AQUÍ, entre el buscador y las pills, y no al final:
                                         la barra reparte en dos filas fijas (ver `.hoi-barra`) y
                                         cierra la del buscador, que es de donde cuelga. Al final
                                         del DOM se caía a una tercera fila ella sola en cuanto las
                                         pills llenaban la suya — a 1024px con el sidebar abierto,
                                         siempre.
                                         `role="status"` porque el texto cambia al teclear: quien no
                                         ve la tabla necesita enterarse de que la búsqueda dejó 12
                                         filas de 878. */ ?>
                                <p class="hoi-barra__cuenta" data-hoi-cuenta role="status" hidden></p>

                                <div class="hoi-pills">
                                    <?php /* El filtro solo existe si hay algo que filtrar: con cero
                                             problemas, marcarlo dejaba la tabla en blanco sin decir
                                             por qué. Lleva el número, que es la única cifra que dice
                                             cuánto queda por revisar. */ ?>
                                    <?php if ($conProblema > 0): ?>
                                    <button type="button" class="hoi-pill hoi-pill--bad"
                                            data-hoi-pill="problemas" aria-pressed="false">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        Con problemas <span class="hoi-pill__n"><?= $conProblema ?></span>
                                    </button>
                                    <?php endif; ?>

                                    <?php /* Las mismas etiquetas que llevan las celdas, aquí arriba y
                                             pulsables: la leyenda era un texto que explicaba unos
                                             chips que no hacían nada. Ahora explica Y filtra, que es
                                             lo que uno intenta hacer al verlos. */ ?>
                                    <?php /* ⚠️ Sin modificador por clave: el color lo pone el
                                             `.hoi-tag` de dentro, que es el mismo chip que llevan
                                             las celdas, y el estado activo es azul de cromo para
                                             las tres. Un `hoi-pill--acomp` que ningún SCSS define
                                             es una clase que la próxima persona intentará usar. */ ?>
                                    <?php foreach ($PILLS as $p): ?>
                                    <button type="button" class="hoi-pill"
                                            data-hoi-pill="<?= s($p['clave']) ?>" aria-pressed="false"
                                            title="<?= (int)$p['n'] ?> <?= s($p['que']) ?>">
                                        <span class="hoi-tag hoi-tag--<?= s($p['clave']) ?>"><?= s($p['tag']) ?></span>
                                        <span class="hoi-pill__n"><?= (int)$p['n'] ?></span>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <?php if ((int)$resumen['errores'] > 0): ?>
                            <?php /* Va ANTES de la tabla: es la razón por la que la cifra del botón
                                     no coincide con la del archivo, y detrás nadie la lee. */ ?>
                            <p class="hoi-nota hoi-nota--bad">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <span>Las <strong><?= (int)$resumen['errores'] ?></strong> filas con error se
                                omiten. Corrige el archivo y vuelve a subirlo si quieres importarlas también.</span>
                            </p>
                            <?php endif; ?>

                            <?php /* Paginada con el contrato de siempre (`admin-table.js`): el
                                     servidor preoculta con `is-hidden` a partir de la fila 25 y
                                     marca cada `<tr data-pager-item>`. Antes se volcaban las 878
                                     de golpe dentro de un contenedor con scroll propio, que además
                                     era una trampa de rueda dentro de una página con scroll. */ ?>
                            <?php
                            $orden   = ['lunes' => 1, 'martes' => 2, 'miercoles' => 3, 'jueves' => 4, 'viernes' => 5];
                            $diaCorto = ['lunes' => 'Lun', 'martes' => 'Mar', 'miercoles' => 'Mié',
                                         'jueves' => 'Jue', 'viernes' => 'Vie'];
                            ?>
                            <div class="admin-table-scroll">
                                <?php /* ⚠️ SIETE columnas, no nueve. Día y Hora eran la misma
                                         pregunta partida en dos, y Nivel es un atributo de la
                                         materia (sale de su prefijo), no una dimensión aparte:
                                         separados obligaban a leer tres celdas para situar una
                                         clase, y a 878 filas eso es lo que saturaba. */ ?>
                                <table class="admin-table hoi-table" data-table data-table-per="25" data-table-noun="filas">
                                    <thead>
                                        <tr>
                                            <th data-sort="num">#</th>
                                            <th data-sort="text">Estado</th>
                                            <th data-sort="text">Profesor</th>
                                            <th data-sort="num">Cuándo</th>
                                            <th data-sort="text">Materia</th>
                                            <th data-sort="text">Grupo</th>
                                            <th data-sort="text">Aula</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($filas as $i => $f): ?>
                                        <?php
                                        /* El heno de la búsqueda lo compone PHP, como en el resto
                                           del panel: el JS solo quita acentos. Va lo que alguien
                                           escribiría para encontrar una fila — incluidas LAS DOS
                                           grafías del profesor, que es justo el caso que el
                                           diccionario introduce. */
                                        $buscable = mb_strtolower(implode(' ', array_filter([
                                            $f['profesor'], $f['profesor_final'] ?? '',
                                            $f['materia'], $f['grupo'], $f['aula'], $f['nivel'],
                                            \Model\Horario::DIAS_LABEL[$f['dia']] ?? $f['dia_csv'],
                                            $f['hora'], $f['motivo'],
                                        ])), 'UTF-8');
                                        // Día y hora en una sola cifra: el orden real de la semana.
                                        $cuando = ($orden[$f['dia']] ?? 9) * 10000
                                                + (int)substr($f['hora'], 0, 2) * 60 + (int)substr($f['hora'], 3, 2);
                                        ?>
                                        <tr class="hoi-row hoi-row--<?= s($f['estado']) ?><?= $i >= 25 ? ' is-hidden' : '' ?>"
                                            data-estado="<?= s($f['estado']) ?>" data-pager-item
                                            data-buscar="<?= s($buscable) ?>"
                                            <?php /* Las marcas que las pills filtran. Se emiten como
                                                     atributos y no se deducen del markup: el JS
                                                     pregunta por un dato, no por una etiqueta. */ ?>
                                            <?= ($f['via_profesor'] ?? '') === 'nuevo' ? ' data-new' : '' ?>
                                            <?= $f['rol_docente'] === 'acompanante' ? ' data-acomp' : '' ?>
                                            <?= (int)$f['division'] > 0 ? ' data-div' : '' ?>>
                                            <td class="hoi-row__n" data-val="<?= (int)$f['linea'] ?>"><?= (int)$f['linea'] ?></td>
                                            <td data-val="<?= s($f['estado']) ?>">
                                                <span class="hoi-chip hoi-chip--<?= s($f['estado']) ?>">
                                                    <i class="fa-solid <?= $iconoFila[$f['estado']] ?>"></i>
                                                    <?= $f['estado'] === 'ok' ? 'Correcta' : ($f['estado'] === 'aviso' ? 'Aviso' : 'Error') ?>
                                                </span>
                                                <?php if ($f['motivo']): ?>
                                                <span class="hoi-motivo"><?= s($f['motivo']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <?php /* Se ordena por la persona de DESTINO, no por lo
                                                     que escribe el archivo: agrupar las filas de
                                                     alguien es lo que se busca al ordenar aquí, y
                                                     «Gaby» y «Gabriela Sánchez» son la misma. */ ?>
                                            <td data-val="<?= s($f['profesor_final'] ?: $f['profesor']) ?>">
                                                <span class="hoi-row__prof"><?= s($f['profesor']) ?></span>
                                                <?php if (($f['profesor_final'] ?? '') !== '' && $f['profesor_final'] !== $f['profesor']): ?>
                                                <?php /* A quién resolvió. Sin esto, una fila que dice
                                                         «Gaby» no deja saber si acabó en la cuenta de
                                                         Gabriela o abrió una segunda. */ ?>
                                                <span class="hoi-alias"><i class="fa-solid fa-arrow-right-long"></i>
                                                    <?= s($f['profesor_final']) ?></span>
                                                <?php endif; ?>
                                                <?php if (($f['via_profesor'] ?? '') === 'nuevo'): ?>
                                                <span class="hoi-tag hoi-tag--new">nueva</span>
                                                <?php endif; ?>
                                                <?php if ($f['rol_docente'] === 'acompanante'): ?>
                                                <span class="hoi-tag hoi-tag--acomp">acompaña</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php /* `data-val` numérico: ordenar por texto pondría el
                                                     jueves antes que el lunes, y las 08:00 de los
                                                     cinco días seguidas antes que las 09:00. */ ?>
                                            <td class="hoi-row__cuando" data-val="<?= $cuando ?>">
                                                <span class="hoi-row__dia"><?= s($diaCorto[$f['dia']] ?? $f['dia_csv']) ?></span>
                                                <?= $f['hora'] !== '' ? s($f['hora']) : '<span class="hoi-nil">' . s($f['periodo']) . '</span>' ?>
                                            </td>
                                            <td data-val="<?= s($f['materia']) ?>">
                                                <span class="hoi-row__mat"><?= s($f['materia']) ?></span>
                                                <?php /* El nivel sale del prefijo de la materia, así que
                                                         es suyo: es lo que decide en qué jornada cae
                                                         «la 3ª hora». Va como etiqueta y no como
                                                         columna porque sin la materia no significa nada. */ ?>
                                                <?php if ($f['nivel'] !== ''): ?>
                                                <span class="hoi-nivel"><?= s($f['nivel']) ?></span>
                                                <?php endif; ?>
                                                <?php if ((int)$f['division'] > 0): ?>
                                                <span class="hoi-tag hoi-tag--div">opción <?= (int)$f['division'] ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-val="<?= s($f['grupo']) ?>"><?= $f['grupo'] !== '' ? s($f['grupo']) : '<span class="hoi-nil">—</span>' ?></td>
                                            <td data-val="<?= s($f['aula']) ?>"><?= $f['aula'] !== '' ? s($f['aula']) : '<span class="hoi-nil">—</span>' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php /* Filtrar hasta cero es un resultado legítimo, pero una tabla
                                     vacía sin explicación se lee como que algo se rompió. */ ?>
                            <p class="hoi-vacio hoi-vacio--nada" data-hoi-empty hidden>
                                <i class="fa-solid fa-magnifying-glass"></i>
                                Ninguna fila coincide con lo que has buscado.
                            </p>
                        </div>
                    </section>

                    <!-- ══ Paso · Confirmar ══ -->
                    <?php /* ⚠️ AUTOSUFICIENTE. El índice deja saltar aquí directamente, así que
                             este paso repite en una línea cada cifra de los anteriores: nadie
                             puede confirmar sin haber visto lo que va a pasar, haya recorrido el
                             asistente o no. */ ?>
                    <section class="hoi-panel" data-hoi-panel tabindex="-1">
                        <div class="hoi-panel__head">
                            <h2 class="hoi-panel__title"><?= s($PASOS[$iPaso['confirmar']]['titulo']) ?></h2>
                            <p class="hoi-panel__sub"><?= s($PASOS[$iPaso['confirmar']]['sub']) ?></p>
                        </div>
                        <div class="hoi-panel__body hoi-panel__body--cierre">

                            <?php /* ── TRES bloques con título, no seis tarjetas en fila ──
                                     Eran todas iguales —clases escritas, catálogos y personas
                                     al mismo peso—, así que no se distinguía lo que es la
                                     acción de lo que es su efecto colateral, ni mucho menos
                                     qué pedía una decisión. Ahora cada bloque responde una
                                     pregunta distinta y en este orden: qué hago, qué le pasa
                                     al catálogo, y qué debería mirar antes. */ ?>
                            <section class="hoi-cierre">
                                <h3 class="hoi-cierre__t">
                                    <span class="hoi-cierre__n">1</span> Lo que se va a escribir
                                </h3>
                                <div class="hoi-cierre__body">
                                    <p class="hoi-cierre__grande">
                                        <strong><?= $importable ?></strong>
                                        clase<?= $importable === 1 ? '' : 's' ?> de este archivo
                                    </p>
                                    <p class="hoi-cierre__d">
                                        <strong>La rejilla actual se vacía entera antes de escribirlas</strong>,
                                        también la de quien no venga en el archivo. Es lo único de toda
                                        esta pantalla que no se deshace volviendo a importar: hace falta
                                        el archivo bueno.
                                        <?php if ((int)$resumen['errores']): ?>
                                        Las <strong><?= (int)$resumen['errores'] ?></strong> filas con
                                        error se omiten.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </section>

                            <section class="hoi-cierre">
                                <h3 class="hoi-cierre__t">
                                    <span class="hoi-cierre__n">2</span> Cómo quedan los catálogos
                                    <span class="hoi-cierre__meta">profesores, grupos, aulas y materias</span>
                                </h3>
                                <div class="hoi-cierre__body">
                                    <ul class="hoi-recap">
                                        <li class="hoi-recap__i hoi-recap__i--ok">
                                            <span class="hoi-recap__n"><?= $totalMatch ?></span>
                                            <span class="hoi-recap__l">ya existen<br><em><?php
                                                echo $traducidos ? $traducidos . ' los casó el diccionario' : 'se reconocen, no se tocan'; ?></em></span>
                                        </li>
                                        <li class="hoi-recap__i hoi-recap__i--new">
                                            <span class="hoi-recap__n"><?= $totalAltas ?></span>
                                            <span class="hoi-recap__l">se crean<br><em>no estaban en ningún catálogo</em></span>
                                        </li>
                                        <li class="hoi-recap__i hoi-recap__i--off">
                                            <span class="hoi-recap__n"><?= $totalApagar ?></span>
                                            <span class="hoi-recap__l">se inhabilitan<br><em>no se borran, se reactivan solos</em></span>
                                        </li>
                                        <?php if ($totalEncender): ?>
                                        <li class="hoi-recap__i hoi-recap__i--reac">
                                            <span class="hoi-recap__n"><?= $totalEncender ?></span>
                                            <span class="hoi-recap__l">se reactivan<br><em>vuelven a estar disponibles</em></span>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </section>

                            <?php /* ── Los HALLAZGOS ──────────────────────────────────────
                                     Estaban repartidos por cuatro pasos y aquí solo reaparecía
                                     uno, suelto y sin decir qué hacer con él. Cada uno lleva su
                                     cifra, la consecuencia en una frase, y el botón que LLEVA
                                     al paso donde se ve: el índice del asistente deja saltar, así
                                     que el hallazgo puede acompañar en vez de describir un sitio. */ ?>
                            <?php /* ⚠️ `--accion`: de los tres bloques, este es el único que PIDE
                                     algo —los otros dos afirman— y con los tres números en el mismo
                                     gris el orden 1·2·3 se leía pero no cuál reclamaba una decisión.
                                     Se marca con el círculo azul del paso actual del asistente, que
                                     es cromo y no un color del eje. */ ?>
                            <section class="hoi-cierre hoi-cierre--accion">
                                <h3 class="hoi-cierre__t">
                                    <span class="hoi-cierre__n">3</span> Revisa esto antes de confirmar
                                    <?php if ($HALLAZGOS): ?>
                                    <span class="hoi-cierre__meta"><?= count($HALLAZGOS) ?>
                                        cosa<?= count($HALLAZGOS) === 1 ? '' : 's' ?> que mirar</span>
                                    <?php endif; ?>
                                </h3>
                                <div class="hoi-cierre__body">
                                    <?php if (!$HALLAZGOS): ?>
                                    <p class="hoi-vacio"><i class="fa-solid fa-circle-check"></i>
                                        Nada raro. El archivo encaja con el colegio: ni filas con error, ni
                                        niveles ausentes, ni nombres sin reconocer.</p>
                                    <?php else: ?>
                                    <ul class="hoi-hallazgos">
                                        <?php foreach ($HALLAZGOS as $h): ?>
                                        <li class="hoi-hallazgo<?= $h['grave'] ? ' hoi-hallazgo--grave' : '' ?>">
                                            <span class="hoi-hallazgo__ico"><i class="fa-solid <?= s($h['icono']) ?>"></i></span>
                                            <div class="hoi-hallazgo__body">
                                                <p class="hoi-hallazgo__t">
                                                    <?php if ($h['n'] !== null): ?><strong><?= (int)$h['n'] ?></strong> <?php endif; ?>
                                                    <?= s(is_array($h['que'])
                                                        ? $h['que'][(int)$h['n'] === 1 ? 0 : 1]
                                                        : $h['que']) ?>
                                                </p>
                                                <p class="hoi-hallazgo__d"><?= s($h['por']) ?></p>
                                            </div>
                                            <?php if (isset($h['paso'])): ?>
                                            <?php /* Sin JS no hay recorrido que saltar: los paneles se
                                                     pintan seguidos, así que el botón no tendría a
                                                     dónde llevar. Lo enseña el módulo al montar. */ ?>
                                            <button type="button" class="hoi-hallazgo__ir"
                                                    data-hoi-ir="<?= (int)$h['paso'] ?>" hidden>
                                                Ver <i class="fa-solid fa-arrow-right-long"></i>
                                            </button>
                                            <?php endif; ?>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php endif; ?>
                                </div>
                            </section>

                            <?php /* La casilla es el freno de mano de una acción que reescribe el
                                     horario del colegio entero, así que no puede ser el pie de foto
                                     del botón: ocupa su propia línea dentro de una caja con entidad.
                                     El `required` es la ayuda; el guard de verdad está en el
                                     servidor (`$_POST['confirmo']`).
                                     ⚠️ El texto separa lo irreversible de lo reversible: mezclarlo
                                     todo en una enumeración de cifras hacía que «se reescribe el
                                     horario» pesara lo mismo que «se crean 75 registros». */ ?>
                            <?php if ($importable): ?>
                            <label class="hoi-confirm">
                                <input type="checkbox" name="confirmo" value="1" required data-hoi-confirmo>
                                <span>
                                    Entiendo que esto <strong>reescribe el horario de todo el colegio</strong>
                                    y que el actual se pierde.
                                    <?php
                                    $rev = [];
                                    if ($totalAltas)  $rev[] = 'se crean ' . $totalAltas . ' registros de catálogo';
                                    if ($totalApagar) $rev[] = 'se inhabilitan ' . $totalApagar . ' que el archivo ya no menciona';
                                    if ($correos)     $rev[] = 'cambia el correo de acceso de ' . count($correos) . ' personas';
                                    if ($rev): ?>
                                    También sé que <?= implode(', que ', $rev) ?>.
                                    <?php endif; ?>
                                </span>
                            </label>
                            <?php else: ?>
                            <p class="hoi-nota hoi-nota--bad">
                                <i class="fa-solid fa-circle-xmark"></i>
                                <span>Ninguna fila del archivo es válida: no hay nada que importar.
                                Corrige el CSV y vuelve a subirlo.</span>
                            </p>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- ── Navegación ───────────────────────────────────────────── -->
                    <?php /* Lo que falta se dice ANTES de pulsar, no como castigo después, y
                             ningún botón sale deshabilitado: uno muerto sin explicación es
                             indistinguible de uno roto. */ ?>
                    <div class="hoi-wiz__nav">
                        <p class="hoi-wiz__hint" data-hoi-hint hidden></p>
                        <div class="hoi-wiz__nav-btns">
                            <button type="submit" name="_accion" value="cancelar" formnovalidate
                                    class="admin-btn admin-btn--ghost">
                                <i class="fa-solid fa-xmark"></i> Descartar archivo
                            </button>
                            <?php /* Sin `hidden` los tres, por lo mismo que los paneles: sin JS
                                     hace falta llegar a «Reemplazar». Quien los pliega antes del
                                     primer pintado es `html.js .hoi-wiz:not(.is-wiz)`, y a partir
                                     de que el bundle monta manda `hidden`. */ ?>
                            <button type="button" class="admin-btn admin-btn--ghost" data-hoi-atras>
                                <i class="fa-solid fa-arrow-left"></i> Atrás
                            </button>
                            <button type="button" class="admin-btn admin-btn--primary" data-hoi-siguiente>
                                Siguiente <i class="fa-solid fa-arrow-right"></i>
                            </button>
                            <button type="submit" name="_accion" value="confirmar"
                                    class="admin-btn admin-btn--danger" data-hoi-enviar
                                    <?= $importable ? '' : 'disabled' ?>>
                                <i class="fa-solid fa-file-import"></i>
                                Reemplazar con <?= $importable ?> clase<?= $importable === 1 ? '' : 's' ?>
                            </button>
                        </div>
                    </div>

                </div>
            </form>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php include __DIR__ . '/../_toast.php'; ?>
