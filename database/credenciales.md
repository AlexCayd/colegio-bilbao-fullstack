# Credenciales del seed — Colegio Bilbao

Cuentas creadas por `database/development/development.sql` (desarrollo) y
`database/deploy/deploy.sql` (producción).

> ⚠️ Este archivo documenta **contraseñas de desarrollo**. No usar estas credenciales en
> producción sin cambiarlas.

---

## Regla única: todo el mundo entra con `password123`

**Las dos semillas asignan la misma contraseña a todas las cuentas: `password123`.**
La única excepción es el administrador:

| Cuenta | Contraseña |
|--------|-----------|
| `admin@bilbao.edu.mx` (solo en `development.sql`) | **`Tlalmimilolpan39%`** |
| `alexander.oliva@bilbao.edu.mx` (solo en `deploy.sql`) | **`Tlalmimilolpan39%`** |
| **todas las demás** (claustro, direcciones, prefectura, cuentas de prueba) | **`password123`** |

Hash compartido: `$2y$12$fE9ZtOwTUDH2Q/0FnQTgHeQKpu1S4uuWA7tlSaDoL.6zjomDTUTjy`
(bcrypt, coste 12). El del administrador es
`$2y$12$nJQBtZftIX.10iSyqFSv6uKIw0BhTQsGeCOO1xSkL.Cu77TWZ1Kai` (el suyo original, mismo
coste) y **no se toca**.

> ⚠️ **En `deploy.sql` no existe `admin@bilbao.edu.mx`**: esa cuenta es del seed de
> desarrollo. El administrador de producción es `alexander.oliva@bilbao.edu.mx` (id 2), y su
> contraseña es la **única fila que no viene tal cual del volcado** — se fija a mano al
> mismo hash. El resto de cuentas de `deploy.sql` conservan el hash real de producción, que
> no es necesariamente el compartido de arriba.

> Antes había cinco hashes distintos —`Tlalmimilolpan39%`, `EditorBilbao25` y tres
> contraseñas personales sin documentar— y no había forma de saber con cuál entraba cada
> cuenta sin probar. Al añadir un usuario nuevo al seed, copiar el hash de arriba.
>
> ⚠️ **En producción esto es una contraseña inicial, no una definitiva**: `deploy.sql`
> siembra al claustro real con ella, así que hay que forzar el cambio en el primer acceso
> o rotarla antes de abrir el panel.

---

## Cuentas de prueba

Solo existen en `development.sql`.

| Email | Nombre | Rol | Tipo de personal | Módulos |
|-------|--------|-----|------------------|---------|
| `admin@bilbao.edu.mx` | Administrador Bilbao | administrador | — | todos (implícito) |
| `prefecto@bilbao.edu.mx` | Prefectura (prueba) | usuario | prefecto | `suplencias,horarios,swaps` |

⚠️ **No queda ninguna cuenta de profesor en el seed.** `profesor1@` y `profesor2@` se retiraron
junto con el resto del claustro: eran dos docentes ficticios sin horario —la rejilla la pone ahora
el importador CSV— y tenerlos solo servía para que alguien probara Suplencias contra un plantel de
dos personas y sacara conclusiones que no valen.

**Para probar cualquier flujo de profesor** (Suplencias, Intercambios, «Mi horario», la ficha del
colaborador) hay que **importar antes el CSV de horarios** y entrar con cualquiera del claustro
real que haya creado, con `password123`.

`prefecto` **no puede cubrir suplencias** — solo registrarlas. Es el rol para probar el flujo de
prefectura (`/dashboard/suplencias/crear`, con opción "sin aviso"). ⚠️ Sin claustro importado no
tiene a quién asignar: la agenda y el buscador de suplentes salen vacíos, y eso es correcto.

---

## Cuentas de dirección y sistema

| Email | Rol | Tipo de personal | Módulos |
|-------|-----|------------------|---------|
| `admin@bilbao.edu.mx` | administrador | — | todos (implícito) |
| `alexander.oliva@bilbao.edu.mx` | administrador | — | todos (implícito) |
| `dr.ludlow@bilbao.edu.mx` | administrador | profesor, administrativo | todos (implícito) |
| `majo.soberon@bilbao.edu.mx` | usuario · revisor | **administrativo** | `redaccion,eventos` |
| `sasha@bilbao.edu.mx` | usuario · editor | **administrativo** | `redaccion,eventos` |

> ⚠️ Las dos cuentas de Redacción eran `profesor` y pasaron a `administrativo` al vaciar el
> claustro. Son el banco de pruebas del flujo editorial —revisor y editor, y además los autores de
> los dos artículos del seed—, no docentes: dejarlas como `profesor` contradecía el vaciado y
> borrarlas dejaba los artículos sin autor y el flujo sin nadie que no fuera admin.
> `mauricio@bilbao.edu.mx` (que sí era solo docente) se retiró.

---

## Direcciones

Seis cuentas `directivo` con **los mismos módulos**. Lo que las distingue no son los permisos
sino el **alcance de los datos**, que sale de `usuarios.niveles`: su tablero, su agenda de
suplencias, sus intercambios, su cola de justificantes y los avisos que reciben se limitan a
esos niveles.

> **`niveles` vacío = todo el colegio.** Es la dirección general, y es deliberado. Ojo al crear una
> dirección nueva: olvidar marcarle los niveles no la deja sin acceso, se lo da entero.

| Id | Email | Alcance |
|----|-------|---------|
| 72 | `direccion@bilbao.edu.mx` | **todo el colegio** (sin niveles) |
| 73 | `direccion.maternal@bilbao.edu.mx` | Maternal |
| 74 | `direccion.kinder@bilbao.edu.mx` | Kinder |
| 75 | `direccion.primaria@bilbao.edu.mx` | Primaria |
| 76 | `direccion.secundaria@bilbao.edu.mx` | Secundaria |
| 77 | `direccion.bachillerato@bilbao.edu.mx` | Bachillerato |

Módulos de las seis: `suplencias,horarios,usuarios,profesores,prefectura,administrativos,directivos,aulas,grupos,eventos,swaps,soporte`.

> Un `directivo` que no sea admin tiene la **configuración en solo lectura** (`soloLectura()`), pero
> el tablero de suplencias y los justificantes **sí** son suyos: prefectura coordina las ausencias,
> dirección revisa el parte médico y mide.

---

## Claustro docente

⚠️ **`development.sql` ya NO siembra claustro.** Los 43 profesores que había (ids 20–62) se
retiraron junto con el horario y las suplencias de ejemplo que colgaban de ellos.

El claustro entra ahora por **`/dashboard/horarios/importar`**, con el CSV de 8 columnas que
exporta el colegio: ese archivo da de alta a cada profesor que no exista, le deriva el correo del
nombre (`Pablo Benlliure` → `pablo.benlliure@bilbao.edu.mx`), le pone los módulos de un docente
(`suplencias,horarios,swaps`), le declara los niveles que imparte según sus clases y le asigna la
contraseña **`password123`**, igual que el resto del seed. Con el archivo real son **39 cuentas**.

**El motivo de vaciarlo:** un claustro inventado obligaba a decidir, en cada importación, si la
«Fernanda» del archivo era la «Fernanda Covarrubias» del seed o la «María Fernanda Uribe». El
importador no lo adivina —y no debe: elegir mal le da a alguien el horario de otra persona—, así
que avisa y crea una cuenta nueva. Partiendo de cero esa ambigüedad no existe.

> En **producción** el claustro real sí va en `deploy.sql`, y ahí los nombres están completos. Si
> el CSV del colegio usa nombres cortos, hay que **renombrar en Usuarios para que coincidan con el
> archivo antes de importar**, o saldrán cuentas duplicadas. La vista previa del importador lista
> los parecidos precisamente para eso.

## Prefectura de ejemplo

| Email | Rol | Tipo de personal | Módulos |
|-------|-----|------------------|---------|
| `ricardo.beltran@bilbao.edu.mx` | usuario | prefecto | `suplencias,horarios` |
| `paola.estrada@bilbao.edu.mx` | usuario | prefecto | `suplencias,horarios` |

---

## Notas sobre roles y tipos

- **Roles** (`usuarios.rol`): solo dos — `administrador` (**Admin** en la UI) y `usuario`.
  El admin accede a **todo** el panel sin excepción; `usuario` solo a los módulos del CSV
  `modulos`. El antiguo `superadmin` se eliminó: sus cuentas pasaron a `administrador`.
- **Tipo de personal** (`usuarios.tipo_personal`): `profesor`, `prefecto`, `administrativo` y
  `directivo`. `prefecto` y `directivo` son **excluyentes** — no se combinan con ningún otro
  tipo ni entre sí. `profesor` y `administrativo` sí se combinan (ej. Alfonso Ludlow).
- **Solo los profesores cubren suplencias.** Prefectura y administrativos las registran y
  coordinan, pero nunca aparecen como candidatos a suplente.

## Cómo cargar el seed

```bash
mysql -u root -p colegiobilbao < database/database.sql                 # estructura
mysql -u root -p colegiobilbao < database/development/development.sql  # datos de desarrollo
# En producción, en vez del seed de desarrollo:
# mysql -u root -p colegiobilbao < database/deploy/deploy.sql
```

## Regenerar el hash

```bash
php -r 'echo password_hash("password123", PASSWORD_BCRYPT, ["cost" => 12]), "\n";'
```
