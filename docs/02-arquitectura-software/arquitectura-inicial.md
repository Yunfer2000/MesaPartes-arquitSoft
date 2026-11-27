# Arquitectura del sistema

Arquitectura del sistema **Mesa de Partes Virtual** tal como está construido (Laravel 13 y MySQL).

## Diagrama de arquitectura

```mermaid
flowchart TB
    subgraph ACT[Actores]
        direction LR
        A1["Administrado<br/><i>sin cuenta</i>"]
        A2["Cualquier persona"]
        A3["Operador de<br/>Mesa de Partes"]
        A4["Responsable<br/>de área"]
        A5["Dirección<br/>General"]
        A6["Administrador"]
        A1 ~~~ A2 ~~~ A3 ~~~ A4 ~~~ A5 ~~~ A6
    end

    subgraph PRE[Capa de presentación · Vistas Blade y CSS propio]
        direction LR
        P1["Portal público · dominio principal<br/>Presentar trámite · Consultar seguimiento con mapa de burbujas<br/>Subsanar · Constancia · Verificar documento"]
        P2["Portal del personal · subdominio<br/>Login · Bandejas con semáforo · Derivar con copias<br/>Búsqueda · Reportes · Pendientes · Administración"]
        P1 ~~~ P2
    end

    subgraph CON[Capa de control · app/Http]
        direction LR
        C1["Rutas<br/>Route::domain"]
        C2["Middleware<br/>auth · VerificarRol · throttle"]
        C3["Controladores<br/>validan con request validate"]
        C4["ExpedientePolicy<br/>view · operar"]
        C1 --> C2 --> C3 --> C4
    end

    subgraph SER[Capa de servicios · app/Services]
        direction LR
        S1["FlujoExpediente<br/>un método por CU<br/>transacción y bloqueo"]
        S2["Solicitantes<br/>natural o jurídica"]
        S3["Archivos<br/>SHA-256"]
        S4["Notificador<br/>CU16"]
        S5["Verificacion<br/>código y QR"]
        S2 ~~~ S1 ~~~ S3 ~~~ S4 ~~~ S5
    end

    subgraph DAT[Capa de datos]
        direction LR
        D1["Modelos Eloquent<br/>14 modelos"]
        D2[("MySQL 8<br/>21 tablas")]
        D3[("Almacenamiento<br/>privado")]
        D1 ~~~ D2 ~~~ D3
    end

    E1["Servidor SMTP"]
    E2["Cloudflare Turnstile"]

    ACT -- "público al dominio · personal al subdominio" --> PRE
    PRE -- "HTTPS · formularios con CSRF" --> CON
    CON -- "llama al caso de uso" --> SER
    SER -- "lee y escribe" --> DAT
    SER -. "envía avisos SMTP" .-> E1
    CON -. "valida captcha" .-> E2

    classDef actor fill:#08427b,stroke:#052e56,color:#ffffff
    classDef pre fill:#dae8fc,stroke:#6c8ebf,color:#0b2545
    classDef con fill:#85bbf0,stroke:#5d8fc4,color:#0b2545
    classDef pol fill:#fde9c4,stroke:#d79b00,color:#3d2c00
    classDef ser fill:#1168bd,stroke:#0b4884,color:#ffffff
    classDef dat fill:#fff2cc,stroke:#d6b656,color:#3a3000
    classDef ext fill:#8a8a8a,stroke:#6b6b6b,color:#ffffff
    class A1,A2,A3,A4,A5,A6 actor
    class P1,P2 pre
    class C1,C2,C3 con
    class C4 pol
    class S1,S2,S3,S4,S5 ser
    class D1,D2,D3 dat
    class E1,E2 ext
    style ACT fill:#f4f8fc,stroke:#08427b,stroke-dasharray:5 5
    style PRE fill:#f3f8ff,stroke:#6c8ebf
    style CON fill:#eef6fe,stroke:#5d8fc4
    style SER fill:#e8f0fa,stroke:#1168bd
    style DAT fill:#fffbea,stroke:#d6b656
```

## Arquitectura en capas

```text
┌────────────────────────────────────────────────────────┐
│ PRESENTACIÓN                                           │
│ Vistas Blade + CSS · portal público y del personal     │
└───────────────────────────┬────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────┐
│ CONTROL                                                │
│ Rutas · Controladores · Validación                     │
│ Middleware VerificarRol · ExpedientePolicy · throttle  │
└───────────────────────────┬────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────┐
│ SERVICIOS (lógica de negocio)                          │
│ FlujoExpediente · Notificador · Archivos · Verificacion│
└───────────────────────────┬────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────┐
│ DATOS                                                  │
│ Modelos Eloquent · Migraciones · MySQL · archivos │
└────────────────────────────────────────────────────────┘
```

Cada capa solo llama a la de abajo. Las vistas no consultan la base de datos por su cuenta: reciben los datos que les pasa el controlador.

## Relación con ICONIX

| Elemento de robustez (ICONIX) | Dónde está en el código |
|---|---|
| Boundary (pantalla) | Vista Blade (`resources/views`) |
| Control | Controlador + método de `FlujoExpediente` |
| Entity | Modelo Eloquent (`app/Models`) y su tabla |
