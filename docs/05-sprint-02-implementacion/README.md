# Sprint 2: Implementación y validación de la arquitectura

En el Sprint 1 se analizó el sistema y se decidió su arquitectura (monolito en capas con el patrón MVC y una capa de servicios). En este sprint esa arquitectura se **implementa** y se **comprueba con pruebas**. El código completo del sistema está en la carpeta [`sistema/`](../../sistema).

## Qué se entrega

| Entregable que pide el sprint | Dónde está |
|---|---|
| Código fuente organizado según la arquitectura | [`sistema/`](../../sistema): `app/Http` (presentación), `app/Services` (negocio), `app/Models` (datos) |
| Primera versión funcional del sistema | Formulario público, bandejas de Mesa de Partes y de las áreas, panel del administrador y la API REST v1 |
| Documentación de la API con OpenAPI y Swagger | [`sistema/public/docs/openapi.yaml`](../../sistema/public/docs/openapi.yaml) y la página `/api-docs` del sistema |
| Pruebas automáticas con reporte de cobertura | 165 pruebas PHPUnit en [`sistema/tests/`](../../sistema/tests) y el reporte que genera GitHub Actions |
| Evidencias de la validación de atributos de calidad | [Validación de atributos de calidad](07-validacion-atributos-calidad.md), Postman y k6 en [`pruebas/`](../../pruebas) |

## Actividades del sprint

| N.° | Actividad | Documento |
|---|---|---|
| 1 | Implementar el estilo arquitectónico | [Estilo y patrón implementados](01-estilo-y-patron-implementados.md) |
| 2 | Aplicar el patrón arquitectónico (MVC) | [Estilo y patrón implementados](01-estilo-y-patron-implementados.md) |
| 3 | Especificaciones SDD con criterios de aceptación y tareas técnicas | [Especificaciones SDD](03-especificaciones-sdd.md) |
| 4 | Endpoints REST documentados con OpenAPI y Swagger | [API REST y OpenAPI](02-api-rest-openapi.md) |
| 5 | Comportamiento de la base de datos y estrategia de caché | [Base de datos y caché](04-base-de-datos-y-cache.md) |
| 6 | Principios SOLID y patrones de diseño | [SOLID y patrones](05-solid-y-patrones.md) |
| 7 | Pruebas automáticas, cobertura e integración continua | [Pruebas automáticas y CI](06-pruebas-automaticas-y-ci.md) |
| 8 | Pruebas de atributos de calidad (Postman, k6 hasta 2,000 usuarios y otras) | [Validación de atributos de calidad](07-validacion-atributos-calidad.md) |

## Equivalencias con la guía del laboratorio

La guía usa Node.js con Express. El sistema usa PHP con Laravel, que ofrece lo mismo con otros nombres:

| Guía (Node.js) | Este sistema (Laravel 13) |
|---|---|
| Express con rutas `/api/products` | `routes/api.php` con rutas `/api/v1/tipos-documento` |
| Controlador y servicio en JavaScript | Controlador en `app/Http/Controllers/Api/V1` y servicio en `app/Services` |
| Validación con un middleware | Form Request en `app/Http/Requests/Api` |
| swagger-jsdoc y swagger-ui-express | `openapi.yaml` escrito a mano y Swagger UI en `/api-docs` |
| Jest | PHPUnit (el marco de pruebas estándar de PHP) |
| Error 400 por datos inválidos | Error 422, que es lo que responde Laravel por defecto |
| Recurso `products` | Recurso `tipos-documento` (catálogo que administra el administrador) |

## Cómo levantar el sistema

```powershell
cd sistema
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Luego abrir `http://127.0.0.1:8000` (sistema) y `http://127.0.0.1:8000/api-docs` (Swagger UI).
