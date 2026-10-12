# Diseño interno de módulos

## 1. Objetivo

Describir la organización interna de los módulos del sistema de Mesa de Partes Virtual del IESTP Manuel Antonio Hierro Pozo – Ayacucho, siguiendo el enfoque de Clean Architecture.

## 2. Organización por capas

### 2.1. Dominio

Contiene las entidades y reglas del negocio.

Ejemplos:
- Usuario.
- Trámite.
- Expediente.
- Documento.
- Área responsable.

### 2.2. Aplicación

Contiene los casos de uso que coordinan las operaciones del sistema.

Ejemplos:
- Registrar solicitud.
- Registrar expediente.
- Derivar expediente.
- Consultar estado.
- Generar reporte.

### 2.3. Infraestructura

Contiene las implementaciones técnicas para persistencia, almacenamiento documental y comunicación con servicios externos.

### 2.4. Presentación

Contiene las interfaces y mecanismos de interacción con los usuarios.

## 3. Organización funcional

| Módulo | Funciones principales |
|---|---|
| Usuarios y seguridad | Autenticación, roles y permisos. |
| Trámites | Registro y consulta de solicitudes. |
| Expedientes | Creación, seguimiento y control de estados. |
| Documentos | Gestión de archivos y metadatos. |
| Derivaciones | Asignación a áreas y responsables. |
| Notificaciones | Comunicación de cambios relevantes. |
| Reportes | Consultas e informes de gestión. |
| Auditoría | Historial de acciones relevantes. |

## 4. Flujo de un caso de uso

Ejemplo: derivar un expediente.

1. El usuario autorizado solicita la derivación desde la interfaz.
2. La presentación envía la operación al caso de uso correspondiente.
3. La aplicación verifica las condiciones del trámite y las autorizaciones necesarias.
4. El dominio aplica las reglas del negocio.
5. La infraestructura registra los cambios y el historial.
6. El sistema devuelve el resultado y comunica el cambio cuando corresponda.

## 5. Criterios de diseño

- Mantener una responsabilidad clara por módulo.
- Evitar dependencias innecesarias entre módulos.
- Mantener las reglas del negocio independientes de la tecnología.
- Utilizar contratos para acceder a mecanismos externos.
- Registrar las operaciones relevantes para asegurar la trazabilidad.

## 6. Consideraciones

Esta organización representa una propuesta de diseño inicial. La implementación concreta de clases, interfaces y componentes se definirá durante el desarrollo del sistema.