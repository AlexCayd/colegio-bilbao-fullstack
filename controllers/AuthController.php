<?php

namespace Controllers;

use Classes\Email;
use Model\Usuario;
use MVC\Router;

/**
 * Autenticación del SITIO PÚBLICO — cuentas de familias y visitantes.
 *
 * ⚠️ No confundir con el acceso al panel. La intranet de colaboradores tiene su propio
 * flujo en BlogController::login()/logout(), con la sesión en $_SESSION['blog_usuario'].
 * Este controlador maneja $_SESSION['id'|'nombre'|'email'|'admin'], que es otra cosa.
 *
 * Rutas: /cuenta/login · /cuenta/logout · /registro · /olvide · /reestablecer ·
 * /mensaje · /confirmar-cuenta.
 *
 * Es el ÚNICO punto de la aplicación que envía correo (classes/Email.php sobre SMTP), así
 * que las variables EMAIL_* de includes/.env solo afectan aquí.
 *
 * ── CÓDIGO HEREDADO ──────────────────────────────────────────────────────────
 * Viene casi intacto de la plantilla DevWebcamp sobre la que se construyó el proyecto y
 * apenas se usa: el colegio no ofrece registro público hoy. Antes de darle uso hay que
 * revisar, como mínimo:
 *   · Textos visibles que aún dicen "DevWebcamp" (registro() y confirmar()).
 *   · Llamadas a session_start() aunque includes/app.php ya abrió la sesión.
 *   · Lecturas de $_GET['token'] sin comprobar que exista.
 *   · header('Location: …') sin exit() a continuación: el script sigue ejecutándose.
 *   · classes/Email.php tiene el remitente 'cuentas@devwebcamp.com' escrito a mano.
 *
 * @package Controllers
 */
class AuthController {
    /**
     * Inicio de sesión de una cuenta pública (GET pinta el formulario, POST lo procesa).
     *
     * Solo entra si la cuenta está confirmada por correo. En vez de redirigir tras un
     * acceso correcto, cae al render de la misma vista con la sesión ya abierta.
     *
     * @param  Router $router
     * @return void
     */
    public static function login(Router $router) {

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
    
            $usuario = new Usuario($_POST);

            $alertas = $usuario->validarLogin();
            
            if(empty($alertas)) {
                // Verificar quel el usuario exista
                $usuario = Usuario::where('email', $usuario->email);
                if(!$usuario || !$usuario->confirmado ) {
                    Usuario::setAlerta('error', 'El Usuario No Existe o no esta confirmado');
                } else {
                    // El Usuario existe
                    if( password_verify($_POST['password'], $usuario->password) ) {
                        
                        // Iniciar la sesión
                        session_start();    
                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre;
                        $_SESSION['apellido'] = $usuario->apellido;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['admin'] = $usuario->admin ?? null;
                        
                    } else {
                        Usuario::setAlerta('error', 'Password Incorrecto');
                    }
                }
            }
        }

        $alertas = Usuario::getAlertas();
        
        // Render a la vista 
        $router->render('auth/login', [
            'titulo' => 'Iniciar Sesión',
            'alertas' => $alertas
        ]);
    }

    /**
     * Cierra la sesión pública vaciando $_SESSION por completo.
     *
     * ⚠️ Vacía la sesión ENTERA, así que también echa del panel a quien tuviera abierta
     * la intranet en la misma sesión de navegador.
     *
     * Único método del controlador que no recibe el Router: no renderiza nada.
     *
     * @return void
     */
    public static function logout() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            session_start();
            $_SESSION = [];
            header('Location: /');
        }
       
    }

    /**
     * Alta de una cuenta pública nueva.
     *
     * La cuenta nace SIN confirmar: se genera un token, se envía por correo y hasta que
     * el destinatario abre el enlace de /confirmar-cuenta no puede iniciar sesión.
     *
     * ⚠️ El correo se envía ANTES de comprobar que el guardado funcionó, así que un
     * fallo de escritura deja un mensaje enviado sin cuenta detrás.
     *
     * @param  Router $router
     * @return void
     */
    public static function registro(Router $router) {
        $alertas = [];
        $usuario = new Usuario;

        if($_SERVER['REQUEST_METHOD'] === 'POST') {

            $usuario->sincronizar($_POST);
            
            $alertas = $usuario->validar_cuenta();

            if(empty($alertas)) {
                $existeUsuario = Usuario::where('email', $usuario->email);

                if($existeUsuario) {
                    Usuario::setAlerta('error', 'El Usuario ya esta registrado');
                    $alertas = Usuario::getAlertas();
                } else {
                    // Hashear el password
                    $usuario->hashPassword();

                    // Eliminar password2
                    unset($usuario->password2);

                    // Generar el Token
                    $usuario->crearToken();

                    // Crear un nuevo usuario
                    $resultado =  $usuario->guardar();

                    // Enviar email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarConfirmacion();
                    

                    if($resultado) {
                        header('Location: /mensaje');
                    }
                }
            }
        }

        // Render a la vista
        $router->render('auth/registro', [
            'titulo' => 'Crea tu cuenta en DevWebcamp',
            'usuario' => $usuario, 
            'alertas' => $alertas
        ]);
    }

    /**
     * Paso 1 de la recuperación de contraseña: pedir el correo y enviar las instrucciones.
     *
     * Genera un token nuevo, lo guarda y lo manda por correo dentro del enlace de
     * /reestablecer.
     *
     * ⚠️ La respuesta distingue "el usuario no existe" de "se enviaron las instrucciones",
     * lo que permite averiguar desde fuera qué correos están registrados. Un mensaje único
     * para ambos casos lo evitaría.
     *
     * ⚠️ Esto NO sirve para el panel de colaboradores: allí las contraseñas las
     * restablece un administrador desde Usuarios.
     *
     * @param  Router $router
     * @return void
     */
    public static function olvide(Router $router) {
        $alertas = [];
        
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = new Usuario($_POST);
            $alertas = $usuario->validarEmail();

            if(empty($alertas)) {
                // Buscar el usuario
                $usuario = Usuario::where('email', $usuario->email);

                if($usuario && $usuario->confirmado) {

                    // Generar un nuevo token
                    $usuario->crearToken();
                    unset($usuario->password2);

                    // Actualizar el usuario
                    $usuario->guardar();

                    // Enviar el email
                    $email = new Email( $usuario->email, $usuario->nombre, $usuario->token );
                    $email->enviarInstrucciones();


                    // Imprimir la alerta
                    // Usuario::setAlerta('exito', 'Hemos enviado las instrucciones a tu email');

                    $alertas['exito'][] = 'Hemos enviado las instrucciones a tu email';
                } else {
                 
                    // Usuario::setAlerta('error', 'El Usuario no existe o no esta confirmado');

                    $alertas['error'][] = 'El Usuario no existe o no esta confirmado';
                }
            }
        }

        // Muestra la vista
        $router->render('auth/olvide', [
            'titulo' => 'Olvide mi Password',
            'alertas' => $alertas
        ]);
    }

    /**
     * Paso 2 de la recuperación: fijar la contraseña nueva a partir del token del enlace.
     *
     * Al guardarla, el token se anula para que el enlace no se pueda reutilizar.
     *
     * ⚠️ El token no caduca: es válido hasta que se use o hasta que otro «olvidé mi
     * contraseña» lo sustituya.
     * ⚠️ Lee $_GET['token'] sin comprobar que exista, y el header() de salida no lleva
     * exit(): con un token ausente el flujo continúa y $usuario queda nulo.
     *
     * @param  Router $router  Espera ?token=… en la URL.
     * @return void
     */
    public static function reestablecer(Router $router) {

        $token = s($_GET['token']);

        $token_valido = true;

        if(!$token) header('Location: /');

        // Identificar el usuario con este token
        $usuario = Usuario::where('token', $token);

        if(empty($usuario)) {
            Usuario::setAlerta('error', 'Token No Válido, intenta de nuevo');
            $token_valido = false;
        }


        if($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Añadir el nuevo password
            $usuario->sincronizar($_POST);

            // Validar el password
            $alertas = $usuario->validarPassword();

            if(empty($alertas)) {
                // Hashear el nuevo password
                $usuario->hashPassword();

                // Eliminar el Token
                $usuario->token = null;

                // Guardar el usuario en la BD
                $resultado = $usuario->guardar();

                // Redireccionar
                if($resultado) {
                    header('Location: /');
                }
            }
        }

        $alertas = Usuario::getAlertas();
        
        // Muestra la vista
        $router->render('auth/reestablecer', [
            'titulo' => 'Reestablecer Password',
            'alertas' => $alertas,
            'token_valido' => $token_valido
        ]);
    }

    /**
     * Pantalla de «revisa tu correo», adonde redirige el registro tras enviar el mensaje.
     *
     * @param  Router $router
     * @return void
     */
    public static function mensaje(Router $router) {

        $router->render('auth/mensaje', [
            'titulo' => 'Cuenta Creada Exitosamente'
        ]);
    }

    /**
     * Destino del enlace de confirmación: activa la cuenta y consume el token.
     *
     * Es lo que desbloquea el inicio de sesión, porque login() rechaza toda cuenta con
     * `confirmado = 0`.
     *
     * ⚠️ Mismo problema que reestablecer(): lee $_GET['token'] sin comprobar que exista y
     * el header() no lleva exit().
     *
     * @param  Router $router  Espera ?token=… en la URL.
     * @return void
     */
    public static function confirmar(Router $router) {
        
        $token = s($_GET['token']);

        if(!$token) header('Location: /');

        // Encontrar al usuario con este token
        $usuario = Usuario::where('token', $token);

        if(empty($usuario)) {
            // No se encontró un usuario con ese token
            Usuario::setAlerta('error', 'Token No Válido');
        } else {
            // Confirmar la cuenta
            $usuario->confirmado = 1;
            $usuario->token = '';
            unset($usuario->password2);
            
            // Guardar en la BD
            $usuario->guardar();

            Usuario::setAlerta('exito', 'Cuenta Comprobada Correctamente');
        }

     

        $router->render('auth/confirmar', [
            'titulo' => 'Confirma tu cuenta DevWebcamp',
            'alertas' => Usuario::getAlertas()
        ]);
    }
}