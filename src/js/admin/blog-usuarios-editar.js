/* blog-usuarios-editar
   Migrado desde el <script> embebido de views/blog/usuarios/editar.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-usuarios-editar') return;
    function togglePassword(fieldId, iconId) {
        const field = document.getElementById(fieldId);
        const icon  = document.getElementById(iconId);
        if (field.type === 'password') {
            field.type = 'text';
            icon.className = 'fa-regular fa-eye-slash';
        } else {
            field.type = 'password';
            icon.className = 'fa-regular fa-eye';
        }
    }

    function togglePasswordSection() {
        const show        = document.getElementById('toggle-password').checked;
        const section     = document.getElementById('password-section');
        const placeholder = document.getElementById('password-placeholder');
        section.style.display     = show ? 'block' : 'none';
        placeholder.style.display = show ? 'none'  : 'block';
        if (show) {
            const pwdEl = document.getElementById('password');
            if (pwdEl) pwdEl.focus();
        }
    }

    /* ── Helpers ── */
    function setFieldState(inputEl, state, msg) {
        inputEl.classList.remove('field--valid', 'field--invalid');
        if (state) inputEl.classList.add('field--' + state);
        const wrapper = inputEl.closest('.admin-form__input-wrapper');
        if (wrapper) {
            wrapper.classList.remove('has-valid', 'has-invalid');
            if (state) wrapper.classList.add('has-' + state);
        }
        const msgEl = document.getElementById('msg-' + inputEl.id);
        if (msgEl) {
            msgEl.textContent = msg || '';
            msgEl.className   = 'field-msg' + (state ? ' field-msg--' + state : '');
        }
    }

    /* ── Password strength ── */
    (function () {
        const strengthMap = [
            { label: '',          color: '#E2E8F0', width: '0%'   },
            { label: 'Muy débil', color: '#E53E3E', width: '25%'  },
            { label: 'Débil',     color: '#E67E22', width: '50%'  },
            { label: 'Buena',     color: '#D69E2E', width: '75%'  },
            { label: 'Segura',    color: '#38A169', width: '100%' },
        ];

        function calcStrength(val) {
            let s = 0;
            if (val.length >= 8)          s++;
            if (/[A-Z]/.test(val))        s++;
            if (/[0-9]/.test(val))        s++;
            if (/[^A-Za-z0-9]/.test(val)) s++;
            return s;
        }

        const pwdEl    = document.getElementById('password');
        const pwdFill  = document.getElementById('pwd-fill');
        const pwdLabel = document.getElementById('pwd-label');

        if (pwdEl) {
            pwdEl.addEventListener('input', function () {
                const m = strengthMap[calcStrength(this.value)];
                if (pwdFill)  { pwdFill.style.width = m.width; pwdFill.style.background = m.color; }
                if (pwdLabel) { pwdLabel.textContent = m.label; pwdLabel.style.color = m.color; }
            });

            pwdEl.addEventListener('blur', function () {
                if (!this.value) return; // field optional in edit mode
                if (this.value.length < 8) {
                    setFieldState(this, 'invalid', 'Mínimo 8 caracteres');
                } else if (!/[A-Z]/.test(this.value)) {
                    setFieldState(this, 'invalid', 'Añade al menos una mayúscula');
                } else if (!/[0-9]/.test(this.value)) {
                    setFieldState(this, 'invalid', 'Añade al menos un número');
                } else {
                    setFieldState(this, 'valid', '');
                }
                const p2 = document.getElementById('password_confirm');
                if (p2 && p2.value) p2.dispatchEvent(new Event('blur'));
            });
        }

        const pwd2El = document.getElementById('password_confirm');
        if (pwd2El) {
            pwd2El.addEventListener('blur', function () {
                const original = document.getElementById('password')?.value || '';
                if (!this.value && !original) return; // both empty = unchanged
                if (!this.value) {
                    setFieldState(this, 'invalid', 'Confirma la nueva contraseña');
                } else if (this.value !== original) {
                    setFieldState(this, 'invalid', 'Las contraseñas no coinciden');
                } else {
                    setFieldState(this, 'valid', 'Las contraseñas coinciden');
                }
            });
        }
    })();

    document.getElementById('nombre')?.addEventListener('input', function() {
        const preview = document.getElementById('avatar-preview');
        if (!preview.querySelector('img')) {
            preview.textContent = this.value.trim().charAt(0).toUpperCase() || 'U';
        }
    });

    const avatarInput    = document.getElementById('avatar');
    const avatarPreview  = document.getElementById('avatar-preview');
    const avatarFilename = document.getElementById('avatar-filename');

    avatarInput?.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) {
            avatarFilename.textContent = '⚠ El archivo supera 2 MB';
            avatarFilename.style.color = '#E53E3E';
            this.value = '';
            return;
        }
        avatarFilename.style.color = 'var(--col-bilbao)';
        avatarFilename.textContent = file.name;
        const reader = new FileReader();
        reader.onload = function (e) {
            avatarPreview.innerHTML = '<img src="' + e.target.result + '" alt="Vista previa" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';
        };
        reader.readAsDataURL(file);
    });

    /* ── Fade-out al guardar cambios ── */
    document.getElementById('form-editar-usuario')?.addEventListener('submit', function () {
        const main = document.querySelector('.admin-main');
        if (main) {
            main.style.transition = 'opacity .35s ease, transform .35s ease';
            main.style.opacity    = '0';
            main.style.transform  = 'translateY(10px)';
        }
    });

    /* ── Delete modal (init after DOM is ready) ── */
    document.addEventListener('DOMContentLoaded', function () {
        let _ubmName = '';
        const modal  = document.getElementById('deleteModal');
        const input  = document.getElementById('ubm-input');
        const submit = document.getElementById('ubm-submit');
        if (!modal) return;

        window.abrirModalEliminar = function (id, nombre) {
            _ubmName = nombre;
            document.getElementById('ubm-name').textContent = nombre;
            document.getElementById('ubm-id').value = id;
            input.value = '';
            input.classList.remove('is-valid');
            submit.disabled = true;
            modal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
            setTimeout(() => input.focus(), 300);
        };

        window.cerrarModalEliminar = function () {
            modal.classList.remove('is-open');
            document.body.style.overflow = '';
            input.value = '';
            input.classList.remove('is-valid');
            submit.disabled = true;
        };

        input.addEventListener('input', function () {
            const match = this.value === _ubmName;
            this.classList.toggle('is-valid', match);
            submit.disabled = !match;
        });

        /* Fade-out antes de enviar el formulario de eliminación */
        modal.querySelector('form').addEventListener('submit', function (e) {
            e.preventDefault();
            const main = document.querySelector('.admin-main');
            modal.classList.remove('is-open');
            document.body.style.overflow = '';
            if (main) {
                main.style.transition = 'opacity .35s ease, transform .35s ease';
                main.style.opacity    = '0';
                main.style.transform  = 'translateY(10px)';
            }
            setTimeout(() => this.submit(), 380);
        });

        modal.addEventListener('click', function (e) { if (e.target === this) cerrarModalEliminar(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrarModalEliminar(); });
    });

    /* ── Toggle de módulos según el rol ── */
    (function () {
        const modulosGroup = document.getElementById('modulos-group');
        if (!modulosGroup) return;
        const rolRadios = document.querySelectorAll('input[name="rol"]');
        function syncModulos() {
            const rol = document.querySelector('input[name="rol"]:checked')?.value;
            modulosGroup.style.display = (rol === 'usuario') ? 'block' : 'none';
        }
        rolRadios.forEach(r => r.addEventListener('change', syncModulos));
        syncModulos();
    })();

    /* Exponer para los atributos inline (onclick/onchange) */
    window.togglePassword = togglePassword;
    window.togglePasswordSection = togglePasswordSection;
})();
