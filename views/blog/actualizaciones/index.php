<?php $paginaVista = 'blog-actualizaciones-index'; ?>
<?php
/**
 * Historial de novedades del panel.
 *
 * Lo ve cualquiera con sesión: es el registro de lo que ha cambiado en su herramienta de
 * trabajo, y sin él un anuncio se lee una vez y desaparece para siempre. Publicar, editar
 * y borrar piden admin, y para el admin la lista incluye además los borradores.
 *
 * @var \Model\Actualizacion[] $lista  @var bool $esAdmin  @var int $total
 */
$estadoLabel = \Model\Actualizacion::ESTADO_LABEL;

$toast = null;
foreach ([
    'creada'       => ['Anuncio creado',      'Queda en borrador: publícalo cuando quieras que lo vean.', '#34a853'],
    'editada'      => ['Cambios guardados',   'Se actualizó el anuncio.',                                 '#34a853'],
    'publicada'    => ['Publicado',           'A partir de ahora lo verán todos al entrar al panel.',     '#4285f4'],
    'despublicada' => ['Vuelto a borrador',   'Deja de bloquear. Quien ya lo vio no lo volverá a ver.',   '#f5b400'],
    'eliminada'    => ['Anuncio eliminado',   'Se borró junto con sus acuses de recibo.',                 '#e51022'],
] as $_k => $_v) {
    if (isset($_GET[$_k])) { $toast = ['title' => $_v[0], 'msg' => $_v[1], 'icon' => 'fa-circle-check', 'color' => $_v[2]]; break; }
}
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left"><span class="admin-topbar__title">Actualizaciones</span></div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">
            <section class="admin-panel">
                <div class="admin-panel__header">
                    <div>
                        <h2 class="admin-panel__title">Novedades del panel</h2>
                        <p class="admin-panel__sub">
                            <?= $esAdmin
                                ? 'Al publicar, todo el claustro verá el anuncio al entrar y no podrá seguir hasta marcarlo como visto.'
                                : 'Todo lo que ha cambiado en el panel, de lo más reciente a lo más antiguo.' ?>
                        </p>
                    </div>
                    <?php if ($esAdmin): ?>
                    <a href="/dashboard/actualizaciones/crear" class="admin-new-btn">
                        <i class="fa-solid fa-plus"></i> Nueva actualización
                    </a>
                    <?php endif; ?>
                </div>

                <?php if (!$lista): ?>
                <div class="act-vacio">
                    <img src="/build/assets/img/alex/alex-espera.png" alt="">
                    <p><?= $esAdmin ? 'Todavía no hay ningún anuncio. Crea el primero.' : 'Todavía no hay novedades que contar.' ?></p>
                </div>
                <?php else: ?>

                <div class="act-lista">
                    <?php foreach ($lista as $a): ?>
                    <?php $pub = $a->estado === 'publicada'; ?>
                    <article class="act-card act-card--<?= $pub ? 'publicada' : 'borrador' ?>">
                        <div class="act-card__head">
                            <?php if ($a->version): ?>
                            <span class="act-ver"><?= s($a->version) ?></span>
                            <?php endif; ?>
                            <h3 class="act-card__titulo"><?= s($a->titulo) ?></h3>
                            <?php if ($esAdmin): ?>
                            <span class="act-estado"><?= s($estadoLabel[$a->estado] ?? $a->estado) ?></span>
                            <?php endif; ?>
                        </div>

                        <p class="act-card__cuerpo"><?= nl2br(s($a->cuerpo)) ?></p>

                        <?php if ($a->imagen): ?>
                        <img src="<?= s($a->imagen) ?>" alt="" class="act-card__img" loading="lazy">
                        <?php endif; ?>

                        <div class="act-card__pie">
                            <?php if ($a->publicada_en): ?>
                            <span><i class="fa-regular fa-calendar"></i> <?= s(fecha_larga(substr((string)$a->publicada_en, 0, 10), true)) ?></span>
                            <?php endif; ?>
                            <?php if ($a->autor_nombre): ?>
                            <span><i class="fa-regular fa-user"></i> <?= s($a->autor_nombre) ?></span>
                            <?php endif; ?>

                            <?php if ($esAdmin && $pub): ?>
                            <?php /* Cuántos lo han acusado. Es el dato que dice si el anuncio
                                     ya llegó a todos o si queda gente por entrar. */ ?>
                            <span class="act-vistas">
                                <i class="fa-solid fa-eye"></i>
                                <?= (int)$a->vistas ?>/<?= (int)$total ?> lo han visto
                            </span>
                            <?php endif; ?>

                            <?php if ($esAdmin): ?>
                            <div class="act-card__acts">
                                <a href="/dashboard/actualizaciones/crear?id=<?= (int)$a->id ?>" class="admin-btn admin-btn--ghost admin-btn--sm">
                                    <i class="fa-solid fa-pen"></i> Editar
                                </a>
                                <form method="POST" action="/dashboard/actualizaciones/publicar">
                                    <input type="hidden" name="id" value="<?= (int)$a->id ?>">
                                    <?php if ($pub): ?>
                                    <input type="hidden" name="despublicar" value="1">
                                    <button type="submit" class="admin-btn admin-btn--ghost admin-btn--sm">
                                        <i class="fa-solid fa-eye-slash"></i> Despublicar
                                    </button>
                                    <?php else: ?>
                                    <button type="submit" class="admin-btn admin-btn--primary admin-btn--sm">
                                        <i class="fa-solid fa-bullhorn"></i> Publicar
                                    </button>
                                    <?php endif; ?>
                                </form>
                                <form method="POST" action="/dashboard/actualizaciones/eliminar"
                                      onsubmit="return confirm('¿Eliminar este anuncio? También se borran los acuses de recibo.');">
                                    <input type="hidden" name="id" value="<?= (int)$a->id ?>">
                                    <button type="submit" class="admin-btn admin-btn--ghost admin-btn--sm">
                                        <i class="fa-solid fa-trash"></i> Eliminar
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../_toast.php'; ?>
