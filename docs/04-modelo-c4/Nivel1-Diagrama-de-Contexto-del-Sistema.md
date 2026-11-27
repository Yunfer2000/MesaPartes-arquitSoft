# C4 Nivel 1: Diagrama de contexto del sistema

## 1. Objetivo

Mostrar el sistema Mesa de Partes Virtual, quiénes lo usan y con qué sistemas externos se relaciona.

## 2. Sistema

**Mesa de Partes Virtual:** sistema web del IESTP Manuel Antonio Hierro Pozo para presentar, recepcionar, derivar, atender, seguir y verificar trámites documentarios.

## 3. Actores y sistemas externos

- **Administrado:** presenta su trámite sin cuenta y lo consulta con N.° y documento de identidad.
- **Cualquier persona:** verifica una constancia o respuesta con el código o el QR.
- **Operador de Mesa de Partes:** recepciona, observa, deriva y registra lo presencial.
- **Responsable de área:** confirma, atiende y responde.
- **Administrador:** gestiona usuarios, áreas y tipos de documento.
- **Dirección General:** revisa reportes y pendientes.
- **Servicio de correo (externo):** entrega las notificaciones.
- **Sitio web del instituto (externo):** enlaza al portal desde el menú "Trámites".
- **Cloudflare Turnstile (externo, opcional):** captcha del formulario público; si no se configura, el sistema usa su propio captcha.

## 4. Diagrama de contexto

```mermaid
flowchart TB
    subgraph USU[Personas que usan el sistema]
        direction LR
        ADM["Administrado<br/><i>Persona natural o jurídica</i><br/>Estudiante, egresado, docente o ciudadano.<br/>Presenta sin crear cuenta."]
        CUA["Cualquier persona<br/><i>Público en general</i><br/>Comprueba si una constancia<br/>o respuesta es auténtica."]
        MP["Operador de Mesa de Partes<br/><i>Personal del instituto</i><br/>Recibe, observa, deriva y<br/>registra trámites presenciales."]
        RA["Responsable de área<br/><i>Jefaturas y programas de estudio</i><br/>Confirma, atiende, deriva<br/>y responde expedientes."]
        DG["Dirección General<br/><i>Director o directora</i><br/>Supervisa plazos, pendientes<br/>y reportes del instituto."]
        AD["Administrador<br/><i>Soporte del sistema</i><br/>Gestiona usuarios, áreas,<br/>tipos de documento y auditoría."]
    end

    SYS["Mesa de Partes Virtual<br/><i>Sistema de software · Laravel 13</i><br/>Recibe trámites las 24 horas, los deriva entre áreas,<br/>controla plazos con semáforo y emite constancias<br/>y respuestas en PDF con código de verificación y QR."]

        WEB["Sitio web del instituto<br/><i>WordPress · sistema existente</i><br/>Publica el enlace Trámites<br/>hacia el portal público."]
        MAIL["Servicio de correo<br/><i>Servidor SMTP</i><br/>Entrega los avisos de cada<br/>cambio de estado."]
        CAP["Cloudflare Turnstile<br/><i>Servicio de captcha · opcional</i><br/>Comprueba que quien presenta<br/>no es un robot."]

    ADM -- "Presenta trámites, subsana<br/>observaciones y consulta el<br/>seguimiento · HTTPS" --> SYS
    CUA -- "Verifica documentos con<br/>código o QR · HTTPS" --> SYS
    MP -- "Gestiona la bandeja de<br/>Mesa de Partes · HTTPS" --> SYS
    RA -- "Atiende la bandeja del área<br/>y revisa pendientes · HTTPS" --> SYS
    DG -- "Consulta reportes y el<br/>panel de pendientes · HTTPS" --> SYS
    AD -- "Configura el sistema<br/>y revisa la auditoría · HTTPS" --> SYS
    SYS -. "Es enlazado desde<br/>el menú Trámites" .- WEB
    SYS -- "Envía avisos de presentación,<br/>recepción, observación,<br/>derivación y respuesta · SMTP" --> MAIL
    SYS -- "Valida el token del<br/>formulario público · HTTPS API" --> CAP

    classDef persona fill:#08427b,stroke:#052e56,color:#ffffff
    classDef sistema fill:#1168bd,stroke:#0b4884,color:#ffffff,font-size:15px
    classDef externo fill:#8a8a8a,stroke:#6b6b6b,color:#ffffff
    class ADM,CUA,MP,RA,DG,AD persona
    class SYS sistema
    class WEB,MAIL,CAP externo
    style USU fill:#f4f8fc,stroke:#08427b,stroke-dasharray:5 5
```

**Leyenda de colores:** azul oscuro = personas · azul = sistema, contenedores o servicios propios · celeste = controladores y entrada · naranja = autorización · amarillo = datos · gris = sistemas externos. Las líneas punteadas son relaciones opcionales o de apoyo.

## 5. Alcance

Fuera del sistema: RENIEC, pagos, Firma Perú y sistemas del MINEDU.
