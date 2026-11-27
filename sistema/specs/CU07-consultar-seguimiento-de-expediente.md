# CU07 Consultar seguimiento de expediente

| Campo | Valor |
|---|---|
| Actor | Administrado (sin cuenta) |
| Paquete | Administrado |
| Requisitos | RF12, RF13, RF14, RF26, RNF06, RNF15 |
| Figuras ICONIX | Robustez Fig 25, Secuencia Fig 43, Prototipo Fig 10 |
| Responsable | Bloque 3 |

## Objetivo

El ciudadano ve dónde está su trámite y todo lo que pasó con él, sin cuenta, con su número o
código y su documento de identidad.

## Flujo básico

1. El Administrado elige «Consultar trámite» en la portada pública.
2. El Administrado escribe el N.° de expediente o su código propio (o el código del sello, si fue presencial) y su documento de identidad.
3. El sistema busca el Expediente sin distinguir mayúsculas y comprueba que el documento sea del remitente.
4. El sistema muestra el detalle en una sola columna: el recorrido con burbujas numeradas, los datos del trámite, su código propio, los documentos, la observación pendiente, las subsanaciones realizadas y las respuestas.
5. El Administrado puede descargar su constancia y las respuestas en PDF con QR.
6. Termina el caso de uso.

## Flujos alternos

- **3a.** El número o código no existe, o el documento no es del remitente: el sistema muestra el mismo mensaje genérico para todos los casos y no abre nada.
- **4a.** El Expediente tiene una observación pendiente: el sistema muestra el aviso arriba y la burbuja roja (continúa en CU06).
- **4b.** El Expediente está finalizado: todas las burbujas están completas y no hay paso actual.

## Reglas de negocio

- **RN-CU07-01.** Sin consultar antes con número o código y documento, no se ve el detalle, la constancia, los archivos ni los PDF (RNF15).
- **RN-CU07-02.** La dirección de la consulta usa siempre el N.° de expediente; el código propio no sirve como dirección.
- **RN-CU07-03.** Una burbuja por cada movimiento real: verde = cumplido, azul = actual, rojo = observado, gris = respuesta pendiente. Las burbujas solo se ven en la consulta pública.
- **RN-CU07-04.** El recorrido aparece una sola vez (no hay historial repetido al costado).
- **RN-CU07-06.** Una persona jurídica consulta solo con el RUC de la entidad; el DNI de su representante no abre el expediente.
- **RN-CU07-05.** La línea «Tu código propio» aparece solo si existe; en presencial se llama «Código del sello».

## Criterios de aceptación

### CA-CU07-01 Exige número y documento correctos

- **Dado** un Expediente presentado
- **Cuando** alguien abre el detalle sin consultar, o consulta con otro DNI, o con el DNI correcto
- **Entonces** sin consultar va a la consulta, con otro DNI se rechaza, y con el correcto ve el asunto
- **Cubre:** pasos 2 a 4, flujo alterno 3a
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_consulta_exige_numero_y_documento_correctos`

### CA-CU07-02 Consulta por número también en minúsculas

- **Dado** un Expediente con su número
- **Cuando** el ciudadano lo escribe en minúsculas con su DNI
- **Entonces** el sistema abre el detalle
- **Cubre:** paso 3
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_b1_consulta_por_numero_sigue_funcionando`

### CA-CU07-03 Consulta por código propio

- **Dado** un trámite virtual con el código propio «ROSA2026»
- **Cuando** el ciudadano consulta con «rosa2026» y su DNI
- **Entonces** el sistema abre el detalle en la dirección del N.° de expediente
- **Cubre:** pasos 2 y 3, RN-CU07-02
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_b1_consulta_por_codigo_propio_virtual`

### CA-CU07-04 Consulta por código del sello

- **Dado** un expediente presencial con el código del sello «MP001»
- **Cuando** el ciudadano consulta con ese código y su DNI
- **Entonces** el sistema abre el detalle
- **Cubre:** paso 2
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_b1_consulta_por_codigo_del_sello_presencial`

### CA-CU07-05 Mensaje genérico si algo no coincide

- **Dado** el código «ROSA2026» de Rosa y otra persona con su propio trámite
- **Cuando** se consulta con el DNI de otro, un DNI inexistente, un código inexistente o el número con DNI ajeno
- **Entonces** siempre sale el mismo mensaje y nunca se abre el trámite de Rosa
- **Cubre:** flujo alterno 3a, RNF15
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_b1_codigo_con_dni_ajeno_o_inexistente_da_mensaje_generico`

### CA-CU07-06 Sin autorización no se abre nada

- **Dado** una persona que no consultó con código y documento
- **Cuando** abre el detalle, la constancia, un archivo o el código propio como dirección
- **Entonces** el sistema la manda a la consulta, y el código como dirección da 404
- **Cubre:** RN-CU07-01, RN-CU07-02
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_b1_acceso_directo_sin_autorizacion`

### CA-CU07-07 Muestra el código propio

- **Dado** un trámite con el código «ROSA2026»
- **Cuando** el ciudadano lo consulta correctamente
- **Entonces** el detalle muestra «Tu código propio: ROSA2026»
- **Cubre:** paso 4, RN-CU07-05
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_b2_detalle_muestra_codigo_propio`

### CA-CU07-08 Con DNI incorrecto no se expone el código

- **Dado** un trámite con código propio
- **Cuando** se consulta con un DNI incorrecto
- **Entonces** no se accede y ninguna página muestra el código ni el asunto
- **Cubre:** flujo alterno 3a, RNF06
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_b2_dni_incorrecto_no_expone_el_codigo_propio`

### CA-CU07-09 Código solo si existe, y «Código del sello» en presencial

- **Dado** un trámite sin código y uno presencial con sello
- **Cuando** se consultan
- **Entonces** el primero no muestra la línea del código y el presencial dice «Código del sello: MP001»
- **Cubre:** RN-CU07-05
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_b2_codigo_solo_si_existe_y_sello_en_presencial`

### CA-CU07-10 Las burbujas siguen los movimientos reales

- **Dado** un Expediente que se presenta, se observa, se subsana, se recibe, se deriva y se confirma
- **Cuando** el ciudadano lo consulta en cada momento
- **Entonces** ve una burbuja numerada por cada movimiento, la roja en la observación, la actual en azul y la respuesta pendiente al final
- **Cubre:** paso 4, flujo alterno 4a, RN-CU07-03
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_burbujas_siguen_los_movimientos_reales`

### CA-CU07-11 Finalizado y solo en la consulta pública

- **Dado** un Expediente finalizado
- **Cuando** el ciudadano lo consulta y el personal abre su ficha
- **Entonces** el ciudadano ve todas las burbujas completas sin paso actual, y el personal no ve burbujas
- **Cubre:** flujo alterno 4b, RN-CU07-03
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_burbujas_finalizado_y_solo_en_consulta_publica`

### CA-CU07-12 Un solo recorrido

- **Dado** un Expediente con varios movimientos
- **Cuando** el ciudadano lo consulta
- **Entonces** las burbujas aparecen una sola vez y no hay historial repetido
- **Cubre:** RN-CU07-04
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_recorrido_en_tarjeta_superior_sin_historial_repetido`

### CA-CU07-13 Conserva respuestas, documentos y constancia

- **Dado** un Expediente con respuesta
- **Cuando** el ciudadano lo consulta
- **Entonces** ve la respuesta con su documento y su constancia
- **Cubre:** paso 4
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_conserva_respuestas_documentos_y_constancia`

### CA-CU07-14 Descarga su constancia y sus respuestas en PDF

- **Dado** un ciudadano que consultó su trámite con su DNI
- **Cuando** pulsa «Descargar PDF con QR» o «Descargar respuesta en PDF»
- **Entonces** recibe el PDF; sin haber consultado, el sistema lo manda a la consulta
- **Cubre:** paso 5, RN-CU07-01
- **Prueba:** `tests/Feature/PdfFirmaTest.php::test_el_administrado_descarga_su_constancia_en_pdf`
- **Prueba:** `tests/Feature/PdfFirmaTest.php::test_sin_consultar_con_dni_no_descarga_el_pdf`
- **Prueba:** `tests/Feature/PdfFirmaTest.php::test_respuesta_en_pdf_para_el_area_y_el_administrado`

### CA-CU07-15 Persona jurídica consulta con el RUC

- **Dado** un trámite presentado por una persona jurídica
- **Cuando** consulta con el N.° de expediente y el DNI del representante, y luego con el RUC
- **Entonces** con el DNI recibe el mensaje genérico y no ve nada; con el RUC abre el detalle
- **Cubre:** pasos 2 y 3, RN-CU07-06
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_persona_juridica_consulta_solo_con_ruc`
