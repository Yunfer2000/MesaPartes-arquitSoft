# Drivers arquitectónicos

| ID | Driver arquitectónico | Origen | ¿Por qué influye en la arquitectura? |
|---|---|---|---|
| DA01 | El sistema debe soportar múltiples usuarios concurrentes realizando trámites y consultas. | AC03 – Escalabilidad | Influye en la capacidad de crecimiento y en la estrategia de despliegue del sistema. |
| DA02 | Las operaciones de registro, consulta y seguimiento de expedientes deben responder adecuadamente. | AC01 – Rendimiento | Influye en el procesamiento de solicitudes, comunicación entre componentes y acceso a datos. |
| DA03 | Los expedientes y documentos deben estar protegidos frente a accesos no autorizados. | AC04 – Seguridad | Influye en la autenticación, autorización, control de acceso y protección de la información. |
| DA04 | El sistema debe mantener la trazabilidad completa de cada expediente. | AC05 – Trazabilidad / RF23 | Influye en el almacenamiento del historial y registro de las acciones realizadas. |
| DA05 | El sistema debe permitir diferentes roles y permisos de usuario. | RF02 / RF21 | Influye en el diseño del control de acceso y separación de responsabilidades. |
| DA06 | El sistema debe permitir la derivación y asignación de expedientes a diferentes áreas y responsables. | RF10 / RF11 | Influye en la organización de los módulos y en las relaciones entre los componentes de gestión de expedientes. |
| DA07 | El sistema debe permitir adjuntar y consultar documentos digitales asociados a los expedientes. | RF05 / RF18 | Influye en la gestión y almacenamiento de documentos dentro de la solución. |
| DA08 | El sistema debe estar preparado para integrarse posteriormente con otros sistemas institucionales. | RNF15 – Interoperabilidad | Influye en la definición de interfaces y mecanismos de integración. |