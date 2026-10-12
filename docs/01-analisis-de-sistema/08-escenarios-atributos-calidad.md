# Escenarios de atributos de calidad

## 1. Objetivo

Definir escenarios que permitan evaluar los atributos de calidad del sistema de Mesa de Partes Virtual del IESTP Manuel Antonio Hierro Pozo – Ayacucho.

## 2. Escenarios de calidad

### EQ01. Rendimiento

- **Atributo:** Rendimiento.
- **Fuente:** Usuario externo.
- **Estímulo:** El usuario consulta el estado de un expediente.
- **Entorno:** Operación normal del sistema.
- **Respuesta esperada:** El sistema consulta y presenta el estado del expediente.
- **Medida de respuesta:** La consulta debe completarse en un máximo de 3 segundos en condiciones normales.

### EQ02. Seguridad

- **Atributo:** Seguridad.
- **Fuente:** Usuario sin autorización.
- **Estímulo:** Intenta acceder a un expediente que no tiene permiso para consultar.
- **Entorno:** Sistema en funcionamiento.
- **Respuesta esperada:** El sistema deniega el acceso y registra el intento cuando corresponda.
- **Medida de respuesta:** Ninguna solicitud no autorizada debe obtener información protegida.

### EQ03. Disponibilidad

- **Atributo:** Disponibilidad.
- **Fuente:** Usuario institucional.
- **Estímulo:** Intenta acceder al sistema durante el horario de servicio.
- **Entorno:** Operación normal, sin mantenimiento programado.
- **Respuesta esperada:** El sistema permite el acceso y la realización de operaciones autorizadas.
- **Medida de respuesta:** Disponibilidad objetivo de 99,5 % mensual, pendiente de validación institucional.

### EQ04. Trazabilidad

- **Atributo:** Trazabilidad.
- **Fuente:** Personal de Mesa de Partes.
- **Estímulo:** Deriva un expediente a otra área.
- **Entorno:** Atención normal de trámites.
- **Respuesta esperada:** El sistema actualiza la información y registra el evento correspondiente.
- **Medida de respuesta:** El 100 % de las derivaciones confirmadas debe contar con un registro de historial.

### EQ05. Usabilidad

- **Atributo:** Usabilidad.
- **Fuente:** Usuario externo.
- **Estímulo:** Registra una nueva solicitud.
- **Entorno:** Uso del portal web.
- **Respuesta esperada:** El sistema presenta formularios comprensibles y mensajes de validación claros.
- **Medida de respuesta:** Un usuario de prueba debe poder completar el registro siguiendo las instrucciones de la interfaz.

## 3. Consideraciones

Las medidas indicadas son objetivos iniciales propuestos para orientar el diseño y las pruebas. Deberán validarse con la institución antes de convertirse en criterios definitivos de aceptación.