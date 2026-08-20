<?php $paginaVista = 'blog-suplencias-trabajo-pendiente'; ?>
<?php
/**
 * Cola de "¿dejó trabajo para el grupo?" sin revisar.
 *
 * @var \Model\SuplenciaHora[] $pendientes
 *
 * Existe porque el dato no tenía dónde rellenarse: había que recordar en qué suplencia
 * estaba cada hora y abrirlas una a una desde la agenda. El único indicio era una cifra
 * en el KPI del tablero, que además prefectura no ve.
 *
 * Se agrupa POR DÍA porque así es como se revisa: prefectura recuerda "el martes" y de
 * ahí saca las clases, no al revés.
 */
$porDia = [];
foreach ($pendientes as $h) {
    $porDia[(string)$h->s_fecha][] = $h;
}

$MESES = ['01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril',
          '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
          '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'];
$DIAS  = ['Sun' => 'Domingo', 'Mon' => 'Lunes', 'Tue' => 'Martes', 'Wed' => 'Miércoles',
          'Thu' => 'Jueves', 'Fri' => 'Viernes', 'Sat' => 'Sábado'];

$total = count($pendientes);

$toast = null;
if (isset($_GET['trabajo'])) {
    $toast = ['title' => 'Registrado', 'msg' => 'Queda anotado si dejó trabajo para el grupo.',
              'icon' => 'fa-clipboard-check', 'color' => '#34a853'];
}
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">Trabajo por revisar</span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">

            <div class="tpe-intro">
                <img src="/build/assets/img/alex/bby-alex-saluda.png" alt="Alex" class="tpe-intro__alex">
                <div class="tpe-intro__body">
                    <?php /* El titular dice QUÉ se revisa y la cifra baja al chip: antes el H2 era
                             "43 clases sin revisar", que ocupa el sitio del sentido con un número. */ ?>
                    <div class="tpe-intro__head">
                        <h2 class="tpe-intro__t">
                            <?= $total === 0 ? 'Todo revisado' : '¿Dejaron trabajo para el grupo?' ?>
                        </h2>
                        <?php if ($total > 0): ?>
                        <span class="tpe-intro__c">
                            <?= $total ?> <?= $total === 1 ? 'clase pendiente' : 'clases pendientes' ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <p class="tpe-intro__p">
                        Cuando un profesor falta, debería dejar material o indicaciones para que el
                        grupo siga trabajando. Aquí confirmas, clase por clase, si lo dejó — y si
                        escribió alguna nota al avisar de su ausencia, la verás en la fila.
                    </p>
                    <?php /* El matiz NULL vs 0 es lo que hace que el porcentaje del tablero
                             signifique algo, pero no necesita el bloque principal. */ ?>
                    <p class="tpe-intro__fine">
                        «Sin revisar» no es «no dejó»: solo lo que marques cuenta en las estadísticas.
                        Las guardias de patio no aparecen: ahí no hay trabajo que dejar.
                    </p>
                </div>
            </div>

            <?php if ($total === 0): ?>
            <div class="admin-empty-state tpe-vacio">
                <p class="admin-empty-state__text">
                    <i class="fa-solid fa-clipboard-check"></i>
                    Todas las clases con ausencia ya tienen registrado si se dejó trabajo.
                </p>
            </div>
            <?php else: ?>

            <?php foreach ($porDia as $fecha => $horas): ?>
            <?php
            $ts    = strtotime($fecha);
            $dia   = $DIAS[date('D', $ts)] ?? '';
            $mes   = $MESES[date('m', $ts)] ?? '';
            // Cuántos días lleva esperando: lo que más tiempo lleva es lo que peor se
            // recuerda, y por eso se avisa en vez de dejarlo como una fecha más.
            $dias  = (int)floor((strtotime('today') - strtotime($fecha)) / 86400);
            ?>
            <section class="admin-panel tpe-dia">
                <div class="admin-panel__header tpe-dia__head">
                    <h2 class="admin-panel__title">
                        <i class="fa-regular fa-calendar"></i>
                        <?= s($dia) ?> <?= (int)date('j', $ts) ?> de <?= s($mes) ?>
                    </h2>
                    <span class="tpe-dia__meta">
                        <?= count($horas) ?> <?= count($horas) === 1 ? 'clase' : 'clases' ?>
                        <?php if ($dias >= 7): ?>
                        <span class="tpe-viejo" title="Lleva <?= $dias ?> días sin revisar">
                            <i class="fa-solid fa-triangle-exclamation"></i> hace <?= $dias ?> días
                        </span>
                        <?php endif; ?>
                    </span>
                </div>

                <ul class="tpe-list">
                    <?php foreach ($horas as $h): ?>
                    <li class="tpe-row">
                        <div class="tpe-row__when">
                            <strong><?= s(substr((string)$h->periodo_inicio, 0, 5)) ?></strong>
                            <small><?= s(substr((string)$h->periodo_fin, 0, 5)) ?></small>
                        </div>
                        <div class="tpe-row__what">
                            <span class="tpe-row__mat"><?= s($h->materia ?: 'Clase') ?></span>
                            <span class="tpe-row__sub">
                                <?php if ($h->grupo_nombre): ?><?= s($h->grupo_nombre) ?> · <?php endif; ?>
                                Faltó <strong><?= s($h->ausente_nombre ?: '—') ?></strong>
                                <?php if ($h->suplente_nombre): ?>
                                    · cubrió <?= s($h->suplente_nombre) ?>
                                <?php else: ?>
                                    · <em>sin suplente</em>
                                <?php endif; ?>
                            </span>
                            <?php /* Las indicaciones que el ausente escribió al avisar. Es el dato
                                     con el que se responde la pregunta de esta pantalla, y hasta
                                     ahora obligaba a abrir la suplencia una por una.

                                     La nota es de la SUPLENCIA, no de la hora, así que se repite en
                                     todas las horas de la misma ausencia. Es correcto: se marca fila
                                     a fila y hay que tenerla delante en cada una. No se deduplica
                                     porque dos ausencias del mismo día se intercalan por hora y las
                                     filas no quedan contiguas. */ ?>
                            <?php if (trim((string)$h->s_notas) !== ''): ?>
                            <p class="tpe-nota" title="<?= s($h->s_notas) ?>">
                                <i class="fa-regular fa-note-sticky"></i>
                                <span><?= s($h->s_notas) ?></span>
                            </p>
                            <?php else: ?>
                            <?php /* El caso negativo también se dice: "no escribió nada" y "no se
                                     cargó el dato" son la misma pantalla en blanco, y esa duda es
                                     justo la que impide marcar con confianza. */ ?>
                            <p class="tpe-nota tpe-nota--sin">El profesor no dejó indicaciones al avisar.</p>
                            <?php endif; ?>
                        </div>
                        <div class="tpe-row__acts">
                            <?php foreach ([['1', 'Sí dejó', 'si'], ['0', 'No dejó', 'no']] as [$val, $txt, $cls]): ?>
                            <form method="POST" action="/dashboard/suplencias/trabajo">
                                <input type="hidden" name="id" value="<?= (int)$h->suplencia_id ?>">
                                <input type="hidden" name="hora_id" value="<?= (int)$h->id ?>">
                                <input type="hidden" name="dejo" value="<?= $val ?>">
                                <?php /* Vuelve a la cola en vez de a la suplencia: aquí se marcan
                                         varias seguidas y saltar a `agendar` rompería el repaso. */ ?>
                                <input type="hidden" name="volver" value="cola">
                                <button type="submit" class="tpe-btn tpe-btn--<?= $cls ?>"><?= $txt ?></button>
                            </form>
                            <?php endforeach; ?>
                            <a href="/dashboard/suplencias/agendar?id=<?= (int)$h->suplencia_id ?>"
                               class="tpe-btn tpe-btn--ver" title="Abrir la suplencia completa">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endforeach; ?>

            <?php endif; ?>

        </main>
    </div>
</div>
<?php if ($toast) include __DIR__ . '/../_toast.php'; ?>
