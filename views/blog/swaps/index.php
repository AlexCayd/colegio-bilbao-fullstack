<?php $paginaVista = 'blog-swaps-index'; ?>
<?php
/**
 * Swaps de clase.
 *
 * Dos públicos, como en Suplencias: quien imparte ve los suyos y responde; quien
 * coordina ve los del claustro y valida. Un admin que además da clase ve las dos
 * secciones.
 *
 * @var \Model\Swap[] $mios
 * @var \Model\Swap[] $todos
 * @var bool $coordina  @var bool $imparte  @var int $uid  @var int $porValidar
 */
$s = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

/* Los cuatro POST del módulo redirigen aquí con su query param, pero esta vista no los
   leía: se proponía un swap, volvías al listado y nada decía que hubiera pasado
   algo. El toast se retira solo a los 5,6 s (admin-toast.js). */
$toast = null;
if (isset($_GET['creado'])) {
    $toast = ['title' => 'Propuesta enviada', 'msg' => 'Tu compañero recibió el aviso; te avisamos cuando responda.', 'icon' => 'fa-paper-plane', 'color' => '#4267ac'];
} elseif (isset($_GET['respondido'])) {
    $toast = ['title' => 'Respuesta registrada', 'msg' => 'Si lo aceptaste, falta el visto bueno de prefectura.', 'icon' => 'fa-reply', 'color' => '#34a853'];
} elseif (isset($_GET['validado'])) {
    $toast = ['title' => 'Intercambio resuelto', 'msg' => 'Se avisó a las dos partes.', 'icon' => 'fa-gavel', 'color' => '#34a853'];
} elseif (isset($_GET['cancelado'])) {
    $toast = ['title' => 'Propuesta retirada', 'msg' => 'Ya no aparece como pendiente para tu compañero.', 'icon' => 'fa-xmark', 'color' => '#94a3b8'];
} elseif (isset($_GET['faltamotivo'])) {
    // El `required` del formulario ya lo pide, pero el guard del servidor manda y
    // rebotar sin explicación parecería que la acción se perdió.
    $toast = ['title' => 'Falta el motivo', 'msg' => 'Para decir que no hay que explicar por qué: la otra persona necesita saberlo.', 'icon' => 'fa-comment-slash', 'color' => '#e51022'];
}

// Alcance por nivel de las direcciones.
$alcance = $alcance ?? [];
?>
<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">

        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">Intercambios de clase</span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">

            <?php /* La acción abre la pantalla, no el topbar: ahí quedaba junto a la campana
                     y el avatar, que son del panel entero. Aquí es lo primero que se ve al
                     entrar, que es lo que se viene a hacer cuando no hay nada que responder.
                     Quien coordina también abre intercambios, pero el suyo no es una propuesta:
                     designa a los dos profesores y nace ya validado. */ ?>
            <?php if ($imparte || $coordina): ?>
            <div class="swp-barra">
                <a href="/dashboard/swaps/crear" class="admin-new-btn">
                    <i class="fa-solid fa-plus"></i> <?= $imparte ? 'Proponer intercambio' : 'Registrar intercambio' ?>
                </a>
            </div>
            <?php endif; ?>

            <?php if ($coordina && $porValidar > 0): ?>
            <div class="swp-aviso">
                <span class="swp-aviso__ico"><i class="fa-solid fa-gavel"></i></span>
                <div>
                    <strong><?= (int)$porValidar ?> intercambio<?= $porValidar === 1 ? '' : 's' ?> esperando tu visto bueno</strong>
                    <span>Las dos partes ya se pusieron de acuerdo; falta confirmarlo.</span>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($imparte): ?>
            <p class="mh-section-label"><i class="fa-solid fa-right-left"></i> Mis intercambios</p>
            <?php if (empty($mios)): ?>
                <div class="swp-vacio">
                    <img src="/build/assets/img/alex/alex-point.png" alt="Alex" class="swp-vacio__alex">
                    <h3>Aún no has propuesto ningún intercambio</h3>
                    <p>
                        Si un día no puedes dar una clase, puedes cambiarla con otro profesor:
                        tú das una suya y esa persona da la tuya. Es un cambio puntual, no toca tu horario.
                    </p>
                    <a href="/dashboard/swaps/crear" class="admin-btn admin-btn--primary">
                        <i class="fa-solid fa-plus"></i> Proponer intercambio
                    </a>
                </div>
            <?php else: ?>
                <div class="swp-lista">
                    <?php foreach ($mios as $sw):
                        $puedeResponder = (int)$sw->destinatario_id === (int)$uid && $sw->estado === 'pendiente';
                        $puedeCancelar  = (int)$sw->solicitante_id === (int)$uid && $sw->estado === 'pendiente';
                        $puedeValidar   = false;   // validar se hace en la sección de coordinación
                        include __DIR__ . '/_tarjeta.php';
                    endforeach; ?>
                </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($coordina): ?>
            <p class="mh-section-label"><i class="fa-solid fa-users-viewfinder"></i> Todo el claustro</p>
            <?php if (empty($todos)): ?>
                <div class="swp-vacio swp-vacio--compacto">
                    <p>Todavía no hay intercambios registrados.</p>
                </div>
            <?php else: ?>
                <div class="swp-lista">
                    <?php foreach ($todos as $sw):
                        $puedeResponder = false;
                        $puedeCancelar  = false;
                        $puedeValidar   = $sw->estado === 'aceptado';
                        include __DIR__ . '/_tarjeta.php';
                    endforeach; ?>
                </div>
            <?php endif; ?>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php include __DIR__ . '/../_toast.php'; ?>
