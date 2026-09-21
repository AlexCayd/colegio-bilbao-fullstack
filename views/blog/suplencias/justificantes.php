<?php $paginaVista = 'blog-suplencias-justificantes'; ?>
<?php
/**
 * Cola de justificantes pendientes de decisión.
 *
 * Un justificante deja de estar disponible sin más pasados
 * Suplencia::DIAS_DESCARGA días desde la ausencia, y llega aquí: alguien con
 * acceso decide si lo descarga (y se archiva fuera) o lo elimina. A los
 * DIAS_PURGA se borra solo, así que la lista se ordena por antigüedad y avisa de
 * cuánto queda.
 *
 * @var \Model\Suplencia[] $cola
 * @var array              $info  id => ['nombre','peso','ext','icono'] | null
 */
require_once __DIR__ . '/../_modulos.php';
$s = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$DIAS_PURGA = \Model\Suplencia::DIAS_PURGA;
?>
<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">

        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">Justificantes</span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">

            <div class="jus-intro">
                <span class="jus-intro__ico"><i class="fa-solid fa-file-shield"></i></span>
                <div>
                    <h2 class="jus-intro__titulo">Justificantes que esperan tu decisión</h2>
                    <p class="jus-intro__txt">
                        Pasados <strong><?= (int)\Model\Suplencia::DIAS_DESCARGA ?> días</strong> desde la ausencia,
                        el justificante deja de descargarse desde la suplencia y llega aquí.
                        Descárgalo si necesitas conservarlo o elimínalo si ya no hace falta:
                        en ambos casos el archivo sale del servidor.
                        A los <strong><?= (int)$DIAS_PURGA ?> días</strong> se elimina automáticamente.
                    </p>
                </div>
            </div>

            <?php if (empty($cola)): ?>
                <div class="jus-vacio">
                    <img src="/build/assets/img/alex/alex-medita.png" alt="Alex" class="jus-vacio__alex">
                    <h3>Todo al día</h3>
                    <p>No hay ningún justificante esperando decisión.</p>
                </div>
            <?php else: ?>

            <div class="admin-panel">
                <?php /* ⚠️ El envoltorio de scroll es OBLIGATORIO y faltaba. `.admin-panel`
                         es `overflow: clip` y `.admin-table` tiene `min-width: 680px`, así
                         que por debajo de ese ancho las últimas columnas —Archivo, Días y
                         Acciones— quedaban CORTADAS y sin forma de llegar a ellas: ni
                         scroll, ni nada. Y es una vista de dirección que se consulta desde
                         el móvil. */ ?>
                <div class="admin-table-scroll">
                <table class="admin-table" data-table data-table-per="12" data-table-noun="justificantes">
                    <thead><tr>
                        <th data-sort="date">Ausencia</th>
                        <th data-sort="text">Profesor</th>
                        <th data-sort="text">Motivo</th>
                        <th data-sort="text">Archivo</th>
                        <th data-sort="num">Plazo</th>
                        <th>Decisión</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($cola as $i => $sup):
                        $dias   = (int)$sup->dias_desde;
                        // Lo calcula el modelo: la vista lo duplicaba a mano.
                        $quedan = $sup->diasParaPurga();
                        $meta   = $info[(int)$sup->id] ?? null;
                        /* Semáforo hasta el borrado automático. La cola solo contiene ausencias
                           de hace 7 días o más, así que `quedan` va de 0 a 23 sobre los 30 de
                           DIAS_PURGA. El corte del naranja son los 7 de DIAS_DESCARGA: le queda
                           tanto plazo como el que ya esperó para entrar aquí.
                           Por encima de 14 se queda en gris a propósito — si todo estuviera
                           teñido, el color dejaría de señalar nada. */
                        $nivelPlazo = $quedan <= 3  ? 'rojo'
                                    : ($quedan <= 7  ? 'naranja'
                                    : ($quedan <= 14 ? 'ambar' : ''));
                    ?>
                        <tr data-pager-item<?= $i >= 12 ? ' class="is-hidden"' : '' ?>>
                            <td data-val="<?= $s($sup->fecha) ?>">
                                <div class="admin-table__title"><?= $s(fecha_larga($sup->fecha)) ?></div>
                                <div class="admin-table__meta">hace <?= $dias ?> días</div>
                            </td>
                            <td data-val="<?= $s($sup->ausente_nombre) ?>">
                                <?= $s($sup->ausente_nombre ?: '—') ?>
                            </td>
                            <td data-val="<?= $s($sup->motivo) ?>">
                                <span class="jus-motivo"><?= $s($sup->motivo ?: '—') ?></span>
                            </td>
                            <td data-val="<?= $s($meta['nombre'] ?? '') ?>">
                                <?php if ($meta): ?>
                                    <span class="jus-file">
                                        <i class="fa-solid <?= $s($meta['icono']) ?>"></i>
                                        <span class="jus-file__n"><?= $s($meta['nombre']) ?></span>
                                        <small><?= $s($meta['peso']) ?></small>
                                    </span>
                                <?php else: ?>
                                    <?php /* El registro apunta a un archivo que ya no está en disco:
                                              conviene poder cerrarlo igualmente. */ ?>
                                    <span class="jus-file jus-file--roto">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Archivo no encontrado
                                    </span>
                                <?php endif; ?>
                            </td>
                            <?php /* ⚠️ `data-val` sigue siendo `$quedan` y sigue en el <td>: el
                                     `data-sort="num"` de la cabecera lee el data-val de la celda,
                                     no el texto del <span>. Moverlo rompería el orden en silencio. */ ?>
                            <td data-val="<?= $quedan ?>">
                                <span class="jus-plazo<?= $nivelPlazo ? ' jus-plazo--' . $nivelPlazo : '' ?>">
                                    <?= $quedan === 0 ? 'Se borra hoy' : 'Quedan ' . $quedan . ' d' ?>
                                </span>
                            </td>
                            <td>
                                <div class="jus-acciones">
                                    <?php if ($meta): ?>
                                    <?php /* La descarga es la acción dominante: hay que hacerla ANTES
                                              de resolver, porque resolver borra el archivo. */ ?>
                                    <a class="admin-btn admin-btn--sm admin-btn--primary"
                                       href="/dashboard/suplencias/justificante?id=<?= (int)$sup->id ?>"
                                       data-jus-descargar="<?= (int)$sup->id ?>">
                                        <i class="fa-solid fa-download"></i> Descargar
                                    </a>
                                    <?php endif; ?>
                                    <button type="button" class="admin-btn admin-btn--sm admin-btn--danger"
                                            data-jus-eliminar="<?= (int)$sup->id ?>"
                                            data-nombre="<?= $s($sup->ausente_nombre) ?>"
                                            data-fecha="<?= $s(fecha_larga($sup->fecha)) ?>">
                                        <i class="fa-solid fa-trash"></i> Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php /* Un solo formulario para las dos resoluciones: el JS rellena id y accion. */ ?>
<form id="jusForm" method="POST" action="/dashboard/suplencias/justificantes/resolver" hidden>
    <input type="hidden" name="id"     id="jusFormId">
    <input type="hidden" name="accion" id="jusFormAccion">
</form>

<?php /* Mismo contrato de visibilidad que _catalogo-modal.php: `.cat-modal` es
         `display:grid` y su ÚNICA regla de ocultación es `&[hidden]`. Sin este
         atributo la modal nacía abierta y no había forma de cerrarla. */ ?>
<div class="cat-modal" id="jusModal" role="dialog" aria-modal="true" aria-labelledby="jusModalTitle" hidden>
    <div class="cat-modal__card">
        <span class="cat-modal__icon"><i class="fa-solid fa-trash"></i></span>
        <h3 id="jusModalTitle">Eliminar el justificante</h3>
        <p>
            Vas a borrar del servidor el justificante de <strong data-jus-quien>—</strong>
            (<span data-jus-cuando></span>). Es irreversible: si aún puede hacer falta,
            descárgalo antes.
        </p>
        <div class="cat-modal__acts">
            <button type="button" class="admin-btn admin-btn--ghost" data-jus-cancelar>Cancelar</button>
            <button type="button" class="admin-btn cat-modal__danger" data-jus-confirmar>
                <i class="fa-solid fa-trash"></i> Eliminar definitivamente
            </button>
        </div>
    </div>
</div>
