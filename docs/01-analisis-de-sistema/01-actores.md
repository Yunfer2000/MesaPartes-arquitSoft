# Actores del sistema

| Actor | Descripción | Interacción principal |
|---|---|---|
| Administrado | Estudiante, egresado, docente o ciudadano que presenta un trámite. **No tiene cuenta.** | Presenta su trámite (CU05), subsana observaciones (CU06) y consulta el seguimiento (CU07) con su N.° de expediente y su documento de identidad. |
| Operador de Mesa de Partes | Personal de Mesa de Partes (Área Administrativa). | Recepciona (CU08), observa (CU09), deriva (CU10) y registra los documentos presenciales (CU17). |
| Responsable de área | Personal a cargo de un área del organigrama (Secretaría Académica, Unidad Académica, programas de estudio, etc.). | Confirma la recepción (CU11), registra avances (CU12), responde (CU13) y deriva a otra área (CU10). |
| Administrador | Encargado del sistema (distinto del operador de Mesa de Partes). | Gestiona usuarios y roles (CU03), áreas (CU04) y tipos de documento; revisa la auditoría. |
| Dirección General | Director o directora del instituto. | Consulta reportes (CU15) y el panel de plazos y pendientes (CU18). |
| Cualquier persona | Quien recibe una constancia o una respuesta impresa o reenviada. | Verifica la autenticidad del documento con el código o el QR (CU19). |
| Servicio de correo (secundario) | Servidor SMTP del instituto u otro proveedor. | Entrega las notificaciones al administrado (CU16). |
