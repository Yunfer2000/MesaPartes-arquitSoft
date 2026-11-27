# Especificaciones del sistema (SDD)

Esta carpeta guarda la **especificación** de cada caso de uso del sistema. Aplicamos
**Desarrollo Guiado por Especificaciones** (SDD, *Spec-Driven Development*): todo cambio
empieza por escribir o actualizar la especificación, luego la prueba y recién después el código.

> **ICONIX sigue siendo la metodología del proyecto.** Las especificaciones salen de los casos
> de uso, robustez y secuencia de ICONIX (carpeta `iconix/` del proyecto). SDD solo agrega
> criterios de aceptación verificables y los une con las pruebas automáticas.

## Qué hay en cada especificación

Un archivo por caso de uso, por ejemplo `CU08-recepcionar-expediente.md`:

1. **Datos del caso de uso:** actor, paquete, requisitos (RF/RNF) y figuras ICONIX.
2. **Flujo básico y alternos:** los mismos pasos del caso de uso de ICONIX.
3. **Reglas de negocio:** lo que el sistema debe cumplir siempre.
4. **Criterios de aceptación** con el formato *Dado / Cuando / Entonces*. Cada criterio:
   - tiene un código único `CA-CUxx-nn`,
   - dice qué paso del flujo cubre,
   - nombra la prueba que lo comprueba: `` `tests/Feature/Archivo.php::nombre_de_la_prueba` ``.

## El ciclo de trabajo (siempre en este orden)

1. **Especificar.** Abre (o crea desde `_plantilla.md`) la especificación del caso de uso y
   escribe el criterio nuevo o cambiado.
2. **Probar.** Escribe la prueba que nombra el criterio. Al correr `php artisan test` debe
   **fallar** (todavía no hay código).
3. **Implementar.** Escribe el código hasta que la prueba pase.
4. **Revisar.** Si el cambio afecta un caso de uso, avisa para actualizar las figuras ICONIX
   y el Informe final.
5. **Pull request.** El PR dice qué criterios (`CA-...`) agrega o cambia.

## Control automático

La prueba `tests/Feature/EspecificacionesTest.php` revisa en cada `php artisan test` (y en
GitHub Actions) que:

- cada especificación tenga sus secciones obligatorias,
- cada criterio tenga *Dado*, *Cuando*, *Entonces* y al menos una prueba,
- las pruebas nombradas existan de verdad (archivo y función),
- no se repitan los códigos de criterio.

Si alguien borra o renombra una prueba sin actualizar la especificación, el control falla.

## Estado

| Caso de uso | Criterios | Bloque |
|---|---|---|
| [CU02 Iniciar sesión](CU02-iniciar-sesion.md) | 9 | Bloque 5 |
| [CU03 Gestionar usuarios y roles](CU03-gestionar-usuarios-y-roles.md) | 5 | Bloque 5 |
| [CU04 Gestionar áreas](CU04-gestionar-areas.md) | 7 | Bloque 5 |
| [CU05 Presentar trámite virtual](CU05-presentar-tramite-virtual.md) | 18 | Bloque 1 |
| [CU06 Subsanar observación](CU06-subsanar-observacion.md) | 6 | Bloque 1 |
| [CU07 Consultar seguimiento de expediente](CU07-consultar-seguimiento-de-expediente.md) | 14 | Bloque 3 |
| [CU08 Recepcionar expediente](CU08-recepcionar-expediente.md) | 7 | Yunfer (Bloque 2) |
| [CU09 Observar expediente](CU09-observar-expediente.md) | 4 | Bloque 2 |
| [CU10 Derivar expediente](CU10-derivar-expediente.md) | 11 | Bloque 4 |
| [CU11 Confirmar recepción en área](CU11-confirmar-recepcion-en-area.md) | 4 | Bloque 4 |
| [CU12 Actualizar estado del expediente](CU12-actualizar-estado-del-expediente.md) | 3 | Bloque 4 |
| [CU13 Registrar respuesta](CU13-registrar-respuesta.md) | 5 | Bloque 4 |
| [CU14 Buscar expedientes](CU14-buscar-expedientes.md) | 3 | Bloque 5 |
| [CU15 Generar reportes](CU15-generar-reportes.md) | 2 | Bloque 5 |
| [CU16 Notificar al administrado](CU16-notificar-al-administrado.md) | 4 | Bloque 3 |
| [CU17 Registrar expediente presencial](CU17-registrar-expediente-presencial.md) | 5 | Yunfer (Bloque 2) |
| [CU18 Monitorear plazos y pendientes](CU18-monitorear-plazos-y-pendientes.md) | 5 | Bloque 5 |
| [CU19 Verificar documento](CU19-verificar-documento.md) | 5 | Yunfer (Bloque 2) |

### API REST v1 (Sprint 2 del curso de Arquitectura de Software)

Las especificaciones `APIxx` siguen el mismo formato y además tienen la sección **Tareas técnicas**.

| Especificación | Criterios | Endpoint |
|---|---|---|
| [API01 Consultar expediente](API01-consultar-expediente.md) | 6 | `POST /api/v1/consultas` |
| [API02 Verificar documento](API02-verificar-documento.md) | 2 | `GET /api/v1/verificaciones/{codigo}` |
| [API03 Tipos de documento](API03-tipos-de-documento.md) | 11 | CRUD `/api/v1/tipos-documento` |
| [API04 Token de acceso](API04-token-de-acceso.md) | 3 | `POST/DELETE /api/v1/tokens` |

CU01 *Registrar cuenta* se eliminó (el ciudadano presenta sin cuenta), por eso no tiene especificación.
Los flujos están actualizados con los cambios acordados después del Informe v3 (formulario sin cuenta, subdominio del personal, derivación múltiple, firma electrónica del sistema).
