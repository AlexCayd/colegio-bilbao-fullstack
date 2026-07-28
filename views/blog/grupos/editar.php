<?php $paginaVista = 'blog-grupos-form'; ?>
<?php
$accion  = '/dashboard/grupos/editar?id=' . (int)$grupo->id;
$esNueva = false;
include __DIR__ . '/_form.php';
