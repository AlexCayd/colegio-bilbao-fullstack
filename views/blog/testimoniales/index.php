<?php $paginaVista = 'blog-testimoniales-index'; ?>

<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <div class="admin-breadcrumb">
                    <a href="/dashboard">Dashboard</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Testimoniales</span>
                </div>
                <span class="admin-topbar__title">Testimoniales</span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn">
                        <i class="fa-solid fa-right-from-bracket"></i> Salir
                    </button>
                </form>
            </div>
        </header>

        <main class="admin-content">

            <?php if (isset($_GET['aprobado'])): ?>
            <div class="admin-alert admin-alert--success" role="alert">
                <i class="fa-solid fa-circle-check"></i> Testimonio aprobado y publicado.
            </div>
            <?php elseif (isset($_GET['rechazado'])): ?>
            <div class="admin-alert admin-alert--info" role="alert">
                <i class="fa-solid fa-trash"></i> Testimonio eliminado.
            </div>
            <?php endif; ?>

            <!-- Pendientes -->
            <?php
            $pendientes = array_filter($testimoniales ?? [], fn($t) => !$t->aprobado);
            $aprobados  = array_filter($testimoniales ?? [], fn($t) => $t->aprobado);
            ?>

            <div class="admin-panel" style="margin-bottom:32px;">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title">
                        Por revisar
                        <span class="admin-panel__count"><?= count($pendientes) ?></span>
                    </h2>
                </div>

                <?php if (empty($pendientes)): ?>
                <div class="admin-empty-state">
                    <img src="/build/assets/img/alex/alex-lee.png" alt="Alex" class="admin-empty-state__img" style="width:80px;">
                    <p class="admin-empty-state__text">No hay testimoniales pendientes. ¡Todo al día!</p>
                </div>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Comentario</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pendientes as $t): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($t->nombre) ?></strong></td>
                                <td><?= htmlspecialchars($t->rol) ?></td>
                                <td style="max-width:320px;"><?= htmlspecialchars(mb_substr($t->comentario, 0, 120)) ?><?= mb_strlen($t->comentario) > 120 ? '…' : '' ?></td>
                                <td style="white-space:nowrap;color:#7a8fa8;font-size:.82rem;"><?= date('d/m/Y', strtotime($t->created_at)) ?></td>
                                <td>
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                        <form method="POST" action="/dashboard/testimoniales/aprobar" data-confirm="¿Aprobar este testimonio y publicarlo en el sitio?">
                                            <input type="hidden" name="id" value="<?= (int)$t->id ?>">
                                            <button type="submit" class="admin-btn admin-btn--primary admin-btn--sm">
                                                <i class="fa-solid fa-check"></i> Aprobar
                                            </button>
                                        </form>
                                        <form method="POST" action="/dashboard/testimoniales/rechazar" data-confirm="¿Eliminar este testimonio? Esta acción no se puede deshacer.">
                                            <input type="hidden" name="id" value="<?= (int)$t->id ?>">
                                            <button type="submit" class="admin-btn admin-btn--danger admin-btn--sm">
                                                <i class="fa-solid fa-trash"></i> Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Aprobados -->
            <div class="admin-panel">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title">
                        Publicados en el landing
                        <span class="admin-panel__count"><?= count($aprobados) ?></span>
                    </h2>
                </div>

                <?php if (empty($aprobados)): ?>
                <div class="admin-empty-state">
                    <p class="admin-empty-state__text">Aún no hay testimoniales aprobados.</p>
                </div>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Comentario</th>
                                <th>Fecha</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($aprobados as $t): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($t->nombre) ?></strong></td>
                                <td><?= htmlspecialchars($t->rol) ?></td>
                                <td style="max-width:320px;"><?= htmlspecialchars(mb_substr($t->comentario, 0, 120)) ?><?= mb_strlen($t->comentario) > 120 ? '…' : '' ?></td>
                                <td style="white-space:nowrap;color:#7a8fa8;font-size:.82rem;"><?= date('d/m/Y', strtotime($t->created_at)) ?></td>
                                <td>
                                    <form method="POST" action="/dashboard/testimoniales/rechazar" data-confirm="¿Eliminar este testimonio publicado? Desaparecerá del sitio.">
                                        <input type="hidden" name="id" value="<?= (int)$t->id ?>">
                                        <button type="submit" class="admin-btn admin-btn--danger admin-btn--sm">
                                            <i class="fa-solid fa-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<div id="confirm-modal" class="cmodal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="cmodal-msg">
    <div class="cmodal__box">
        <img src="/build/assets/img/alex/alex-lee.png" alt="Alex" class="cmodal__alex">
        <p class="cmodal__msg" id="cmodal-msg">¿Confirmar acción?</p>
        <div class="cmodal__actions">
            <button id="cmodal-cancel" class="admin-btn admin-btn--secondary">Cancelar</button>
            <button id="cmodal-ok" class="admin-btn admin-btn--primary">Confirmar</button>
        </div>
    </div>
</div>

