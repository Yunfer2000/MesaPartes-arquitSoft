# Arquitectura inicial del sistema

## Diagrama de arquitectura

```mermaid
flowchart TD

subgraph ACTORES["ACTORES"]
    Usuario["Usuario externo"]
    Mesa["Mesa de Partes"]
    Responsable["Responsable de Área"]
    Jefe["Jefe de Área"]
    Admin["Administrador"]
    Director["Director"]
end

subgraph PRESENTACION["PRESENTACIÓN"]
    Web["Portal Web"]
    API["API / Servicios"]
end

subgraph NEGOCIO["LÓGICA DE NEGOCIO"]
    Auth["Autenticación y autorización"]
    Usuarios["Gestión de usuarios"]
    Tramites["Gestión de trámites"]
    Expedientes["Gestión de expedientes"]
    Documentos["Gestión de documentos"]
    Notificaciones["Notificaciones"]
    Reportes["Reportes y estadísticas"]
end

subgraph DATOS["DATOS"]
    BD["Base de datos"]
    Archivos["Almacenamiento de documentos"]
    Auditoria["Historial y auditoría"]
end

Usuario --> Web
Mesa --> Web
Responsable --> Web
Jefe --> Web
Admin --> Web
Director --> Web

Web --> API

API --> Auth
API --> Usuarios
API --> Tramites
API --> Expedientes
API --> Documentos
API --> Notificaciones
API --> Reportes

Auth --> BD
Usuarios --> BD
Tramites --> BD
Expedientes --> BD
Documentos --> Archivos
Expedientes --> Auditoria
Notificaciones --> BD
Reportes --> BD

Expedientes --> Documentos
Expedientes --> Notificaciones
Expedientes --> Reportes
```

## Arquitectura en capas

```text
┌────────────────────────────────────────────────────┐
│                  PRESENTACIÓN                      │
│                                                    │
│              Portal Web / Interfaz                │
│                    API REST                       │
└────────────────────────┬───────────────────────────┘
                         ↓
┌────────────────────────────────────────────────────┐
│                LÓGICA DE NEGOCIO                  │
│                                                    │
│ Autenticación y autorización                       │
│ Gestión de usuarios y roles                        │
│ Gestión de trámites                                │
│ Gestión de expedientes                             │
│ Gestión de documentos                              │
│ Derivación y asignación de expedientes             │
│ Notificaciones                                     │
│ Reportes y estadísticas                            │
└────────────────────────┬───────────────────────────┘
                         ↓
┌────────────────────────────────────────────────────┐
│                      DATOS                         │
│                                                    │
│ Base de datos                                      │
│ Documentos digitales                               │
│ Historial y auditoría                              │
└────────────────────────────────────────────────────┘
```

## Diagrama final de arquitectura inicial

```mermaid
flowchart TD

subgraph ACTORES["ACTORES"]
    U["Usuario externo"]
    MP["Mesa de Partes"]
    RA["Responsable de Área"]
    JA["Jefe de Área"]
    AD["Administrador"]
    DI["Director"]
end

subgraph PRESENTACION["PRESENTACIÓN"]
    WEB["Portal Web"]
    API["API REST"]
end

subgraph NEGOCIO["LÓGICA DE NEGOCIO"]
    AUTH["Autenticación y autorización"]
    USU["Gestión de usuarios y roles"]
    TRA["Gestión de trámites"]
    EXP["Gestión de expedientes"]
    DOC["Gestión de documentos"]
    DER["Derivación y asignación"]
    NOT["Notificaciones"]
    REP["Reportes y estadísticas"]
end

subgraph DATOS["DATOS"]
    BD["Base de datos"]
    ARCHIVOS["Almacenamiento de documentos"]
    AUD["Historial y auditoría"]
end

subgraph EXTERNOS["SISTEMAS EXTERNOS"]
    EMAIL["Servicio de correo electrónico"]
end

U --> WEB
MP --> WEB
RA --> WEB
JA --> WEB
AD --> WEB
DI --> WEB

WEB --> API

API --> AUTH
API --> USU
API --> TRA
API --> EXP
API --> DOC
API --> DER
API --> NOT
API --> REP

AUTH --> BD
USU --> BD
TRA --> BD
EXP --> BD
DOC --> ARCHIVOS
DER --> BD
NOT --> BD
REP --> BD

EXP --> AUD
EXP --> DOC
EXP --> DER
EXP --> NOT

NOT --> EMAIL
```