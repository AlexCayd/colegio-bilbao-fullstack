<?php
/**
 * Aviso de acceso de solo lectura.
 *
 * Lo incluyen las pantallas de configuración (usuarios, horarios, aulas, grupos)
 * cuando quien mira es directivo. No es el guard —ese vive en
 * BlogController::requireEscritura(), que es quien bloquea el POST— sino la
 * explicación: sin él, los botones de acción desaparecerían sin motivo aparente y
 * parecería que la página está rota.
 *
 * Uso:  <?php if (blog_modulos_solo_lectura()) include __DIR__ . '/_solo-lectura.php'; ?>
 * Opcional: $soloLecturaQue = 'los horarios' para concretar el texto.
 */
$_slQue = $soloLecturaQue ?? 'esta sección';
?>
<div class="admin-readonly" role="status">
    <span class="admin-readonly__ico"><i class="fa-solid fa-eye"></i></span>
    <div class="admin-readonly__txt">
        <strong>Estás viendo <?= htmlspecialchars($_slQue, ENT_QUOTES, 'UTF-8') ?> en modo consulta</strong>
        <span>Tu perfil de dirección permite revisarlo todo, pero los cambios los hace un administrador.</span>
    </div>
</div>
