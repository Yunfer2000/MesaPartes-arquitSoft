# Principios de diseño (SOLID)

## 1. Objetivo

Documentar cómo se aplican los principios SOLID en el backend del sistema de Mesa de Partes Virtual del IESTP Manuel Antonio Hierro Pozo – Ayacucho, organizado mediante un monolito modular y el enfoque de Clean Architecture.

Estos principios buscan que los módulos tengan responsabilidades bien definidas, que las reglas del negocio no dependan de tecnologías externas y que los componentes puedan mantenerse, probarse y ampliarse sin afectar innecesariamente a otras partes del sistema.

## 2. Contexto

- **Estilo arquitectónico:** Monolito modular.
- **Enfoque interno:** Clean Architecture (presentación, aplicación, dominio e infraestructura).
- **Base de datos:** PostgreSQL.
- **Frontend:** Angular 18 + TypeScript.
- **Módulo de referencia:** Expedientes, porque interviene en el registro, almacenamiento, seguimiento y control de los expedientes administrativos.

La aplicación de SOLID se plantea como una guía para el desarrollo del backend y la organización de sus módulos.

## 3. SOLID en el módulo Expedientes

Se toma como referencia el módulo Expedientes para explicar cómo se aplican los cinco principios SOLID.

| Principio | Dónde se aplica (capa) | Cómo se cumple en el sistema | Archivos de referencia |
|---|---|---|---|
| **S · Responsabilidad única (SRP)** | Todas las capas | Cada componente tiene una responsabilidad específica: el controlador recibe solicitudes HTTP, el caso de uso coordina el registro, la entidad aplica las reglas del expediente y el repositorio se encarga de la persistencia. | `expediente.controller.ts`, `registrar-expediente.use-case.ts`, `expediente.ts`, `repositorio-expedientes.postgres.ts` |
| **O · Abierto/cerrado (OCP)** | Infraestructura y aplicación | Se pueden incorporar nuevas implementaciones para almacenar documentos o consultar información sin modificar las reglas principales del caso de uso, siempre que respeten los contratos definidos. | `repositorio-expedientes.ts`, `repositorio-expedientes.postgres.ts`, `almacenamiento-documentos.adapter.ts` |
| **L · Sustitución de Liskov (LSP)** | Infraestructura | Las diferentes implementaciones de un repositorio deben poder sustituirse entre sí sin alterar el comportamiento esperado por el caso de uso. Por ejemplo, un repositorio en memoria y uno conectado a PostgreSQL deben respetar el mismo contrato. | `repositorio-expedientes.ts`, `repositorio-expedientes.memoria.ts`, `repositorio-expedientes.postgres.ts` |
| **I · Segregación de interfaces (ISP)** | Dominio | Los contratos deben contener únicamente las operaciones necesarias para cada responsabilidad. Por ejemplo, el repositorio de expedientes no debe incluir operaciones propias de notificaciones o almacenamiento documental. | `repositorio-expedientes.ts`, `notificador-tramite.contrato.ts` |
| **D · Inversión de dependencias (DIP)** | Aplicación y dominio | Los casos de uso dependen de contratos y no de implementaciones concretas. De esta manera, el caso de uso de registro no necesita conocer PostgreSQL ni los detalles técnicos de almacenamiento. | `registrar-expediente.use-case.ts`, `repositorio-expedientes.ts`, `repositorio-expedientes.postgres.ts` |

**Nota:** Los nombres de archivos son referencias propuestas para organizar el backend. Las implementaciones concretas se definirán durante el desarrollo.

## 4. ¿Qué pasaría sin SOLID en el sistema?

| Situación | Sin SOLID | Con SOLID |
|---|---|---|
| Se cambia PostgreSQL por otro mecanismo de persistencia. | Se pueden tener que modificar los casos de uso y otras partes del sistema. | Se reemplaza la implementación del repositorio respetando el mismo contrato. |
| Se incorpora otro mecanismo de almacenamiento documental. | Se modifican directamente los componentes que gestionan los expedientes. | Se incorpora un nuevo adaptador sin alterar las reglas del negocio. |
| Se quiere probar el registro de expedientes. | Se necesita depender de los mecanismos reales de almacenamiento. | Se puede utilizar una implementación en memoria que cumpla el contrato. |
| Cambia una regla de negocio del expediente. | La regla puede estar distribuida entre controladores y servicios. | La regla se mantiene dentro del componente de dominio correspondiente. |
| Se agrega una nueva operación al sistema. | Se pueden afectar componentes que realizan varias responsabilidades. | Se incorporan componentes específicos manteniendo las responsabilidades existentes. |

## 5. Relación con Clean Architecture y los patrones de diseño

Los principios SOLID complementan el enfoque de Clean Architecture y ayudan a mantener una separación adecuada entre las responsabilidades del sistema.

| Principio | Relación con la arquitectura o patrón |
|---|---|
| Responsabilidad única (SRP) | Se relaciona con la separación de responsabilidades entre las capas de Clean Architecture. |
| Abierto/cerrado (OCP) | Se favorece mediante el uso de interfaces y adaptadores que permiten incorporar nuevas implementaciones. |
| Sustitución de Liskov (LSP) | Se aplica cuando las implementaciones de un mismo contrato pueden reemplazarse sin alterar el comportamiento esperado. |
| Segregación de interfaces (ISP) | Se relaciona con la definición de contratos pequeños y específicos para cada necesidad del dominio. |
| Inversión de dependencias (DIP) | Se relaciona con la regla de dependencias de Clean Architecture y el uso de inyección de dependencias. |

### 5.1. Ejemplo de aplicación

En el módulo Expedientes, el caso de uso `RegistrarExpediente` utiliza el contrato `RepositorioExpedientes` para almacenar la información.

Este contrato puede tener diferentes implementaciones, como un repositorio en memoria para pruebas o un repositorio conectado a PostgreSQL para el funcionamiento del sistema.

De esta manera, el caso de uso permanece independiente de los detalles de persistencia.

## 6. Otros criterios de diseño complementarios

Además de SOLID, se tendrán en cuenta los siguientes criterios:

- **Bajo acoplamiento:** reducir las dependencias innecesarias entre módulos.
- **Alta cohesión:** mantener agrupados los componentes que cumplen una misma responsabilidad.
- **Simplicidad:** evitar complejidad técnica que no responda a una necesidad identificada.
- **Separación de responsabilidades:** mantener diferenciadas la presentación, las reglas del negocio y la infraestructura.

## 7. Beneficios esperados

La aplicación de estos principios permitirá:

- Facilitar el mantenimiento del sistema.
- Mejorar la comprensión y organización del código.
- Favorecer las pruebas independientes de los casos de uso.
- Reducir el impacto de los cambios tecnológicos.
- Evitar dependencias innecesarias entre módulos.
- Facilitar la incorporación de nuevas funcionalidades.
- Mantener una organización coherente entre los componentes del backend.

## 8. Consideraciones

Los principios SOLID orientarán las decisiones de diseño e implementación del backend del sistema de Mesa de Partes Virtual.

Su aplicación se realizará de acuerdo con las necesidades reales de cada módulo, evitando introducir interfaces, clases o componentes innecesarios. Los ejemplos y nombres de archivos descritos en este documento representan una propuesta de diseño y no implican que todas las implementaciones se encuentren desarrolladas actualmente.