# C4 Nivel 2: Diagrama de contenedores

## 1. Objetivo

Mostrar las piezas que se ejecutan en el hosting.

## 2. Contenedores

| Contenedor | Tecnología | Responsabilidad |
|---|---|---|
| Navegador | Chrome, Edge, Firefox (PC o celular) | Muestra el HTML que genera el servidor. |
| Aplicación web | PHP 8.4+, Laravel 13, Blade | Portal público, portal del personal (subdominio), reglas de negocio y PDF. |
| Base de datos | MySQL 8 | Expedientes, movimientos, usuarios, áreas, sesiones, caché y auditoría. |
| Almacenamiento de archivos | Disco privado del servidor (`storage/app/private`) | PDF y ZIP de los trámites, escaneos y respuestas. |
| Servidor de correo | SMTP externo | Envío de notificaciones. |

## 3. Diagrama de contenedores

```mermaid
flowchart TB
    PUB["Administrado y público<br/><i>Persona · sin cuenta</i>"]
    PER["Personal del instituto<br/><i>Persona · con cuenta y rol</i><br/>Mesa de Partes, áreas,<br/>Dirección General, Administrador"]
    WEB["Sitio web del instituto<br/><i>Sistema externo · WordPress</i>"]

    subgraph SIS[Mesa de Partes Virtual · hosting institucional]
        PP["Portal público<br/><i>Vistas Blade · dominio principal</i><br/>Formulario sin cuenta, consulta de<br/>seguimiento y verificación de documentos"]
        PS["Portal del personal<br/><i>Vistas Blade · subdominio personal</i><br/>Login, bandejas, derivación, reportes,<br/>pendientes y administración"]
        NUC["Aplicación web<br/><i>Contenedor · PHP 8.4 · Laravel 13</i><br/>Rutas, middleware, controladores, políticas,<br/>servicios y modelos Eloquent.<br/>PDF con DomPDF, QR con BaconQrCode<br/>y Excel con OpenSpout"]
        DB[("Base de datos<br/><i>Contenedor · MySQL 8</i><br/>21 tablas: expedientes, movimientos,<br/>copias, observaciones, respuestas,<br/>administrados, usuarios, áreas,<br/>notificaciones, auditoría y sesiones")]
        FS[("Almacenamiento privado<br/><i>Contenedor · disco del servidor</i><br/>storage/app/private<br/>PDF, ZIP e imágenes con huella SHA-256")]
    end

    MAIL["Servidor de correo<br/><i>Sistema externo · SMTP</i>"]
    CAP["Cloudflare Turnstile<br/><i>Sistema externo · captcha opcional</i>"]

    PUB -- "Usa · HTTPS 443" --> PP
    PER -- "Usa · HTTPS 443" --> PS
    WEB -. "Enlace Trámites" .-> PP
    PP <-- "Formularios con token CSRF<br/>⇄ páginas HTML y PDF" --> NUC
    PS <-- "Formularios con sesión y CSRF<br/>⇄ HTML, PDF y XLSX" --> NUC
    NUC -- "Eloquent · transacciones y<br/>bloqueo de fila · TCP 3306" --> DB
    NUC -- "Guarda y entrega archivos<br/>solo con permiso" --> FS
    NUC -- "Avisos · SMTP 587 TLS" --> MAIL
    NUC -- "Valida captcha · HTTPS" --> CAP

    classDef persona fill:#08427b,stroke:#052e56,color:#ffffff
    classDef contenedor fill:#438dd5,stroke:#2e6295,color:#ffffff
    classDef datos fill:#2f6aa8,stroke:#1e4870,color:#ffffff
    classDef externo fill:#8a8a8a,stroke:#6b6b6b,color:#ffffff
    class PUB,PER persona
    class PP,PS,NUC contenedor
    class DB,FS datos
    class WEB,MAIL,CAP externo
    style SIS fill:#f4f8fc,stroke:#1168bd,stroke-width:2px,stroke-dasharray:6 4
```

**Leyenda de colores:** azul oscuro = personas · azul = sistema, contenedores o servicios propios · celeste = controladores y entrada · naranja = autorización · amarillo = datos · gris = sistemas externos. Las líneas punteadas son relaciones opcionales o de apoyo.

## 4. Comunicación entre contenedores

- Navegador ↔ aplicación: HTTPS, formularios y páginas HTML (sin API REST).
- Aplicación ↔ base de datos: conexión `mysql` de Laravel.
- Aplicación ↔ archivos: disco local privado; descarga solo con permiso.
- Aplicación → correo: SMTP configurado en `.env`.
- Aplicación → Cloudflare Turnstile: HTTPS, solo si están las claves `TURNSTILE_*` en `.env`; si no, usa su captcha propio.

## 5. Consideraciones

Todo corre en un solo hosting. El subdominio del personal apunta a la misma aplicación; Laravel separa las rutas según el dominio (`APP_STAFF_DOMAIN`).
