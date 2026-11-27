# Tecnología y estructura del código

El código fuente está en la carpeta [`sistema/`](../sistema) de este repositorio (copia del repositorio del equipo). Aquí se resume su tecnología y organización.

## Tecnología

| Parte | Tecnología |
|---|---|
| Lenguaje | PHP 8.4 o superior |
| Framework | Laravel 13 |
| Vistas | Blade y CSS propio (sin npm) |
| Base de datos | MySQL 8 (pruebas con SQLite en memoria) |
| PDF y QR | barryvdh/laravel-dompdf y bacon/bacon-qr-code |
| API | REST v1 en JSON, token Bearer con Laravel Sanctum, contrato OpenAPI 3 y Swagger UI |
| Pruebas | PHPUnit (`php artisan test`), Postman, k6 y GitHub Actions |
| Control de versiones | Git y GitHub, una rama y un Pull Request por bloque |

## Estructura

```text
app/Http/Controllers   capa de control (Api/V1 para la API REST)
app/Http/Requests      validación de la API
app/Http/Resources     forma del JSON de la API
app/Policies           permisos por expediente
app/Services           capa de servicios (FlujoExpediente, ConsultaExpediente, CatalogoTiposDocumento, Notificador, Archivos, Verificacion)
app/Models             capa de datos (Eloquent)
resources/views        capa de presentación (Blade)
database/migrations    tablas de MySQL
routes/web.php         rutas públicas y del personal (subdominio)
routes/api.php         rutas de la API REST v1
public/docs            contrato OpenAPI (openapi.yaml)
specs/                 especificación por caso de uso
tests/Feature          pruebas por caso de uso
```

## Puesta en marcha (local)

```bash
cd sistema
composer install
cp .env.example .env      # DB_CONNECTION=mysql y datos de la base
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
