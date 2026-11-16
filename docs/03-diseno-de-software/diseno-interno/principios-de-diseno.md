# Principios de diseño (SOLID)

## 1. Objetivo

Mostrar cómo se aplican los principios SOLID en el código real y dónde no se aplican del todo.

## 2. Contexto

El código es un monolito en capas con Laravel. SOLID se aplica de forma pragmática, sin crear interfaces que no se necesitan.

## 3. SOLID en el sistema

| Principio | Cómo se aplica | Ejemplo |
|---|---|---|
| S – Responsabilidad única | Cada clase tiene un propósito. | `Archivos` solo guarda archivos y calcula la huella; `Notificador` solo envía avisos; `Verificacion` solo maneja códigos y QR. |
| O – Abierto/cerrado | Se agregan funciones nuevas en clases nuevas sin modificar las existentes. | La descarga en PDF se agregó con `PdfController` sin cambiar `TramiteController`. |
| L – Sustitución de Liskov | Los servicios del framework se reemplazan por dobles en las pruebas. | `Storage::fake()` y `Notification::fake()` sustituyen al disco y al correo. |
| I – Segregación de interfaces | Cada controlador expone solo lo de su caso de uso. | `DerivacionController` tiene un solo método `store`. |
| D – Inversión de dependencias | **Parcial.** Los servicios se inyectan, pero dependen de clases concretas y de Eloquent, no de interfaces. | `FlujoExpediente` recibe `Archivos` y `Notificador` por el constructor. |

## 4. ¿Qué pasaría sin estos principios?

Las reglas del trámite estarían repetidas en cada pantalla (virtual, presencial, área), un cambio en los estados obligaría a tocar muchos archivos y no se podría probar sin enviar correos reales.

## 5. Otros criterios complementarios

- **DRY:** las reglas de estados están en `exigirEstado`, y los parciales de Blade evitan repetir vistas.
- **KISS:** sin capas ni interfaces que el tamaño del sistema no necesita.
- **Separación de responsabilidades:** las vistas no consultan la base de datos.

## 6. Beneficios

Código fácil de leer por el equipo y por el área de TI, pruebas automáticas por caso de uso y cambios localizados.

## 7. Consideraciones

Aplicar la inversión de dependencias completa (interfaces y repositorios) es el paso hacia Clean Architecture, propuesto como mejora futura.
