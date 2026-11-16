# Patrones de diseño

## 1. Objetivo

Identificar los patrones de diseño que **sí usa** el sistema construido.

## 2. Patrones aplicados

### 2.1. Modelo-Vista-Controlador (MVC)
Base de Laravel: modelos Eloquent, vistas Blade y controladores.

### 2.2. Service Layer (capa de servicios)
`FlujoExpediente`, `Notificador`, `Archivos` y `Verificacion` contienen la lógica de negocio que los controladores reutilizan. Así, el registro presencial (CU17) y el virtual (CU05) comparten las mismas reglas.

### 2.3. Active Record
Cada modelo Eloquent (`Expediente`, `Movimiento`…) representa una fila y sabe guardarse. Es el patrón de acceso a datos de Laravel; reemplaza al patrón Repository.

### 2.4. Inyección de dependencias
El contenedor de Laravel entrega los servicios a los controladores y a `FlujoExpediente` sus colaboradores. En las pruebas se reemplazan el correo y el disco por versiones falsas.

### 2.5. Policy (autorización)
`ExpedientePolicy` reúne en un solo lugar las reglas de quién ve (`view`) y quién opera (`operar`) un expediente.

### 2.6. Middleware (cadena de responsabilidad)
Cada petición pasa por middleware en cadena: subdominio, sesión, `VerificarRol` y `throttle`.

### 2.7. Observer (eventos del modelo)
`Expediente` y `Respuesta` asignan su código de verificación en el evento `creating`, sin que el resto del código tenga que recordarlo.

### 2.8. Template View
Las vistas Blade heredan de una plantilla base (`@extends`) y reutilizan parciales (`@include`), igual que los PDF con `pdf/base.blade.php`.

## 3. Patrones no usados

Repository y Adapter (propios de Clean Architecture) no se usan, porque Eloquent ya cumple ese papel. Se proponen si en el futuro se separa la lógica de negocio del framework.

## 4. Consideraciones

Los patrones se eligieron por ser los que Laravel ya trae, para que el equipo y el área de TI del instituto puedan mantener el sistema.
