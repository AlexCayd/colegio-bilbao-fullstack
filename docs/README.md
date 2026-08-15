# Documentación — Colegio Bilbao

Sitio web institucional del Colegio Bilbao e intranet de colaboradores.
PHP 8 con MVC propio · MySQL · SCSS/Gulp · IIS.

---

## Índice

### Para desarrollo y sistemas

| # | Documento | Qué responde |
|---|---|---|
| 1 | [Arquitectura del sistema](01-arquitectura.md) | Cómo funcionan el enrutamiento, el MVC, el ORM, los permisos y el aislamiento de assets. Incluye el inventario de deuda técnica |
| 2 | [Estándares de código](02-estandares-codigo.md) | Formato PHPDoc obligatorio, nomenclatura, reglas de seguridad y cómo generar la documentación de la API |
| 3 | [Dependencias](03-dependencias.md) | Versiones exactas de PHP, Composer, npm y las librerías de cliente por CDN |
| 4 | [Base de datos](04-base-de-datos.md) | Diagrama entidad-relación, diccionario de las 19 tablas, ausencia de triggers y comprobaciones de integridad |
| 5 | [Despliegue y configuración](05-despliegue.md) | Requisitos del servidor, variables de entorno, diferencias entre entornos y resolución de problemas |

### Para el personal del colegio

| # | Documento | Qué responde |
|---|---|---|
| 6 | [Manual de usuario](06-manual-usuario.md) | Roles y permisos, flujos paso a paso de cada módulo, preguntas frecuentes y glosario |

### Documentación complementaria en el repositorio

| Archivo | Contenido |
|---|---|
| [`CLAUDE.md`](../CLAUDE.md) | **Por qué** cada decisión de diseño es como es. Lectura obligada antes de cambiar el comportamiento de un módulo |
| [`database/CLAUDE.md`](../database/CLAUDE.md) | Reglas de los tres archivos SQL, `ALTER` históricos y consultas de verificación completas |
| [`database/credenciales.md`](../database/credenciales.md) | Cuentas del seed y sus contraseñas |
| [`includes/.env.example`](../includes/.env.example) | Plantilla de configuración comentada |
| [`phpdoc.dist.xml`](../phpdoc.dist.xml) | Configuración de phpDocumentor |

### Versiones publicadas

Dos documentos están además publicados como páginas web para compartir fuera del
repositorio. **Son renderizaciones: el original sigue siendo el `.md`**, y los cambios se
hacen ahí y se vuelven a publicar.

| Página | Contenido |
|---|---|
| [Base de datos](https://claude.ai/code/artifact/1af65897-cca5-4338-9547-62bf73a201df) | El mismo `04-base-de-datos.md`, con el diagrama ER ya dibujado |
| [Manual de la intranet](https://claude.ai/code/artifact/887d59e6-979b-4549-b014-40730e9962ad) | `06-manual-usuario.md` como página navegable, para la dirección y el personal |

---

## Por dónde empezar

| Situación | Ruta de lectura |
|---|---|
| **Primera vez con el código** | [Arquitectura](01-arquitectura.md) → [Base de datos §4.2](04-base-de-datos.md#42-tres-invariantes-que-gobiernan-todo-el-esquema) → [`CLAUDE.md`](../CLAUDE.md) |
| **Hay que levantarlo en local** | [Despliegue §5.3](05-despliegue.md#53-entorno-de-desarrollo-local) |
| **Hay que desplegar a producción** | [Despliegue §5.5](05-despliegue.md#55-despliegue-a-producción) |
| **Voy a tocar el esquema** | [`database/CLAUDE.md`](../database/CLAUDE.md) **antes** de abrir un `.sql` |
| **Voy a escribir código** | [Estándares §2.6](02-estandares-codigo.md#26-reglas-de-seguridad-no-negociables) y la [checklist §2.9](02-estandares-codigo.md#29-checklist-de-revisión) |
| **Alguien del colegio pregunta cómo se hace algo** | [Manual de usuario](06-manual-usuario.md) |

---

## Tres cosas que hay que saber antes de tocar nada

**1. El servidor local necesita el router file.**

```bash
php -S localhost:3000 dev-server.php     # ✅
php -S localhost:3000                    # ❌ el sitio se pinta sin CSS ni JS
```

**2. `database/database.sql` empieza con `DROP TABLE`.** Re-ejecutarlo sobre producción destruye
todos los datos. Para actualizar una base con datos reales se aplica a mano el `ALTER` puntual
documentado en `database/CLAUDE.md`.

**3. La jornada escolar es *por nivel*, y la disponibilidad se calcula *por reloj*.** Dos clases no
chocan por compartir `periodo_id`: chocan por solaparse en el tiempo. Es el invariante del que
cuelga todo el módulo de horarios y suplencias — detalle en
[§4.2](04-base-de-datos.md#42-tres-invariantes-que-gobiernan-todo-el-esquema).

---

## Asuntos abiertos

| Asunto | Dónde está documentado |
|---|---|
| 🔴 **`includes/.env` estuvo versionado en git** — hay que rotar las credenciales | [Despliegue §5.2](05-despliegue.md#️-secretos-versionados--acción-pendiente) |
| 🟠 SQL por concatenación en lugar de sentencias preparadas | [Arquitectura §1.12](01-arquitectura.md#112-deuda-técnica-conocida) |
| 🟠 `diagnostico.php` e `informacion.php` deben eliminarse antes de producción | [Despliegue §5.5](05-despliegue.md#lista-de-comprobación-previa) |
| 🟡 `BlogController` con 4.200 líneas: candidato a dividirse por módulo | [Arquitectura §1.12](01-arquitectura.md#112-deuda-técnica-conocida) |
| 🟡 Sin suite de pruebas automatizadas | [Base de datos §4.6](04-base-de-datos.md#46-comprobaciones-de-integridad) tiene los invariantes ya escritos como SQL |
| 🟡 Librerías de cliente por CDN sin *subresource integrity* | [Dependencias §3.4](03-dependencias.md#34-librerías-de-cliente-cdn) |

---

*Última revisión: 11 de agosto de 2026.*
