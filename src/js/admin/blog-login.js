/* blog-login
   Migrado desde el <script> embebido de views/blog/login.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-login') return;
    document.getElementById('togglePassword')?.addEventListener('click',function(){
        const inp=document.getElementById('password');
        const ico=document.getElementById('eyeIcon');
        const show=inp.type==='password';
        inp.type=show?'text':'password';
        ico.className=show?'fa-regular fa-eye-slash':'fa-regular fa-eye';
        this.setAttribute('aria-label',show?'Ocultar contraseña':'Mostrar contraseña');
    });
})();
