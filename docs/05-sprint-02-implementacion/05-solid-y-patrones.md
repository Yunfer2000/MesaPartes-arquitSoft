# Principios SOLID y patrones de diseño en el código

## 1. SOLID

| Principio | Dónde se cumple | Ejemplo concreto |
|---|---|---|
| **S** Responsabilidad única | Cada clase tiene un solo motivo para cambiar | `ConsultaRequest` solo valida; `ConsultaExpediente` solo busca; `SeguimientoResource` solo arma el JSON; `ConsultaController` solo coordina. Si cambia la forma del JSON, se toca solo el Resource |
| **O** Abierto/cerrado | Se agregan comportamientos sin modificar lo que ya funciona | La API v1 se agregó **sin cambiar** `FlujoExpediente` ni los modelos. Los límites de peticiones se definen en un bucle: un límite nuevo es una línea en `config/api.php` |
| **L** Sustitución de Liskov | Las subclases se usan donde se espera la clase base | Los cuatro Form Requests extienden `FormRequest` y Laravel los trata igual. Los Resources extienden `JsonResource`. `ReglaNegocio` extiende `Exception` y se lanza en cualquier servicio |
| **I** Segregación de interfaces | Cada cliente depende solo de lo que usa | El cliente público solo ve las operaciones de lectura; las de escritura están en un grupo aparte con `auth:sanctum` y `rol:Administrador`. `SeguimientoResource` no expone correo ni celular |
| **D** Inversión de dependencias | Las clases reciben sus dependencias, no las crean | `ConsultaController::store(ConsultaRequest $request, ConsultaExpediente $consulta)`: el contenedor de Laravel inyecta ambas. En las pruebas se pueden reemplazar. `CatalogoTiposDocumento` usa la fachada `Cache`, que puede ser `array`, `database` o `redis` sin cambiar el código |

## 2. Patrones de diseño

| Patrón | Tipo | Dónde | Para qué |
|---|---|---|---|
| **MVC** | Arquitectónico | Todo el sistema | Separar datos, vistas y control |
| **Capa de servicios** (Service Layer) | Arquitectónico | `app/Services` | Que la pantalla y la API compartan las reglas del negocio |
| **Objeto de transferencia de datos** (DTO) | Estructural | `app/Http/Resources` | Definir qué datos salen de la API, separados del modelo |
| **Active Record** | De datos | Modelos Eloquent | Cada fila de la tabla es un objeto con sus relaciones |
| **Cache-aside** | De rendimiento | `CatalogoTiposDocumento` | Leer de la caché y limpiarla al escribir |
| **Cadena de responsabilidad** | De comportamiento | Middleware: `throttle`, `auth:sanctum`, `rol` | Cada filtro decide si la petición pasa al siguiente |
| **Inyección de dependencias** | Creacional | Controladores y servicios | El contenedor de Laravel crea y entrega los objetos |
| **Fachada** (Facade) | Estructural | `Cache`, `DB`, `RateLimiter`, `Storage` | Una interfaz simple sobre subsistemas complejos |
| **Política** (Policy) | De comportamiento | `ExpedientePolicy` | Decidir quién puede ver o actuar sobre un expediente |
| **Máquina de estados** | De comportamiento | `FlujoExpediente` | Solo se permiten las transiciones válidas (Presentado, Recibido, Derivado...) |
| **Observador** (Notificaciones) | De comportamiento | `Notificador` y `AvisoExpediente` | Avisar al ciudadano y a las áreas cuando cambia un expediente |

## 3. Ejemplo de código: controlador delgado

```php
// app/Http/Controllers/Api/V1/ConsultaController.php
public function store(ConsultaRequest $request, ConsultaExpediente $consulta)
{
    $expediente = $consulta->buscar($request->validated('numero'), $request->validated('numero_documento'));

    if (! $expediente) {
        return response()->json(['message' => 'No encontramos un expediente con ese número o código y ese documento de identidad.'], 404);
    }

    $expediente->load(['tipoDocumento', 'administrado', 'movimientos.areaDestino']);

    return new SeguimientoResource($expediente);
}
```

El controlador no valida a mano (lo hace `ConsultaRequest`), no escribe SQL (lo hace `ConsultaExpediente`) y no arma el JSON (lo hace `SeguimientoResource`).
