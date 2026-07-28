<?php $paginaVista = 'blog-horarios-importar'; ?>
<?php
/** @var array $filas  @var array $resumen  @var array $alertas  @var int $importado */
$hayPrevia  = !empty($filas);
$importable = $hayPrevia ? (int)$resumen['total'] - (int)$resumen['errores'] : 0;
$iconoFila  = ['ok' => 'fa-circle-check', 'aviso' => 'fa-triangle-exclamation', 'error' => 'fa-circle-xmark'];
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">
                    <i class="fa-solid fa-file-csv"></i> Importar horarios
                    <span class="hoi-super"><i class="fa-solid fa-shield-halved"></i> Superadmin</span>
                </span>
            </div>
            <div class="admin-topbar__actions">
                <a href="/dashboard/horarios/importar?plantilla=1" class="admin-btn admin-btn--ghost">
                    <i class="fa-solid fa-download"></i> Plantilla CSV
                </a>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</button>
                </form>
            </div>
        </header>

        <main class="admin-content">

            <?php /* La carga es destructiva y afecta a todo el claustro del archivo:
                     conviene decirlo antes del formulario, no solo en la ayuda lateral. */ ?>
            <div class="hoi-aviso-super">
                <img src="/build/assets/img/alex/alex-cientifico.png" alt="Alex">
                <div>
                    <strong>Herramienta de superadmin</strong>
                    <span>Carga el horario de <em>cualquier</em> profesor. El archivo
                          <strong>reemplaza el horario completo</strong> de cada profesor que aparezca
                          en él; a los que no aparezcan no les pasa nada. Siempre podrás revisar la
                          vista previa antes de confirmar.</span>
                </div>
            </div>

            <?php if ($importado > 0): ?>
            <div class="admin-alerta admin-alerta--exito" style="margin-bottom:16px;">
                <i class="fa-solid fa-circle-check"></i> Se importaron <strong><?= $importado ?></strong> clases correctamente.
            </div>
            <?php endif; ?>

            <?php if (!empty($alertas['error'])): ?>
            <div class="admin-alerta admin-alerta--error" style="margin-bottom:16px;">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul class="admin-alerta__list"><?php foreach ($alertas['error'] as $e): ?><li><?= s($e) ?></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>

            <?php if (!$hayPrevia): ?>
            <!-- ══ Paso 1: subir el archivo ══ -->
            <div class="hoi-grid">
                <div class="admin-panel">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title"><i class="fa-solid fa-file-csv"></i> Sube el archivo</h2>
                    </div>
                    <div class="admin-form-section">
                        <form method="POST" action="/dashboard/horarios/importar" enctype="multipart/form-data">
                            <input type="hidden" name="_accion" value="previsualizar">

                            <div class="admin-form__group">
                                <label class="admin-file" data-file data-file-max="2">
                                    <input type="file" name="csv" accept=".csv,text/csv" required>
                                    <span class="admin-file__ico"><i class="fa-solid fa-file-arrow-up"></i></span>
                                    <span class="admin-file__text">
                                        <span class="admin-file__title" data-file-title>Elige un archivo CSV</span>
                                        <span class="admin-file__hint" data-file-hint>Codificación UTF-8 · máx. 2 MB</span>
                                    </span>
                                </label>
                            </div>

                            <div class="hoi-actions">
                                <button type="submit" class="admin-btn admin-btn--primary">
                                    <i class="fa-solid fa-magnifying-glass"></i> Revisar archivo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="admin-panel">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title"><i class="fa-solid fa-list-check"></i> Formato esperado</h2>
                    </div>
                    <div class="admin-form-section">
                        <p class="hoi-help">
                            Una fila por clase. La primera línea debe ser exactamente esta cabecera:
                        </p>
                        <pre class="hoi-code">profesor_email,dia,periodo,materia,grupo,aula
ana.torres@bilbao.edu.mx,lunes,1,Matemáticas,1A,A-101
ana.torres@bilbao.edu.mx,lunes,2,Matemáticas,2B,A-101</pre>
                        <ul class="hoi-rules">
                            <li><strong>profesor_email</strong> — debe existir ya como colaborador.</li>
                            <li><strong>dia</strong> — <code>lunes</code> … <code>viernes</code>, sin acentos.</li>
                            <li><strong>periodo</strong> — la etiqueta de la jornada o su número. Los recesos no admiten clase.</li>
                            <li><strong>materia</strong> — obligatoria; debe existir en el catálogo.</li>
                            <li><strong>grupo</strong> y <strong>aula</strong> — opcionales, pero si vienen deben existir.</li>
                        </ul>
                        <p class="hoi-warn">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            El archivo <strong>reemplaza el horario completo</strong> de cada profesor que aparezca en él.
                            El resto del claustro no se toca.
                        </p>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- ══ Paso 2: vista previa ══ -->
            <div class="hoi-summary">
                <div class="hoi-stat">
                    <span class="hoi-stat__n"><?= (int)$resumen['total'] ?></span>
                    <span class="hoi-stat__l">filas leídas</span>
                </div>
                <div class="hoi-stat hoi-stat--ok">
                    <span class="hoi-stat__n"><?= $importable ?></span>
                    <span class="hoi-stat__l">se importarán</span>
                </div>
                <div class="hoi-stat hoi-stat--warn">
                    <span class="hoi-stat__n"><?= (int)$resumen['avisos'] ?></span>
                    <span class="hoi-stat__l">con avisos</span>
                </div>
                <div class="hoi-stat hoi-stat--bad">
                    <span class="hoi-stat__n"><?= (int)$resumen['errores'] ?></span>
                    <span class="hoi-stat__l">con errores</span>
                </div>
                <div class="hoi-stat">
                    <span class="hoi-stat__n"><?= (int)$resumen['profesores'] ?></span>
                    <span class="hoi-stat__l">profesores afectados</span>
                </div>
            </div>

            <div class="admin-panel">
                <div class="admin-panel__header hor-head">
                    <h2 class="admin-panel__title"><i class="fa-solid fa-table-list"></i> Vista previa</h2>
                    <div class="hoi-filter">
                        <label class="hoi-filter__opt">
                            <input type="checkbox" data-solo-problemas> Ver solo filas con problemas
                        </label>
                    </div>
                </div>

                <div class="hoi-table-wrap">
                    <table class="admin-table hoi-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Estado</th>
                                <th>Profesor</th>
                                <th>Día</th>
                                <th>Hora</th>
                                <th>Materia</th>
                                <th>Grupo</th>
                                <th>Aula</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($filas as $f): ?>
                            <tr class="hoi-row hoi-row--<?= s($f['estado']) ?>" data-estado="<?= s($f['estado']) ?>">
                                <td class="hoi-row__n"><?= (int)$f['linea'] ?></td>
                                <td>
                                    <span class="hoi-chip hoi-chip--<?= s($f['estado']) ?>" <?= $f['motivo'] ? 'title="' . s($f['motivo']) . '"' : '' ?>>
                                        <i class="fa-solid <?= $iconoFila[$f['estado']] ?>"></i>
                                        <?= $f['estado'] === 'ok' ? 'Correcta' : ($f['estado'] === 'aviso' ? 'Aviso' : 'Error') ?>
                                    </span>
                                    <?php if ($f['motivo']): ?>
                                    <span class="hoi-motivo"><?= s($f['motivo']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= s($f['email']) ?></td>
                                <td><?= s(\Model\Horario::DIAS_LABEL[$f['dia']] ?? $f['dia']) ?></td>
                                <td><?= s($f['periodo']) ?></td>
                                <td><?= s($f['materia']) ?></td>
                                <td><?= $f['grupo'] !== '' ? s($f['grupo']) : '<span class="hoi-nil">—</span>' ?></td>
                                <td><?= $f['aula'] !== '' ? s($f['aula']) : '<span class="hoi-nil">—</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="hoi-actions hoi-actions--footer">
                    <form method="POST" action="/dashboard/horarios/importar">
                        <input type="hidden" name="_accion" value="cancelar">
                        <button type="submit" class="admin-btn admin-btn--ghost"><i class="fa-solid fa-xmark"></i> Cancelar</button>
                    </form>
                    <form method="POST" action="/dashboard/horarios/importar">
                        <input type="hidden" name="_accion" value="confirmar">
                        <button type="submit" class="admin-btn admin-btn--primary" <?= $importable ? '' : 'disabled' ?>>
                            <i class="fa-solid fa-file-import"></i> Importar <?= $importable ?> clase<?= $importable === 1 ? '' : 's' ?>
                        </button>
                    </form>
                </div>
                <?php if ((int)$resumen['errores'] > 0): ?>
                <p class="hoi-note">
                    Las <?= (int)$resumen['errores'] ?> filas con error se omiten. Corrige el archivo y vuelve a subirlo
                    si quieres importarlas también.
                </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>
