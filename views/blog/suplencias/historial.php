<?php $paginaVista = 'blog-suplencias-historial'; ?>
<?php
/**
 * Histórico de suplencias de TODO el plantel, en versión resumida.
 *
 * Tres datos y ninguno más: la fecha, quién faltó y quién le cubrió. La agenda
 * (`/dashboard/suplencias`) sigue siendo de quien coordina porque enseña motivos y
 * justificantes; esto es lo que puede ver cualquiera con el módulo, y responde a la
 * única pregunta que un profesor se hace sobre las ausencias ajenas: quién cubrió a
 * quién. Cada fila es una HORA cubierta, no una ausencia: si a alguien le cubrieron
 * tres clases y fueron tres personas distintas, son tres filas y eso es el dato.
 *
 * Sin JS propio: `admin-table.js` da orden por columna y paginación a partir de
 * `data-table`, igual que el resto de listados del panel.
 *
 * @var object[] $filas  id, estado_hora, s_fecha, ausente_nombre, suplente_nombre
 */
require_once __DIR__ . '/../_modulos.php';
$porPagina = 15;
?>
<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">

        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">Histórico del plantel</span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">

            <?php if (empty($filas)): ?>
                <div class="hpl-vacio">
                    <img src="/build/assets/img/alex/alex-medita.png" alt="Alex" class="hpl-vacio__alex">
                    <h3>Todavía no hay coberturas</h3>
                    <p>Cuando prefectura asigne una suplencia aparecerá aquí, con quién faltó y quién cubrió.</p>
                </div>
            <?php else: ?>

            <div class="admin-panel">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title">
                        <i class="fa-solid fa-clock-rotate-left"></i> Clases cubiertas
                        <span class="admin-panel__count"><?= count($filas) ?></span>
                    </h2>
                </div>

                <table class="admin-table" data-table data-table-per="<?= $porPagina ?>" data-table-noun="coberturas">
                    <thead><tr>
                        <th data-sort="date">Fecha</th>
                        <th data-sort="text">Faltó</th>
                        <th data-sort="text">Cubrió</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($filas as $i => $f): ?>
                        <?php /* El servidor preoculta lo que pasa de una página: sin esto la
                                 tabla se pinta entera y parpadea al montar el paginador. */ ?>
                        <tr data-pager-item<?= $i >= $porPagina ? ' class="is-hidden"' : '' ?>>
                            <td data-val="<?= s($f->s_fecha) ?>">
                                <div class="admin-table__title"><?= s(fecha_larga($f->s_fecha)) ?></div>
                            </td>
                            <td data-val="<?= s($f->ausente_nombre) ?>">
                                <span class="hpl-persona hpl-persona--falto">
                                    <i class="fa-solid fa-user-minus"></i>
                                    <?= s($f->ausente_nombre ?: '—') ?>
                                </span>
                            </td>
                            <td data-val="<?= s($f->suplente_nombre) ?>">
                                <span class="hpl-persona hpl-persona--cubrio">
                                    <i class="fa-solid fa-user-check"></i>
                                    <?= s($f->suplente_nombre ?: '—') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>
