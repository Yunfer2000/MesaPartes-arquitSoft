# C4 Nivel 1: Diagrama de Contexto del Sistema

## 1. Objetivo

Representar el sistema de Mesa de Partes Virtual y su relación con los usuarios y los sistemas externos.

## 2. Sistema

**Mesa de Partes Virtual:** sistema propuesto para registrar, derivar, atender y realizar el seguimiento de trámites y expedientes del IESTP Manuel Antonio Hierro Pozo – Ayacucho.

## 3. Actores principales

- **Usuario externo:** registra solicitudes, adjunta documentos y consulta el estado de sus trámites.
- **Personal de Mesa de Partes:** recibe y verifica documentos, registra expedientes y realiza derivaciones.
- **Responsable de área:** recibe expedientes asignados y registra su atención.
- **Jefe de área:** supervisa la atención de expedientes de su área.
- **Administrador:** gestiona usuarios, roles y parámetros del sistema.
- **Director:** consulta reportes e información de gestión.

## 4. Diagrama de contexto

```mermaid
flowchart TB
    EXT[Usuario externo]
    MP[Personal de Mesa de Partes]
    RA[Responsable de área]
    JA[Jefe de área]
    AD[Administrador]
    DI[Director]

    SYS[Mesa de Partes Virtual<br/>Sistema web para gestionar trámites y expedientes]

    EXT -->|Registra solicitudes y consulta trámites| SYS
    MP -->|Registra, verifica y deriva expedientes| SYS
    RA -->|Atiende expedientes asignados| SYS
    JA -->|Supervisa la atención| SYS
    AD -->|Administra usuarios y permisos| SYS
    DI -->|Consulta reportes de gestión| SYS
```

## 5. Alcance

El sistema comprende el registro de solicitudes, la gestión de expedientes y documentos, las derivaciones, el seguimiento, las notificaciones y los reportes.

Las integraciones con otros sistemas institucionales se consideran futuras y deberán definirse según los requisitos correspondientes.