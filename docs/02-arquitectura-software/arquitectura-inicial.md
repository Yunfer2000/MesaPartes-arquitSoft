# Arquitectura del sistema

Arquitectura del sistema **Mesa de Partes Virtual** tal como está construido (Laravel 13 y PostgreSQL).

## Diagrama de arquitectura

```mermaid
flowchart TD
subgraph ACTORES["ACTORES"]
    Adm["Administrado (sin cuenta)"]
    Cual["Cualquier persona"]
    Mesa["Operador de Mesa de Partes"]
    Resp["Responsable de área"]
    Admin["Administrador"]
    Dir["Dirección General"]
end

subgraph PRESENTACION["PRESENTACIÓN (vistas Blade)"]
    Pub["Portal público<br/>dominio principal"]
    Per["Portal del personal<br/>subdominio"]
end

subgraph CONTROL["CONTROL (controladores, middleware y políticas)"]
    CPub["TramiteExterno · Verificacion · Pdf"]
    CPer["MesaPartes · Area · Derivacion · Busqueda · Reporte · Pendientes · Admin"]
    Seg["VerificarRol · ExpedientePolicy · throttle"]
end

subgraph SERVICIOS["SERVICIOS (lógica de negocio)"]
    Flujo["FlujoExpediente<br/>un método por caso de uso"]
    Not["Notificador"]
    Arch["Archivos"]
    Ver["Verificacion"]
end

subgraph DATOS["DATOS"]
    Mod["Modelos Eloquent"]
    BD[("PostgreSQL")]
    Disco[("Almacenamiento privado<br/>de archivos")]
end

SMTP["Servicio de correo (SMTP)"]

Adm --> Pub
Cual --> Pub
Mesa --> Per
Resp --> Per
Admin --> Per
Dir --> Per

Pub --> CPub
Per --> CPer
CPub --> Seg
CPer --> Seg
CPub --> Flujo
CPer --> Flujo
CPub --> Ver
Flujo --> Not
Flujo --> Arch
Flujo --> Mod
Ver --> Mod
Arch --> Disco
Mod --> BD
Not --> SMTP
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
│ Modelos Eloquent · Migraciones · PostgreSQL · archivos │
└────────────────────────────────────────────────────────┘
```

Cada capa solo llama a la de abajo. Las vistas no consultan la base de datos por su cuenta: reciben los datos que les pasa el controlador.

## Relación con ICONIX

| Elemento de robustez (ICONIX) | Dónde está en el código |
|---|---|
| Boundary (pantalla) | Vista Blade (`resources/views`) |
| Control | Controlador + método de `FlujoExpediente` |
| Entity | Modelo Eloquent (`app/Models`) y su tabla |
