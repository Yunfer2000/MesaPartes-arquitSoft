# Estilo y patrón implementados

## 1. Estilo: monolito en capas

Todo el sistema es una sola aplicación Laravel que se despliega junta (DA-01 del Sprint 1). Dentro de ella el código se separa en tres capas. Cada capa solo llama a la de abajo:

| Capa | Carpeta | Qué hace | Ejemplos |
|---|---|---|---|
| Presentación | `app/Http`, `resources/views`, `routes` | Recibe la petición, valida los datos y arma la respuesta (HTML o JSON) | `MesaPartesController`, `Api/V1/ConsultaController`, `ConsultaRequest`, `SeguimientoResource` |
| Negocio | `app/Services`, `app/Policies` | Reglas del trámite: estados, plazos, permisos, firma | `FlujoExpediente`, `ConsultaExpediente`, `CatalogoTiposDocumento`, `Verificacion`, `ExpedientePolicy` |
| Datos | `app/Models`, `database/migrations` | Tablas y relaciones con Eloquent sobre MySQL 8 | `Expediente`, `Movimiento`, `Administrado`, `TipoDocumento` |

La API REST v1 **no es otro sistema**: es otra puerta de entrada al mismo monolito. Sus controladores usan los mismos servicios que las pantallas Blade, así que una regla del negocio se escribe una sola vez.

```mermaid
flowchart TB
    subgraph P[Capa de presentación]
        direction LR
        WEB[Controladores web<br/>vistas Blade]
        API[Controladores API v1<br/>JSON]
        REQ[Form Requests<br/>validación]
        RES[API Resources<br/>forma del JSON]
    end
    subgraph N[Capa de negocio]
        direction LR
        FLU[FlujoExpediente]
        CON[ConsultaExpediente]
        CAT[CatalogoTiposDocumento]
        VER[Verificacion]
        POL[ExpedientePolicy]
    end
    subgraph D[Capa de datos]
        direction LR
        MOD[Modelos Eloquent]
        CACHE[(Caché)]
        DB[(MySQL 8)]
    end
    WEB --> FLU
    WEB --> CON
    WEB -.permisos.-> POL
    API --> CON
    API --> CAT
    API --> VER
    REQ -.valida.-> API
    RES -.da forma.-> API
    FLU --> MOD
    CON --> MOD
    CAT --> CACHE
    CAT --> MOD
    VER --> MOD
    MOD --> DB
    classDef pres fill:#dbeafe,stroke:#1d4ed8,color:#0f172a
    classDef neg fill:#dcfce7,stroke:#15803d,color:#0f172a
    classDef dat fill:#fef3c7,stroke:#b45309,color:#0f172a
    class WEB,API,REQ,RES pres
    class FLU,CON,CAT,VER,POL neg
    class MOD,CACHE,DB dat
```

**Leyenda:** azul es presentación, verde es negocio y amarillo es datos.

### Prueba de que el estilo se respeta

- La búsqueda del expediente estaba dentro de `TramiteExternoController`. En este sprint se movió a `app/Services/ConsultaExpediente.php`, y ahora la usan la pantalla pública y la API. Las pruebas `TramiteExternoTest` (pantalla) y `ConsultaApiTest` (API) pasan con la misma clase.
- El catálogo de tipos de documento lo administran la pantalla del administrador y la API. Ambas pasan por `CatalogoTiposDocumento`, que guarda la auditoría y limpia la caché.
- Ningún controlador de la API escribe SQL ni cambia el estado de un expediente directamente.

## 2. Patrón: MVC

| Parte | En el sistema |
|---|---|
| Modelo | `app/Models` (Eloquent). Por ejemplo `Expediente` con sus relaciones `administrado`, `movimientos`, `tipoDocumento` |
| Vista | Plantillas Blade en `resources/views` para las pantallas. En la API, la "vista" es el **API Resource** que arma el JSON |
| Controlador | `app/Http/Controllers`. Son delgados: validan con un Form Request, llaman a un servicio y devuelven la vista o el JSON |

Ejemplo real del recorrido de una petición de la API:

```mermaid
sequenceDiagram
    autonumber
    actor C as Cliente (Postman o la web del instituto)
    participant R as routes/api.php<br/>throttle:api-consultas
    participant Q as ConsultaRequest
    participant K as ConsultaController
    participant S as ConsultaExpediente
    participant M as Expediente (Eloquent)
    participant J as SeguimientoResource
    C->>R: POST /api/v1/consultas
    R->>Q: valida numero y numero_documento
    Q-->>C: 422 si falta un dato
    Q->>K: datos válidos
    K->>S: buscar(numero, documento)
    S->>M: where numero o codigo_propio y documento
    M-->>S: expediente o null
    S-->>K: resultado
    K-->>C: 404 si no existe
    K->>J: expediente con remitente y movimientos
    J-->>C: 200 JSON con estado, área y recorrido
```
