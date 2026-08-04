# Credenciales del seed — Colegio Bilbao

Cuentas creadas por `database/development/development.sql` (desarrollo) y
`database/deploy/deploy.sql` (producción).

> ⚠️ Este archivo documenta **contraseñas de desarrollo**. Las cuentas de dirección conservan sus
> hashes originales y su contraseña real no está aquí. No usar estas credenciales en producción.

---

## Cuentas de prueba

Todas con la contraseña **`Tlalmimilolpan39%`**. Solo existen en `development.sql`.

| Email | Nombre | Rol | Tipo de personal | Módulos |
|-------|--------|-----|------------------|---------|
| `admin@bilbao.edu.mx` | Administrador Bilbao | administrador | — | todos (implícito) |
| `profesor1@bilbao.edu.mx` | Profesor Uno (prueba) | usuario | profesor | `suplencias,horarios` |
| `profesor2@bilbao.edu.mx` | Profesor Dos (prueba) | usuario | profesor | `suplencias,horarios` |
| `prefecto@bilbao.edu.mx` | Prefectura (prueba) | usuario | prefecto | `suplencias,horarios` |

`profesor1` y `profesor2` tienen **horario real** (10 clases, 2 por día): `profesor1` imparte
Matemáticas en Secundaria y `profesor2` Filosofía en Bachillerato. Les quedan 5 horas libres
diarias, así que sirven tanto de ausente como de suplente en el módulo de Suplencias.

`prefecto` **no puede cubrir suplencias** — solo registrarlas. Es el rol para probar el flujo de
prefectura (`/dashboard/suplencias/crear`, con opción "sin aviso").

---

## Cuentas de dirección y sistema

| Email | Contraseña | Rol | Tipo de personal | Módulos |
|-------|-----------|-----|------------------|---------|
| `admin@bilbao.edu.mx` | `Tlalmimilolpan39%` | administrador | — | todos (implícito) |
| `alexander.oliva@bilbao.edu.mx` | *(propia)* | administrador | — | todos (implícito) |
| `dr.ludlow@bilbao.edu.mx` | *(propia)* | administrador | profesor, administrativo | todos (implícito) |
| `majo.soberon@bilbao.edu.mx` | *(propia)* | usuario · revisor | profesor | `redaccion,suplencias,horarios` |
| `sasha@bilbao.edu.mx` | `EditorBilbao25` | usuario · editor | profesor | `redaccion,suplencias,horarios` |
| `mauricio@bilbao.edu.mx` | *(propia)* | usuario | profesor | `suplencias,horarios` |

---

## Claustro docente

Los **43 profesores reales** (ids 20–62), con el patrón `nombre.apellido@bilbao.edu.mx`, comparten
la contraseña de desarrollo **`EditorBilbao25`**. Todos son `usuario` · `profesor` con módulos
`suplencias,horarios`, y todos tienen horario cargado.

## Prefectura de ejemplo

| Email | Contraseña | Rol | Tipo de personal | Módulos |
|-------|-----------|-----|------------------|---------|
| `ricardo.beltran@bilbao.edu.mx` | `EditorBilbao25` | usuario | prefecto | `suplencias,horarios` |
| `paola.estrada@bilbao.edu.mx` | `EditorBilbao25` | usuario | prefecto | `suplencias,horarios` |

---

## Notas sobre roles y tipos

- **Roles** (`usuarios.rol`): solo dos — `administrador` (**Admin** en la UI) y `usuario`.
  El admin accede a **todo** el panel sin excepción; `usuario` solo a los módulos del CSV
  `modulos`. El antiguo `superadmin` se eliminó: sus cuentas pasaron a `administrador`.
- **Tipo de personal** (`usuarios.tipo_personal`): `profesor`, `prefecto` y `administrativo`.
  `prefecto` es **excluyente** — no se combina con ningún otro tipo. `profesor` y `administrativo`
  sí se combinan (ej. Alfonso Ludlow).
- **Solo los profesores cubren suplencias.** Prefectura y administrativos las registran y
  coordinan, pero nunca aparecen como candidatos a suplente.

## Cómo cargar el seed

```bash
mysql -u root -p colegiobilbao < database/database.sql                 # estructura
mysql -u root -p colegiobilbao < database/development/development.sql  # datos de desarrollo
# En producción, en vez del seed de desarrollo:
# mysql -u root -p colegiobilbao < database/deploy/deploy.sql
```
