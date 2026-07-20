/* blog-perfil
   Migrado desde el <script> embebido de views/blog/perfil.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-perfil') return;
    document.getElementById('avatar')?.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            const preview = document.getElementById('avatar-preview');
            if (preview) preview.innerHTML = `<img src="${e.target.result}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
        };
        reader.readAsDataURL(file);
    });

    /* ── Ojo toggle contraseñas ── */
    function eyeToggle(btnId, inputId, iconId) {
        document.getElementById(btnId)?.addEventListener('click', function () {
            const inp = document.getElementById(inputId);
            const ico = document.getElementById(iconId);
            if (!inp) return;
            const show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            if (ico) ico.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
            this.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    }
    eyeToggle('togglePwPerfil', 'password', 'eyePerfil');
    eyeToggle('togglePwConfirm', 'password_confirm', 'eyeConfirm');

    /* ── Fortaleza de contraseña ── */
    (function () {
        const pwInput      = document.getElementById('password');
        const confirmGroup = document.getElementById('confirm-group');
        const confirmInput = document.getElementById('password_confirm');
        const confirmMsg   = document.getElementById('confirm-msg');
        const spBar        = document.getElementById('sp-bar');
        const spLabel      = document.getElementById('sp-label');
        const segs         = [document.getElementById('sp1'), document.getElementById('sp2'), document.getElementById('sp3'), document.getElementById('sp4')];
        const submitBtn    = document.querySelector('[form="form-perfil"]') || document.querySelector('#form-perfil [type="submit"]');

        const LEVELS = [
            { color: '#ef4444', label: 'Contraseña débil' },
            { color: '#f97316', label: 'Contraseña regular' },
            { color: '#eab308', label: 'Contraseña buena' },
            { color: '#22c55e', label: 'Contraseña fuerte' },
        ];

        function evalStrength(pw) {
            let score = 0;
            if (pw.length >= 8)                score++;
            if (/[A-Z]/.test(pw))              score++;
            if (/[0-9]/.test(pw))              score++;
            if (/[^A-Za-z0-9]/.test(pw))       score++;
            return score;
        }

        function updateBar(score) {
            segs.forEach((s, i) => {
                s.style.background = i < score ? LEVELS[score - 1].color : '#E2E8F0';
            });
            if (spLabel) {
                spLabel.textContent = score > 0 ? LEVELS[score - 1].label : '';
                spLabel.style.color = score > 0 ? LEVELS[score - 1].color : '#94A3B8';
            }
        }

        function checkMatch() {
            if (!confirmInput || !confirmMsg) return true;
            const pw = pwInput ? pwInput.value : '';
            const cf = confirmInput.value;
            if (cf === '') { confirmMsg.textContent = ''; return pw === ''; }
            const match = pw === cf;
            confirmMsg.textContent = match ? '✓ Las contraseñas coinciden' : '✗ Las contraseñas no coinciden';
            confirmMsg.style.color = match ? '#22c55e' : '#ef4444';
            return match;
        }

        if (pwInput) {
            pwInput.addEventListener('input', function () {
                const pw = this.value;
                const hasContent = pw.length > 0;
                if (spBar)        spBar.style.display = hasContent ? 'flex' : 'none';
                if (spLabel)      spLabel.style.display = hasContent ? 'block' : 'none';
                if (confirmGroup) confirmGroup.style.display = hasContent ? 'block' : 'none';
                if (hasContent) updateBar(evalStrength(pw));
                checkMatch();
            });
        }

        if (confirmInput) {
            confirmInput.addEventListener('input', function () {
                const ok = checkMatch();
                if (submitBtn) submitBtn.disabled = !ok;
            });
        }
    })();
})();
