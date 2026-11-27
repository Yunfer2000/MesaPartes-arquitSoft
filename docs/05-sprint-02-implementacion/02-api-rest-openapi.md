# API REST v1 y documentación OpenAPI

## 1. Por qué una API

Hasta el Sprint 1 el sistema solo tenía pantallas Blade. Se agregó la API REST v1 para dos usos reales:

- Que la web del instituto (WordPress) u otro sistema muestre el estado de un expediente sin copiar la pantalla.
- Que un tercero compruebe si una constancia es auténtica leyendo su código de verificación.

Además, la API permite probar los atributos de calidad con herramientas estándar (Postman y k6).

## 2. Endpoints

Base: `/api/v1`. Todas las respuestas son JSON.

| Método | Ruta | Qué hace | Acceso | Límite por minuto e IP |
|---|---|---|---|---|
| GET | `/tipos-documento` | Lista el catálogo. Filtro opcional `?activo=1` | Público | 60 |
| GET | `/tipos-documento/{id}` | Muestra un tipo | Público | 60 |
| POST | `/tipos-documento` | Registra un tipo (201 con cabecera `Location`) | Token de administrador | 60 |
| PUT | `/tipos-documento/{id}` | Actualiza un tipo | Token de administrador | 60 |
| DELETE | `/tipos-documento/{id}` | Elimina un tipo (409 si un expediente lo usa) | Token de administrador | 60 |
| POST | `/consultas` | Seguimiento de un expediente (CU07) con N.° y documento | Público | 10 |
| GET | `/verificaciones/{codigo}` | Verifica una constancia o respuesta (CU19) | Público | 30 |
| POST | `/tokens` | Entrega un token Bearer al personal | Público | 5 |
| DELETE | `/tokens/actual` | Revoca el token que se está usando | Con token | 60 |

### Códigos de respuesta

| Código | Cuándo |
|---|---|
| 200 | Consulta correcta |
| 201 | Recurso creado |
| 204 | Eliminado o token revocado, sin contenido |
| 401 | Falta el token o no es válido, o credenciales incorrectas |
| 403 | El usuario no es administrador |
| 404 | El recurso no existe |
| 409 | Regla de negocio incumplida (tipo en uso) |
| 422 | Datos inválidos, con el detalle por campo. Laravel usa 422 donde la guía usa 400 |
| 429 | Se pasó el límite de peticiones |

## 3. Decisiones de diseño de la API

| Decisión | Motivo |
|---|---|
| Versión en la ruta (`/api/v1`) | Una versión 2 no rompe a los clientes de la versión 1 |
| La consulta usa **POST** y no GET | El DNI no queda en la URL, en el historial del navegador ni en los registros del servidor (Ley 29733) |
| No se devuelven correo ni celular | Solo se muestra lo que el ciudadano ya ve en la pantalla pública |
| El 404 de la consulta no dice qué dato falló | Así nadie puede averiguar si un DNI tiene trámites |
| Token Bearer con Laravel Sanctum, vence en 8 horas | Una jornada de trabajo. El token se guarda cifrado con SHA-256 en `personal_access_tokens` |
| Límites con nombre (`api-general`, `api-consultas`, `api-verificaciones`, `api-tokens`) | Cada uno tiene su propio contador; se cambian en `.env` sin tocar el código |
| Errores siempre en JSON con `message` | El cliente no recibe una página HTML de error |

## 4. Archivos de la API

| Archivo | Rol |
|---|---|
| `routes/api.php` | Rutas, límites y protección con `auth:sanctum` y `rol:Administrador` |
| `app/Http/Controllers/Api/V1/*Controller.php` | Cuatro controladores delgados |
| `app/Http/Requests/Api/*Request.php` | Reglas de validación con mensajes en español |
| `app/Http/Resources/*Resource.php` | Forma del JSON (objetos de transferencia de datos) |
| `app/Services/ConsultaExpediente.php`, `CatalogoTiposDocumento.php` | Lógica compartida con las pantallas |
| `app/Providers/AppServiceProvider.php` | Limitadores de peticiones |
| `config/api.php` | Valores de los límites (variables `API_LIMITE_*`) |
| `bootstrap/app.php` | Respuestas JSON para 401 y 404 en `/api/*` |

## 5. OpenAPI y Swagger

- El contrato está en `sistema/public/docs/openapi.yaml` (OpenAPI 3.0.3). Describe cada ruta, sus parámetros, los cuerpos de entrada y salida, los ejemplos, el esquema de seguridad `bearerAuth` y todas las respuestas de error.
- Swagger UI se abre en **`http://127.0.0.1:8000/api-docs`**. Desde ahí se puede probar cada endpoint con el botón *Try it out*. Para las operaciones protegidas, primero se pide un token con `POST /tokens` y se pega en el botón *Authorize*.
- El contrato se revisa en cada push con **Redocly** (trabajo `contrato-openapi` del CI).
- La prueba `tests/Feature/Api/ContratoOpenApiTest.php` falla si alguien agrega una ruta a la API y se olvida de documentarla.

## 6. Ejemplo

Petición:

```http
POST /api/v1/consultas
Content-Type: application/json

{ "numero": "EXP-2026-000001", "numero_documento": "70451236" }
```

Respuesta 200 (resumida):

```json
{
  "data": {
    "numero": "EXP-2026-000001",
    "estado": "Derivado",
    "area_actual": "Secretaría Académica",
    "recorrido": [
      { "paso": 1, "titulo": "Presentado", "estado": "completado" },
      { "paso": 2, "titulo": "Recibido", "estado": "completado" },
      { "paso": 3, "titulo": "Derivado", "estado": "actual" },
      { "paso": 4, "titulo": "Respuesta pendiente", "estado": "pendiente" }
    ]
  }
}
```
