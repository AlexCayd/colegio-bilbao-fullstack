<?php
/**
 * Modal de confirmación de borrado para los catálogos (aulas, grupos).
 * Variables esperadas antes del include:
 *   $catModal = ['accion' => '/dashboard/…/eliminar', 'que' => 'el aula']
 *
 * Solo se ofrece para registros sin uso: los que tienen clases asignadas ya salen
 * con el botón deshabilitado en la tabla, así que aquí no hace falta reconfirmar
 * nada más que la intención.
 */
$catModal = $catModal ?? ['accion' => '#', 'que' => 'el registro'];
?>
<div class="cat-modal" id="catModal" hidden>
    <div class="cat-modal__card">
        <span class="cat-modal__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
        <h3>¿Eliminar <?= htmlspecialchars($catModal['que']) ?>?</h3>
        <p>Se eliminará <strong data-cat-nombre>—</strong> del catálogo. Esta acción no se puede deshacer.</p>
        <form method="POST" action="<?= htmlspecialchars($catModal['accion']) ?>">
            <input type="hidden" name="id" data-cat-id value="">
            <div class="cat-modal__acts">
                <button type="button" class="admin-btn admin-btn--ghost" data-cat-cancel>Cancelar</button>
                <button type="submit" class="admin-btn cat-modal__danger">
                    <i class="fa-solid fa-trash"></i> Sí, eliminar
                </button>
            </div>
        </form>
    </div>
</div>
