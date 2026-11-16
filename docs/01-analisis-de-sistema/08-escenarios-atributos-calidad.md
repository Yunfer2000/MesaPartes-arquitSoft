# Escenarios de atributos de calidad

## EQ01. Rendimiento
- **Fuente:** administrado. **Estímulo:** consulta su expediente con N.° y DNI.
- **Entorno:** operación normal en el hosting.
- **Respuesta:** muestra estado, área actual y mapa de recorrido.
- **Medida:** 3 s o menos (RNF04).

## EQ02. Seguridad
- **Fuente:** responsable de un área que no participó en el trámite. **Estímulo:** abre la URL de un expediente o de su respuesta en PDF.
- **Respuesta:** `ExpedientePolicy` deniega el acceso (403).
- **Medida:** ninguna petición no autorizada obtiene datos. Lo comprueban las pruebas automáticas (por ejemplo, "otra área no descarga la respuesta").

## EQ03. Disponibilidad
- **Fuente:** administrado, de noche o en fin de semana. **Estímulo:** presenta un trámite.
- **Respuesta:** se registra y llega el correo con el número.
- **Medida:** objetivo de 99,5 % mensual, sujeto al hosting de la DREA.

## EQ04. Trazabilidad
- **Fuente:** operador de Mesa de Partes. **Estímulo:** deriva un expediente con copias.
- **Respuesta:** se crea el movimiento y las copias, y el mapa de recorrido se actualiza.
- **Medida:** el 100 % de las derivaciones tiene su movimiento.

## EQ05. Integridad ante acciones simultáneas
- **Fuente:** dos operadores. **Estímulo:** recepcionan el mismo expediente a la vez.
- **Respuesta:** el primero lo recepciona; el segundo recibe el aviso "otro operador ya lo recepcionó".
- **Medida:** nunca hay dos recepciones del mismo expediente.

## EQ06. Autenticidad de documentos
- **Fuente:** un tercero. **Estímulo:** escanea el QR de una constancia y sube el PDF.
- **Respuesta:** muestra "Documento auténtico" y si la huella coincide.
- **Medida:** un archivo cambiado en un solo byte da "No coincide".

## EQ07. Usabilidad
- **Fuente:** estudiante sin experiencia. **Estímulo:** presenta un trámite desde el celular.
- **Respuesta:** formulario mínimo con revisión de datos antes de enviar.
- **Medida:** lo completa sin ayuda.
