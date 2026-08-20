<?php
/**
 * Preguntas frecuentes del panel, agrupadas por módulo.
 *
 * La vista solo muestra los bloques de los módulos que el usuario tiene, más los
 * generales: un profesor no necesita leer cómo se importa un CSV de horarios, y
 * enseñárselo solo hace más difícil encontrar lo que sí le sirve.
 *
 * Añadir una pregunta se hace aquí y en ningún otro sitio.
 *
 * @return array<string, array{titulo:string, icon:string, items:array<int,array{q:string,a:string}>}>
 */
return [
    'general' => [
        'titulo' => 'Lo básico',
        'icon'   => 'fa-circle-info',
        'items'  => [
            ['q' => '¿Cómo cambio mi contraseña o mi foto?',
             'a' => 'Entra en tu avatar (arriba a la derecha) y elige <strong>Mi perfil</strong>. Ahí puedes cambiar tu foto, tu nombre visible y tu contraseña.'],
            ['q' => 'No veo un módulo que antes sí usaba',
             'a' => 'Los módulos los asigna un administrador. Si te falta uno, escríbenos por WhatsApp con el botón de arriba indicando cuál necesitas y para qué.'],
            ['q' => '¿Qué es la campana del menú superior?',
             'a' => 'Son tus notificaciones: coberturas asignadas, justificantes, cambios en tus horarios. Marcar una como completada la elimina, pero tienes 6 segundos para deshacerlo.'],
            ['q' => 'El panel se ve raro o no carga bien',
             'a' => 'Prueba a recargar con <strong>Ctrl + F5</strong>. Si sigue igual, mándanos una captura por WhatsApp indicando qué navegador usas.'],
        ],
    ],

    'suplencias' => [
        'titulo' => 'Suplencias',
        'icon'   => 'fa-user-clock',
        'items'  => [
            ['q' => 'Voy a faltar. ¿Cómo aviso?',
             'a' => 'Entra en <strong>Suplencias › Solicitar</strong>, elige el día y marca en tu horario las clases que hay que cubrir. Si tienes justificante, adjúntalo ahí mismo.'],
            ['q' => '¿Por qué no aparezco como candidato para cubrir una clase?',
             'a' => 'El sistema descarta a quien tenga clase o guardia a esa hora, a quien ya cubra otra suplencia y a quien se quedaría sin su descanso mínimo. También reparte el trabajo: si ya llevas bastantes coberturas, deja pasar a otros.'],
            ['q' => 'Me asignaron una cobertura. ¿Tengo que hacer algo?',
             'a' => 'Sí: cuando pase el día, entra en <strong>Suplencias › Suplencias</strong> y confirma que la cubriste. Si no lo haces, el sistema te lo recuerda y prefectura acaba cerrándola por su cuenta.'],
            ['q' => '¿Hasta cuándo puedo descargar un justificante?',
             'a' => 'Siete días desde la ausencia. Después pasa a una cola donde dirección decide si lo descarga o lo elimina, y a los 30 días se borra automáticamente del servidor.'],
        ],
    ],

    'horarios' => [
        'titulo' => 'Horarios',
        'icon'   => 'fa-table-cells',
        'items'  => [
            ['q' => 'Mi horario tiene un error',
             'a' => 'El horario solo lo edita un administrador. Escríbenos indicando el día, la hora y qué debería decir, y lo corregimos.'],
            ['q' => '¿Por qué aparecen dos profesores en una misma clase?',
             'a' => 'Es una clase con titular y acompañante. Los dos la tienen ocupada, así que a ninguno se le puede asignar una suplencia a esa hora.'],
            ['q' => 'Veo dos materias a la misma hora en un grupo',
             'a' => 'Es una materia dividida: el grupo se reparte entre las dos opciones. Aparecen las dos porque las dos se imparten.'],
            ['q' => '¿Las guardias cuentan como ocupación?',
             'a' => 'Sí. Una guardia de receso aparece en tu horario y bloquea que te asignen una suplencia a esa hora, igual que una clase.'],
        ],
    ],

    'usuarios' => [
        'titulo' => 'Usuarios',
        'icon'   => 'fa-users-gear',
        'items'  => [
            ['q' => '¿Qué diferencia hay entre rol y tipo de personal?',
             'a' => 'El <strong>rol</strong> dice cuánto puede tocar en el panel (Admin o Usuario). El <strong>tipo de personal</strong> dice qué hace en el colegio (profesor, prefecto, administrativo, directivo) y es lo que decide, por ejemplo, quién cubre suplencias.'],
            ['q' => '¿Por qué no puedo marcar «profesor» y «prefecto» a la vez?',
             'a' => 'Prefecto y directivo son excluyentes: coordinan las ausencias del claustro pero no imparten clase. Profesor y administrativo sí se combinan.'],
            ['q' => '¿Para qué sirven los niveles de un profesor?',
             'a' => 'Acotan su rejilla de horario y priorizan sus candidaturas a suplencia: para cubrir Primaria, un profesor de Primaria pasa antes.'],
        ],
    ],

    'eventos' => [
        'titulo' => 'Eventos',
        'icon'   => 'fa-calendar-day',
        'items'  => [
            ['q' => '¿Dónde se publica un evento?',
             'a' => 'Depende de su audiencia: <strong>Interno</strong> se queda en el panel, <strong>Familias</strong> sale en Comunidad › Familias y <strong>Estudiantes</strong> en Comunidad › Estudiantes. Todos aparecen en el calendario del inicio.'],
            ['q' => '¿Para qué sirven los niveles de un evento?',
             'a' => 'Para acotar a quién va dirigido. Si no marcas ninguno, el evento es para todo el colegio.'],
        ],
    ],

    'swaps' => [
        'titulo' => 'Swaps de clase',
        'icon'   => 'fa-right-left',
        'items'  => [
            ['q' => '¿En qué se diferencia de una suplencia?',
             'a' => 'Una suplencia la cubre otra persona porque tú faltas. Un swap es un trato entre dos profesores: tú das su clase y esa persona da la tuya, cada uno otro día.'],
            ['q' => '¿Cuánto margen tengo para proponerlo?',
             'a' => 'Puedes proponer un swap con clases dentro de los 7 días siguientes al día que vas a faltar.'],
            ['q' => 'La otra persona aceptó. ¿Ya está?',
             'a' => 'Falta el visto bueno de prefectura o dirección. Hasta entonces el swap queda como aceptado pero pendiente de validar.'],
        ],
    ],

    'redaccion' => [
        'titulo' => 'Redacción',
        'icon'   => 'fa-pen-nib',
        'items'  => [
            ['q' => 'Escribí un artículo y no se publica',
             'a' => 'Los artículos pasan por revisión: guárdalo y envíalo a revisión desde el propio artículo. Un revisor lo aprueba y entonces se publica.'],
            ['q' => '¿Puedo editar algo ya publicado?',
             'a' => 'Sí. Los cambios quedan como versión pendiente hasta que un revisor los apruebe, así que lo publicado no cambia hasta ese momento.'],
        ],
    ],
];
