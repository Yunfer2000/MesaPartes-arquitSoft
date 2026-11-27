# API03 Gestionar tipos de documento por API

| Campo | Valor |
|---|---|
| Endpoints | `GET/POST /api/v1/tipos-documento`, `GET/PUT/DELETE /api/v1/tipos-documento/{id}` |
| Caso de uso de origen | Catálogo de tipos de documento del Administrador (paquete Administración) |
| Actor | Público (lectura) y Administrador con token (escritura) |
| Requisitos | RNF04 (rendimiento: catálogo en caché), RNF06 (permisos por rol), RNF14 (auditoría) |
| Sprint | Sprint 2 · Arquitectura de Software |

## Objetivo

Exponer el catálogo de tipos de documento como recurso REST completo (CRUD), equivalente al
recurso `products` del caso de referencia ayaPet.

## Flujo básico

1. Cualquiera lista el catálogo (`GET`) o consulta un tipo por su id.
2. El Administrador obtiene un token (API04) y registra un tipo con `POST`.
3. El sistema valida el cuerpo, guarda el tipo, registra la auditoría y borra la caché del catálogo.
4. El Administrador lo actualiza con `PUT` o lo elimina con `DELETE`.

## Flujos alternos

- **1a.** El id no existe: 404.
- **2a.** Sin token: 401. Con token de otro rol: 403.
- **3a.** Datos inválidos (vacío, repetido o más de 100 caracteres): 422 y no se guarda nada.
- **4a.** El tipo lo usan expedientes: `DELETE` responde 409; hay que desactivarlo con `PUT` (`activo: false`).

## Reglas de negocio

- **RN-API03-01.** El nombre es único y se guardan sin espacios en los extremos.
- **RN-API03-02.** Solo el Administrador crea, edita o elimina.
- **RN-API03-03.** No se elimina un tipo con expedientes (integridad referencial).
- **RN-API03-04.** La lista se guarda en caché una hora y se invalida al crear, editar, activar, desactivar o eliminar.
- **RN-API03-05.** Cada cambio queda en el registro de auditoría.

## Criterios de aceptación

### CA-API03-01 Lista el catálogo en JSON

- **Dado** el catálogo inicial
- **Cuando** se pide `GET /api/v1/tipos-documento`
- **Entonces** responde 200 con todos los tipos y sus campos `id`, `nombre`, `descripcion` y `activo`
- **Cubre:** paso 1
- **Prueba:** `tests/Feature/Api/TiposDocumentoApiTest.php::test_lista_los_tipos_de_documento_en_json`

### CA-API03-02 Filtra los activos

- **Dado** un tipo desactivado
- **Cuando** se pide la lista con `?activo=1`
- **Entonces** solo vienen los activos
- **Cubre:** paso 1
- **Prueba:** `tests/Feature/Api/TiposDocumentoApiTest.php::test_filtra_solo_los_activos`

### CA-API03-03 Consulta por id

- **Dado** un tipo existente y un id que no existe
- **Cuando** se consultan
- **Entonces** el primero responde 200 y el segundo 404 «Recurso no encontrado.»
- **Cubre:** paso 1, flujo 1a
- **Prueba:** `tests/Feature/Api/TiposDocumentoApiTest.php::test_consulta_por_id_y_404_si_no_existe`

### CA-API03-04 Registro válido

- **Dado** el Administrador con token
- **Cuando** registra «  Solicitud de traslado  »
- **Entonces** responde 201 con la cabecera Location, el nombre sin espacios, activo, y queda en la auditoría
- **Cubre:** pasos 2 y 3, RN-API03-01, RN-API03-05
- **Prueba:** `tests/Feature/Api/TiposDocumentoApiTest.php::test_el_administrador_registra_un_tipo_y_responde_201`

### CA-API03-05 Registro inválido

- **Dado** el Administrador con token
- **Cuando** envía un nombre vacío, uno repetido o uno de 101 caracteres
- **Entonces** responde 422 en los tres casos y la cantidad de tipos no cambia
- **Cubre:** flujo 3a, RN-API03-01
- **Prueba:** `tests/Feature/Api/TiposDocumentoApiTest.php::test_rechaza_datos_invalidos_sin_guardar_nada`

### CA-API03-06 Actualización

- **Dado** un tipo existente
- **Cuando** el Administrador lo actualiza con `PUT`
- **Entonces** responde 200 y al volver a consultarlo se ve el cambio; un id inexistente da 404
- **Cubre:** paso 4
- **Prueba:** `tests/Feature/Api/TiposDocumentoApiTest.php::test_actualiza_y_el_cambio_se_ve_al_volver_a_consultar`

### CA-API03-07 Eliminación

- **Dado** un tipo sin expedientes
- **Cuando** el Administrador lo elimina
- **Entonces** responde 204, ya no existe y eliminarlo otra vez da 404
- **Cubre:** paso 4
- **Prueba:** `tests/Feature/Api/TiposDocumentoApiTest.php::test_elimina_un_tipo_sin_uso_y_responde_204`

### CA-API03-08 No elimina un tipo en uso

- **Dado** un tipo usado por un expediente
- **Cuando** el Administrador lo elimina
- **Entonces** responde 409 con el aviso y el tipo sigue en la base de datos
- **Cubre:** flujo 4a, RN-API03-03
- **Prueba:** `tests/Feature/Api/TiposDocumentoApiTest.php::test_no_elimina_un_tipo_usado_por_expedientes_y_responde_409`

### CA-API03-09 Permisos

- **Dado** una petición sin token y otra con token de Mesa de Partes
- **Cuando** intentan registrar un tipo
- **Entonces** responden 401 y 403 y no se guarda nada
- **Cubre:** flujo 2a, RN-API03-02
- **Prueba:** `tests/Feature/Api/TiposDocumentoApiTest.php::test_sin_token_responde_401_y_sin_rol_administrador_403`

### CA-API03-10 Caché del catálogo

- **Dado** la caché vacía
- **Cuando** se pide la lista dos veces y luego se registra un tipo
- **Entonces** la primera vez consulta MySQL, la segunda no, y al registrar se borra la caché y el nuevo tipo aparece
- **Cubre:** RN-API03-04
- **Prueba:** `tests/Feature/Api/TiposDocumentoApiTest.php::test_la_lista_sale_de_la_cache_y_se_renueva_al_cambiar`

## Tareas técnicas

- [x] Servicio `app/Services/CatalogoTiposDocumento.php` (listar con caché, crear, actualizar, eliminar con regla de integridad).
- [x] El controlador Blade del Administrador usa el mismo servicio, así la caché se invalida también desde la pantalla.
- [x] Relación `TipoDocumento::expedientes()`.
- [x] `TipoDocumentoRequest`, `TipoDocumentoResource` y `Api/V1/TipoDocumentoController`.
- [x] `ReglaNegocio` responde 409 en JSON cuando la petición es de la API.
- [x] Rutas con `auth:sanctum` y `rol:Administrador` para escribir.
- [x] Documentar el recurso en `openapi.yaml` y probarlo en Postman.
