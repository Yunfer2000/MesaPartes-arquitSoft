# Enfoque arquitectónico

## 1. Descripción

El sistema sigue un enfoque **orientado a objetos y dirigido por casos de uso**:

- El análisis y el diseño se hicieron con **ICONIX**: casos de uso, robustez, secuencia, clases y base de datos.
- Cada caso de uso se implementa como un método de la capa de servicios (`FlujoExpediente`), llamado por un controlador.
- Cada caso de uso tiene una especificación en `specs/` con criterios de aceptación "Dado / Cuando / Entonces" enlazados a pruebas automáticas. Es una práctica de **desarrollo guiado por especificaciones (SDD)** que apoya a ICONIX.

## 2. Capas

### 2.1. Presentación
Vistas Blade (`resources/views`) y CSS propio. Formularios con validación, mensajes y diseño adaptable al celular.

### 2.2. Control
Rutas (`routes/web.php`), controladores, middleware `VerificarRol`, política `ExpedientePolicy` y límites de peticiones (`throttle`). Validan la entrada y deciden quién puede hacer qué.

### 2.3. Servicios (lógica de negocio)
- `FlujoExpediente`: presentar, recepcionar, observar, derivar, confirmar recepción, registrar avance, responder, registrar presencial y subsanar. Revisa los estados permitidos y lanza `ReglaNegocio` si no se cumplen.
- `Notificador`: envía los correos de cada evento (CU16).
- `Archivos`: guarda los archivos en almacenamiento privado y calcula su huella SHA-256.
- `Verificacion`: códigos de verificación, QR y búsqueda del documento firmado (CU19).

### 2.4. Datos
Modelos Eloquent (`Expediente`, `Movimiento`, `Observacion`, `Respuesta`, `Copia`, `DocumentoAdjunto`, `Administrado`, `Usuario`, `Area`, etc.), migraciones y MySQL.

## 3. Inyección de dependencias

Laravel crea los servicios y los entrega a los controladores por su constructor. Por ejemplo, `FlujoExpediente` recibe `Archivos` y `Notificador`. Esto facilita las pruebas, donde el correo y el disco se reemplazan por versiones falsas (`Notification::fake()`, `Storage::fake()`).

## 4. Regla de dependencias

```text
Presentación → Control → Servicios → Datos
```

Ninguna capa llama a una superior. Las vistas no contienen reglas de negocio.

## 5. Diagrama de trazabilidad del enfoque

Muestra cómo un caso de uso de ICONIX (ejemplo: CU10 Derivar expediente) llega hasta el código y sus pruebas. Las capas del sistema se ven en la [arquitectura del sistema](arquitectura-inicial.md).

```mermaid
flowchart LR
    subgraph ICX[ICONIX · análisis y diseño]
        direction TB
        I1["Caso de uso<br/><b>CU10 Derivar expediente</b>"]
        I2["Diagrama de robustez<br/>boundary · control · entity"]
        I3["Diagrama de secuencia<br/>y de clases"]
        I1 --> I2 --> I3
    end
    subgraph SDD[SDD · especificación]
        direction TB
        E1["specs/CU10-derivar-expediente.md"]
        E2["Reglas RN-CU10-nn"]
        E3["Criterios CA-CU10-nn<br/>Dado · Cuando · Entonces"]
        E1 --> E2 --> E3
    end
    subgraph COD[Código en capas]
        direction TB
        K1["Vista Blade<br/>formulario de derivación"]
        K2["DerivacionController::store"]
        K3["FlujoExpediente::derivar"]
        K4["Modelos Movimiento y Copia"]
        K1 --> K2 --> K3 --> K4
    end
    subgraph PRU[Pruebas automáticas]
        direction TB
        T1["tests/Feature<br/>una prueba por criterio"]
        T2["EspecificacionesTest<br/>controla que cada criterio<br/>tenga su prueba"]
        T1 --> T2
    end

    I2 -- "boundary" --> K1
    I2 -- "control" --> K3
    I2 -- "entity" --> K4
    I3 --> E1
    E3 -- "cada criterio nombra<br/>su prueba" --> T1
    T1 -- "verifica" --> K3

    classDef icx fill:#e1d5e7,stroke:#9673a6,color:#2b1a33
    classDef sdd fill:#d5e8d4,stroke:#82b366,color:#1d3a1b
    classDef cod fill:#cfe2f7,stroke:#1168bd,color:#0b2545
    classDef pru fill:#fff2cc,stroke:#d6b656,color:#3a3000
    class I1,I2,I3 icx
    class E1,E2,E3 sdd
    class K1,K2,K3,K4 cod
    class T1,T2 pru
    style ICX fill:#f7f2fa,stroke:#9673a6
    style SDD fill:#f2f9f1,stroke:#82b366
    style COD fill:#eef5fd,stroke:#1168bd
    style PRU fill:#fffbea,stroke:#d6b656
```

## 6. Beneficios para el proyecto

- Trazabilidad directa: caso de uso de ICONIX → especificación → método del servicio → prueba.
- Las reglas del trámite están en un solo archivo de servicio, no repartidas en las pantallas.
- Cualquier miembro del equipo encuentra rápido dónde cambiar algo.

## 7. Enfoques evaluados y no adoptados

| Enfoque | Por qué no se adoptó ahora |
|---|---|
| Clean Architecture | Requiere entidades propias, interfaces y repositorios para no depender de Laravel. Para este tamaño añade capas sin un beneficio visible para el usuario. Queda como mejora futura. |
| Microservicios | El hosting compartido no lo permite y el dominio es uno solo. |
| SPA (Angular o React) + API REST | Necesita compilar con Node.js y mantener dos aplicaciones; Blade cubre la necesidad. |
