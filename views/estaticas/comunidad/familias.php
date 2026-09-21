<?php $paginaVista = 'estaticas-comunidad-familias'; ?>
<?php
// ── EVENTOS DINÁMICOS (desde el módulo Eventos del panel) ─────────
// El controlador pasa $eventosCal ya aplanado, con el icono, el color y la etiqueta
// pública de cada evento RESUELTOS en PHP (EstaticasController::aplanarEventos()).
// Si no hay eventos, la página sale sin avisos y con el calendario limpio: no hay
// datos de muestra.
//
// ⚠️ Aquí había una tabla `$tipoMeta` propia con OTROS cinco colores que los del
// calendario de al lado, así que el mismo evento salía morado en su tarjeta de aviso
// y azul en su punto del calendario. La fuente única es ahora `Evento::TIPO_COLOR`,
// que comparten el panel, esta página y el PDF del ciclo.
$eventos = $eventosCal ?? [];
$mesesEs = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$ciclo   = $ciclo   ?? \Model\Evento::ciclo();
$niveles = \Model\Materia::NIVELES;
?>
<main id="main-content" class="fam">

    <!-- ── HERO ─────────────────────────────────── -->
    <section class="fam__hero">
        <?php $bg_scene = 'bosque'; $bg_colores = ['#46bdc6', '#4D8ABB', '#7DC6E5', '#374C69', '#F1C400']; include __DIR__ . '/_bg.php'; ?>
        <div class="fam__hero-inner">
            <div class="fam__hero-text" data-fam-reveal>
                <span class="fam__eyebrow"><i class="fa-solid fa-people-roof"></i> <span data-i18n="comunidad-familias.eyebrow">Familias Bilbao</span></span>
                <h1 class="fam__title"><span data-i18n="comunidad-familias.titleA">¡Bienvenidas,</span><br><span data-i18n="comunidad-familias.titleB">familias!</span></h1>
                <p class="fam__lead" data-i18n="comunidad-familias.lead">
                    Aquí encontrarás los avisos importantes y el calendario del colegio,
                    para que nunca te pierdas de nada.
                </p>
                <div class="fam__hero-chips">
                    <span class="fam__chip"><i class="fa-solid fa-bullhorn"></i> <span data-i18n="comunidad-familias.chipAvisos">Avisos</span></span>
                    <span class="fam__chip"><i class="fa-solid fa-calendar-days"></i> <span data-i18n="comunidad-familias.chipCal">Calendario</span></span>
                </div>
            </div>
            <div class="fam__hero-art" data-fam-reveal>
                <span class="fam__hero-halo"></span>
                <img src="/build/assets/img/alex/fam-alex-surp.png" alt="Familia Alex" data-i18n-attr="alt:comunidad-familias.alexAlt" class="fam__hero-img" loading="lazy">
            </div>
        </div>
    </section>

    <!-- ── AVISOS ───────────────────────────────── -->
    <?php if ($eventos): ?>
    <section class="fam__section">
        <div class="fam__section-head">
            <h2 class="fam__section-title"><i class="fa-solid fa-bullhorn"></i> <span data-i18n="comunidad-familias.avisosTitle">Avisos del colegio</span></h2>
            <p class="fam__section-sub" data-i18n="comunidad-familias.avisosSub">Lo último que necesitas saber esta temporada.</p>
        </div>

        <div class="fam__avisos">
            <?php foreach ($eventos as $a):
                $ts  = strtotime($a['fecha']);
                $dia = date('d', $ts);
                $mes = $mesesEs[(int)date('n', $ts) - 1];
            ?>
            <article class="fam-aviso" style="--aviso-color: <?= s($a['color']) ?>;">
                <div class="fam-aviso__date">
                    <span class="fam-aviso__day"><?= $dia ?></span>
                    <span class="fam-aviso__month"><?= mb_substr($mes, 0, 3) ?></span>
                </div>
                <div class="fam-aviso__body">
                    <span class="fam-aviso__pill"><i class="fa-solid <?= s($a['icono']) ?>"></i> <?= s($a['etiqueta']) ?></span>
                    <h3 class="fam-aviso__title"><?= s($a['titulo']) ?></h3>
                    <?php if ($a['desc'] !== ''): ?><p class="fam-aviso__text"><?= s($a['desc']) ?></p><?php endif; ?>
                    <?php /* A quién va dirigido. Sin niveles es todo el colegio y no se
                             pinta nada: un chip «Todo el colegio» en cada tarjeta sería
                             ruido en la mayoría de ellas. */ ?>
                    <?php if ($a['niveles']): ?>
                    <p class="fam-aviso__nivs">
                        <?php foreach ($a['niveles'] as $n): ?><span class="fam-nivtag"><?= s($n) ?></span><?php endforeach; ?>
                    </p>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ── ALGEBRAIX ────────────────────────────── -->
    <?php /* Acceso directo al sistema de gestión escolar que las familias ya usan
             (calificaciones, pagos, boletas). Va ARRIBA del calendario a propósito: es la
             razón nº1 por la que una familia entra a esta página, y debajo del calendario
             habría que bajar dos pantallas para encontrarlo.

             Enlace externo y de sesión ajena: `target="_blank"` para no sacar a la
             familia del sitio, y `rel="noopener noreferrer"` porque sin `noopener` la
             pestaña destino puede reescribir esta vía `window.opener`. */ ?>
    <section class="fam__section fam__section--alg">
        <a class="fam-alg" href="https://www.algebraix.com/iniciar_sesion"
           target="_blank" rel="noopener noreferrer">
            <span class="fam-alg__art" aria-hidden="true">
                <span class="fam-alg__halo"></span>
                <img src="/build/assets/img/alex/fam-alex.png" alt="" class="fam-alg__alex" loading="lazy">
            </span>
            <span class="fam-alg__body">
                <span class="fam-alg__pill">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span data-i18n="comunidad-familias.algPill">Plataforma escolar</span>
                </span>
                <span class="fam-alg__title" data-i18n="comunidad-familias.algTitle">Entra a Algebraix</span>
                <span class="fam-alg__text" data-i18n="comunidad-familias.algText">
                    Consulta calificaciones, boletas y pagos en el sistema de gestión del colegio.
                </span>
            </span>
            <span class="fam-alg__cta">
                <span data-i18n="comunidad-familias.algCta">Iniciar sesión</span>
                <i class="fa-solid fa-arrow-right"></i>
            </span>
        </a>
    </section>

    <!-- ── CALENDARIO ───────────────────────────── -->
    <?php /* El id es el ancla a la que enlaza el panel («Ver el calendario publicado»). */ ?>
    <section class="fam__section fam__section--cal" id="calendario"
             data-fam-cal
             data-ciclo='<?= s(json_encode($ciclo)) ?>'
             data-meses='<?= s(json_encode($mesesCiclo ?? \Model\Evento::mesesCiclo())) ?>'>

        <div class="fam__section-head">
            <h2 class="fam__section-title"><i class="fa-solid fa-calendar-days"></i> <span data-i18n="comunidad-familias.calTitle">Calendario escolar</span></h2>
            <p class="fam__section-sub" data-i18n="comunidad-familias.calSub">Haz clic en un día marcado para ver los detalles.</p>
        </div>

        <?php
        // ── BARRA DE FILTROS ──
        // Los chips de nivel son de selección MÚLTIPLE (una familia puede tener hijos en
        // dos niveles). «Todo el colegio» no es un nivel más: es el estado sin filtro, y
        // por eso apaga a los demás en vez de sumarse.
        //
        // ⚠️ Los eventos SIN niveles salen siempre, se filtre lo que se filtre: son del
        // colegio entero. La ayuda de debajo lo dice porque, si no, un filtro de Kinder
        // que sigue mostrando la junta general se lee como un filtro roto.
        ?>
        <div class="fam-calbar" data-cal-bar>
            <div class="fam-calbar__niveles" role="group" aria-label="Filtrar el calendario por nivel">
                <button type="button" class="fam-nivchip is-on" data-nivel="" aria-pressed="true">
                    <i class="fa-solid fa-school"></i> <span data-i18n="comunidad-familias.filtroTodos">Todo el colegio</span>
                </button>
                <?php foreach ($niveles as $n): ?>
                <button type="button" class="fam-nivchip" data-nivel="<?= s($n) ?>" aria-pressed="false"><?= s($n) ?></button>
                <?php endforeach; ?>
            </div>

            <div class="fam-calbar__acciones">
                <div class="fam-calvista" role="group" aria-label="Cambiar la vista del calendario">
                    <button type="button" class="fam-calvista__btn is-on" data-cal-vista="mes" aria-pressed="true">
                        <i class="fa-solid fa-calendar-day"></i> <span data-i18n="comunidad-familias.vistaMes">Mes</span>
                    </button>
                    <button type="button" class="fam-calvista__btn" data-cal-vista="ciclo" aria-pressed="false">
                        <i class="fa-solid fa-table-cells"></i> <span data-i18n="comunidad-familias.vistaCiclo">Ciclo completo</span>
                    </button>
                </div>

                <?php /* El botón solo existe si el módulo Eventos lo habilitó — y la ruta
                         comprueba lo mismo, así que esconderlo no es lo único que protege
                         el documento. El `href` lo reescribe el JS con los niveles activos:
                         se descarga lo que se está mirando. */ ?>
                <?php if (!empty($calendarioPdf)): ?>
                <a class="fam-caldesc" data-cal-descarga href="/comunidad/familias/calendario.pdf">
                    <i class="fa-solid fa-file-arrow-down"></i>
                    <span data-i18n="comunidad-familias.descargar">Descargar PDF</span>
                </a>
                <?php endif; ?>
            </div>

            <p class="fam-calbar__nota">
                <i class="fa-solid fa-circle-info"></i>
                <span data-i18n="comunidad-familias.filtroNota">Los eventos dirigidos a todo el colegio se muestran siempre.</span>
                <span class="fam-calbar__ciclo">· <span data-i18n="comunidad-familias.cicloLabel">Ciclo</span> <?= s($ciclo['etiqueta']) ?></span>
            </p>
        </div>

        <div class="fam__cal-wrap" data-cal-panel="mes">
            <div class="bilbao-cal" id="famCalendar"
                 data-events='<?= s(json_encode($eventos)) ?>'>
                <div class="bilbao-cal__header">
                    <button type="button" class="bilbao-cal__nav" data-cal-prev aria-label="Mes anterior" data-i18n-attr="aria-label:comunidad-familias.calPrev"><i class="fa-solid fa-chevron-left"></i></button>
                    <h3 class="bilbao-cal__month" data-cal-label>—</h3>
                    <button type="button" class="bilbao-cal__nav" data-cal-next aria-label="Mes siguiente" data-i18n-attr="aria-label:comunidad-familias.calNext"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
                <div class="bilbao-cal__weekdays">
                    <span data-i18n="comunidad-familias.dow.dom">Dom</span><span data-i18n="comunidad-familias.dow.lun">Lun</span><span data-i18n="comunidad-familias.dow.mar">Mar</span><span data-i18n="comunidad-familias.dow.mie">Mié</span><span data-i18n="comunidad-familias.dow.jue">Jue</span><span data-i18n="comunidad-familias.dow.vie">Vie</span><span data-i18n="comunidad-familias.dow.sab">Sáb</span>
                </div>
                <div class="bilbao-cal__grid" data-cal-grid></div>
                <div class="bilbao-cal__legend">
                    <span class="bilbao-cal__leg" data-type="festivo"><i></i> <span data-i18n="comunidad-familias.leg.festivo">Festivo</span></span>
                    <span class="bilbao-cal__leg" data-type="evento"><i></i> <span data-i18n="comunidad-familias.leg.evento">Evento</span></span>
                    <span class="bilbao-cal__leg" data-type="junta"><i></i> <span data-i18n="comunidad-familias.leg.junta">Junta</span></span>
                    <span class="bilbao-cal__leg" data-type="entrega"><i></i> <span data-i18n="comunidad-familias.leg.entrega">Entrega</span></span>
                    <span class="bilbao-cal__leg" data-type="suspension"><i></i> <span data-i18n="comunidad-familias.leg.suspension">Suspensión</span></span>
                </div>
            </div>

            <aside class="fam__cal-detail" data-cal-detail>
                <div class="fam__cal-detail-head">
                    <h4 class="fam__cal-detail-title" data-i18n="comunidad-familias.proximos">Próximos eventos</h4>
                    <button type="button" class="fam__cal-reset" data-cal-reset hidden>
                        <i class="fa-solid fa-arrow-left"></i> <span data-i18n="comunidad-familias.volverProximos">Próximos</span>
                    </button>
                </div>
                <ul class="fam__cal-detail-list" data-cal-list></ul>
            </aside>
        </div>

        <?php
        // ── VISTA DE CICLO COMPLETO ──
        // Doce tarjetas, una por mes del curso, cada una con su mini-rejilla y la lista
        // de sus eventos debajo. La lista no es redundante con la rejilla: en una celda
        // de 26px solo cabe un punto de color, y el evento hay que poder leerlo.
        //
        // Sin interacción obligatoria —todo está a la vista— porque es la vista que se
        // consulta de una pasada y la que se imprime; pulsar un día solo resalta sus
        // eventos en la lista de ese mes.
        //
        // La rellena el JS (`renderCiclo()`): tiene que responder a los chips de nivel,
        // y un render en servidor obligaría a recargar la página en cada filtro.
        ?>
        <div class="fam-ciclo" data-cal-panel="ciclo" hidden>
            <div class="fam-ciclo__head">
                <h3 class="fam-ciclo__titulo"><?= s($ciclo['etiqueta']) ?></h3>
                <p class="fam-ciclo__meta" data-ciclo-meta></p>
            </div>
            <div class="fam-ciclo__grid" data-ciclo-grid></div>
            <p class="fam-ciclo__vacio" data-ciclo-vacio hidden data-i18n="comunidad-familias.cicloVacio">
                No hay eventos publicados para este filtro en todo el ciclo.
            </p>
        </div>
    </section>

</main>

