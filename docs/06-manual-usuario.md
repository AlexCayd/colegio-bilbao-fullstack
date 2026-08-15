# 6. Manual de usuario

> Público: personal del Colegio Bilbao que use la intranet — dirección, prefectura,
> profesorado, administración y quien redacte contenido.

---

## 6.1 Qué es la intranet

Es la parte privada del sitio web del colegio. Se entra desde **`/login`** con el correo
institucional, y sirve para organizar el día a día académico:

- **Suplencias** — quién falta, qué clases quedan sin cubrir y quién las cubre.
- **Horarios** — la semana de cada profesor, cada aula y cada grupo.
- **Intercambios** — cambiar una clase con un compañero por excepción.
- **Eventos** — el calendario del colegio, con avisos que llegan a las familias.
- **Usuarios y directorios** — el claustro, sus permisos y sus cumpleaños.
- **Redacción** — el blog y las noticias públicas del colegio.
- **Soporte técnico** — para pedir ayuda cuando algo no funciona.

Cada persona ve **solo los módulos que tiene asignados**. Al entrar, la pantalla de inicio muestra
esos módulos como tarjetas, más el calendario del mes y los cumpleaños del claustro.

> **La intranet no gestiona alumnos ni calificaciones.** Es una herramienta para el personal del
> colegio. Las cuentas son de colaboradores; no hay acceso para familias ni para alumnado.

### Entrar y salir

1. Ir a la dirección del colegio y añadir **`/login`**.
2. Escribir el correo institucional y la contraseña.
3. Para salir, pulsar el **botón rojo redondo** de la esquina superior derecha. Está en todas las
   pantallas.

> **¿Olvidaste la contraseña?** El panel **no** envía correos de recuperación. Hay que pedirle a un
> administrador que la restablezca desde Usuarios (ver [§6.4](#64-restablecer-la-contraseña-de-alguien)).

---

## 6.2 Roles y permisos

Hay **dos preguntas distintas** que el sistema hace sobre cada persona, y conviene no
confundirlas.

### Pregunta 1 — El rol: ¿cuánto puede tocar?

| Rol | Alcance |
|---|---|
| **Admin** | Acceso a **todos** los módulos. Único que puede crear y eliminar usuarios, importar horarios, editar el horario de un profesor y ver los tableros de estadísticas |
| **Usuario** | Acceso **solo** a los módulos que se le hayan marcado en su ficha |

> Solo existen estos dos roles. El antiguo `superadmin` se eliminó, y el antiguo rol `editor` pasó
> a llamarse `usuario`.

### Pregunta 2 — El tipo de personal: ¿qué papel juega?

Es lo que decide **de quién son los datos** que puede ver, y no tiene nada que ver con el rol.

| Tipo | Qué significa |
|---|---|
| **Profesor** | Da clase. Tiene horario, puede faltar a una clase y puede cubrir la de otro |
| **Prefecto** | Coordina la operación académica: abre y reparte suplencias, revisa justificantes. **No** da clase |
| **Directivo** | Coordina igual que prefectura, pero su acceso a la configuración es de **solo lectura**: ve el claustro, los horarios y los catálogos, y no puede modificarlos |
| **Administrativo** | Personal de oficina. Sin horario ni suplencias |

**Reglas de combinación:**

- **Profesor + Administrativo** — combinación válida.
- **Prefecto** y **Directivo** son **excluyentes**: no se combinan con nada, ni entre sí.

> **Por qué prefecto no puede ser además profesor:** prefectura reparte las suplencias, no las
> cubre. Combinar ambos pondría a la misma persona a los dos lados del mismo proceso.

### Qué puede hacer cada quién

| Acción | Admin | Directivo | Prefecto | Profesor | Administrativo |
|---|:--:|:--:|:--:|:--:|:--:|
| Ver su propio horario | ✅ | ✅ | ✅ | ✅ | — |
| Ver el horario de cualquiera | ✅ | ✅ | ✅ | — | — |
| Abrir una ausencia propia | — | — | — | ✅ | — |
| Abrir la ausencia de otro | ✅ | ✅ | ✅ | — | — |
| Agendar suplentes | ✅ | ✅ | ✅ | — | — |
| Aparecer como candidato a suplente | — | — | — | ✅ | — |
| Ver el motivo de una ausencia ajena | ✅ | ✅ | ✅ | — | — |
| Descargar un justificante | ✅ | ✅ | ✅ | — | — |
| Ver el histórico del plantel *(sin motivos)* | ✅ | ✅ | ✅ | ✅ | ✅ |
| Validar un intercambio de clase | ✅ | ✅ | ✅ | — | — |
| Crear o eliminar usuarios | ✅ | — | — | — | — |
| Editar el horario de un profesor | ✅ | — | — | — | — |
| Importar horarios por CSV | ✅ | — | — | — | — |
| Editar aulas y grupos | ✅ | 👁️ | ✅ | ✅ | ✅ |
| Ver el tablero de estadísticas | ✅ | — | — | — | — |
| Pedir soporte técnico | ✅ | ✅ | ✅ | ✅ | ✅ |

✅ puede · 👁️ solo mirar · — no tiene acceso
*(Las filas de módulos asignables suponen que la persona tiene ese módulo marcado en su ficha.)*

> **Soporte técnico lo tiene todo el mundo, siempre.** No se asigna: es la vía para pedir ayuda
> cuando el panel falla, y condicionarla a un permiso dejaría sin ella justo a quien no puede
> arreglarlo por su cuenta.

### Los módulos que se pueden asignar

Agrupados igual que en el menú lateral:

| Categoría | Módulos |
|---|---|
| **Personal y accesos** | Usuarios · Profesores · Prefectura · Administrativos · Directivos |
| **Operación académica** | Eventos · Horarios · Aulas · Grupos · Suplencias · Intercambios |
| **Contenido** | Redacción |
| **Ayuda** | Soporte técnico *(automático)* |

---

## 6.3 Moverse por el panel

| Elemento | Dónde | Para qué |
|---|---|---|
| **Menú lateral** | Izquierda, siempre visible | Todos tus módulos, agrupados. El módulo abierto se despliega con sus subopciones |
| **Ruta de navegación** | Arriba a la izquierda | «Inicio › Suplencias › Agendar». Cada tramo es un enlace |
| **Campana** | Arriba a la derecha | Notificaciones pendientes, con contador |
| **Avatar** | Arriba a la derecha | Tu perfil |
| **Botón rojo** | Arriba a la derecha | Cerrar sesión |
| **Avisos de Alex** | Ventana emergente | Confirmación tras una acción. Se retira solo a los 5 segundos |

El menú lateral se puede **plegar** para ganar espacio; recuerda tu preferencia entre visitas.

### Notificaciones

> ⚠️ **«Marcar como completada» BORRA la notificación.** No se archiva en ningún sitio.

Tienes **6 segundos para deshacerlo** con el botón «Deshacer» que aparece al borrarla. Pasado ese
tiempo, no hay forma de recuperarla.

Cada notificación tiene dos acciones: **«Marcar como completada»** (verde, cierra el aviso) y
**«Ver detalle»** (te lleva al sitio donde tienes que actuar). Se agrupan por *Hoy*, *Esta semana*
y *Anteriores*.

---

## 6.4 Flujos de Usuarios *(solo administradores)*

### Dar de alta a un colaborador

1. **Usuarios** → botón **«Nuevo usuario»**.
2. **Identidad:** nombre completo y correo institucional. El correo es con lo que entrará y no
   puede repetirse.
3. **Contraseña:** mínimo 8 caracteres, con **al menos una mayúscula y un número**. Hay que
   escribirla dos veces.
4. **Fecha de nacimiento** *(opcional)*: aparecerá en el calendario de cumpleaños.
5. **Rol:** *Admin* o *Usuario*.
6. **Tipo de personal:** marcar las tarjetas que correspondan. Al marcar *Prefecto* o *Directivo*
   las demás se desactivan solas: son excluyentes.
7. **Si marcaste Profesor**, aparecen dos ajustes más:
   - **Niveles en los que imparte** — opcional pero recomendable. Acota su rejilla de horario y
     prioriza sus sugerencias como suplente.
   - **«No puede suplir a otros profesores»** — dejarlo desactivado salvo excepción justificada.
8. **Módulos** *(solo si el rol es Usuario)*: marcar al menos uno.
9. **Si marcaste Redacción**, elegir si será **revisor** (aprueba contenido) o **editor** (escribe
   y envía a revisión).
10. **Guardar**.

> **El interruptor «No puede suplir» solo está aquí.** Un profesor no puede auto-excluirse de las
> suplencias desde su perfil.

### Restablecer la contraseña de alguien

1. **Usuarios** → localizar a la persona → botón del **lápiz** (ámbar).
2. Escribir la contraseña nueva dos veces.
3. Guardar y comunicársela por un canal seguro.

> Dejar los campos de contraseña **vacíos** conserva la actual: sirve para editar solo permisos.

### Eliminar un colaborador

**Usuarios** → lápiz → **Zona de peligro** al final del formulario → botón rojo.

> ⚠️ **Es irreversible.** Sus clases del horario se borran con él. Sus artículos y sus suplencias
> **no** se borran: quedan sin autor y sin suplente asignado.

### Calendario de cumpleaños

**Usuarios → Cumpleaños.** Lo alimenta la fecha de nacimiento de cada ficha. El cumpleaños del día
aparece además en la pantalla de inicio de **todo el mundo**, tenga o no el módulo Usuarios.

---

## 6.5 Flujos de Suplencias

Es el módulo con más movimiento. Hay **dos caminos de entrada** según quién abra la ausencia.

### 6.5.1 Como profesor: avisar de que vas a faltar

**Suplencias → Solicitar**

1. **Elegir la fecha** en el calendario desplegable. Los fines de semana están bloqueados: no hay
   clases que cubrir.
2. **Marcar las horas** que hay que cubrir tocándolas **directamente en tu rejilla semanal**. Se
   marcan las clases del día elegido; púlsalas y quedan seleccionadas.
3. **Motivo:** elegir del catálogo, o *Otro* y describirlo (si eliges *Otro*, describirlo es
   obligatorio).
4. **Notas** *(opcional)*: cualquier cosa que ayude a quien te cubra — «el examen está en el
   cajón», «el grupo va por la página 40».
5. **Justificante** *(opcional aquí)*: se puede adjuntar un PDF o una imagen de hasta 50 MB.
6. **Enviar**. El botón está tanto arriba como al pie del formulario.

Prefectura recibe el aviso y se encarga de buscar suplentes. Tú puedes seguirlo en
**Suplencias → Mis suplencias**.

> Al solicitar tú la ausencia, el origen queda fijado en **«anticipada»**. Solo prefectura puede
> registrar una ausencia «sin aviso».

### 6.5.2 Como prefectura o dirección: abrir la ausencia de otro

**Suplencias → Agenda → «Abrir suplencia»**, o pulsando un día del calendario.

Igual que el flujo anterior, con tres diferencias:

- **El ausente se elige con el buscador** (se escriben unas letras del nombre).
- Se puede marcar **«sin aviso»** cuando la persona no avisó con antelación. En ese caso el
  justificante **es obligatorio**.
- La rejilla que se marca es la del profesor ausente.

### 6.5.3 Asignar suplentes a las horas

**Suplencias → Agenda → abrir la suplencia → «Agendar»**

La pantalla tiene dos paneles:

**Izquierda — las horas a cubrir.** Cada tarjeta dice su estado por el color:

| Color | Significa |
|---|---|
| 🟠 **Ámbar** | Sin asignar |
| 🔵 **Azul** | Ya tiene suplente |
| 🟢 **Verde** | Validada: se confirmó que se cubrió |

**Derecha — los candidatos** para la hora seleccionada.

Para cada uno verás:

- **Cuántas coberturas** lleva acumuladas — el sistema prioriza a quien menos tenga.
- **Cuántas horas libres** le quedan ese día.
- Si está **bloqueado**, el motivo exacto: *«Tiene clase a esa hora»*, *«Tiene guardia a esa
  hora»*, *«Ya cubre otra suplencia a esa hora»*, *«Debe conservar una hora de descanso»*,
  *«Bloqueado por equidad»*.
- **Chips ámbar de salvedad** — por ejemplo *«No imparte en Primaria»*. **Avisan, no bloquean**:
  puedes asignarlo igualmente.

Pulsando un candidato se despliega **su horario real** de ese día, con el tramo que cubriría
resaltado. Es la forma de comprobar de un vistazo que la asignación tiene sentido.

Pulsa **Asignar** y listo. Cuando todas las horas tengan suplente, el botón «Volver» se convierte
en **«Finalizar»** (verde).

> **A un candidato bloqueado no se le puede asignar.** No hay «asignar de todos modos»: si el
> sistema dice que tiene clase a esa hora, es que la tiene.

#### Cómo elige el sistema a quién proponer

No hay que declarar disponibilidad en ningún sitio: **se deduce del horario**. El orden de la
lista es:

1. **Elegibles primero** — quien no tenga ningún bloqueo.
2. **Afinidad de nivel** — quien imparte ese nivel pasa antes.
3. **Menos salvedades**.
4. **Menos coberturas acumuladas** — el reparto más justo arriba.
5. **Más horas libres**.

Solo se proponen **profesores** que no tengan marcado «No puede suplir». Prefectura y
administrativos coordinan las ausencias, pero nunca aparecen como candidatos.

### 6.5.4 Confirmar que cubriste una clase

**Suplencias → Mis suplencias**

Cuando llega el día de una cobertura que te asignaron, aparece el botón para confirmarla.

> **No se puede confirmar antes de la fecha.** El sistema lo impide: confirmar «sí la cubrí» dos
> semanas antes de la clase sería justo lo contrario de lo que la pantalla promete.

Si pasa la fecha y no confirmas, recibes un **recordatorio automático** por la campana la próxima
vez que alguien entre al panel. Si aun así no respondes, prefectura cierra el caso por ti.

### 6.5.5 Cerrar una hora que quedó sin confirmar *(coordinación)*

En la pantalla de agendar, cada hora vencida ofrece dos botones:

- **«Sí se cubrió»** → la hora queda validada.
- **«No se cubrió»** → queda registrada como incidencia.

> **«No se cubrió» deja constancia, no borra nada.** El responsable queda anotado y **sobrevive
> aunque la hora se reasigne a otra persona**; aparece en el tablero de estadísticas. Se notifica a
> los dos implicados.
>
> Devolver la hora al circuito para buscar otro suplente es un **paso aparte y deliberado**
> («Reabrir hora»): si volviera sola a la bolsa, la incidencia se confundiría con una hora que
> nadie ha tomado todavía.

### 6.5.6 Justificantes: descargar antes de que caduquen

Un justificante suele ser un parte médico y vive en una carpeta pública, así que **no se conserva
indefinidamente**. La cuenta arranca el **día de la ausencia**:

| Días desde la ausencia | Qué pasa | Qué hay que hacer |
|---|---|---|
| **0 – 7** | Se descarga con normalidad desde la suplencia | Nada |
| **8 – 29** | Pasa a la cola de **Suplencias → Justificantes** | Dirección o prefectura decide: descargarlo o eliminarlo |
| **30 o más** | **Se borra solo** | Ya es tarde |

**Para resolver uno:**

1. **Suplencias → Justificantes**.
2. Pulsar el justificante. El aviso te dice **el nombre del archivo, su peso y su tipo**.
3. **Descargarlo primero** — es la acción destacada, y lo que hay que hacer antes de nada.
4. Marcar la casilla de confirmación; solo entonces se activa el botón rojo de eliminar.

La decisión queda **firmada**: se guarda quién resolvió, cuándo y qué decidió.

### 6.5.7 Consultar el histórico

| Pantalla | Qué muestra | Quién la ve |
|---|---|---|
| **Agenda** | Todas las ausencias, con motivos y justificantes | Coordinación |
| **Mis suplencias** | Tus coberturas y tus ausencias | Quien da clase |
| **Histórico del plantel** | Fecha · quién faltó · quién cubrió | **Todo el módulo**, también profesores |
| **Tablero** | Estadísticas: quién cubre más, motivos frecuentes, incumplimientos | Solo administradores |

> **El histórico del plantel es deliberadamente público dentro del módulo.** Quién cubrió a quién
> no es una pregunta privada. Lo que no lleva son **motivos ni justificantes**: para eso está la
> agenda, que es de coordinación.

---

## 6.6 Flujos de Horarios

El módulo **Horarios es de solo consulta**. Se escribe desde otros dos sitios, ambos de
administrador: el importador CSV y el editor por bloques.

### 6.6.1 Consultar horarios

**Horarios** → tres vistas: **por profesor**, **por aula** y **por grupo**. Se elige en el
desplegable de arriba.

Quien no coordina solo ve **Mi horario**: su propia semana, sin edición.

Cada bloque muestra la materia, el grupo y el aula, con un color por materia. Los recesos van en
ámbar y los conflictos en rojo.

### 6.6.2 Importar horarios por CSV *(solo administradores)*

**Horarios → Importar CSV.** Es la forma de cargar el horario de todo el colegio de una vez.

> ⚠️ **Es destructivo.** El archivo **reemplaza el horario completo** de todos los profesores que
> aparezcan en él. A quien no aparezca no se le toca nada.

**Formato — 7 columnas:**

```csv
profesor_email,dia,nivel,periodo,materia,grupo,aula
ana.torres@bilbao.edu.mx,lunes,,1,Matemáticas,1A Primaria,A-101
ana.torres@bilbao.edu.mx,lunes,,2,Matemáticas,2A Primaria,A-101
```

| Columna | Contenido |
|---|---|
| `profesor_email` | Correo institucional, tal cual está en su ficha |
| `dia` | `lunes`, `martes`, `miercoles`, `jueves` o `viernes` — sin acento en «miercoles» |
| `nivel` | **Normalmente vacío**: se deduce del grupo. Solo se escribe en una clase sin grupo |
| `periodo` | La etiqueta del periodo («1ª hora») o su número de orden, **dentro de la jornada de ese nivel** |
| `materia` | Nombre del catálogo. Se resuelve por nombre **y nivel** |
| `grupo` | Nombre del catálogo (ej. `1A Primaria`). Puede ir vacío |
| `aula` | Nombre del catálogo. Puede ir vacío |

**Pasos:**

1. Descargar la **plantilla de ejemplo** desde la misma pantalla: viene con la cabecera correcta y
   dos filas de muestra con datos reales del colegio.
2. Rellenarla. Máximo **2 MB**.
3. Subirla y pulsar **Previsualizar**.
4. **Revisar la vista previa.** Cada fila sale marcada como correcta, con aviso o con error, y
   explica el porqué.
5. **Confirmar.**

**Qué se valida:**

| Problema | Resultado |
|---|---|
| Profesor solapado consigo mismo | ❌ Error |
| Grupo con dos clases a la vez | ❌ Error |
| El nivel no cuadra con el grupo | ❌ Error |
| Periodo, materia, grupo o aula que no existen en el catálogo | ❌ Error |
| **Aula** ocupada por otro a la misma hora | ⚠️ Aviso — se puede importar |

Se comprueba tanto **dentro del archivo** como **contra el horario ya cargado** de los profesores
que no vienen en él.

> **Si hay filas con error, no se importan; las correctas sí.** Y si algo falla a mitad, no se
> escribe nada: la operación es atómica.

> **Los colores elegidos a mano se conservan.** El CSV no transporta el color, así que el
> importador lo fotografía antes de borrar y lo devuelve a las casillas equivalentes.

Cada profesor afectado recibe una notificación pidiéndole que revise su horario.

### 6.6.3 Corregir una clase suelta *(solo administradores)*

**Usuarios → localizar al profesor → botón naranja del calendario.**

> Está en Usuarios y no en Horarios porque es el único punto del panel que **escribe** horario.

- Arriba hay **cinco pestañas, una por nivel**. Se edita un nivel a la vez.
- **Un clic en una casilla = un bloque.** No hay selección múltiple ni arrastre: para cargar
  semanas enteras está el CSV.
- **Qué abre cada casilla:**
  - Casilla **libre** → formulario de **Clase**: grupo, materia, aula, acompañantes y color.
  - Casilla de **receso** → formulario de **Guardia**: lugar y color.
  - Casilla con **bloque existente** → edición de ese bloque.
  - Casilla **rayada** → ocupada por una clase de otro nivel; no se puede pulsar. Lleva un enlace
    a la pestaña donde sí se edita.
- Debajo de la rejilla está la **semana consolidada**: el editor muestra un nivel, esa rejilla
  muestra la verdad completa.

> **El tipo no se elige, lo decide la casilla.** El sistema rechaza una clase sobre un receso y una
> guardia fuera de él.

**Clases con dos profesores (coteaching):** en el formulario de Clase hay un campo de
**acompañantes** (hasta 2). El titular es el profesor cuya rejilla estás editando. Cada
acompañante tendrá esa hora ocupada en su propio horario, así que tampoco recibirá suplencias a
esa hora.

> ⚠️ **Borrar el bloque se lleva también a los acompañantes.** Es intencionado: una fila de
> acompañante sin titular no aparecería en ninguna rejilla, así que nadie podría volver a
> borrarla, pero le seguiría ocupando la hora a esa persona.

### 6.6.4 Agendar una guardia de receso

Desde el mismo editor: **pulsar una casilla de receso**. Se elige el lugar entre los disponibles
(patio, comedor, pasillos…) y se puede dar de alta uno nuevo ahí mismo.

Una guardia cuenta como ocupación real: a nadie se le asigna una suplencia mientras vigila el
patio, y el motivo que verá prefectura será *«Tiene guardia a esa hora»*. Y una guardia se puede
**suplir** igual que una clase.

---

## 6.7 Flujos de Intercambios

Un intercambio es un cambio **puntual** de dos clases entre dos profesores. **No altera el horario
permanente**: solo dice qué pasa esos dos días concretos.

### Pedir un intercambio

**Intercambios → Nuevo**, en tres pasos:

1. **Tu clase** — se marca **directamente en tu rejilla semanal** la clase que no vas a poder dar.
2. **El compañero** — se busca por nombre. Solo aparecen profesores.
3. **Su clase a cambio** — de entre las suyas dentro de los **7 días siguientes**.

Añadir el motivo y enviar.

> **La ventana de 7 días no es negociable.** Más allá deja de ser un intercambio puntual y pasa a
> ser un cambio de horario, que se hace en el editor.

### Responder a uno

Te llega por la campana. **Intercambios** → la solicitud → **Aceptar** o **Rechazar** (indicando
por qué).

### Validarlo *(coordinación)*

Todo intercambio aceptado necesita el visto bueno de prefectura o dirección:
**Intercambios → Validar** o **Denegar**.

**El ciclo completo:** `pendiente → aceptado / rechazado → validado / denegado`.
Los tres pasos avisan a quien corresponda.

---

## 6.8 Flujos de Eventos

**Eventos → Nuevo evento.**

1. **Fecha** (y fecha de fin, si dura varios días).
2. **Tipo:** festivo, evento, junta, entrega o suspensión.
3. **Título y descripción.**
4. **Audiencia** — decide **dónde se publica** y es excluyente:

| Audiencia | Dónde aparece |
|---|---|
| **Interno** | Solo en el panel |
| **Familias** | Panel + *Comunidad › Familias* del sitio público |
| **Estudiantes** | Panel + *Comunidad › Estudiantes* del sitio público |

5. **Niveles** *(opcional)*: dejar vacío = todo el colegio.

> **Todos los eventos, sea cual sea su audiencia, salen en el calendario del panel.** La audiencia
> solo decide si además se publican de cara al exterior.

En la pantalla de inicio, **pulsar un día del calendario** abre su ficha con los eventos y
cumpleaños de esa fecha.

---

## 6.9 Flujos de Redacción

El módulo cubre el blog (*Voces Bilbao*) y las noticias del colegio.

### Los dos sub-roles

| Sub-rol | Puede |
|---|---|
| **Editor** | Escribir y enviar a revisión. **No** publica directamente |
| **Revisor** | Todo lo del editor, más aprobar o rechazar lo que otros envían |

Los administradores actúan siempre como revisores.

### Publicar un artículo o una noticia

1. **Redacción → Artículos** (o **Noticias**) → **Nuevo**.
2. Título, extracto, contenido, imagen de portada, categoría y etiquetas.
3. **Estado:**
   - **Borrador** — no visible.
   - **Publicado** — visible ya.
   - **Programado** — se publica solo en la fecha indicada.
4. Si eres **editor**, pulsar **«Enviar a revisión»**. Si eres **revisor** o **admin**, puedes
   publicar directamente.

### Revisar lo que envían otros

**Redacción → Revisiones** → abrir → **Aprobar** o **Rechazar** con un comentario. El autor recibe
el aviso por la campana.

> Al editar algo **ya publicado**, los cambios quedan guardados como *versión pendiente* y la
> versión publicada **no cambia** hasta que un revisor los apruebe.

### Testimoniales

Los envían las familias desde el formulario público. **Redacción → Testimoniales** → aprobar o
rechazar. No se publican hasta aprobarse.

---

## 6.10 Catálogos: Aulas y Grupos

Alimentan el horario, y cada uno es su propio módulo asignable.

- **Aulas** — solo nombre.
- **Grupos** — nombre y nivel. Se filtran por pestañas de nivel.

Ambos listados tienen la acción **«Ver horario»** (naranja), que abre la semana de ese aula o ese
grupo: es lo que hace falta para saber cuándo un espacio está libre.

> **Antes de borrar se cuentan las dependencias.** Si un aula o un grupo está en uso, el botón de
> eliminar sale desactivado y el sistema dice **cuántas** clases dependen de él.

---

## 6.11 Preguntas frecuentes

### Acceso

**Olvidé mi contraseña.**
El panel no envía correos de recuperación. Pídele a un administrador que la restablezca desde
Usuarios. *(Ojo: el enlace «¿Olvidaste tu contraseña?» del sitio público es para cuentas de
familias, no para el panel.)*

**Escribo bien mis datos y no entra.**
El mensaje distingue los dos casos. Si dice *«No encontramos ninguna cuenta con ese correo»*,
revisa que esté bien escrito. Si dice *«Contraseña incorrecta»*, comprueba el bloqueo de
mayúsculas.

**«No tienes acceso a ese módulo».**
Alguien te enlazó a un módulo que no tienes asignado, o te lo quitaron. Pídeselo a un
administrador.

**Veo menos módulos que un compañero con el mismo puesto.**
Los módulos se asignan uno a uno en cada ficha. No se heredan del puesto.

### Suplencias

**Solicité una ausencia y sigue sin suplente.**
Es normal al principio: prefectura asigna manualmente. Puedes seguirlo en *Mis suplencias*.

**Un compañero está libre y me sale bloqueado.**
El sistema mira más cosas que el hueco:
- ya cubre otra suplencia a esa hora;
- tiene **guardia** de receso;
- se quedaría con **menos de 40 minutos libres** en todo el día;
- **equidad**: acumula bastantes más coberturas que el resto.

El motivo exacto aparece en su tarjeta.

**Sale el aviso «No imparte en Primaria» pero quiero asignarlo igual.**
Puedes: esos chips ámbar **avisan, no bloquean**.

**No puedo confirmar una cobertura.**
Solo se confirma **a partir del día de la clase**.

**Marqué «No se cubrió» y la hora sigue sin suplente.**
Es el comportamiento correcto. Registrar el incumplimiento y volver a abrir la hora son dos pasos
distintos: usa **«Reabrir hora»**.

**Ya no puedo descargar un justificante.**
Pasaron más de 7 días desde la ausencia y está en la cola de **Suplencias → Justificantes**. Si
pasaron 30, se borró solo.

**El justificante «se sube» pero llega vacío o dañado.**
Casi siempre son los límites de subida del servidor, no el archivo. Avisa a soporte técnico con el
peso del archivo.

**Aparezco como candidato a suplente y no debería.**
Un administrador puede marcar «No puede suplir a otros profesores» en tu ficha. No es
auto-servicio.

### Horarios

**Mi horario tiene una clase mal.**
Solo un administrador puede corregirlo, desde Usuarios → tu ficha → botón del calendario.

**Importé un CSV y desaparecieron clases.**
Es el comportamiento documentado: el archivo **reemplaza el horario completo** de los profesores
que aparecen en él. Si una clase no estaba en el archivo, se borró. Vuelve a importar con el
archivo completo.

**El importador dice que un periodo no existe.**
El número de periodo es **relativo a la jornada de su nivel**. Comprueba que el grupo de esa fila
es del nivel correcto: de él se deduce la jornada.

**Un bloque sale rayado y no puedo pulsarlo.**
Es una clase de **otro nivel** que pisa esa hora. Se edita desde su propia pestaña; hay un enlace
en la casilla.

**Una clase aparece marcada en rojo.**
Es un conflicto: dos clases que se pisan. Suele venir de datos importados antes de una corrección
del esquema. Se puede borrar desde el editor.

### General

**Borré una notificación sin querer.**
Tienes **6 segundos** para pulsar «Deshacer». Después no hay forma de recuperarla.

**El sitio se ve sin colores ni formato.**
Es un problema de los archivos de estilo. Avisa a soporte técnico: no es algo que se arregle desde
el panel.

**Cambié algo y no se ve.**
Recarga con `Ctrl + F5` para forzar la descarga de los archivos actualizados.

**¿Qué formatos acepta un justificante?**
PDF e imágenes, hasta **50 MB**.

**¿A quién aviso si algo falla?**
**Soporte técnico** en el menú lateral. Está disponible para todo el mundo y abre WhatsApp con tu
nombre y tu puesto ya rellenados.

---

## 6.12 Glosario

| Término | Significado en este sistema |
|---|---|
| **Módulo** | Sección del panel que se asigna a cada persona (Suplencias, Horarios…) |
| **Tipo de personal** | Papel en la operación académica: profesor, prefecto, administrativo, directivo |
| **Coordinar** | Poder ver datos de terceros: horarios ajenos, motivos, justificantes. Lo hacen admin, prefectura y dirección |
| **Periodo** | Un bloque de la jornada («1ª hora», «Receso»). **Cada nivel tiene la suya** |
| **Nivel** | Maternal, Kinder, Primaria, Secundaria o Bachillerato |
| **Bloque** | Una clase concreta en la rejilla: día + periodo + grupo + materia |
| **Guardia** | Vigilancia durante el receso. Cuenta como ocupación y se puede suplir |
| **Coteaching** | Una clase impartida por un titular y hasta dos acompañantes |
| **Materia dividida** | Dos asignaturas a la misma hora entre las que se reparte el grupo |
| **Clase conjunta** | Dos grupos juntos en la misma clase |
| **Origen anticipada / sin aviso** | Si la ausencia se avisó con antelación o no. «Sin aviso» exige justificante |
| **Equidad** | Regla que reparte las coberturas: se prioriza a quien menos acumule |
| **Validar una hora** | Confirmar que la cobertura efectivamente ocurrió |
| **Intercambio (*swap*)** | Cambio puntual de dos clases entre dos profesores |
| **Audiencia de un evento** | Quién lo ve: interno, familias o estudiantes |
