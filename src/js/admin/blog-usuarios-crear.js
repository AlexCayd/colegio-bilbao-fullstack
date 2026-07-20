/* blog-usuarios-crear
   Migrado desde el <script> embebido de views/blog/usuarios/crear.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-usuarios-crear') return;
    (function () {

        /* ── Toggle password visibility ── */
        window.togglePassword = function (fieldId, iconId) {
            const f = document.getElementById(fieldId);
            const i = document.getElementById(iconId);
            if (f.type === 'password') {
                f.type = 'text';
                i.className = 'fa-regular fa-eye-slash';
            } else {
                f.type = 'password';
                i.className = 'fa-regular fa-eye';
            }
        };

        /* ── Helpers ── */
        function setFieldState(inputEl, state, msg) {
            inputEl.classList.remove('field--valid', 'field--invalid');
            if (state) inputEl.classList.add('field--' + state);
            const wrapper = inputEl.closest('.admin-form__input-wrapper');
            if (wrapper) {
                wrapper.classList.remove('has-valid', 'has-invalid');
                if (state) wrapper.classList.add('has-' + state);
            }
            const msgId = 'msg-' + inputEl.id;
            const msgEl = document.getElementById(msgId);
            if (msgEl) {
                msgEl.textContent = msg || '';
                msgEl.className   = 'field-msg' + (state ? ' field-msg--' + state : '');
            }
        }

        /* ── Nombre ── */
        const nombreEl = document.getElementById('nombre');
        if (nombreEl) {
            nombreEl.addEventListener('input', function () {
                document.getElementById('avatar-preview').textContent =
                    this.value.trim().charAt(0).toUpperCase() || 'U';
            });
            nombreEl.addEventListener('blur', function () {
                if (!this.value.trim()) {
                    setFieldState(this, 'invalid', 'El nombre es obligatorio');
                } else {
                    setFieldState(this, 'valid', '');
                }
            });
        }

        /* ── Email ── */
        const emailEl = document.getElementById('email');
        if (emailEl) {
            emailEl.addEventListener('blur', function () {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!this.value.trim()) {
                    setFieldState(this, 'invalid', 'El correo es obligatorio');
                } else if (!re.test(this.value)) {
                    setFieldState(this, 'invalid', 'Formato de correo inválido');
                } else {
                    setFieldState(this, 'valid', '');
                }
            });
        }

        /* ── Password strength ── */
        const pwdEl    = document.getElementById('password');
        const pwdFill  = document.getElementById('pwd-fill');
        const pwdLabel = document.getElementById('pwd-label');

        function calcStrength(val) {
            let score = 0;
            if (val.length >= 8)              score++;
            if (/[A-Z]/.test(val))            score++;
            if (/[0-9]/.test(val))            score++;
            if (/[^A-Za-z0-9]/.test(val))     score++;
            return score;
        }

        const strengthMap = [
            { label: '',          color: '#E2E8F0', width: '0%'   },
            { label: 'Muy débil', color: '#E53E3E', width: '25%'  },
            { label: 'Débil',     color: '#E67E22', width: '50%'  },
            { label: 'Buena',     color: '#D69E2E', width: '75%'  },
            { label: 'Segura',    color: '#38A169', width: '100%' },
        ];

        if (pwdEl) {
            pwdEl.addEventListener('input', function () {
                const s = calcStrength(this.value);
                const m = strengthMap[s];
                if (pwdFill)  { pwdFill.style.width = m.width; pwdFill.style.background = m.color; }
                if (pwdLabel) { pwdLabel.textContent = m.label; pwdLabel.style.color = m.color; }
            });

            pwdEl.addEventListener('blur', function () {
                if (!this.value) {
                    setFieldState(this, 'invalid', 'La contraseña es obligatoria');
                } else if (this.value.length < 8) {
                    setFieldState(this, 'invalid', 'Mínimo 8 caracteres');
                } else if (!/[A-Z]/.test(this.value)) {
                    setFieldState(this, 'invalid', 'Añade al menos una mayúscula');
                } else if (!/[0-9]/.test(this.value)) {
                    setFieldState(this, 'invalid', 'Añade al menos un número');
                } else {
                    setFieldState(this, 'valid', '');
                }
                // re-check confirm
                const p2 = document.getElementById('password2');
                if (p2 && p2.value) p2.dispatchEvent(new Event('blur'));
            });
        }

        /* ── Confirmar contraseña ── */
        const pwd2El = document.getElementById('password2');
        if (pwd2El) {
            pwd2El.addEventListener('blur', function () {
                const original = document.getElementById('password')?.value || '';
                if (!this.value) {
                    setFieldState(this, 'invalid', 'Confirma la contraseña');
                } else if (this.value !== original) {
                    setFieldState(this, 'invalid', 'Las contraseñas no coinciden');
                } else {
                    setFieldState(this, 'valid', 'Las contraseñas coinciden');
                }
            });
        }

        /* ── Avatar file picker ── */
        const avatarInput    = document.getElementById('avatar');
        const avatarPreview  = document.getElementById('avatar-preview');
        const avatarFilename = document.getElementById('avatar-filename');

        if (avatarInput) {
            avatarInput.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;

                if (file.size > 2 * 1024 * 1024) {
                    avatarFilename.textContent = '⚠ El archivo supera 2 MB';
                    avatarFilename.style.color = '#E53E3E';
                    this.value = '';
                    return;
                }

                avatarFilename.textContent = file.name;
                avatarFilename.style.color = 'var(--col-bilbao)';

                const reader = new FileReader();
                reader.onload = function (e) {
                    avatarPreview.innerHTML =
                        '<img src="' + e.target.result + '" alt="Vista previa" '
                        + 'style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';
                };
                reader.readAsDataURL(file);
            });
        }

        /* ── Validación completa al enviar ── */
        const form = document.getElementById('form-usuario');
        if (form) {
            form.addEventListener('submit', function (e) {
                let ok = true;

                [nombreEl, emailEl, pwdEl, pwd2El].forEach(el => {
                    if (el) el.dispatchEvent(new Event('blur'));
                });

                // Verificar si hay campos inválidos
                if (form.querySelectorAll('.field--invalid').length > 0) {
                    e.preventDefault();
                    form.querySelector('.field--invalid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    const main = document.querySelector('.admin-main');
                    if (main) {
                        main.style.transition = 'opacity .35s ease, transform .35s ease';
                        main.style.opacity    = '0';
                        main.style.transform  = 'translateY(10px)';
                    }
                }
            });
        }

        /* ── Toggle de módulos según el rol ── */
        const modulosGroup = document.getElementById('modulos-group');
        const rolRadios    = document.querySelectorAll('input[name="rol"]');
        function syncModulos() {
            const rol = document.querySelector('input[name="rol"]:checked')?.value;
            if (modulosGroup) modulosGroup.style.display = (rol === 'usuario') ? 'block' : 'none';
        }
        rolRadios.forEach(r => r.addEventListener('change', syncModulos));
        syncModulos();

    })();
})();
