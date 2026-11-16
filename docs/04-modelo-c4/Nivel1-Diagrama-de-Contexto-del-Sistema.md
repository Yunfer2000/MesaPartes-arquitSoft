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

## 4. Diagrama de contexto

```mermaid
flowchart TB
    ADM[Administrado]
    CUA[Cualquier persona]
    MP[Operador de Mesa de Partes]
    RA[Responsable de área]
    AD[Administrador]
    DG[Dirección General]
    SYS[Mesa de Partes Virtual<br/>Sistema web Laravel]
    MAIL[Servicio de correo SMTP]
    WEB[Sitio web del instituto]

    ADM -->|Presenta, subsana y consulta trámites| SYS
    CUA -->|Verifica documentos| SYS
    MP -->|Recepciona, observa, deriva, registra presencial| SYS
    RA -->|Confirma, atiende y responde| SYS
    AD -->|Administra usuarios y áreas| SYS
    DG -->|Consulta reportes y pendientes| SYS
    SYS -->|Envía avisos| MAIL
    MAIL -->|Correo| ADM
    WEB -->|Enlace "Trámites"| SYS
```

## 5. Alcance

Fuera del sistema: RENIEC, pagos, Firma Perú y sistemas del MINEDU.
