# API04 Obtener y revocar token de acceso

| Campo | Valor |
|---|---|
| Endpoints | `POST /api/v1/tokens`, `DELETE /api/v1/tokens/actual` |
| Caso de uso de origen | CU02 Iniciar sesión (versión API) |
| Actor | Personal del instituto |
| Requisitos | RNF05 (límite de intentos), RNF06 (acceso por rol) |
| Sprint | Sprint 2 · Arquitectura de Software |

## Objetivo

Que el personal obtenga un token Bearer (Laravel Sanctum) para usar las operaciones protegidas de la API.

## Flujo básico

1. El cliente envía correo y contraseña.
2. El sistema comprueba que el usuario existe, está activo y la contraseña coincide.
3. Crea un token que vence en 8 horas y responde 201 con el token, su tipo y el rol del usuario.
4. Al terminar, el cliente revoca el token con `DELETE /api/v1/tokens/actual`.

## Flujos alternos

- **2a.** Credenciales incorrectas o usuario inactivo: 401 sin decir cuál dato falló.
- **1a.** Datos mal formados: 422.
- **1b.** Más de 5 intentos por minuto: 429.

## Reglas de negocio

- **RN-API04-01.** Un usuario desactivado no obtiene token.
- **RN-API04-02.** El token vence a las 8 horas y se puede revocar antes.

## Criterios de aceptación

### CA-API04-01 Entrega un token y con él se opera

- **Dado** el Administrador de prueba
- **Cuando** pide un token con sus credenciales y registra un tipo de documento con ese token
- **Entonces** recibe 201 con tipo Bearer y rol Administrador, y el registro responde 201
- **Cubre:** pasos 1 a 3
- **Prueba:** `tests/Feature/Api/TokenApiTest.php::test_entrega_un_token_bearer_y_con_el_se_opera`

### CA-API04-02 Rechaza credenciales incorrectas

- **Dado** una contraseña equivocada, un usuario desactivado y un correo mal escrito
- **Cuando** piden un token
- **Entonces** responden 401, 401 y 422
- **Cubre:** flujos 2a y 1a, RN-API04-01
- **Prueba:** `tests/Feature/Api/TokenApiTest.php::test_credenciales_incorrectas_o_usuario_inactivo_responden_401`

### CA-API04-03 Revocar el token

- **Dado** un token válido
- **Cuando** se revoca y se vuelve a usar
- **Entonces** la revocación responde 204 y el uso posterior 401
- **Cubre:** paso 4, RN-API04-02
- **Prueba:** `tests/Feature/Api/TokenApiTest.php::test_revocar_el_token_lo_deja_sin_uso`

## Tareas técnicas

- [x] Instalar Laravel Sanctum (`php artisan install:api`) y su tabla `personal_access_tokens`.
- [x] `HasApiTokens` en el modelo `Usuario`.
- [x] `TokenRequest` y `Api/V1/TokenController` (crear y revocar).
- [x] Ruta con `throttle:5,1` y documentación en `openapi.yaml` (esquema `bearerAuth`).
- [x] Pruebas en `tests/Feature/Api/TokenApiTest.php`.
