# Diseño interno

## 1. Objetivo

Describir cómo está organizado por dentro el código del sistema y cómo se ejecuta un caso de uso.

## 2. Estructura de carpetas

```text
app/
├── Exceptions/ReglaNegocio.php        # error de regla de negocio (vuelve con aviso)
├── Http/
│   ├── Controllers/                   # capa de control
│   │   ├── Admin/                     # usuarios, áreas, tipos de documento, auditoría
│   │   ├── Auth/SesionController.php  # CU02
│   │   ├── TramiteExternoController.php  # CU05, CU06, CU07
│   │   ├── MesaPartesController.php   # CU08, CU09, CU17
│   │   ├── DerivacionController.php   # CU10
│   │   ├── AreaController.php         # CU11, CU12, CU13
│   │   ├── VerificacionController.php # CU19
│   │   ├── PdfController.php          # RF26 (PDF con QR)
│   │   └── ...                        # Busqueda, Reporte, Pendientes, Copia, Documento
│   └── Middleware/VerificarRol.php
├── Models/                            # capa de datos (Eloquent)
├── Notifications/AvisoExpediente.php  # correo
├── Policies/ExpedientePolicy.php      # quién ve y quién opera un expediente
├── Services/                          # capa de servicios
│   ├── FlujoExpediente.php
│   ├── Notificador.php
│   ├── Archivos.php
│   └── Verificacion.php
└── Support/                           # Menu, Captcha, AreasDerivacion
config/plazos.php                      # días del semáforo
database/migrations/                   # tablas de MySQL
resources/views/                       # capa de presentación (Blade)
routes/web.php                         # rutas públicas y del personal (subdominio)
specs/                                 # una especificación por caso de uso (SDD)
tests/Feature/                         # pruebas automáticas por caso de uso
```

## 3. Organización por capas

### 3.1. Presentación
`resources/views`: `externo/` (portal público), `mesa/`, `area/`, `admin/`, `verificar/`, `pdf/` y `partials/` (estado, semáforo, verificación).

### 3.2. Control
Controladores delgados: validan con `$request->validate()`, autorizan con `Gate::authorize()` y llaman al servicio.

### 3.3. Servicios
`FlujoExpediente` concentra las reglas: estados permitidos (`exigirEstado`), bloqueo de fila, movimientos y notificaciones.

### 3.4. Datos
Modelos con relaciones (`hasMany`, `belongsTo`) y métodos de dominio como `Expediente::areaActual()`, `burbujas()` y `semaforo()`.

## 4. Regla de dependencias entre capas

### 4.1. Diagrama de dependencias

```mermaid
flowchart TB
    subgraph L1[1 · Presentación · resources/views]
        direction LR
        V1[externo/ · portal público]
        V2[mesa/ · area/ · admin/]
        V3[pdf/ · verificar/ · partials/]
        V1 ~~~ V2 ~~~ V3
    end
    subgraph L2[2 · Control · app/Http]
        direction LR
        RT[routes/web.php]
        MW[Middleware VerificarRol]
        CT[Controladores]
        PO[ExpedientePolicy]
        RT --> MW --> CT --> PO
    end
    subgraph L3[3 · Servicios · app/Services]
        direction LR
        FE[FlujoExpediente]
        SO[Solicitantes]
        AR[Archivos]
        NO[Notificador]
        VE[Verificacion]
        FE ~~~ SO ~~~ AR ~~~ NO ~~~ VE
    end
    subgraph L4[4 · Datos · app/Models]
        direction LR
        MO[Modelos Eloquent]
        MI[database/migrations]
        MO ~~~ MI
    end
    subgraph TR[Transversal]
        direction LR
        EX[Exceptions/ReglaNegocio]
        SU[Support: Menu, Captcha,<br/>AreasDerivacion, ReglasSolicitante]
        CF[config/plazos.php]
        EX ~~~ SU ~~~ CF
    end
    BD[(MySQL 8)]

    L2 -- "① arma la vista con datos" --> L1
    L2 -- "② llama un método por caso de uso" --> L3
    L3 -- "③ lee y escribe en transacción" --> L4
    L2 -. "consultas simples y Policy" .-> L4
    L4 -- "④ SQL" --> BD
    L3 -. "lanza ReglaNegocio" .-> TR
    L1 x-. "prohibido" .-x L3
    classDef p fill:#dae8fc,stroke:#6c8ebf,color:#0b2545
    classDef c fill:#85bbf0,stroke:#5d8fc4,color:#0b2545
    classDef s fill:#1168bd,stroke:#0b4884,color:#ffffff
    classDef d fill:#fff2cc,stroke:#d6b656,color:#3a3000
    classDef t fill:#f5f5f5,stroke:#999999,color:#333333
    classDef pol fill:#fde9c4,stroke:#d79b00,color:#3d2c00
    class V1,V2,V3 p
    class RT,MW,CT c
    class PO pol
    class FE,SO,AR,NO,VE s
    class MO,MI,BD d
    class EX,SU,CF t
    style L1 fill:#f3f8ff,stroke:#6c8ebf
    style L2 fill:#eef6fe,stroke:#5d8fc4
    style L3 fill:#e8f0fa,stroke:#1168bd
    style L4 fill:#fffbea,stroke:#d6b656
    style TR fill:#fafafa,stroke:#999999,stroke-dasharray:4 4
```

## 5. Organización funcional

| Caso de uso | Controlador → método | Servicio → método |
|---|---|---|
| CU05 Presentar trámite virtual | `TramiteExternoController@store` | `FlujoExpediente::presentar` |
| CU06 Subsanar observación | `TramiteExternoController@subsanar` | `FlujoExpediente::subsanar` |
| CU08 Recepcionar expediente | `MesaPartesController@recepcionar` | `FlujoExpediente::recepcionar` |
| CU09 Observar expediente | `MesaPartesController@observar` | `FlujoExpediente::observar` |
| CU10 Derivar expediente | `DerivacionController@store` | `FlujoExpediente::derivar` |
| CU11 Confirmar recepción | `AreaController@confirmar` | `FlujoExpediente::confirmarRecepcion` |
| CU12 Actualizar estado | `AreaController@avance` | `FlujoExpediente::registrarAvance` |
| CU13 Registrar respuesta | `AreaController@responder` | `FlujoExpediente::responder` |
| CU17 Registro presencial | `MesaPartesController@registrarPresencial` | `FlujoExpediente::registrarPresencial` |
| CU19 Verificar documento | `VerificacionController@show` | `Verificacion::buscar` |

## 6. Diagrama de clases del flujo del expediente

### 6.1. Diagrama de clases

```mermaid
classDiagram
    direction LR
    class FlujoExpediente {
        <<service>>
        -Archivos archivos
        -Notificador notificador
        +presentar(Administrado, Usuario, datos, principal, anexos) Expediente
        +subsanar(Expediente, Usuario, descripcion, archivos) void
        +observacionPendiente(Expediente) Observacion
        +recepcionar(Expediente, Usuario, datos) void
        +observar(Expediente, Usuario, motivo, detalle) Observacion
        +derivar(Expediente, Usuario, Area, proveido, copias) void
        +confirmarRecepcion(Expediente, Usuario) void
        +tomarConocimiento(Copia, Usuario) void
        +registrarAvance(Expediente, Usuario, nota) void
        +responder(Expediente, Usuario, contenido, documento) Respuesta
        +registrarPresencial(Administrado, Usuario, datos, escaneado) Expediente
        -mover(Expediente, Usuario, accion, Area, proveido) Movimiento
        -exigirEstado(Expediente, estados) void
    }
    class Solicitantes {
        <<service>>
        +resolver(datos, tipoAdministrado) Administrado
        +datosRepresentante(datos)$ array
        -remitente(datos, tipo) Administrado
        -entidad(datos, tipo) Administrado
    }
    class Archivos {
        <<service>>
        +array REGLA_PUBLICO$
        +array REGLA_PRESENCIAL$
        +guardar(Expediente, archivo, principal) DocumentoAdjunto
    }
    class Notificador {
        <<service>>
        -array MENSAJES$
        +notificar(Expediente, evento, datos) Notificacion
    }
    class Verificacion {
        <<service>>
        +nuevoCodigo()$ string
        +buscar(codigo)$ array
        +url(codigo)$ string
        +qrSvg(texto, tamano)$ string
    }
    class Expediente {
        <<entity>>
        +numero
        +codigo_verificacion
        +estado
        +areaActual() Area
        +burbujas() Collection
        +semaforo() string
    }
    class Administrado {
        <<entity>>
        +tipo_doc_identidad
        +numero_documento
        +razon_social
        +esJuridica() bool
        +ocultar(dato)$ string
    }
    class Movimiento { <<entity>> }
    class Copia { <<entity>> }
    class Observacion { <<entity>> }
    class Subsanacion { <<entity>> }
    class Respuesta {
        <<entity>>
        +codigo_verificacion
    }
    class DocumentoAdjunto {
        <<entity>>
        +ruta
        +hash_sha256
        +es_principal
    }
    class Notificacion { <<entity>> }
    class AvisoExpediente { <<notification>> }

    FlujoExpediente o-- Archivos
    FlujoExpediente o-- Notificador
    Archivos ..> DocumentoAdjunto : crea con SHA-256
    Notificador ..> Notificacion : crea
    Notificador ..> AvisoExpediente : envía
    Solicitantes ..> Administrado : busca o crea
    Verificacion ..> Expediente : busca constancia
    Verificacion ..> Respuesta : busca respuesta
    FlujoExpediente ..> Expediente : cambia estado
    Administrado "1" --> "0..*" Expediente : presenta
    Expediente "1" *-- "1..*" Movimiento
    Expediente "1" *-- "0..*" Copia
    Expediente "1" *-- "0..*" Observacion
    Observacion "1" *-- "0..1" Subsanacion
    Expediente "1" *-- "0..*" Respuesta
    Expediente "1" *-- "1..*" DocumentoAdjunto
    Expediente "1" *-- "0..*" Notificacion

    style FlujoExpediente fill:#cfe2f7,stroke:#1168bd,color:#0b2545
    style Solicitantes fill:#cfe2f7,stroke:#1168bd,color:#0b2545
    style Archivos fill:#cfe2f7,stroke:#1168bd,color:#0b2545
    style Notificador fill:#cfe2f7,stroke:#1168bd,color:#0b2545
    style Verificacion fill:#cfe2f7,stroke:#1168bd,color:#0b2545
    style AvisoExpediente fill:#e1d5e7,stroke:#9673a6,color:#2b1a33
```

### 6.2. Ciclo de vida del expediente

Estados que maneja `FlujoExpediente`. Cada flecha es un método del servicio que revisa el estado actual con `exigirEstado` antes de cambiarlo.

```mermaid
stateDiagram-v2
    direction LR
    [*] --> Presentado : CU05 presentar<br/>portal sin cuenta
    [*] --> Recibido : CU17 registro presencial<br/>en ventanilla
    Presentado --> Recibido : CU08 recepcionar
    Presentado --> Observado : CU09 observar
    Recibido --> Observado : CU09 observar
    Observado --> Subsanado : CU06 subsanar<br/>el administrado corrige
    Subsanado --> Recibido : CU08 recepcionar
    Subsanado --> Observado : CU09 observar de nuevo
    Recibido --> Derivado : CU10 derivar<br/>con copias opcionales
    Derivado --> EnAtencion : CU11 confirmar recepción
    EnAtencion --> EnAtencion : CU12 registrar avance
    EnAtencion --> Derivado : CU10 derivar a otra área
    EnAtencion --> Finalizado : CU13 responder<br/>PDF con código y QR
    Finalizado --> [*]

    state "En atención" as EnAtencion

    note right of Presentado
        Mesa de Partes lo ve en su bandeja.
        El semáforo cuenta días hábiles
        desde el último movimiento.
    end note
    note right of Finalizado
        La respuesta se verifica en
        /verificar con su código.
    end note

    classDef inicio fill:#dae8fc,stroke:#6c8ebf,color:#0b2545
    classDef alerta fill:#ffe6cc,stroke:#d79b00,color:#3d2c00
    classDef curso fill:#cfe2f7,stroke:#1168bd,color:#0b2545
    classDef fin fill:#d5e8d4,stroke:#82b366,color:#1d3a1b
    class Presentado,Recibido inicio
    class Observado,Subsanado alerta
    class Derivado,EnAtencion curso
    class Finalizado fin
```

### 6.3. Modelo de datos

Tablas principales en MySQL 8 y sus relaciones (las de sesiones, caché y colas de Laravel no se muestran).

```mermaid
erDiagram
    ADMINISTRADOS ||--o{ EXPEDIENTES : presenta
    TIPOS_DOCUMENTO |o--o{ EXPEDIENTES : clasifica
    AREAS ||--o{ EXPEDIENTES : "dirigido a"
    EXPEDIENTES ||--|{ MOVIMIENTOS : recorre
    EXPEDIENTES ||--|{ DOCUMENTOS_ADJUNTOS : adjunta
    EXPEDIENTES ||--o{ OBSERVACIONES : recibe
    OBSERVACIONES ||--o| SUBSANACIONES : "se corrige con"
    EXPEDIENTES ||--o{ RESPUESTAS : "se responde con"
    EXPEDIENTES ||--o{ COPIAS : "se copia a"
    MOVIMIENTOS ||--o{ COPIAS : genera
    EXPEDIENTES ||--o{ NOTIFICACIONES : avisa
    AREAS ||--o{ MOVIMIENTOS : "origen y destino"
    AREAS ||--o{ COPIAS : recibe
    AREAS |o--o{ AREAS : "subárea de"
    AREAS |o--o{ USUARIOS : agrupa
    ROLES ||--o{ USUARIOS : asigna
    USUARIOS ||--o{ MOVIMIENTOS : registra
    USUARIOS ||--o{ REGISTROS_AUDITORIA : realiza

    ADMINISTRADOS {
        bigint id PK
        varchar tipo_doc_identidad
        varchar numero_documento UK
        varchar nombres
        varchar apellido_paterno
        varchar razon_social
        varchar correo
        varchar telefono
    }
    EXPEDIENTES {
        bigint id PK
        bigint administrado_id FK
        bigint tipo_documento_id FK
        bigint area_destino_id FK
        varchar numero UK
        varchar codigo_propio UK
        varchar codigo_verificacion UK
        varchar asunto
        varchar modalidad
        varchar estado
        datetime fecha_presentacion
        datetime fecha_recepcion
    }
    MOVIMIENTOS {
        bigint id PK
        bigint expediente_id FK
        bigint area_origen_id FK
        bigint area_destino_id FK
        bigint usuario_id FK
        varchar accion
        varchar proveido
        datetime fecha_hora
        boolean confirmado
    }
    COPIAS {
        bigint id PK
        bigint movimiento_id FK
        bigint area_id FK
        boolean leido
        datetime fecha_lectura
    }
    DOCUMENTOS_ADJUNTOS {
        bigint id PK
        bigint expediente_id FK
        varchar ruta
        char hash_sha256
        boolean es_principal
    }
    OBSERVACIONES {
        bigint id PK
        bigint expediente_id FK
        varchar motivo
        datetime fecha
    }
    SUBSANACIONES {
        bigint id PK
        bigint observacion_id FK
        text descripcion
    }
    RESPUESTAS {
        bigint id PK
        bigint expediente_id FK
        text contenido
        varchar codigo_verificacion UK
    }
    NOTIFICACIONES {
        bigint id PK
        bigint expediente_id FK
        varchar evento
        boolean enviada
    }
    AREAS {
        bigint id PK
        bigint area_padre_id FK
        varchar nombre UK
        varchar sigla
        boolean activa
    }
    USUARIOS {
        bigint id PK
        bigint rol_id FK
        bigint area_id FK
        varchar correo UK
        varchar contrasena
        boolean activo
    }
    ROLES {
        bigint id PK
        varchar nombre UK
    }
    TIPOS_DOCUMENTO {
        bigint id PK
        varchar nombre UK
        boolean activo
    }
    REGISTROS_AUDITORIA {
        bigint id PK
        bigint usuario_id FK
        varchar accion
        varchar tabla
        varchar ip
        datetime fecha_hora
    }
```

## 7. Flujo de ejecución de un caso de uso (CU05 Presentar trámite virtual)

### 7.1. Descripción del flujo

1. El administrado abre el formulario público, sin crear cuenta, y lo envía con su archivo PDF o ZIP.
2. `TramiteExternoController` limita los envíos (10 por minuto), descarta robots con un campo trampa y valida los datos con `ReglasSolicitante` y las reglas de `Archivos`.
3. Comprueba el captcha (Cloudflare Turnstile o el captcha propio).
4. `Solicitantes` busca al administrado por su documento; si no existe lo crea, y si es persona jurídica guarda los datos del representante en el expediente.
5. Revisa el límite diario de trámites por documento.
6. `FlujoExpediente::presentar` abre una transacción: crea el expediente en estado Presentado con su número y código de verificación, guarda el archivo con su huella SHA-256 y registra el primer movimiento hacia Mesa de Partes.
7. Al terminar la transacción, el `Notificador` guarda el aviso y lo envía por correo.
8. El administrado ve su constancia con el N.° de expediente, el código y el QR.

El flujo de CU10 Derivar a nivel de SQL está en el [C4 Nivel 4](../../04-modelo-c4/Nivel4-Diagrama-de-Codigo.md).

### 7.2. Diagrama de secuencia

```mermaid
sequenceDiagram
    autonumber
    actor Adm as Administrado
    participant V as Vista Blade<br/>externo/create
    participant C as TramiteExternoController
    participant K as Captcha
    participant S as Solicitantes
    participant F as FlujoExpediente
    participant A as Archivos
    participant M as Modelos Eloquent
    participant N as Notificador

    Adm->>V: abre Presentar trámite
    V-->>Adm: formulario sin cuenta
    Adm->>C: POST /tramite<br/>tipo de persona, documento, nombres,<br/>asunto, archivo PDF o ZIP, captcha
    Note over C: throttle 10 por minuto
    C->>C: campo trampa sitio_web vacío
    C->>C: validate con ReglasSolicitante<br/>y Archivos REGLA_PUBLICO
    alt datos o archivo no válidos
        C-->>Adm: vuelve al formulario con errores
    end
    C->>K: valido(captcha)
    alt captcha incorrecto
        K-->>C: false
        C-->>Adm: error en el captcha
    end
    C->>S: resolver(datos)
    S->>M: buscar Administrado por documento
    alt ya existe
        M-->>S: Administrado
        S->>M: completa datos de contacto que falten
    else es nuevo
        S->>M: crea Administrado
    end
    S-->>C: Administrado
    C->>M: contar trámites virtuales de hoy
    alt llegó al límite diario por documento
        C-->>Adm: aviso de límite diario
    end
    C->>F: presentar(administrado, null, datos, archivo)
    rect rgb(230, 240, 252)
        Note over F,M: DB::transaction
        F->>M: Expediente::create estado Presentado<br/>número generado y código de verificación
        F->>A: guardar(expediente, archivo, principal)
        A->>A: hash_file sha256
        A->>M: DocumentoAdjunto con ruta y huella
        F->>M: Movimiento presentado hacia Mesa de Partes
    end
    F->>N: notificar(expediente, presentación)
    N->>M: Notificacion y envío por correo
    F-->>C: Expediente
    C->>C: permite ver el expediente en la sesión
    C-->>Adm: redirect a la constancia<br/>N.° de expediente, código y QR
```

## 8. Criterios de diseño

- Un método de servicio por caso de uso.
- Las reglas de negocio lanzan `ReglaNegocio`; el usuario ve un aviso y no un error.
- Las operaciones que cambian un expediente van en transacción con bloqueo de fila.
- Código nuevo en archivos nuevos cuando es posible, para no chocar entre ramas del equipo.

## 9. Consideraciones

El diseño corresponde a los diagramas de clases y de secuencia del análisis ICONIX del proyecto.
