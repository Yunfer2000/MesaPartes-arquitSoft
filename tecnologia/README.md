# Tecnología y estructura del código

El código fuente está en el repositorio del equipo (privado). Aquí se resume su tecnología y organización.

## Tecnología

| Parte | Tecnología |
|---|---|
| Lenguaje | PHP 8.4 o superior |
| Framework | Laravel 13 |
| Vistas | Blade y CSS propio (sin npm) |
| Base de datos | PostgreSQL (pruebas con SQLite en memoria) |
| PDF y QR | barryvdh/laravel-dompdf y bacon/bacon-qr-code |
| Pruebas | PHPUnit (`php artisan test`) |
| Control de versiones | Git y GitHub, una rama y un Pull Request por bloque |

## Estructura

```text
app/Http/Controllers   capa de control
app/Policies           permisos por expediente
app/Services           capa de servicios (FlujoExpediente, Notificador, Archivos, Verificacion)
app/Models             capa de datos (Eloquent)
resources/views        capa de presentación (Blade)
database/migrations    tablas de PostgreSQL
routes/web.php         rutas públicas y del personal (subdominio)
specs/                 especificación por caso de uso
tests/Feature          pruebas por caso de uso
```

## Puesta en marcha (local)

```bash
composer install
cp .env.example .env      # DB_CONNECTION=pgsql y datos de la base
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
