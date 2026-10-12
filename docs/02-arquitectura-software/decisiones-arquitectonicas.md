# Decisiones arquitectónicas

## DA-01: Arquitectura de monolito modular

**Contexto:** El sistema debe gestionar usuarios, trámites, expedientes, documentos, derivaciones, notificaciones y reportes.

**Decisión:** Organizar la solución como un monolito modular, separando las responsabilidades en módulos funcionales.

**Drivers relacionados:** DA05, DA06, DA07.

**Justificación:** Esta organización facilita el desarrollo, despliegue y mantenimiento del sistema sin introducir la complejidad inicial de una arquitectura de microservicios.

**Consecuencias:** Los módulos compartirán el mismo despliegue, pero deberán mantener responsabilidades bien delimitadas.

## DA-02: Separación de responsabilidades mediante Clean Architecture

**Contexto:** Las reglas de gestión de expedientes deben mantenerse independientes de los detalles de interfaz, almacenamiento y servicios externos.

**Decisión:** Organizar el diseño en capas de dominio, aplicación, infraestructura y presentación.

**Drivers relacionados:** DA02, DA03, DA04, DA06.

**Justificación:** La separación permite mantener las reglas del negocio aisladas de las tecnologías utilizadas y facilita las pruebas y el mantenimiento.

**Consecuencias:** Las dependencias deberán dirigirse hacia el dominio; la infraestructura implementará los contratos definidos por las capas internas.

## DA-03: Control de acceso basado en roles

**Contexto:** El sistema contempla usuarios externos, personal de Mesa de Partes, responsables de área, jefes de área y administradores.

**Decisión:** Aplicar autenticación y autorización según los roles y permisos asignados.

**Drivers relacionados:** DA03, DA05.

**Justificación:** Permite limitar las operaciones y la consulta de información de acuerdo con las responsabilidades de cada usuario.

**Consecuencias:** Las operaciones protegidas deberán verificar la identidad y los permisos correspondientes.

## DA-04: Trazabilidad de expedientes

**Contexto:** Es necesario conocer el estado de cada expediente y las acciones realizadas durante su atención.

**Decisión:** Mantener un historial de eventos relevantes, incluyendo cambios de estado, derivaciones y acciones de atención.

**Drivers relacionados:** DA04, DA06.

**Justificación:** Facilita el seguimiento de los trámites y la consulta de su historial.

**Consecuencias:** Las operaciones relevantes deberán registrar la información necesaria para reconstruir el recorrido del expediente.

## DA-05: Gestión centralizada de documentos digitales

**Contexto:** Los expedientes requieren adjuntar y consultar documentos durante el trámite.

**Decisión:** Definir un módulo responsable de registrar los metadatos de los documentos y gestionar su almacenamiento mediante una interfaz independiente.

**Drivers relacionados:** DA03, DA07.

**Justificación:** Permite organizar los documentos y separar las reglas del sistema del mecanismo concreto de almacenamiento.

**Consecuencias:** Se deberán controlar los permisos de acceso y mantener la relación entre documentos y expedientes.

## DA-06: Integraciones mediante contratos

**Contexto:** En el futuro, el sistema podría necesitar comunicarse con otros servicios institucionales.

**Decisión:** Definir interfaces para los servicios externos, de modo que las integraciones puedan incorporarse sin modificar las reglas centrales del negocio.

**Drivers relacionados:** DA02, DA08.

**Justificación:** Reduce el acoplamiento con proveedores y facilita futuras ampliaciones.

**Consecuencias:** Las integraciones concretas se implementarán cuando se definan los sistemas externos y sus requisitos.