# API02 Verificar documento por API

| Campo | Valor |
|---|---|
| Endpoint | `GET /api/v1/verificaciones/{codigo}` |
| Caso de uso de origen | CU19 Verificar documento |
| Actor | Cualquier persona u otro sistema (por ejemplo, una app que lee el QR) |
| Requisitos | RF26, RNF18 |
| Sprint | Sprint 2 · Arquitectura de Software |

## Objetivo

Comprobar por API que una constancia o una respuesta las emitió el sistema y obtener la huella
SHA-256 de sus archivos para compararla.

## Flujo básico

1. El cliente envía el código de verificación en la URL.
2. El sistema normaliza el código (mayúsculas y guiones).
3. `Verificacion::buscar` lo busca en las constancias de los expedientes y en las respuestas.
4. Responde 200 con `autentico: true`, tipo, expediente, fecha, firmante y la huella de cada archivo.

## Flujos alternos

- **3a.** El código no existe: responde 404 con `autentico: false`.

## Reglas de negocio

- **RN-API02-01.** El código se acepta con o sin guiones y en minúsculas.
- **RN-API02-02.** Es público y no pide cuenta, con un límite de 30 peticiones por minuto.

## Criterios de aceptación

### CA-API02-01 Verifica una constancia

- **Dado** un trámite presentado con un PDF
- **Cuando** se verifica su código en minúsculas y sin guiones
- **Entonces** responde 200, auténtico, Constancia de presentación, con el número de expediente y la huella SHA-256 del PDF
- **Cubre:** pasos 1 a 4, RN-API02-01
- **Prueba:** `tests/Feature/Api/VerificacionApiTest.php::test_verifica_una_constancia_y_devuelve_la_huella`

### CA-API02-02 Código inexistente

- **Dado** un código que no emitió el sistema
- **Cuando** se verifica
- **Entonces** responde 404 con `autentico: false`
- **Cubre:** flujo 3a
- **Prueba:** `tests/Feature/Api/VerificacionApiTest.php::test_un_codigo_inexistente_responde_404`

## Tareas técnicas

- [x] `app/Http/Controllers/Api/V1/VerificacionController.php` que reutiliza el servicio `Verificacion`.
- [x] `app/Http/Resources/VerificacionResource.php`.
- [x] Ruta con `throttle:30,1` y documentación en `openapi.yaml`.
- [x] Pruebas en `tests/Feature/Api/VerificacionApiTest.php`.
