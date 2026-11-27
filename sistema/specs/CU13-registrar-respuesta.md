# CU13 Registrar respuesta

| Campo | Valor |
|---|---|
| Actor | Responsable de área |
| Paquete | Área |
| Requisitos | RF16, RF17, RF19, RF26, RNF18 |
| Figuras ICONIX | Robustez Fig 31, Secuencia Fig 49, Prototipo Fig 15 |
| Responsable | Bloque 4 |

## Objetivo

El área entrega la respuesta del trámite (constancia, oficio, resolución) y lo finaliza. La
respuesta lleva firma electrónica del sistema.

## Flujo básico

1. El Responsable pulsa «Registrar respuesta» en la pantalla Atención del expediente.
2. El sistema muestra el formulario Respuesta.
3. El Responsable escribe la respuesta, opcionalmente adjunta un documento (constancia, oficio o resolución) y pulsa «Finalizar».
4. El sistema guarda la Respuesta con su código de verificación y «Firmado por» (nombre y área), guarda el DocumentoAdjunto con su huella SHA-256 si se adjuntó, registra un Movimiento (acción «finalizado») y cambia el estado a Finalizado.
5. El sistema ejecuta CU16 para avisar al Administrado que su trámite tiene respuesta.
6. El área y el Administrado pueden descargar la respuesta en PDF con QR.
7. Termina el caso de uso.

## Flujos alternos

- **3a.** Falta el texto de la respuesta: el sistema lo pide y vuelve al paso 3.
- **6a.** Un usuario de otra área intenta descargar la respuesta: el sistema se lo niega.

## Reglas de negocio

- **RN-CU13-01.** El texto de la respuesta es obligatorio; el documento adjunto es opcional, porque el sistema genera el PDF de la respuesta con su código de verificación.
- **RN-CU13-02.** Cada respuesta tiene un código de verificación distinto al de la constancia (RF26).
- **RN-CU13-03.** El PDF de la respuesta solo lo descargan quienes pueden ver el Expediente y el Administrado que lo consultó con su documento.

## Criterios de aceptación

### CA-CU13-01 Responde con documento y finaliza

- **Dado** un Expediente En atención en Secretaría Académica
- **Cuando** el Responsable responde sin texto y luego con texto y un PDF
- **Entonces** sin texto se marca el campo; con texto y PDF el Expediente queda Finalizado con 1 respuesta, se notifica y el ciudadano la ve en su consulta
- **Cubre:** pasos 3 a 5, flujo alterno 3a, RN-CU13-01
- **Prueba:** `tests/Feature/FlujoMesaAreaTest.php::test_recorrido_completo_hasta_finalizar`

### CA-CU13-02 La respuesta lleva código y huella

- **Dado** una respuesta registrada con un PDF
- **Cuando** se revisan sus datos
- **Entonces** tiene un código de verificación distinto al de la constancia y el archivo guarda su huella SHA-256
- **Cubre:** paso 4, RN-CU13-02
- **Prueba:** `tests/Feature/VerificacionTest.php::test_constancias_respuestas_y_archivos_llevan_codigo_y_huella`

### CA-CU13-03 Descarga la respuesta en PDF

- **Dado** un Expediente respondido
- **Cuando** el área o el ciudadano (tras consultar) pulsan «Descargar respuesta en PDF»
- **Entonces** ambos reciben el PDF de la respuesta
- **Cubre:** paso 6, RN-CU13-03
- **Prueba:** `tests/Feature/PdfFirmaTest.php::test_respuesta_en_pdf_para_el_area_y_el_administrado`

### CA-CU13-04 Otra área no descarga la respuesta

- **Dado** un Expediente respondido por Secretaría Académica
- **Cuando** un usuario de otra área pide el PDF de la respuesta
- **Entonces** el sistema le niega el acceso
- **Cubre:** flujo alterno 6a, RN-CU13-03
- **Prueba:** `tests/Feature/PdfFirmaTest.php::test_otra_area_no_descarga_la_respuesta`

### CA-CU13-05 Finaliza sin documento de respuesta

- **Dado** un Expediente En atención en Secretaría Académica
- **Cuando** el Responsable escribe «Atendido» y pulsa «Finalizar» sin adjuntar nada
- **Entonces** el formulario dice «Documento de respuesta (opcional)», el Expediente queda Finalizado con una respuesta sin archivos y el PDF de la respuesta se descarga igual
- **Cubre:** pasos 3 y 4, RN-CU13-01
- **Prueba:** `tests/Feature/MejorasSistemaTest.php::test_finaliza_sin_documento_de_respuesta`
