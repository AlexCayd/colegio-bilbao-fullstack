/* estaticas-feedback-testimoniales
   Migrado desde el <script> embebido de views/estaticas/feedback-testimoniales.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'estaticas-feedback-testimoniales') return;
    (function(){
        var ta = document.getElementById('comentario');
        var ct = document.getElementById('char-count');
        if (!ta || !ct) return;
        function update(){ ct.textContent = ta.value.length; }
        ta.addEventListener('input', update);
        update();
    })();
})();
