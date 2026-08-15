<?php $paginaVista = 'blog-notificaciones-index'; ?>
<?php
$totalNoLeidas = count(array_filter($notifs, fn($n) => !(int)$n->leida));
$total         = count($notifs);
$porPagina     = 8;

/** Plural correcto: "notificación" pierde la tilde al pluralizar. */
function nt_plural(int $n): string {
    return $n === 1 ? 'notificación' : 'notificaciones';
}

// Icono por módulo emisor y color por nivel: así se distingue de un vistazo de
// dónde viene cada aviso sin tener que leerlo entero.
$NOTIF_MOD = [
    'redaccion'  => ['icon' => 'fa-pen-nib',      'label' => 'Redacción'],
    'suplencias' => ['icon' => 'fa-user-clock',   'label' => 'Suplencias'],
    'horarios'   => ['icon' => 'fa-table-cells',  'label' => 'Horarios'],
    'eventos'    => ['icon' => 'fa-calendar-day', 'label' => 'Eventos'],
    'usuarios'   => ['icon' => 'fa-users-gear',   'label' => 'Usuarios'],
    'general'    => ['icon' => 'fa-bell',         'label' => 'General'],
];

// Agrupación temporal: una lista plana de 20 avisos no dice cuál es reciente.
$hoy       = strtotime('today');
$semana    = strtotime('-7 days', $hoy);
$NT_TRAMOS = ['hoy' => 'Hoy', 'semana' => 'Esta semana', 'antes' => 'Anteriores'];
$tramoDe = function ($n) use ($hoy, $semana): string {
    $ts = $n->creado_en ? strtotime($n->creado_en) : 0;
    if ($ts >= $hoy)    return 'hoy';
    if ($ts >= $semana) return 'semana';
    return 'antes';
};
// El índice global manda para la paginación: es continuo entre tramos.
$idxGlobal = 0;

$DIAS_TIRA = ['L', 'M', 'X', 'J', 'V'];

// date('d M Y') devuelve el mes en inglés ("03 Aug 2026"): la fecha se compone a
// mano para que salga en español sin depender del locale del servidor.
$NT_MESES = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$NT_MESES_LG = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$NT_DIAS  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];

/**
 * Reparte el timestamp en tres piezas de distinto peso visual: un chip relativo
 * (Hoy / Ayer / día de la semana / fecha corta), la hora y la fecha larga para
 * el title. Antes todo iba aplanado en una sola línea gris.
 */
$fechaPartes = function ($creado) use ($hoy, $NT_MESES, $NT_MESES_LG, $NT_DIAS): array {
    if (!$creado) return ['chip' => '', 'hora' => '', 'largo' => ''];
    $ts  = strtotime($creado);
    $dia = strtotime('today', $ts);

    if      ($dia === $hoy)                          $chip = 'Hoy';
    elseif  ($dia === strtotime('-1 day', $hoy))     $chip = 'Ayer';
    elseif  ($dia >  strtotime('-7 days', $hoy))     $chip = $NT_DIAS[(int)date('w', $ts)];
    else                                             $chip = (int)date('j', $ts) . ' ' . $NT_MESES[(int)date('n', $ts) - 1];

    return [
        'chip'  => $chip,
        'hora'  => date('H:i', $ts),
        'largo' => $NT_DIAS[(int)date('w', $ts)] . ', ' . (int)date('j', $ts) . ' de '
                 . $NT_MESES_LG[(int)date('n', $ts) - 1] . ' de ' . date('Y', $ts)
                 . ' a las ' . date('H:i', $ts),
    ];
};
?>
<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">
                    <i class="fa-solid fa-bell"></i> Notificaciones
                </span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <div class="admin-content">
            <?php if (isset($_GET['limpiadas'])): ?>
            <div class="admin-alerta admin-alerta--success">
                <i class="fa-solid fa-circle-check"></i>
                Bandeja vaciada: se eliminaron <?= (int)$_GET['limpiadas'] ?> notificaciones.
            </div>
            <?php endif; ?>

            <div class="admin-panel nt-panel">
                <div class="nt-head">
                    <div class="nt-head__info">
                        <strong><?= $total ?> <?= nt_plural($total) ?></strong>
                        <?php if ($totalNoLeidas > 0): ?>
                        <span class="nt-head__pend"><?= $totalNoLeidas ?> sin leer</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($total > 0): ?>
                    <div class="nt-head__acts">
                        <?php if ($totalNoLeidas > 0): ?>
                        <form method="POST" action="/dashboard/notificaciones/leer-todas">
                            <button type="submit" class="admin-btn admin-btn--ghost admin-btn--sm">
                                <i class="fa-solid fa-check-double"></i> Marcar todas leídas
                            </button>
                        </form>
                        <?php endif; ?>
                        <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm nt-clear" data-nt-clear>
                            <i class="fa-regular fa-trash-can"></i> Vaciar bandeja
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!$notifs): ?>
                <div class="nt-empty">
                    <img src="/build/assets/img/alex/bby-alex-feliz.png" alt="">
                    <p><strong>Todo al día</strong></p>
                    <p>No tienes notificaciones pendientes.</p>
                </div>
                <?php else: ?>
                <ul class="nt-list" id="ntList">
                    <?php
                    $tramoPrevio = null;
                    foreach ($notifs as $notif):
                        $esLeida = (int)$notif->leida;
                        $mod     = $NOTIF_MOD[$notif->modulo ?? 'general'] ?? $NOTIF_MOD['general'];
                        $nivel   = in_array($notif->nivel ?? 'info', ['info','exito','aviso','error'], true) ? $notif->nivel : 'info';
                        $tramo   = $tramoDe($notif);
                        $oculta  = $idxGlobal >= $porPagina;
                        $idxGlobal++;

                        // El separador viaja con su primera fila: si se paginara aparte,
                        // quedarían encabezados huérfanos al cambiar de página.
                        $nuevoTramo = $tramo !== $tramoPrevio;
                        $tramoPrevio = $tramo;

                        // Tira L·M·X·J·V: el día de la semana es el dato con el que un
                        // profesor ubica realmente una clase. Se pinta para cualquier fila
                        // que traiga fecha de referencia, no solo las de suplencias.
                        $diaIdx = dia_habil_indice($notif->ref_fecha ?? null);
                        $fp     = $fechaPartes($notif->creado_en);
                    ?>
                    <li class="nt-row nt-row--<?= $nivel ?><?= $esLeida ? '' : ' is-unread' ?><?= $oculta ? ' is-hidden' : '' ?>"
                        id="notif-<?= (int)$notif->id ?>" data-pager-item data-notif-id="<?= (int)$notif->id ?>">

                        <?php if ($nuevoTramo): ?>
                        <span class="nt-row__tramo"><?= s($NT_TRAMOS[$tramo]) ?></span>
                        <?php endif; ?>

                        <?php /* El módulo lo dice el icono: color y forma propios por módulo.
                                 El chip de texto que había debajo lo repetía en mayúsculas y era
                                 el elemento más ruidoso de una fila que ya tenía ocho. */ ?>
                        <span class="nt-row__icon nt-row__icon--<?= s($notif->modulo ?? 'general') ?>"
                              title="<?= s($mod['label']) ?>" aria-label="<?= s($mod['label']) ?>">
                            <i class="fa-solid <?= $mod['icon'] ?>"></i>
                        </span>

                        <div class="nt-row__body">
                            <p class="nt-row__msg"><?= s($notif->mensaje) ?></p>

                            <?php /* La fecha larga salía además escrita al lado de la tira, y el
                                     propio mensaje suele llevarla: se queda en el aria-label. */ ?>
                            <?php if ($diaIdx): ?>
                            <div class="nt-dias" role="img" title="<?= s(fecha_larga($notif->ref_fecha)) ?>"
                                 aria-label="Día de la clase: <?= s(fecha_larga($notif->ref_fecha)) ?>">
                                <?php foreach ($DIAS_TIRA as $k => $d): ?>
                                <span class="nt-dias__d<?= ($k + 1) === $diaIdx ? ' is-on' : '' ?>"><?= $d ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php /* La marca temporal en columna propia: el chip relativo es lo que
                                 responde a "¿esto es de ahora?", y la hora queda de apoyo. La
                                 fecha completa vive en el title, no ocupando línea. */ ?>
                        <?php if ($fp['chip']): ?>
                        <div class="nt-row__cuando" title="<?= s($fp['largo']) ?>">
                            <span class="nt-row__chip"><?= s($fp['chip']) ?></span>
                            <span class="nt-row__hora"><?= s($fp['hora']) ?></span>
                        </div>
                        <?php endif; ?>

                        <?php /* Dos acciones explícitas, no un icono ambiguo: el usuario tiene
                                 que saber que "completada" es lo que la saca de la bandeja.
                                 El payload viaja en data-* para poder restaurar la fila. */ ?>
                        <div class="nt-row__acts">
                            <?php if (!empty($notif->enlace)): ?>
                            <a href="<?= s($notif->enlace) ?>" class="nt-btn nt-btn--ver" data-nt-ver>
                                Ver detalle <i class="fa-solid fa-arrow-right"></i>
                            </a>
                            <?php endif; ?>
                            <button type="button" class="nt-btn nt-btn--ok" title="Marcar como completada y quitarla de la bandeja"
                                    data-nt-del
                                    data-tipo="<?= s((string)$notif->tipo) ?>"
                                    data-mensaje="<?= s((string)$notif->mensaje) ?>"
                                    data-modulo="<?= s((string)($notif->modulo ?? 'general')) ?>"
                                    data-nivel="<?= s($nivel) ?>"
                                    data-enlace="<?= s((string)($notif->enlace ?? '')) ?>"
                                    data-referencia-id="<?= (int)($notif->referencia_id ?? 0) ?>"
                                    data-referencia-tipo="<?= s((string)($notif->referencia_tipo ?? '')) ?>">
                                <i class="fa-solid fa-check"></i> Completada
                            </button>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <div class="nt-pager" data-pager data-pager-for="#ntList"
                     data-pager-per="<?= $porPagina ?>" data-pager-noun="notificaciones">
                    <button type="button" class="nt-pager__btn" data-pager-prev aria-label="Anteriores">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <span class="nt-pager__info" data-pager-info></span>
                    <button type="button" class="nt-pager__btn" data-pager-next aria-label="Siguientes">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php /* Deshacer: la notificación ya se borró, esto la reinserta. */ ?>
<div class="nt-undo" id="ntUndo" hidden>
    <img src="/build/assets/img/alex/alex-mano.png" alt="Alex">
    <div class="nt-undo__body">
        <strong>Notificación eliminada</strong>
        <small>Se quitó de tu bandeja.</small>
    </div>
    <button type="button" class="nt-undo__btn" data-nt-undo>Deshacer</button>
    <span class="nt-undo__bar"></span>
</div>

<?php /* Vaciar bandeja es irreversible: pasa por confirmación explícita. */ ?>
<div class="nt-modal" id="ntModal" hidden>
    <div class="nt-modal__card">
        <span class="nt-modal__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
        <h3>¿Vaciar la bandeja?</h3>
        <p>Se eliminarán <strong><?= $total ?></strong> <?= nt_plural($total) ?>.
           Esta acción no se puede deshacer.</p>
        <div class="nt-modal__acts">
            <button type="button" class="admin-btn admin-btn--ghost" data-nt-cancel>Cancelar</button>
            <form method="POST" action="/dashboard/notificaciones/limpiar">
                <button type="submit" class="admin-btn nt-modal__danger">
                    <i class="fa-regular fa-trash-can"></i> Sí, vaciar
                </button>
            </form>
        </div>
    </div>
</div>
