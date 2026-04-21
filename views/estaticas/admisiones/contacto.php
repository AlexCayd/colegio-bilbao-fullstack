<main id="main-content">
    
    <!-- 1. HERO COMPACTO -->
    <section class="contact-hero">
        <div class="bg-blob blob-1"></div>
        <div class="bg-blob blob-2"></div>
        
        <div class="container" style="position:relative; z-index:2;">
            <h1 class="admisiones-title">Queremos escucharte</h1>
            <p class="hero-lead">Ya sea para agendar una visita, resolver dudas o simplemente saludar. Tu historia en el Bilbao comienza aquí.</p>
        </div>
    </section>

    <!-- 2. CONNECTION HUB -->
    <section class="contact-content">
        <div class="container contact-grid">
            
            <!-- COLUMN LEFT: FORMULARIO -->
            <div class="form-card reveal">
                <div id="formContainer">
                    <div class="form-header">
                        <h2 class="form-title">Envíanos un mensaje</h2>
                        <p class="form-subtitle">Déjanos tus datos y el área de interés se pondrá en contacto contigo a la brevedad.</p>
                    </div>

                    <!-- EMAILJS FORM -->
                    <form id="contactForm" onsubmit="event.preventDefault(); handleSubmit();">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" id="nombre" name="nombre" class="form-input" placeholder="Tu nombre" required>
                            </div>
                            <div class="form-group">
                                <label for="apellido" class="form-label">Apellido</label>
                                <input type="text" id="apellido" name="apellido" class="form-input" placeholder="Tu apellido" required>
                            </div>
                            <div class="form-group">
                                <label for="email" class="form-label">Correo electrónico</label>
                                <input type="email" id="email" name="email" class="form-input" placeholder="ejemplo@correo.com" required>
                            </div>
                            <div class="form-group">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" id="telefono" name="telefono" class="form-input" placeholder="55 1234 5678">
                            </div>
                            
                            <!-- Preferencia de Contacto Mejorada -->
                            <div class="form-group form-full">
                                <label class="form-label">Prefiero ser contactado por:</label>
                                <div class="radio-group">
                                    <label class="radio-label">
                                        <input type="radio" name="contacto_pref" value="Correo" class="radio-input" checked>
                                        <span class="radio-icon"></span>
                                        Correo
                                    </label>
                                    <label class="radio-label">
                                        <input type="radio" name="contacto_pref" value="Llamada" class="radio-input">
                                        <span class="radio-icon"></span>
                                        Llamada
                                    </label>
                                    <label class="radio-label">
                                        <input type="radio" name="contacto_pref" value="WhatsApp" class="radio-input">
                                        <span class="radio-icon"></span>
                                        WhatsApp
                                    </label>
                                </div>
                            </div>

                            <div class="form-group form-full">
                                <label for="interes" class="form-label">Me interesa saber sobre</label>
                                <select id="interes" name="interes" class="form-select">
                                    <option value="" disabled selected>Selecciona una opción</option>
                                    <option value="Admisiones">Proceso de Admisión</option>
                                    <option value="Visita">Agendar una visita</option>
                                    <option value="Academico">Información Académica</option>
                                    <option value="Comunidad">Comunidad de familias Bilbao</option>
                                    <option value="Acompañamiento">Acompañamiento socioemocional</option>
                                    <option value="Idiomas">Idiomas y programas internacionales</option>
                                    <option value="Transporte">Transporte escolar</option>
                                    <option value="Otro">Otro tema</option>
                                </select>
                            </div>
                            <div class="form-group form-full">
                                <label for="mensaje" class="form-label">Mensaje</label>
                                <textarea id="mensaje" name="mensaje" class="form-textarea" placeholder="¿Cómo podemos ayudarte?"></textarea>
                            </div>
                        </div>
                        
                        <button type="submit" id="submitBtn" class="btn-submit" style="margin-top: 2rem;">
                            <span>Enviar Mensaje</span>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                        </button>
                    </form>
                </div>

                <!-- Mensaje de Éxito -->
                <div id="successMessage" class="success-message">
                    <div class="success-icon">✓</div>
                    <h3 style="font-size:1.8rem; font-weight:800; color:var(--col-bilbao); margin-bottom:1rem;">¡Mensaje Enviado!</h3>
                    <p style="font-size:1.1rem; color:var(--text-gray); max-width:400px; margin:0 auto;">Gracias por contactarnos. Hemos recibido tu información correctamente y nos pondremos en contacto contigo muy pronto.</p>
                    <button onclick="location.reload()" class="btn-direct" style="margin-top:2rem; width:auto; display:inline-flex;">Volver al formulario</button>
                </div>
            </div>

            <!-- COLUMN RIGHT: INFO STACK -->
            <div class="info-stack reveal" style="animation-delay: 0.2s;">
                
                <!-- Botones Directos Actualizados -->
                <div class="separator-text">O si prefieres algo más veloz...</div>
                <div class="direct-actions">
                    <a href="https://wa.me/525614612682?text=Hola,%20los%20contacto%20porque%20acabo%20de%20ver%20la%20página%20del%20bilbao,%20me%20gustó%20y%20quiero%20conocer%20el%20colegio%20para%20nuestra%20familia%20en%20una%20visita%20guiada." class="btn-direct btn-whatsapp" target="_blank">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        Mándanos WhatsApp
                    </a>
                    <a href="tel:+525558101346" class="btn-direct">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        Llámanos
                    </a>
                </div>

                <!-- Datos Directos -->
                <div class="info-card">
                    <h3>Datos de contacto</h3>
                    <ul class="contact-list" style="list-style:none; padding:0;">
                        <li>
                            <span class="c-icon">📍</span>
                            <div>
                                <strong>Ubicación:</strong><br>
                                Tlalmimilolpan 39, San Mateo Tlaltenango,
Cuajimalpa de Morelos, 05600 Ciudad de México, CDMX
                            </div>
                        </li>
                        <li>
                            <span class="c-icon">✉️</span>
                            <div>
                                <strong>Correo:</strong><br>
                                <a href="mailto:contacto@bilbao.edu.mx" class="c-link">contacto@bilbao.edu.mx</a>
                            </div>
                        </li>
                        <li>
                            <span class="c-icon">🕒</span>
                            <div>
                                <strong>Horario:</strong><br>
                                Lunes a Viernes: 7:30 - 17:00
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Mapa -->
                <div class="map-container">
                    <!-- Placeholder de Mapa Interactivo -->
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1001.9546976996666!2d-99.27843048232893!3d19.33600608051829!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x85d20753654b5aa1%3A0xc93eb6aa2f89009e!2sColegio%20Bilbao!5e1!3m2!1ses!2smx!4v1767767480787!5m2!1ses!2smx" class="map-frame" allowfullscreen="" loading="lazy"></iframe>
                </div>

            </div>

        </div>
    </section>

</main>