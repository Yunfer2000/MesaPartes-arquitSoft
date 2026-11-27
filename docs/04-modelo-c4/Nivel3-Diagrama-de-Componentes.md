# C4 Nivel 3: Diagrama de componentes de la aplicación web

## 1. Objetivo

Mostrar los componentes dentro de la aplicación Laravel.

## 2. Componentes

Ver la tabla completa en [componentes arquitectónicos](../02-arquitectura-software/componentes-arquitectonicos.md).

## 3. Diagrama de componentes

```mermaid
flowchart TB
    NAV["Navegador<br/><i>portal público o del personal</i>"]

    subgraph APP[Aplicación web · Laravel 13]
        direction TB
        subgraph ENT[Entrada y seguridad]
            direction LR
            RT["Enrutador<br/><i>routes/web.php</i><br/>Route::domain separa<br/>público y subdominio"]
            MW["Middleware<br/><i>auth · VerificarRol · throttle</i><br/>sesión, rol y límite<br/>de intentos por minuto"]
            CAPT["Captcha<br/><i>Support/Captcha</i><br/>Turnstile o captcha<br/>propio de respaldo"]
        end
        subgraph CTRL[Controladores · capa de control]
            direction LR
            CPUB["Públicos<br/><i>TramiteExterno · Verificacion · Pdf</i><br/>CU05 CU06 CU07 CU19"]
            CMP["Mesa de Partes<br/><i>MesaPartes · Derivacion</i><br/>CU08 CU09 CU10 CU17"]
            CAR["Áreas<br/><i>Area · Copia · Derivacion</i><br/>CU10 CU11 CU12 CU13"]
            CCON["Consultas<br/><i>Busqueda · Reporte · Pendientes</i><br/>CU14 CU15 CU18"]
            CADM["Administración<br/><i>Admin/Usuario · Area ·<br/>TipoDocumento · Auditoria</i><br/>CU03 CU04"]
            CSES["Sesión y documentos<br/><i>Auth/Sesion · Documento · Tramite</i><br/>CU02"]
            CPUB ~~~ CMP ~~~ CAR ~~~ CCON ~~~ CADM ~~~ CSES
        end
        POL["ExpedientePolicy<br/><i>autorización</i><br/>view: quién puede ver<br/>operar: el expediente está en su área"]
        subgraph SRV[Servicios · lógica de negocio]
            direction LR
            FE["FlujoExpediente<br/><i>un método por caso de uso</i><br/>transacción, bloqueo de fila<br/>y estados permitidos"]
            SOL["Solicitantes<br/><i>persona natural o jurídica</i><br/>busca o crea al administrado"]
            AR["Archivos<br/><i>adjuntos</i><br/>guarda y calcula SHA-256"]
            NO["Notificador<br/><i>CU16</i><br/>registra y envía el aviso"]
            VE["Verificacion<br/><i>firma electrónica del sistema</i><br/>código y QR"]
            SOL ~~~ FE
            FE -- "adjunta" --> AR
            FE -- "avisa" --> NO
            NO ~~~ VE
        end
        subgraph SUP[Soporte y librerías]
            direction LR
            SUPP["Support<br/><i>AreasDerivacion · Menu ·<br/>ReglasSolicitante</i>"]
            LIB["Librerías<br/><i>DomPDF · BaconQrCode · OpenSpout</i><br/>PDF, QR y Excel"]
            AV["AvisoExpediente<br/><i>Notification de Laravel</i><br/>plantilla del correo"]
            SUPP ~~~ LIB ~~~ AV
        end
        MO["Modelos Eloquent · capa de datos<br/><i>Expediente · Movimiento · Copia · Observacion · Subsanacion · Respuesta ·<br/>DocumentoAdjunto · Administrado · Usuario · Rol · Area · TipoDocumento ·<br/>Notificacion · RegistroAuditoria</i>"]
    end

    DB[("MySQL 8")]
    FS[("storage/app/private")]
    MAIL["Servidor SMTP"]
    TS["Cloudflare Turnstile"]

    NAV -- "HTTPS · dominio o subdominio" --> ENT
    ENT -- "petición autenticada<br/>y con rol válido" --> CTRL
    CTRL -- "Gate::authorize" --> POL
    CTRL -- "invoca un método<br/>por caso de uso" --> SRV
    CTRL -. "usa" .-> SUP
    SRV -- "lee y escribe" --> MO
    POL -- "consulta área actual" --> MO
    MO -- "SQL · TCP 3306" --> DB
    AR -- "escribe" --> FS
    NO -. "usa" .-> AV
    AV -- "SMTP" --> MAIL
    CAPT -. "siteverify" .-> TS

    classDef ext fill:#8a8a8a,stroke:#6b6b6b,color:#ffffff
    classDef entrada fill:#6c8ebf,stroke:#4a6a99,color:#ffffff
    classDef ctrl fill:#85bbf0,stroke:#5d8fc4,color:#0b2545
    classDef srv fill:#1168bd,stroke:#0b4884,color:#ffffff
    classDef pol fill:#d79b00,stroke:#a77700,color:#ffffff
    classDef sup fill:#dae8fc,stroke:#6c8ebf,color:#0b2545
    classDef datos fill:#2f6aa8,stroke:#1e4870,color:#ffffff
    class NAV,MAIL,TS ext
    class RT,MW,CAPT entrada
    class CPUB,CMP,CAR,CCON,CADM,CSES ctrl
    class FE,SOL,AR,NO,VE srv
    class POL pol
    class SUPP,LIB,AV sup
    class MO,DB,FS datos
    style APP fill:#f7fafd,stroke:#1168bd,stroke-width:2px,stroke-dasharray:6 4
    style ENT fill:#eef3fa,stroke:#6c8ebf
    style CTRL fill:#eef6fe,stroke:#5d8fc4
    style SRV fill:#e8f0fa,stroke:#1168bd
    style SUP fill:#f5f9ff,stroke:#6c8ebf,stroke-dasharray:3 3
```

**Leyenda de colores:** azul oscuro = personas · azul = sistema, contenedores o servicios propios · celeste = controladores y entrada · naranja = autorización · amarillo = datos · gris = sistemas externos. Las líneas punteadas son relaciones opcionales o de apoyo.

## 4. Descripción de las interacciones

1. Las rutas aplican el middleware y envían la petición al controlador.
2. Los controladores del personal consultan `ExpedientePolicy` antes de operar.
3. Todas las operaciones sobre un expediente pasan por `FlujoExpediente`.
4. La verificación pública usa `Verificacion`, que solo lee datos.
5. Búsquedas, reportes y pendientes consultan los modelos directamente (solo lectura).

## 5. Consideraciones

Los componentes son clases dentro de una sola aplicación.
