<?php $paginaVista = 'blog-aulas-form'; ?>
<?php
$accion  = '/dashboard/aulas/editar?id=' . (int)$aula->id;
$esNueva = false;
include __DIR__ . '/_form.php';
