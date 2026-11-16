# C4 Nivel 3: Diagrama de componentes de la aplicación web

## 1. Objetivo

Mostrar los componentes dentro de la aplicación Laravel.

## 2. Componentes

Ver la tabla completa en [componentes arquitectónicos](../02-arquitectura-software/componentes-arquitectonicos.md).

## 3. Diagrama de componentes

```mermaid
flowchart TB
    subgraph APP[Aplicación web Laravel]
        R[Rutas y middleware<br/>subdominio, VerificarRol, throttle]
        TE[TramiteExternoController]
        VC[VerificacionController / PdfController]
        MPC[MesaPartesController]
        DC[DerivacionController]
        AC[AreaController]
        CR[Busqueda / Reporte / Pendientes]
        ADM[Admin/*]
        POL[ExpedientePolicy]
        FE[FlujoExpediente]
        VE[Verificacion]
        AR[Archivos]
        NO[Notificador]
        MO[Modelos Eloquent]
    end
    R --> TE & VC & MPC & DC & AC & CR & ADM
    MPC & DC & AC --> POL
    TE & MPC & DC & AC --> FE
    VC --> VE
    FE --> AR & NO & MO
    VE & CR & ADM & POL --> MO
    MO --> DB[(PostgreSQL)]
    AR --> FS[(Archivos privados)]
    NO --> MAIL[Correo]
```

## 4. Descripción de las interacciones

1. Las rutas aplican el middleware y envían la petición al controlador.
2. Los controladores del personal consultan `ExpedientePolicy` antes de operar.
3. Todas las operaciones sobre un expediente pasan por `FlujoExpediente`.
4. La verificación pública usa `Verificacion`, que solo lee datos.
5. Búsquedas, reportes y pendientes consultan los modelos directamente (solo lectura).

## 5. Consideraciones

Los componentes son clases dentro de una sola aplicación.
