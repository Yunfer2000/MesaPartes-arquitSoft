# Atributos de calidad

## 1. Identificación de atributos de calidad

Los atributos de calidad establecen las características que deberá cumplir el sistema de Mesa de Partes Virtual del IESTP Manuel Antonio Hierro Pozo – Ayacucho para garantizar un funcionamiento adecuado, seguro y eficiente.

| ID | Atributo de calidad | Escenario de calidad |
|---|---|---|
| AC01 | Rendimiento | Las consultas y operaciones de los trámites deberán responder adecuadamente incluso cuando exista una cantidad elevada de usuarios concurrentes. |
| AC02 | Disponibilidad | El sistema deberá permanecer disponible para permitir el registro y seguimiento de trámites, salvo períodos de mantenimiento. |
| AC03 | Escalabilidad | La arquitectura deberá permitir incrementar la cantidad de usuarios sin requerir un rediseño completo del sistema. |
| AC04 | Seguridad | Los datos de los usuarios, expedientes y documentos deberán estar protegidos frente a accesos no autorizados. |
| AC05 | Trazabilidad | Las acciones importantes realizadas sobre cada expediente deberán quedar registradas para permitir su seguimiento. |
| AC06 | Usabilidad | La interfaz deberá ser sencilla e intuitiva para usuarios con diferentes niveles de conocimiento tecnológico. |
| AC07 | Mantenibilidad | El sistema deberá estar organizado modularmente para facilitar futuras modificaciones y correcciones. |

## 2. Herramientas para la evaluación de atributos de calidad

Para comprobar el cumplimiento de los atributos de calidad se propone utilizar herramientas especializadas que permitan evaluar el comportamiento, la seguridad, la disponibilidad y la calidad del sistema.

| ID | Atributo de calidad | Software o herramienta | Propósito de la prueba |
|---|---|---|---|
| AC01 | Rendimiento | Apache JMeter | Medir los tiempos de respuesta de la API y el comportamiento del sistema ante múltiples solicitudes simultáneas. |
| AC02 | Disponibilidad | Uptime Kuma | Monitorear la disponibilidad de la aplicación y verificar si los servicios responden correctamente. |
| AC03 | Escalabilidad | Grafana k6 | Incrementar progresivamente la cantidad de usuarios virtuales para evaluar cómo responde el sistema ante el aumento de carga. |
| AC04 | Seguridad | OWASP ZAP | Analizar la aplicación web y detectar posibles vulnerabilidades de seguridad. |
| AC05 | Trazabilidad | Postman y PostgreSQL | Ejecutar operaciones mediante la API y comprobar que las acciones realizadas queden almacenadas en el historial de expedientes. |
| AC06 | Usabilidad | Maze | Evaluar la facilidad de uso de las interfaces mediante tareas realizadas por usuarios. |
| AC07 | Mantenibilidad | SonarQube | Analizar la calidad del código fuente, identificar duplicaciones, complejidad y posibles problemas de mantenimiento. |

## 3. Descripción de las herramientas propuestas

### 3.1. Apache JMeter – Rendimiento

Permite simular múltiples solicitudes de usuarios para medir los tiempos de respuesta y evaluar el comportamiento del backend bajo carga.

**Prueba propuesta:** simular usuarios que consultan expedientes y registran trámites simultáneamente.

Página oficial: https://jmeter.apache.org/

### 3.2. Uptime Kuma – Disponibilidad

Permite monitorear los servicios y comprobar si la aplicación y la API se encuentran disponibles.

**Prueba propuesta:** supervisar periódicamente la respuesta de la API y registrar los períodos de disponibilidad e interrupción.

Página oficial: https://github.com/louislam/uptime-kuma

### 3.3. Grafana k6 – Escalabilidad

Permite realizar pruebas de carga incrementando progresivamente los usuarios virtuales y observar cómo responde el sistema.

**Prueba propuesta:** incrementar la carga de usuarios virtuales en diferentes etapas y evaluar los tiempos de respuesta y errores.

Página oficial: https://grafana.com/docs/k6/latest/

### 3.4. OWASP ZAP – Seguridad

Permite analizar aplicaciones web para identificar posibles vulnerabilidades y problemas de configuración de seguridad.

**Prueba propuesta:** analizar los mecanismos de autenticación, los controles de acceso y los posibles problemas de seguridad de la aplicación.

Página oficial: https://www.zaproxy.org/

### 3.5. Postman y PostgreSQL – Trazabilidad

Postman permite ejecutar solicitudes a la API REST, mientras que PostgreSQL permite comprobar si las operaciones importantes quedan registradas en el historial del expediente.

**Prueba propuesta:** realizar una derivación de expediente mediante la API y verificar que el usuario responsable, la fecha, la acción y los cambios realizados queden registrados.

Página oficial de Postman: https://www.postman.com/

### 3.6. Maze – Usabilidad

Permite evaluar la experiencia de los usuarios al interactuar con las interfaces del sistema y completar determinadas tareas.

**Prueba propuesta:** solicitar a diferentes usuarios que registren una solicitud y consulten el estado de su trámite, evaluando la facilidad para completar ambas operaciones.

Página oficial: https://maze.co/

### 3.7. SonarQube – Mantenibilidad

Permite analizar el código fuente para identificar problemas de calidad, duplicaciones, complejidad y posibles errores.

**Prueba propuesta:** analizar los módulos del backend para evaluar la calidad y mantenibilidad del código desarrollado.

Página oficial: https://www.sonarsource.com/products/sonarqube/

## 4. Consideraciones

Las herramientas presentadas constituyen una propuesta para evaluar los atributos de calidad del sistema de Mesa de Partes Virtual.

Su utilización se realizará durante las etapas de implementación y validación. Los resultados obtenidos permitirán identificar problemas y comprobar el cumplimiento de los escenarios de calidad establecidos.

La selección de herramientas no representa una implementación ni una prueba ya ejecutada. Los resultados y evidencias se documentarán cuando el sistema esté desarrollado y disponible para su evaluación.