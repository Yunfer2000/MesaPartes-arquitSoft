# Especificaciones SDD

El sistema sigue el desarrollo guiado por especificaciones (SDD): **primero se escribe la especificación y después el código y la prueba**. Las especificaciones están en [`sistema/specs/`](../../sistema/specs).

## 1. Formato

Cada especificación tiene:

1. Datos del caso (endpoint o caso de uso, actor, requisitos que cubre).
2. Flujo básico y flujos alternos.
3. Reglas de negocio con código `RN-XXnn-nn`.
4. Criterios de aceptación con código `CA-XXnn-nn` en formato **Dado / Cuando / Entonces**. Cada criterio nombra la prueba automática que lo comprueba (`tests/...php::test_...`).
5. **Tareas técnicas**: la lista de cambios en el código que hacen falta para cumplir la especificación, marcadas cuando están hechas.

## 2. Especificaciones de este sprint

| Especificación | Criterios | Tareas técnicas | Endpoint |
|---|---|---|---|
| [API01 Consultar expediente](../../sistema/specs/API01-consultar-expediente.md) | 6 | 7 | `POST /api/v1/consultas` |
| [API02 Verificar documento](../../sistema/specs/API02-verificar-documento.md) | 2 | 4 | `GET /api/v1/verificaciones/{codigo}` |
| [API03 Tipos de documento](../../sistema/specs/API03-tipos-de-documento.md) | 10 | 7 | CRUD `/api/v1/tipos-documento` |
| [API04 Token de acceso](../../sistema/specs/API04-token-de-acceso.md) | 3 | 5 | `POST` y `DELETE /api/v1/tokens` |

Además siguen vigentes las 18 especificaciones `CUxx` de los casos de uso del sistema (124 criterios), escritas antes de este sprint.

## 3. Ejemplo de criterio

```text
CA-API03-08 No elimina un tipo en uso
- Dado     un tipo usado por un expediente
- Cuando   el Administrador lo elimina
- Entonces responde 409 con el aviso y el tipo sigue en la base de datos
- Cubre:   flujo 4a, RN-API03-03
- Prueba:  tests/Feature/Api/TiposDocumentoApiTest.php::test_no_elimina_un_tipo_usado_por_expedientes_y_responde_409
```

## 4. Control automático

La prueba `tests/Feature/EspecificacionesTest.php` revisa en cada ejecución que:

- todas las especificaciones `CUxx` y `APIxx` tengan criterios de aceptación;
- cada prueba nombrada en un criterio exista de verdad en el código;
- las especificaciones `APIxx` tengan la sección **Tareas técnicas**.

Así, la documentación y el código no se pueden separar sin que el CI lo avise.
