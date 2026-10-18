# Mesa de Partes Virtual — Arquitectura Limpia con Angular

Curso: **IS-488 Arquitectura de Software** · UNSCH · Semestre 2026-II

Proyecto: Mesa de Partes Virtual del Instituto de Educación Superior Tecnológico Público Manuel Antonio Hierro Pozo – Ayacucho.

Proyecto de aplicación web para la gestión de trámites y expedientes institucionales. La organización propuesta sigue el enfoque de Clean Architecture, separando las reglas del negocio, los casos de uso, los adaptadores tecnológicos y la presentación.

---

## Puesta en marcha

### Requisitos previos

- Node.js y npm.
- Visual Studio Code (recomendado).

### Instalación

Desde esta carpeta, instala las dependencias:

```bash
npm install
```

### Ejecución en desarrollo

```bash
npm start
```

La aplicación estará disponible normalmente en `http://localhost:4200/`.

### Compilación

Para comprobar que la aplicación compila correctamente, ejecuta:

```bash
npm run build
```

### Pruebas del dominio

Para ejecutar las pruebas actuales del dominio, utiliza:

```bash
npm run pruebas
```

Estas pruebas verifican reglas básicas de las entidades `Solicitud` y `Expediente`.

---

## Las cuatro capas

La estructura propuesta para organizar el código es:

```text
src/app/
├── dominio/              ← Entidades y reglas del negocio.
│   ├── modelos/          ← Trámites, expedientes y documentos.
│   └── contratos/        ← Interfaces para acceso a datos y servicios.
│
├── aplicacion/           ← Casos de uso del sistema.
│
├── infraestructura/      ← Adaptadores para almacenamiento y servicios externos.
│
├── presentacion/         ← Componentes e interfaces de usuario Angular.
│
├── app.config.ts         ← Configuración y composición de dependencias.
└── app.component.ts      ← Componente raíz de la aplicación.
```

Esta estructura es una propuesta inicial. Los archivos y módulos definitivos se definirán conforme avance la implementación.

---

## Principios de diseño

### 1. El dominio no depende de Angular

Las entidades y reglas del negocio deben mantenerse independientes de la interfaz y de los detalles tecnológicos.

### 2. Los contratos separan las reglas del negocio de la infraestructura

Las interfaces permiten definir cómo se accede a los datos o a los servicios sin depender de una implementación concreta.

### 3. La infraestructura contiene los adaptadores

Los adaptadores conectan las capas internas con los mecanismos concretos de almacenamiento y otros servicios.

### 4. La configuración organiza las dependencias

La configuración de la aplicación permite establecer qué implementaciones utiliza cada contrato, manteniendo las dependencias organizadas.

---

## Alcance funcional de referencia

La documentación del sistema contempla los siguientes procesos:

- Registro y consulta de solicitudes.
- Gestión y seguimiento de expedientes.
- Derivación y asignación de expedientes.
- Gestión de documentos digitales.
- Notificaciones relacionadas con los trámites.
- Reportes de gestión.

Estos elementos describen el alcance funcional propuesto; no implican que ya estén implementados en la aplicación Angular.

---

## Relación con la documentación de arquitectura

La documentación general del proyecto se encuentra en la carpeta `docs/` del repositorio principal. Allí se describen el análisis del sistema, las decisiones arquitectónicas, el diseño de software y los diagramas C4.

---

## Consideración final

La arquitectura busca mantener separadas las reglas del negocio y las tecnologías utilizadas. Así, los cambios en la interfaz o en los mecanismos de almacenamiento podrán evaluarse sin introducir dependencias innecesarias en el dominio.