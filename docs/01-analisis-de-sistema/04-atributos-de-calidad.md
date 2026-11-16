# Atributos de calidad

## 1. Identificación de atributos de calidad

Se relacionan con los requisitos no funcionales (RNF) del análisis ICONIX del proyecto.

| ID | Atributo | Escenario de calidad | RNF |
|---|---|---|---|
| AC01 | Rendimiento | La consulta de un expediente responde en 3 s o menos y un registro presencial se completa en 1 minuto o menos. | RNF04 |
| AC02 | Disponibilidad | El formulario y la consulta pública funcionan las 24 horas, salvo mantenimiento. | RNF01 |
| AC03 | Escalabilidad | El sistema soporta el crecimiento de expedientes y usuarios del instituto sin rediseño. | RNF11 |
| AC04 | Seguridad | Login solo en el subdominio del personal, bloqueo tras 5 intentos, captcha y límite de trámites por documento en el formulario público, permisos por rol y por expediente. | RNF05, RNF06, RNF15, RNF17 |
| AC05 | Trazabilidad | Cada movimiento del expediente queda registrado con usuario, área, fecha y estado. | RNF08, RNF14 |
| AC06 | Usabilidad | Interfaz sencilla, adaptable al celular, con revisión de datos antes de enviar. | RNF03, RNF12 |
| AC07 | Mantenibilidad | Código en capas, con la lógica de negocio separada por caso de uso, especificaciones por CU (`specs/`) y pruebas automáticas. | RNF13 |
| AC08 | Integridad | Acciones simultáneas no duplican operaciones (bloqueo de fila) y cada archivo guarda su huella SHA-256. | RNF07, RNF18 |

## 2. Herramientas para la evaluación de atributos de calidad

| ID | Atributo | Herramienta | Propósito de la prueba |
|---|---|---|---|
| AC01 | Rendimiento | Apache JMeter | Medir el tiempo de respuesta de la consulta pública y del registro presencial con varios usuarios a la vez. |
| AC02 | Disponibilidad | Uptime Kuma | Vigilar que el portal público y el subdominio del personal respondan. |
| AC03 | Escalabilidad | Grafana k6 | Aumentar por etapas los usuarios virtuales que consultan y presentan trámites. |
| AC04 | Seguridad | OWASP ZAP | Analizar el portal público y el subdominio del personal. |
| AC05 | Trazabilidad | PHPUnit (pruebas del sistema) y consultas a la base de datos | Comprobar que cada derivación, recepción y respuesta deja su movimiento en el historial. |
| AC06 | Usabilidad | Prueba con usuarios (tareas guiadas) | Pedir a estudiantes que presenten y consulten un trámite y medir si lo logran sin ayuda. |
| AC07 | Mantenibilidad | PHPUnit, Laravel Pint y SonarQube | Mantener las pruebas en verde, el estilo de código y medir duplicación y complejidad. |
| AC08 | Integridad | PHPUnit | Pruebas de acciones simultáneas (segundo operador recibe aviso) y de comparación de huella. |

## 3. Evidencia disponible

- El sistema tiene **pruebas automáticas con PHPUnit** (86 al 14/11/2026) que cubren cada caso de uso; se ejecutan con `php artisan test`.
- Cada caso de uso tiene su especificación en `specs/` con criterios de aceptación enlazados a sus pruebas (desarrollo guiado por especificaciones como práctica de apoyo a ICONIX).
- Las pruebas con JMeter, k6, ZAP y Uptime Kuma se harán sobre el sistema publicado en el hosting del instituto.
