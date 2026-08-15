<?php
/**
 * Aviso efímero de Alex tras una acción. Lo anima y lo retira solo
 * `src/js/admin/admin-toast.js` (5,6 s), que se activa por el id `#alexToast` y no
 * lleva guarda de página.
 *
 * El ciclo del panel es siempre POST → redirect → query param: no hay endpoints JSON de
 * escritura, así que "avisar sin recargar" no aplica. Lo que faltaba en algunos módulos
 * era simplemente leer el parámetro y pintar esto.
 *
 * Uso:
 *   $toast = ['title' => '¡Listo!', 'msg' => 'Se guardó.', 'icon' => 'fa-check', 'color' => '#34a853'];
 *   include __DIR__ . '/../_toast.php';
 *
 * Este markup estaba copiado a mano en diez vistas del panel. Las que quedan se migran
 * cuando se toque cada una; lo nuevo entra por aquí.
 *
 * @var array{title:string,msg:string,icon:string,color:string}|null $toast
 */
if (empty($toast)) return;
$_tEsc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div id="alexToast" class="at-wrap" role="alert" aria-live="polite">
    <span class="at-stripe" style="background:<?= $_tEsc($toast['color'] ?? '#4267ac') ?>;"></span>
    <img src="/build/assets/img/alex/alex-mano.png" alt="Alex" class="at-alex">
    <div class="at-body">
        <p class="at-title" style="color:<?= $_tEsc($toast['color'] ?? '#4267ac') ?>;">
            <i class="fa-solid <?= $_tEsc($toast['icon'] ?? 'fa-circle-check') ?>"></i>
            <?= $_tEsc($toast['title'] ?? '') ?>
        </p>
        <p class="at-msg"><?= $_tEsc($toast['msg'] ?? '') ?></p>
    </div>
    <button class="at-close" onclick="cerrarAlexToast()" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
    <span class="at-bar" style="background:<?= $_tEsc($toast['color'] ?? '#4267ac') ?>;"></span>
</div>
