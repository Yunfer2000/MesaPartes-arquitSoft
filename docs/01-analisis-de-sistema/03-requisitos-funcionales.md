# Requisitos funcionales

Tomados del análisis ICONIX del proyecto (Informe final, Tabla de requisitos funcionales), con los cambios acordados tras la reunión con el área de TI del instituto.

| ID | Requisito | Descripción |
|---|---|---|
| RF01 | Registro de usuarios | **Eliminado.** El administrado ya no crea cuenta. |
| RF02 | Inicio de sesión | Solo para el personal interno, en un subdominio aparte. |
| RF03 | Gestión de usuarios y roles | Administrar usuarios internos, roles y permisos. |
| RF04 | Presentación virtual | Formulario público mínimo y sin cuenta: documento de identidad, nombres, celular, correo obligatorio, asunto, un PDF o ZIP (máx. 10 MB) y código propio opcional. |
| RF05 | Registro de expediente | Tipo, folios y N.° de documento los completa Mesa de Partes al recepcionar o en el registro presencial. |
| RF06 | Carga de documentos | Un solo archivo en lo virtual; escaneo opcional en lo presencial. |
| RF07 | Número de expediente | Número automático `EXP-2026-000001` y código propio opcional. |
| RF08 | Recepción de expediente | Mesa de Partes confirma la recepción y registra la fecha. |
| RF09 | Observación | Registrar el motivo y pedir la subsanación. |
| RF10 | Derivación | A un área destino que atiende y, opcionalmente, copias para conocimiento a varias áreas. |
| RF11 | Recepción por área | El área destino confirma la recepción. |
| RF12 | Seguimiento | Consulta pública con N.° de expediente o código propio más el documento de identidad. |
| RF13 | Ubicación actual | Mostrar en qué área está el expediente. |
| RF14 | Historial | Mapa de recorrido con burbujas de colores. |
| RF15 | Gestión de estados | Presentado, Recibido, Observado, Subsanado, Derivado, En atención, Finalizado. |
| RF16 | Registro de respuesta | El área registra la respuesta y adjunta su documento. |
| RF17 | Notificaciones | Correo al presentar, recepcionar, observar, derivar y responder. |
| RF18 | Búsqueda | Por número, código propio, documento, fecha, área, tipo o estado. |
| RF19 | Archivo digital | Guardar los documentos de cada expediente en almacenamiento privado. |
| RF20 | Gestión de áreas | Áreas del organigrama con área padre. |
| RF21 | Reportes | Reportes de expedientes en PDF (impresión) y CSV. |
| RF22 | Subsanación | El administrado subsana desde la consulta pública. |
| RF23 | Registro presencial | Registrar documentos presentados en físico, que siguen el mismo flujo. |
| RF24 | Control de plazos | Semáforo por días hábiles: verde 0–3, ámbar 4–5, rojo 6 o más. |
| RF25 | Alertas de pendientes | Panel de pendientes para el Administrador y Dirección General. |
| RF26 | Firma electrónica del sistema | Código de verificación, QR y huella SHA-256 en constancias y respuestas; descarga en PDF. |
| RF27 | Corrección y devolución de derivación | Corregir el destino antes de que el área confirme o devolver a Mesa de Partes con motivo (en desarrollo). |

## Relación entre historias de usuario y requisitos funcionales

| Historia | Requisitos |
|---|---|
| HU02 | RF02 |
| HU03 | RF03 |
| HU04 | RF20 |
| HU05 | RF04, RF06, RF07, RF26 |
| HU06 | RF22 |
| HU07 | RF12, RF13, RF14 |
| HU08 | RF05, RF08 |
| HU09 | RF09 |
| HU10 | RF10, RF27 |
| HU11 | RF11 |
| HU12 | RF15 |
| HU13 | RF16, RF26 |
| HU14 | RF18 |
| HU15 | RF21 |
| HU16 | RF17 |
| HU17 | RF05, RF06, RF07, RF19, RF23, RF26 |
| HU18 | RF24, RF25 |
| HU19 | RF26 |
