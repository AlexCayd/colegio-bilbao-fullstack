<?php
/**
 * Campos de Perfil y permisos, compartido por crear.php y editar.php.
 * Espera definidas: $modsSel(array), $rolActual, $rolRed, $tiposSel(array), $puedeSupl(bool),
 * $nivelesSel(array).
 * Abre .admin-panel + .admin-form-section pero NO los cierra: el include continúa con el footer.
 *
 * ── Por qué dos pasos y dos lenguajes visuales ──
 * Antes los dos bloques (tipo de personal y módulos) se pintaban con la MISMA tarjeta
 * `.admin-modulo-check`, y sus nombres son homónimos: el módulo «Profesores» es un
 * directorio que se consulta, el tipo «Profesor» es lo que alguien ES. Con el mismo
 * aspecto no había forma de saber qué se estaba respondiendo. Ahora:
 *
 *   1 · ¿Quién es?    → tarjetas de identidad grandes, con el color del tipo
 *   2 · ¿A qué entra? → chips bajos de permiso, con casilla visible
 *
 * Y la identidad va PRIMERO porque condiciona todo lo demás (niveles, puede-suplir,
 * y qué módulos tienen sentido para esa persona).
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

// ⚠️ Los cuatro directorios de personal y Soporte técnico YA NO SE MARCAN: son
// transversales (UsuarioBlog::MODULOS_TRANSVERSALES) y los tiene todo el mundo, así que
// no están en MODULOS_ASIGNABLES y el filtro de abajo los deja fuera solo. Con ellos se
// fue el rótulo «Directorio · X» que los desambiguaba del tipo de personal homónimo, y
// la categoría «Ayuda» entera, que se queda sin claves.

// El orden va alineado con UsuarioBlog::normalizarTipoPersonal(), que es quien
// decide en qué orden se guarda el CSV y salen los chips del listado. El color es el
// mismo de los chips de views/blog/usuarios/index.php, así que la ficha y el listado
// hablan del mismo tipo con el mismo color.
$TIPOS = [
    'administrativo' => ['nombre' => 'Administrativo', 'desc' => 'Personal administrativo; no cubre suplencias.',             'icon' => 'fa-user-tie',        'color' => '#4267ac'],
    'profesor'       => ['nombre' => 'Profesor',       'desc' => 'Imparte clases; puede solicitar y cubrir suplencias.',      'icon' => 'fa-chalkboard-user', 'color' => '#fc6722'],
    'prefecto'       => ['nombre' => 'Prefecto',       'desc' => 'Agenda y coordina suplencias; no las cubre.',               'icon' => 'fa-user-shield',     'color' => '#aa2296'],
    'directivo'      => ['nombre' => 'Directivo',      'desc' => 'Coordina suplencias y resuelve justificantes. Solo lectura en usuarios, horarios y catálogos.', 'icon' => 'fa-user-gear', 'color' => '#34a853'],
];
// Niveles en los que imparte. Cada nivel tiene su propia jornada (entra, sale y
// descansa a su hora), así que declararlos acota su rejilla y ordena a los candidatos
// a suplencia. El color sale de `Materia::NIVEL_COLOR` — fuente única, la misma que
// pintan las tabs de Grupos y los chips de Eventos; estuvo copiada a mano aquí.
$NIVEL_COLOR = \Model\Materia::NIVEL_COLOR;
$nivelesSel = $nivelesSel ?? [];
$EXCLUYENTES = \Model\UsuarioBlog::TIPOS_EXCLUYENTES;
?>
<div class="admin-panel">
    <div class="admin-panel__header">
        <h2 class="admin-panel__title">Perfil y permisos</h2>
    </div>
    <div class="admin-form-section" id="permisos-section">

        <!-- ═════════ PASO 1 · ¿QUIÉN ES? ═════════ -->
        <div class="admin-form-step">
            <span class="admin-form-step__n">1</span>
            <div class="admin-form-step__txt">
                <strong>¿Quién es en el colegio?</strong>
                <small>Su puesto. Decide si tiene horario, si cubre suplencias y si coordina a otros.</small>
            </div>
        </div>

        <!-- TIPO DE PERSONAL -->
        <div class="admin-identidad">
            <label class="admin-form__label">
                <i class="fa-solid fa-id-badge"></i> Tipo de personal
            </label>
            <span class="admin-form__hint admin-form__hint--pre">
                <strong>Profesor</strong> y <strong>Administrativo</strong> se combinan entre sí.
                <strong>Prefecto</strong> y <strong>Directivo</strong> son excluyentes: no se combinan con nada.
            </span>
            <div class="admin-tipo-grid">
                <?php foreach ($TIPOS as $key => $t): ?>
                <?php /* `data-modulos` viaja desde UsuarioBlog::MODULOS_SUGERIDOS para que
                          el JS no tenga su propia copia de la lista: marcar un tipo
                          preselecciona los módulos con los que ese puesto trabaja. */ ?>
                <label class="admin-tipo-card" style="--c:<?= $t['color'] ?>;"
                       data-modulos="<?= s(implode(',', \Model\UsuarioBlog::MODULOS_SUGERIDOS[$key] ?? [])) ?>"
                       <?= in_array($key, $EXCLUYENTES, true) ? 'data-excluyente="1"' : '' ?>>
                    <input type="checkbox" name="tipo_personal[]" value="<?= $key ?>" <?= in_array($key, $tiposSel, true) ? 'checked' : '' ?>>
                    <span class="admin-tipo-card__box">
                        <span class="admin-tipo-card__icon"><i class="fa-solid <?= $t['icon'] ?>"></i></span>
                        <span class="admin-tipo-card__name"><?= $t['nombre'] ?></span>
                        <span class="admin-tipo-card__desc"><?= $t['desc'] ?></span>
                        <?php if (in_array($key, $EXCLUYENTES, true)): ?>
                        <span class="admin-tipo-card__tag">Excluyente</span>
                        <?php endif; ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>

            <?php /* NIVELES — lo revela el JS para PROFESOR y para DIRECTIVO, que son los
                     dos puestos que usan la columna, aunque signifique cosas distintas:
                     un profesor IMPARTE esos niveles, una dirección los GESTIONA.
                     El texto se alterna en cliente (dos <span>, sin interpolar PHP en JS). */ ?>
            <?php $nivelesVisible = (bool)array_intersect(['profesor', 'directivo'], $tiposSel); ?>
            <div class="admin-subcampo" id="niveles-group" <?= $nivelesVisible ? '' : 'hidden' ?>>
                <label class="admin-form__label" style="margin-bottom:12px;">
                    <i class="fa-solid fa-layer-group"></i>
                    <span data-niveles-label="profesor"<?= in_array('directivo', $tiposSel, true) ? ' hidden' : '' ?>>Niveles que imparte</span>
                    <span data-niveles-label="directivo"<?= in_array('directivo', $tiposSel, true) ? '' : ' hidden' ?>>Niveles que dirige</span>
                    <span style="font-weight:400;color:var(--text-gray);">(uno o varios)</span>
                </label>
                <div class="admin-niveles-grid">
                    <?php foreach (\Model\Materia::NIVELES as $n): ?>
                    <label class="admin-nivel-check" style="--c:<?= $NIVEL_COLOR[$n] ?? '#94a3b8' ?>;">
                        <input type="checkbox" name="niveles[]" value="<?= s($n) ?>" <?= in_array($n, $nivelesSel, true) ? 'checked' : '' ?>>
                        <span class="admin-nivel-check__box">
                            <span class="admin-nivel-check__dot"></span>
                            <span class="admin-nivel-check__name"><?= s($n) ?></span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <span class="admin-form__hint" style="margin-top:10px;display:block;"
                      data-niveles-hint="profesor"<?= in_array('directivo', $tiposSel, true) ? ' hidden' : '' ?>>
                    Cada nivel tiene su propia jornada, con horas de entrada y recesos distintos.
                    Declararlos hace que su horario se muestre sobre la jornada correcta y que
                    aparezca primero al buscar quién cubre una clase de ese nivel.
                    Si lo dejas vacío se deducen de sus clases.
                </span>
                <?php /* Para una dirección el campo NO es opcional en el mismo sentido:
                          vacío no significa "se deduce" sino "ve el colegio entero", que
                          es justo lo contrario de lo que se busca al crear una dirección
                          de nivel. Hay que decirlo o se creará sin alcance sin querer. */ ?>
                <span class="admin-form__hint" style="margin-top:10px;display:block;"
                      data-niveles-hint="directivo"<?= in_array('directivo', $tiposSel, true) ? '' : ' hidden' ?>>
                    Acota lo que verá: su tablero, su agenda de suplencias, los intercambios
                    y la cola de justificantes se limitan a estos niveles.
                    <strong>Déjalo vacío solo si debe ver todo el colegio.</strong>
                </span>
            </div>

            <!-- Solo aplica al personal docente: el mismo JS lo muestra si "Profesor" está marcado -->
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

        <!-- ═════════ PASO 2 · ¿A QUÉ ENTRA? ═════════ -->
        <div class="admin-form-step admin-form-step--sep">
            <span class="admin-form-step__n">2</span>
            <div class="admin-form-step__txt">
                <strong>¿A qué puede entrar en el panel?</strong>
                <small>El administrador entra a todo. El usuario, solo a lo que marques aquí abajo.</small>
            </div>
        </div>

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
            <?php /* Marcar un tipo de personal preselecciona sus módulos habituales
                     (UsuarioBlog::MODULOS_SUGERIDOS). Se anuncia porque cambiar casillas
                     en silencio deja al admin sin saber si el formulario le ayuda o le
                     estorba. Nace oculto y lo revela admin-usuario-permisos.js. */ ?>
            <p class="admin-sugerido" id="modulos-sugeridos-aviso" role="status" hidden></p>
            <?php foreach ($MODS_GRP as $grp):
                $claves = array_values(array_filter($grp['claves'], fn($k) => in_array($k, $ASIGNABLES, true)));
                if (!$claves) continue; ?>
            <div class="admin-modulos__cat">
                <span class="admin-modulos__cat-label"><i class="fa-solid <?= $grp['icon'] ?>"></i> <?= $grp['label'] ?></span>
                <div class="admin-mod-grid">
                    <?php foreach ($claves as $key): $m = $MODS_CAT[$key]; ?>
                    <?php /* Solo el nombre. La descripción se leía una vez, al aprender el
                             panel, y luego eran trece líneas de texto entre el admin y las
                             casillas que venía a marcar. Sigue disponible en el `title`. */ ?>
                    <label class="admin-mod-chip" data-modulo="<?= $key ?>" title="<?= s($m['nombre'] . ' — ' . $m['desc']) ?>">
                        <input type="checkbox" name="modulos[]" value="<?= $key ?>" <?= in_array($key, $modsSel, true) ? 'checked' : '' ?>>
                        <span class="admin-mod-chip__box"><i class="fa-solid fa-check"></i></span>
                        <span class="admin-mod-chip__icon"><i class="fa-solid <?= $m['icon'] ?>"></i></span>
                        <span class="admin-mod-chip__name"><?= s($m['nombre']) ?></span>
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
    </div>
