# Componentes arquitectónicos

## 1. Objetivo

Identificar los componentes del sistema construido y sus responsabilidades.

## 2. Componentes principales

| Componente | Responsabilidad | Clases principales |
|---|---|---|
| Portal público | Formulario sin cuenta, consulta, subsanación y verificación. | `TramiteExternoController`, `VerificacionController`, `PdfController` |
| Acceso del personal | Login en el subdominio, bloqueo tras 5 intentos. | `Auth/SesionController` |
| Mesa de Partes | Bandeja, recepción, observación, registro presencial. | `MesaPartesController` |
| Derivaciones | Derivar a un área destino con copias para conocimiento. | `DerivacionController`, `CopiaController` |
| Atención en áreas | Confirmar recepción, avances y respuesta. | `AreaController` |
| Flujo del expediente | Reglas de cada caso de uso, estados y movimientos. | `FlujoExpediente`, `ReglaNegocio` |
| Gestión documental | Guardar y descargar archivos con permisos y huella. | `Archivos`, `DocumentoController` |
| Firma electrónica del sistema | Códigos, QR, verificación y PDF. | `Verificacion`, `PdfController` |
| Notificaciones | Correos al administrado. | `Notificador`, `AvisoExpediente` |
| Plazos y pendientes | Semáforo por días hábiles y panel de pendientes. | `PendientesController`, `config/plazos.php` |
| Consultas y reportes | Búsqueda, reportes en PDF y Excel (.xlsx). | `BusquedaController`, `ReporteController` |
| Administración | Usuarios, áreas, tipos de documento y auditoría. | `Admin/*Controller`, `RegistroAuditoria` |
| Seguridad | Roles, permisos por expediente, límites de peticiones y captcha. | `VerificarRol`, `ExpedientePolicy`, `Captcha` |
| Persistencia | Modelos y tablas. | `app/Models/*`, migraciones, MySQL |

## 3. Interacción entre componentes

Los controladores reciben la petición, validan y consultan a Seguridad. Las operaciones que cambian un expediente pasan siempre por Flujo del expediente, que usa Gestión documental para los archivos, Notificaciones para los correos y Persistencia para los datos. La firma electrónica se genera sola al crear constancias y respuestas, y se consulta desde el portal público.

## 4. Diagrama de componentes

```mermaid
flowchart LR
    subgraph ENT[Componentes de entrada]
        direction TB
        PUB["Portal público<br/><i>CU05 CU06 CU07 CU19</i>"]
        ACC["Acceso del personal<br/><i>CU02 · bloqueo tras 5 intentos</i>"]
        MP["Mesa de Partes<br/><i>CU08 CU09 CU17</i>"]
        DER["Derivaciones<br/><i>CU10 · uno a uno y copias</i>"]
        AT["Atención en áreas<br/><i>CU11 CU12 CU13</i>"]
        CR["Consultas y reportes<br/><i>CU14 CU15 · PDF y Excel</i>"]
        PL["Plazos y pendientes<br/><i>CU18 · semáforo</i>"]
        AD["Administración<br/><i>CU03 CU04 · auditoría</i>"]
    end

    subgraph NUC[Núcleo del negocio]
        direction TB
        FL["Flujo del expediente<br/><i>FlujoExpediente · ReglaNegocio</i><br/>estados, movimientos y reglas"]
        FI["Firma electrónica del sistema<br/><i>Verificacion · PdfController</i><br/>código, QR y SHA-256"]
    end

    subgraph SOP[Componentes de soporte]
        direction TB
        GD["Gestión documental<br/><i>Archivos · DocumentoController</i>"]
        NO["Notificaciones<br/><i>Notificador · AvisoExpediente</i><br/>CU16"]
        PE[("Persistencia<br/><i>Modelos Eloquent · MySQL 8</i>")]
    end

    SEG{{"Seguridad transversal<br/>VerificarRol · ExpedientePolicy<br/>throttle · Captcha · CSRF"}}
    SMTP["Servidor SMTP"]

    PUB & MP & DER & AT -- "operaciones que<br/>cambian el expediente" --> FL
    PUB -- "verifica" --> FI
    AT -- "respuesta firmada" --> FI
    FL --> GD
    FL --> NO
    FL --> PE
    FI --> PE
    GD --> PE
    CR & PL & AD & ACC -- "consultas" --> PE
    NO --> SMTP
    SEG -. "protege" .-> ENT

    classDef ent fill:#85bbf0,stroke:#5d8fc4,color:#0b2545
    classDef nuc fill:#1168bd,stroke:#0b4884,color:#ffffff
    classDef sop fill:#fff2cc,stroke:#d6b656,color:#3a3000
    classDef seg fill:#fde9c4,stroke:#d79b00,color:#3d2c00
    classDef ext fill:#8a8a8a,stroke:#6b6b6b,color:#ffffff
    class PUB,ACC,MP,DER,AT,CR,PL,AD ent
    class FL,FI nuc
    class GD,NO,PE sop
    class SEG seg
    class SMTP ext
    style ENT fill:#eef6fe,stroke:#5d8fc4
    style NUC fill:#e8f0fa,stroke:#1168bd,stroke-width:2px
    style SOP fill:#fffbea,stroke:#d6b656
```

## 5. Consideraciones

Los componentes son agrupaciones lógicas dentro del monolito en capas; no se despliegan por separado.
