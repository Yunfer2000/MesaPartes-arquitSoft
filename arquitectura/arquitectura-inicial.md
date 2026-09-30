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