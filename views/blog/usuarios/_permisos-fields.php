<?php
/**
 * Campos de Rol y permisos, compartido por crear.php y editar.php.
 * Espera definidas: $modsSel(array), $rolActual, $rolRed, $tiposSel(array), $puedeSupl(bool).
 * Abre .admin-panel + .admin-form-section pero NO los cierra: el include continúa con el footer.
 */
require_once __DIR__ . '/../_modulos.php';

/**
 * Los checkboxes salen del catálogo compartido y agrupados por sus mismas
 * categorías: antes había aquí una segunda lista paralela que ya divergía del
 * home y del sidebar en nombres e iconos.
 */
// true explícito: aquí se describen los módulos que se le conceden a OTRA persona,
// no los del usuario en sesión, así que siempre va la redacción completa.
$MODS_CAT = blog_modulos_catalogo(true);
$MODS_GRP = blog_modulos_categorias();
$ASIGNABLES = \Model\UsuarioBlog::MODULOS_ASIGNABLES;
// El orden va alineado con UsuarioBlog::normalizarTipoPersonal(), que es quien
// decide en qué orden se guarda el CSV y salen los chips del listado.
$TIPOS = [
    'administrativo' => ['nombre' => 'Administrativo', 'desc' => 'Personal administrativo; no cubre suplencias.',             'icon' => 'fa-user-tie'],
    'profesor'       => ['nombre' => 'Profesor',       'desc' => 'Imparte clases; puede solicitar y cubrir suplencias.',      'icon' => 'fa-chalkboard-user'],
    'prefecto'       => ['nombre' => 'Prefecto',       'desc' => 'Agenda y coordina suplencias; no las cubre. Excluyente.',   'icon' => 'fa-user-shield'],
];
?>
<div class="admin-panel">
    <div class="admin-panel__header">
        <h2 class="admin-panel__title">Rol y permisos</h2>
    </div>
    <div class="admin-form-section" id="permisos-section">
        <p style="font-size:.88rem;color:var(--text-gray);line-height:1.65;margin-bottom:20px;">
            El <strong>Administrador</strong> accede a todo el panel sin excepción.
            El <strong>Usuario</strong> solo a los módulos que marques aquí abajo.
        </p>

        <div class="admin-role-cards" style="grid-template-columns:repeat(2,1fr);">
            <div class="admin-role-card">
                <input type="radio" id="rol-admin" name="rol" value="administrador" <?= $rolActual === 'administrador' ? 'checked' : '' ?>>
                <label for="rol-admin">
                    <div class="admin-role-card__icon"><i class="fa-solid fa-crown"></i></div>
                    <div class="admin-role-card__name">Administrador</div>
                    <div class="admin-role-card__desc">Acceso total a todos los módulos del panel.</div>
                </label>
            </div>

            <div class="admin-role-card">
                <input type="radio" id="rol-usuario" name="rol" value="usuario" <?= $rolActual !== 'administrador' ? 'checked' : '' ?>>
                <label for="rol-usuario">
                    <div class="admin-role-card__icon"><i class="fa-solid fa-user-gear"></i></div>
                    <div class="admin-role-card__name">Usuario</div>
                    <div class="admin-role-card__desc">Acceso solo a los módulos seleccionados.</div>
                </label>
            </div>
        </div>

        <!-- MÓDULOS (solo para rol Usuario) -->
        <div class="admin-modulos" id="modulos-group" style="margin-top:22px;">
            <label class="admin-form__label" style="margin-bottom:12px;">
                <i class="fa-solid fa-grip"></i> Módulos con acceso
            </label>
            <?php foreach ($MODS_GRP as $grp):
                $claves = array_values(array_filter($grp['claves'], fn($k) => in_array($k, $ASIGNABLES, true)));
                if (!$claves) continue; ?>
            <div class="admin-modulos__cat">
                <span class="admin-modulos__cat-label"><i class="fa-solid <?= $grp['icon'] ?>"></i> <?= $grp['label'] ?></span>
                <div class="admin-modulos__grid">
                    <?php foreach ($claves as $key): $m = $MODS_CAT[$key]; ?>
                    <label class="admin-modulo-check" data-modulo="<?= $key ?>">
                        <input type="checkbox" name="modulos[]" value="<?= $key ?>" <?= in_array($key, $modsSel, true) ? 'checked' : '' ?>>
                        <span class="admin-modulo-check__box">
                            <span class="admin-modulo-check__icon"><i class="fa-solid <?= $m['icon'] ?>"></i></span>
                            <span class="admin-modulo-check__text">
                                <span class="admin-modulo-check__name"><?= $m['nombre'] ?></span>
                                <span class="admin-modulo-check__desc"><?= $m['desc'] ?></span>
                            </span>
                            <span class="admin-modulo-check__tick"><i class="fa-solid fa-check"></i></span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <span class="admin-form__hint" style="margin-top:10px;display:block;">Selecciona al menos un módulo para el rol Usuario.</span>

            <!-- SUB-ROL de REDACCIÓN (revisor / editor) -->
            <div class="admin-subrol" id="redaccion-subrol" style="margin-top:18px;<?= in_array('redaccion', $modsSel, true) ? '' : 'display:none;' ?>">
                <label class="admin-form__label" style="margin-bottom:10px;">
                    <i class="fa-solid fa-user-pen"></i> Rol en Redacción
                </label>
                <div class="admin-role-cards" style="grid-template-columns:repeat(2,1fr);">
                    <div class="admin-role-card">
                        <input type="radio" id="red-revisor" name="rol_redaccion" value="revisor" <?= $rolRed === 'revisor' ? 'checked' : '' ?>>
                        <label for="red-revisor">
                            <div class="admin-role-card__icon"><i class="fa-solid fa-user-check"></i></div>
                            <div class="admin-role-card__name">Revisor</div>
                            <div class="admin-role-card__desc">Aprueba/rechaza contenido y gestiona testimoniales.</div>
                        </label>
                    </div>
                    <div class="admin-role-card">
                        <input type="radio" id="red-editor" name="rol_redaccion" value="editor" <?= $rolRed === 'editor' ? 'checked' : '' ?>>
                        <label for="red-editor">
                            <div class="admin-role-card__icon"><i class="fa-solid fa-pen-nib"></i></div>
                            <div class="admin-role-card__name">Editor</div>
                            <div class="admin-role-card__desc">Redacta y envía a revisión (flujo borrador→revisión).</div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- TIPO DE PERSONAL -->
        <div class="admin-modulos" style="margin-top:26px;">
            <label class="admin-form__label" style="margin-bottom:12px;">
                <i class="fa-solid fa-id-badge"></i> Tipo de personal <span style="font-weight:400;color:var(--text-gray);">(combinable)</span>
            </label>
            <div class="admin-modulos__grid">
                <?php foreach ($TIPOS as $key => $t): ?>
                <label class="admin-modulo-check">
                    <input type="checkbox" name="tipo_personal[]" value="<?= $key ?>" <?= in_array($key, $tiposSel, true) ? 'checked' : '' ?>>
                    <span class="admin-modulo-check__box">
                        <span class="admin-modulo-check__icon"><i class="fa-solid <?= $t['icon'] ?>"></i></span>
                        <span class="admin-modulo-check__text">
                            <span class="admin-modulo-check__name"><?= $t['nombre'] ?></span>
                            <span class="admin-modulo-check__desc"><?= $t['desc'] ?></span>
                        </span>
                        <span class="admin-modulo-check__tick"><i class="fa-solid fa-check"></i></span>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- Solo aplica al personal docente: el JS lo muestra si "Profesor" está marcado -->
            <div class="admin-suplir-toggle" id="suplir-toggle" <?= in_array('profesor', $tiposSel, true) ? '' : 'hidden' ?>>
                <label class="admin-switch-row admin-switch-row--danger">
                    <input type="checkbox" name="no_puede_suplir" value="1" <?= $puedeSupl ? '' : 'checked' ?>>
                    <span class="admin-switch-row__track"><span class="admin-switch-row__knob"></span></span>
                    <span class="admin-switch-row__text">
                        <strong><i class="fa-solid fa-ban"></i> No puede suplir a otros profesores</strong>
                        <small>Actívalo si este profesor nunca debe aparecer como suplente disponible.</small>
                    </span>
                </label>
            </div>
        </div>
    </div>
