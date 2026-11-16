# Restricciones

| ID | Restricción | Descripción |
|---|---|---|
| RC01 | Aplicación web | Portal público sin cuenta y portal del personal en un subdominio aparte. |
| RC02 | Acceso por roles | Mesa de Partes, Área, Administrador y Dirección General; cada área solo ve sus expedientes. |
| RC03 | Hosting institucional | Se publica en el hosting y dominio que paga la DREA y administra una empresa externa. Hosting compartido: PHP y PostgreSQL, sin servidores de colas ni Node.js. |
| RC04 | Tecnología | PHP con Laravel 13, vistas Blade generadas en el servidor y base de datos PostgreSQL. Sin paso de compilación con npm. |
| RC05 | Metodología | El análisis y diseño siguen ICONIX (curso de Proyección Social). |
| RC06 | Equipo y plazo | Cinco estudiantes, entrega el 20/12/2026. |
| RC07 | Protección de datos | Ley 29733: el autocompletado por documento solo devuelve el nombre; correo y celular se muestran ocultos. |
| RC08 | Alcance | Fuera del alcance: aplicación móvil nativa, firma digital con certificado (Firma Perú, Ley 27269), consulta a RENIEC, pagos en línea, integración con SIGAEST u otros sistemas del MINEDU. |
