# Decisiones arquitectónicas

## DA-01: Monolito en capas

**Contexto:** hosting compartido, equipo pequeño y un solo dominio de negocio (trámite documentario).

**Decisión:** una sola aplicación Laravel, organizada en capas: presentación, control, servicios y datos.

**Drivers:** DA01, DA07.

**Justificación:** se instala y mantiene como un solo sistema en el hosting de la DREA; no necesita orquestar servicios ni una API aparte.

**Consecuencias:** todo se despliega junto. La organización es por tipo de archivo (controladores, servicios, modelos), no por módulos.

## DA-02: Patrón MVC con capa de servicios

**Contexto:** las reglas del trámite (estados, plazos, quién puede operar) no deben quedar repartidas en las pantallas.

**Decisión:** controladores delgados que validan y llaman a una **capa de servicios**. `FlujoExpediente` tiene un método por caso de uso (presentar, recepcionar, observar, derivar, confirmarRecepcion, responder, registrarPresencial, subsanar).

**Drivers:** DA04, DA05, DA07.

**Justificación:** la lógica de negocio queda en un solo lugar, se prueba con PHPUnit y corresponde uno a uno con los casos de uso de ICONIX.

**Consecuencias:** los servicios usan los modelos Eloquent directamente. Se evaluó Clean Architecture (dominio independiente con repositorios e interfaces) y se dejó como mejora futura, porque para este tamaño añade capas sin un beneficio visible.

## DA-03: Control de acceso por rol, por expediente y por subdominio

**Decisión:** middleware `VerificarRol` para las secciones de cada rol, `ExpedientePolicy` para decidir si un usuario ve u opera un expediente, y rutas del personal solo en el subdominio (`APP_STAFF_DOMAIN`). El público entra a su expediente con N.° y documento de identidad, guardado en la sesión.

**Drivers:** DA03.

## DA-04: Trazabilidad e integridad con transacciones

**Decisión:** cada operación de `FlujoExpediente` se ejecuta en una transacción, bloquea la fila del expediente (`lockForUpdate`), revisa el estado y registra un movimiento. Las acciones del Administrador quedan en `registros_auditoria`.

**Drivers:** DA04, DA05.

## DA-05: Documentos en almacenamiento privado con huella

**Decisión:** los archivos se guardan fuera de la carpeta pública y solo se descargan a través de un controlador que revisa permisos. Al subirlos se calcula su huella SHA-256.

**Drivers:** DA03, DA06.

## DA-06: Firma electrónica del sistema

**Decisión:** servicio `Verificacion` que asigna un código único a cada constancia y respuesta, dibuja el QR y permite comprobar el documento en `/verificar`. Constancias y respuestas se descargan en PDF con DomPDF.

**Drivers:** DA06.

**Consecuencias:** no reemplaza a la firma digital con certificado (Ley 27269), que queda como fase siguiente.

## DA-07: Vistas generadas en el servidor

**Decisión:** vistas Blade con CSS propio, sin framework de JavaScript ni compilación con npm.

**Drivers:** DA01, DA02, DA07.

**Justificación:** funciona en un hosting compartido, carga rápido en celulares y es más simple para el equipo.

## DA-08: Base de datos PostgreSQL

**Decisión:** PostgreSQL como base de datos de producción; SQLite en memoria para las pruebas automáticas. El acceso es solo mediante Eloquent y migraciones de Laravel, sin SQL propio del motor.

**Drivers:** DA01.
