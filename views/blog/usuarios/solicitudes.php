<?php $paginaVista = 'blog-usuarios-solicitudes'; ?>
<?php
/**
 * Cola de restablecimientos pedidos desde /recuperar.
 *
 * Resolver genera una contraseña temporal y la muestra UNA sola vez: no se guarda en
 * claro en ningún sitio, lo que queda en base de datos es el hash. Viaja por sesión y no
 * por query string a propósito — una URL con la contraseña dentro acaba en el historial
 * del navegador y en los logs del servidor.
 *
 * @var \Model\SolicitudPassword[] $lista
 * @var array|null $generada  ['nombre','email','clave'] recién generada
 */
$estadoLabel = \Model\SolicitudPassword::ESTADO_LABEL;
$fmt = fn($d) => $d ? date('d/m/Y H:i', strtotime($d)) : '—';

$toast = null;
foreach ([
    'descartada' => ['Solicitud descartada', 'No se cambió ninguna contraseña.',                  '#94a3b8'],
    'sincuenta'  => ['Sin cuenta asociada',  'Ese correo no pertenece a nadie del panel. Se descartó.', '#f5b400'],
] as $_k => $_v) {
    if (isset($_GET[$_k])) { $toast = ['title' => $_v[0], 'msg' => $_v[1], 'icon' => 'fa-circle-info', 'color' => $_v[2]]; break; }
}
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left"><span class="admin-topbar__title">Solicitudes de contraseña</span></div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">

            <?php if ($generada): ?>
            <?php /* Se muestra una vez y no vuelve: al recargar ya no está en sesión. Por
                     eso el aviso insiste en copiarla ahora — regenerarla es trivial, pero
                     nadie quiere descubrirlo con el profesor al teléfono. */ ?>
            <section class="admin-panel spw-generada">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title"><i class="fa-solid fa-key"></i> Contraseña temporal generada</h2>
                </div>
                <div class="admin-form-section">
                    <p class="spw-generada__quien">
                        Para <strong><?= s($generada['nombre']) ?></strong> · <?= s($generada['email']) ?>
                    </p>
                    <p class="spw-clave"><?= s($generada['clave']) ?></p>
                    <p class="admin-form__hint">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <strong>Cópiala ahora:</strong> no se guarda en ningún sitio y al recargar esta
                        página desaparece. Pásasela en persona o por teléfono y pídele que la cambie
                        desde su perfil.
                    </p>
                </div>
            </section>
            <?php endif; ?>

            <section class="admin-panel">
                <div class="admin-panel__header">
                    <div>
                        <h2 class="admin-panel__title">Peticiones recibidas</h2>
                        <p class="admin-panel__sub">
                            Llegan del enlace «¿Olvidaste tu contraseña?» del login. El panel no manda
                            correo: la temporal se genera aquí y se comunica a mano.
                        </p>
                    </div>
                </div>

                <?php if (!$lista): ?>
                <div class="act-vacio">
                    <img src="/build/assets/img/alex/alex-espera.png" alt="">
                    <p>No hay ninguna solicitud. Aquí aparecerán las que lleguen desde el login.</p>
                </div>
                <?php else: ?>

                <div class="admin-table-scroll">
                    <table class="admin-table" data-table data-table-per="15" data-table-noun="solicitudes">
                        <thead>
                            <tr>
                                <th data-sort="date" aria-sort="descending" class="is-desc">Recibida</th>
                                <th data-sort="text">Quién dice ser</th>
                                <th data-sort="text">Cuenta</th>
                                <th data-sort="text">Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($lista as $i => $s): ?>
                            <tr data-pager-item<?= $i >= 15 ? ' class="is-hidden"' : '' ?>>
                                <td data-val="<?= s((string)$s->creado_en) ?>" data-label="Recibida"><?= $fmt($s->creado_en) ?></td>
                                <td data-label="Quién dice ser">
                                    <strong><?= s($s->nombre) ?></strong><br>
                                    <small><?= s($s->email) ?></small>
                                </td>
                                <td data-label="Cuenta">
                                    <?php if ($s->usuario_id): ?>
                                    <a href="/dashboard/usuarios/detalle?id=<?= (int)$s->usuario_id ?>"><?= s($s->usuario_nombre) ?></a>
                                    <?php else: ?>
                                    <?php /* Sin cuenta: el correo no existe en el panel. La solicitud se
                                             guardó igual porque el formulario no puede decírselo a quien
                                             la manda —sería un verificador de cuentas— y porque al admin
                                             le sirve para detectar a quien se equivoca de dirección. */ ?>
                                    <span class="spw-sincuenta"><i class="fa-solid fa-circle-question"></i> Ese correo no existe</span>
                                    <?php endif; ?>
                                </td>
                                <td data-val="<?= s($s->estado) ?>" data-label="Estado">
                                    <span class="spw-estado spw-estado--<?= s($s->estado) ?>"><?= s($estadoLabel[$s->estado] ?? $s->estado) ?></span>
                                    <?php if ($s->resolutor_nombre): ?>
                                    <br><small><?= s($s->resolutor_nombre) ?> · <?= $fmt($s->resuelto_en) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Acciones">
                                    <?php if ($s->estado === 'pendiente'): ?>
                                    <div class="spw-acts">
                                        <?php if ($s->usuario_id): ?>
                                        <form method="POST" action="/dashboard/usuarios/solicitudes/resolver"
                                              onsubmit="return confirm('Se generará una contraseña temporal y la actual dejará de servir. ¿Continuar?');">
                                            <input type="hidden" name="id" value="<?= (int)$s->id ?>">
                                            <button type="submit" class="admin-btn admin-btn--primary admin-btn--sm">
                                                <i class="fa-solid fa-key"></i> Generar temporal
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <form method="POST" action="/dashboard/usuarios/solicitudes/resolver">
                                            <input type="hidden" name="id" value="<?= (int)$s->id ?>">
                                            <input type="hidden" name="descartar" value="1">
                                            <button type="submit" class="admin-btn admin-btn--ghost admin-btn--sm">
                                                <i class="fa-solid fa-xmark"></i> Descartar
                                            </button>
                                        </form>
                                    </div>
                                    <?php else: ?>
                                    <span class="spw-nil">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../_toast.php'; ?>
