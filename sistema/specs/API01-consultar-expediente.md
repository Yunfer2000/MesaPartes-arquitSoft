# API01 Consultar expediente por API

| Campo | Valor |
|---|---|
| Endpoint | `POST /api/v1/consultas` |
| Caso de uso de origen | CU07 Consultar seguimiento de expediente |
| Actor | Administrado u otro sistema (por ejemplo, la web del instituto) |
| Requisitos | RF de CU07, RNF04 (consulta en 3 s o menos), RNF05 (límite de peticiones) |
| Sprint | Sprint 2 · Arquitectura de Software |

## Objetivo

Que otro sistema muestre el estado y el recorrido de un expediente en JSON, con las mismas reglas
que la pantalla pública de seguimiento.

## Flujo básico

1. El cliente envía `numero` (N.° de expediente o código propio) y `numero_documento` en JSON.
2. El sistema valida que ambos datos lleguen.
3. `ConsultaExpediente::buscar` busca el expediente con ese número (sin importar mayúsculas) y ese documento.
4. El sistema responde 200 con número, estado, área actual, fechas, remitente, código de verificación y el recorrido (una burbuja por movimiento).

## Flujos alternos

- **2a.** Falta un dato: responde 422 con el detalle por campo.
- **3a.** No coincide: responde 404 sin decir cuál dato falló.
- **1a.** Más de 10 consultas por minuto desde la misma IP: responde 429.

## Reglas de negocio

- **RN-API01-01.** Se usa POST para que el documento de identidad no quede en la URL ni en los registros del servidor.
- **RN-API01-02.** No se devuelven correo ni celular del administrado (Ley 29733).
- **RN-API01-03.** La búsqueda es la misma que usa la pantalla Blade: una sola clase `ConsultaExpediente`.

## Criterios de aceptación

### CA-API01-01 Devuelve el seguimiento con número y documento

- **Dado** un trámite presentado por el formulario público
- **Cuando** se consulta por API con su N.° y el DNI del remitente
- **Entonces** responde 200 con estado Presentado, área Mesa de Partes y el recorrido, sin el correo
- **Cubre:** pasos 1 a 4, RN-API01-02
- **Prueba:** `tests/Feature/Api/ConsultaApiTest.php::test_devuelve_estado_area_y_recorrido_con_numero_y_dni`

### CA-API01-02 El recorrido se actualiza con cada movimiento

- **Dado** un expediente recepcionado y derivado a Secretaría
- **Cuando** se consulta con el número en minúsculas
- **Entonces** el estado es Derivado y el recorrido es Presentado, Recibido, Derivado y Respuesta pendiente
- **Cubre:** paso 4, RN-API01-03
- **Prueba:** `tests/Feature/Api/ConsultaApiTest.php::test_el_recorrido_se_actualiza_al_derivar`

### CA-API01-03 No dice cuál dato falló

- **Dado** un expediente existente
- **Cuando** se consulta con otro DNI o con un número que no existe
- **Entonces** responde 404 con el mismo mensaje en ambos casos
- **Cubre:** flujo 3a
- **Prueba:** `tests/Feature/Api/ConsultaApiTest.php::test_no_dice_cual_dato_fallo_y_responde_404`

### CA-API01-04 Valida los datos obligatorios

- **Dado** una petición sin cuerpo
- **Cuando** se envía a `/api/v1/consultas`
- **Entonces** responde 422 con errores en `numero` y `numero_documento`
- **Cubre:** flujo 2a
- **Prueba:** `tests/Feature/Api/ConsultaApiTest.php::test_valida_los_datos_obligatorios`

### CA-API01-05 Limita las consultas por minuto

- **Dado** 10 consultas en el mismo minuto
- **Cuando** llega la consulta 11
- **Entonces** responde 429
- **Cubre:** flujo 1a, RN-API01-01
- **Prueba:** `tests/Feature/Api/ConsultaApiTest.php::test_limita_a_10_consultas_por_minuto`

### CA-API01-06 La consulta no cae en N+1

- **Dado** un expediente presentado
- **Cuando** se consulta por API
- **Entonces** el sistema hace 5 consultas SQL o menos (búsqueda por índice único y carga anticipada de remitente, movimientos y áreas)
- **Cubre:** RNF04
- **Prueba:** `tests/Feature/Api/ConsultaApiTest.php::test_la_consulta_usa_pocas_consultas_sql_sin_n_mas_1`

## Tareas técnicas

- [x] Extraer la búsqueda de `TramiteExternoController` a `app/Services/ConsultaExpediente.php` y usarla en la pantalla y en la API.
- [x] `app/Http/Requests/Api/ConsultaRequest.php` con las reglas de validación.
- [x] `app/Http/Resources/SeguimientoResource.php` con la forma del JSON.
- [x] `app/Http/Controllers/Api/V1/ConsultaController.php` y la ruta con el limitador `throttle:api-consultas`.
- [x] Documentar `/consultas` en `public/docs/openapi.yaml`.
- [x] Usar los movimientos ya cargados para el área actual (de 6 a 4 consultas SQL).
- [x] Pruebas en `tests/Feature/Api/ConsultaApiTest.php`.
