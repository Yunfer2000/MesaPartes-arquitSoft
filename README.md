# Mesa de Partes Virtual

## 1. Información del proyecto

**Estudiante:** Yunfer Lifoncio Torres

**Curso:** Arquitectura de Software

**Institución:** Instituto de Educación Superior Tecnológico Público Manuel Antonio Hierro Pozo – Ayacucho.

## 2. Descripción del proyecto

Documentación de la arquitectura y código fuente del sistema **Mesa de Partes Virtual**, ya construido y en funcionamiento, que permite presentar trámites sin cuenta, recepcionarlos, derivarlos entre áreas, atenderlos, seguirlos con un mapa de recorrido y verificar la autenticidad de constancias y respuestas con código y QR.

**Resumen de la arquitectura**

| Aspecto | Decisión |
|---|---|
| Enfoque | Orientado a objetos, dirigido por casos de uso (ICONIX), con especificaciones por caso de uso (SDD) |
| Estilo arquitectónico | Monolito en capas |
| Patrones | MVC + capa de servicios (Service Layer), Policy, Active Record |
| Tecnología | PHP 8.4+, Laravel 13, vistas Blade, API REST v1 (JSON), MySQL 8 |
| Despliegue | Hosting institucional; portal público en el dominio y personal en un subdominio |

## 3. Documentación del proyecto por sprint

| Sprint | Contenido | Estado |
|---|---|---|
| Sprint 1 | Análisis del sistema, arquitectura de software, diseño y modelo C4 | Terminado |
| Sprint 2 | Implementación de la arquitectura, API REST con OpenAPI, pruebas automáticas y validación de atributos de calidad | Terminado |
| Sprint 3 | Despliegue del sistema | Pendiente |

## Sprint 1: Análisis y arquitectura

### Análisis del sistema
- [Caso de negocio](docs/caso-de-negocio.md)
- [Actores](docs/01-analisis-de-sistema/01-actores.md)
- [Historias de usuario](docs/01-analisis-de-sistema/02-historias-del-usuario.md)
- [Requisitos funcionales](docs/01-analisis-de-sistema/03-requisitos-funcionales.md)
- [Atributos de calidad](docs/01-analisis-de-sistema/04-atributos-de-calidad.md)
- [Restricciones](docs/01-analisis-de-sistema/05-restricciones.md)
- [Drivers arquitectónicos](docs/01-analisis-de-sistema/06-driver-arquitectonicos.md)
- [Decisiones arquitectónicas](docs/01-analisis-de-sistema/07-decisiones-arquitectonicas.md)
- [Escenarios de atributos de calidad](docs/01-analisis-de-sistema/08-escenarios-atributos-calidad.md)

### Arquitectura de software
- [Arquitectura del sistema](docs/02-arquitectura-software/arquitectura-inicial.md)
- [Estilo arquitectónico: monolito en capas](docs/02-arquitectura-software/estilo-arquitectonico.md)
- [Enfoque arquitectónico](docs/02-arquitectura-software/enfoque-arquitectonico.md)
- [Componentes arquitectónicos](docs/02-arquitectura-software/componentes-arquitectonicos.md)

### Diseño de software
- [Diseño interno](docs/03-diseno-de-software/diseno-interno/diseno-interno-de-modulos.md)
- [Patrones de diseño](docs/03-diseno-de-software/diseno-interno/patrones-de-diseno.md)
- [Principios de diseño](docs/03-diseno-de-software/diseno-interno/principios-de-diseno.md)

### Modelo C4
- [Nivel 1: Contexto del sistema](docs/04-modelo-c4/Nivel1-Diagrama-de-Contexto-del-Sistema.md)
- [Nivel 2: Contenedores](docs/04-modelo-c4/Nivel2-Diagrama-de-Contenedores.md)
- [Nivel 3: Componentes](docs/04-modelo-c4/Nivel3-Diagrama-de-Componentes.md)
- [Nivel 4: Código](docs/04-modelo-c4/Nivel4-Diagrama-de-Codigo.md)

### Tecnología
- [Estructura del código](tecnologia/README.md)

## Sprint 2: Implementación y validación de la arquitectura

- [Índice del Sprint 2 y entregables](docs/05-sprint-02-implementacion/README.md)
- [Estilo y patrón implementados](docs/05-sprint-02-implementacion/01-estilo-y-patron-implementados.md)
- [API REST v1 y OpenAPI](docs/05-sprint-02-implementacion/02-api-rest-openapi.md)
- [Especificaciones SDD](docs/05-sprint-02-implementacion/03-especificaciones-sdd.md)
- [Base de datos y caché](docs/05-sprint-02-implementacion/04-base-de-datos-y-cache.md)
- [SOLID y patrones de diseño](docs/05-sprint-02-implementacion/05-solid-y-patrones.md)
- [Pruebas automáticas e integración continua](docs/05-sprint-02-implementacion/06-pruebas-automaticas-y-ci.md)
- [Validación de atributos de calidad](docs/05-sprint-02-implementacion/07-validacion-atributos-calidad.md)

| Carpeta | Contenido |
|---|---|
| [`sistema/`](sistema) | Código fuente completo del sistema (Laravel 13) |
| [`sistema/public/docs/openapi.yaml`](sistema/public/docs/openapi.yaml) | Contrato OpenAPI 3 de la API. Swagger UI en `/api-docs` |
| [`sistema/specs/`](sistema/specs) | Especificaciones SDD por caso de uso y por endpoint |
| [`sistema/tests/`](sistema/tests) | 165 pruebas automáticas (PHPUnit) |
| [`pruebas/postman/`](pruebas/postman) | Colección de Postman |
| [`pruebas/k6/`](pruebas/k6) | Prueba de carga hasta 2,000 usuarios virtuales |
| [`.github/workflows/ci.yml`](.github/workflows/ci.yml) | Integración continua: pruebas, cobertura, MySQL, OpenAPI, Postman y k6 |

## 4. Estado del proyecto

El sistema está construido, tiene una API REST v1 documentada con OpenAPI y 165 pruebas automáticas que corren en GitHub Actions en cada cambio. Las mejoras futuras evaluadas son: organización en monolito modular, Clean Architecture y firma digital con certificado (Firma Perú).
