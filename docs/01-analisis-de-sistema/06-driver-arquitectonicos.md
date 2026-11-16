# Drivers arquitectónicos

| ID | Driver | Origen | ¿Por qué influye en la arquitectura? |
|---|---|---|---|
| DA01 | Debe funcionar en un hosting compartido institucional, sin colas ni Node.js. | RC03 | Obliga a una sola aplicación PHP con vistas generadas en el servidor. |
| DA02 | La consulta responde en 3 s o menos. | AC01 | Consultas directas a la base de datos, caché de configuración y rutas en el despliegue. |
| DA03 | El personal y el público deben estar separados; el login no debe quedar expuesto. | AC04 / RNF17 | Rutas del personal en un subdominio y permisos por rol y por expediente. |
| DA04 | Trazabilidad completa de cada expediente. | AC05 / RF14 | Cada operación crea un movimiento dentro de una transacción. |
| DA05 | Dos operadores no deben recepcionar o derivar el mismo expediente a la vez. | AC08 / RNF07 | Bloqueo de fila y revisión del estado dentro de la transacción. |
| DA06 | Cualquiera debe poder comprobar la autenticidad de una constancia o respuesta. | RF26 | Servicio de verificación con código, QR y huella SHA-256, y una página pública. |
| DA07 | Equipo de 5 estudiantes que trabaja en paralelo por bloques. | RC06 | Código fácil de entender y cambios en archivos nuevos para evitar conflictos de Git. |
