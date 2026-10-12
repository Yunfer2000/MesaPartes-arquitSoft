# Principios de diseño

## 1. Objetivo

Establecer los principios que orientarán el diseño interno del sistema de Mesa de Partes Virtual del IESTP Manuel Antonio Hierro Pozo – Ayacucho.

## 2. Principios propuestos

### 2.1. Responsabilidad única (SRP)

Cada módulo o componente debe tener una responsabilidad principal claramente definida.

**Aplicación:** Separar la gestión de expedientes, documentos, usuarios y notificaciones.

### 2.2. Bajo acoplamiento

Los componentes deben depender lo menos posible de las implementaciones concretas de otros componentes.

**Aplicación:** Utilizar contratos para acceder a repositorios y servicios externos.

### 2.3. Alta cohesión

Los elementos relacionados con una misma responsabilidad deben mantenerse agrupados.

**Aplicación:** Mantener las operaciones de derivación dentro del módulo correspondiente.

### 2.4. Inversión de dependencias (DIP)

Las capas internas deben depender de abstracciones y no de detalles concretos de infraestructura.

**Aplicación:** Los casos de uso utilizarán contratos para acceder a la persistencia y a los servicios externos.

### 2.5. Separación de responsabilidades

La presentación, las reglas del negocio y los mecanismos técnicos deben permanecer diferenciados.

**Aplicación:** Organizar el sistema según las capas propuestas por Clean Architecture.

### 2.6. Simplicidad

La solución debe evitar complejidad que no responda a una necesidad identificada.

**Aplicación:** Mantener el monolito modular durante la etapa inicial, sin introducir servicios independientes innecesarios.

## 3. Beneficios esperados

- Facilitar el mantenimiento del sistema.
- Mejorar la comprensión del código.
- Favorecer las pruebas de los casos de uso.
- Reducir el impacto de los cambios tecnológicos.
- Mantener una organización coherente entre módulos.

## 4. Consideraciones

Estos principios orientarán las decisiones de implementación y deberán aplicarse de acuerdo con las necesidades reales del sistema.