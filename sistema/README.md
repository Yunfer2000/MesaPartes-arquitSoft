# Mesa de Partes Virtual

Sistema web de trámite documentario para el IESTP Manuel Antonio Hierro Pozo (Huamanga, Ayacucho).
Proyecto de Proyección Social (servicio social) desarrollado con la metodología ICONIX.

## Tecnologías

- PHP 8.4 o superior (usar 8.4.27+ / 8.5.12+ por los parches de seguridad de octubre de 2026)
- Laravel 13
- MySQL 8 (en desarrollo también funciona con SQLite)
- Composer y Node.js 20+

## Instalación local

```bash
git clone https://github.com/Yeltsin-MC/mesa-de-partes-virtual.git
cd mesa-de-partes-virtual
composer install
cp .env.example .env
php artisan key:generate
# crear la base de datos vacía "mesa_partes" en MySQL y revisar DB_* en .env
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Abrir http://localhost:8000

### Usuarios de prueba (solo en APP_ENV=local)

Contraseña de todos: `password`

| Rol | Correo |
|---|---|
| Administrador | admin@demo.test |
| Mesa de Partes | mesadepartes@demo.test |
| Área (Secretaría Académica) | secretaria@demo.test |
| Área (Dirección General) | direccion@demo.test |
| Administrado (estudiante) | estudiante@demo.test |

## Estructura (relación con el informe)

| Informe | Código |
|---|---|
| Modelo de base de datos (Figura 55, Tabla 29) | `database/migrations` |
| Clases de entidad (Figura 54) | `app/Models` |
| Controles de los diagramas de robustez | `app/Http/Controllers` |
| Prototipos de interfaz (Figuras 9 a 18) | `resources/views` |

## Estados del expediente

Presentado → Recibido → (Observado → Subsanado) → Derivado → En atención → Finalizado.
Los expedientes registrados de forma presencial (CU17) entran como Recibido.

## Casos de uso implementados

| CU | Pantalla | Rol |
|---|---|---|
| CU01 Registrar cuenta, CU02 Iniciar sesión | `/registro`, `/ingresar` | Todos |
| CU05 Presentar trámite, CU06 Subsanar, CU07 Seguimiento | `/mis-tramites` | Administrado |
| CU08 Recepcionar, CU09 Observar, CU10 Derivar, CU17 Registro presencial | `/mesa-de-partes` | Mesa de Partes |
| CU10 Derivar, CU11 Confirmar recepción, CU12 Avance, CU13 Respuesta | `/area` | Responsable de área |
| CU16 Notificar | Automático, en recepción, observación, derivación y respuesta | Sistema |
| CU03 Usuarios, CU04 Áreas, tipos de documento, auditoría | `/admin/...` | Administrador |
| CU14 Búsqueda, CU15 Reportes (PDF por impresión y CSV para Excel) | `/busqueda`, `/reportes` | Personal |

## Correo

En desarrollo los correos se escriben en `storage/logs/laravel.log` (`MAIL_MAILER=log`).
En el servidor se configuran `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` y `MAIL_FROM_ADDRESS` con la cuenta de correo del instituto.

## Pruebas

```bash
php artisan test
```

Las pruebas usan SQLite en memoria y también pasan contra MySQL/MariaDB.

## Configurar MySQL en local

1. Crear la base y el usuario (`sudo mysql`):

   ```sql
   CREATE DATABASE mesa_partes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'mesa_user'@'localhost' IDENTIFIED BY 'tu_contraseña';
   GRANT ALL PRIVILEGES ON mesa_partes.* TO 'mesa_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. En `.env`, completar `DB_USERNAME` y `DB_PASSWORD`.
3. Ejecutar `php artisan config:clear` y `php artisan migrate --seed`.

Después de cada `git pull`: `composer install` y `php artisan migrate`.