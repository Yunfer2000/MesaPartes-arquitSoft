# CU05 Presentar trámite virtual

| Campo | Valor |
|---|---|
| Actor | Administrado (sin cuenta) |
| Paquete | Administrado |
| Requisitos | RF04, RF05, RF06, RF07, RF17, RF19, RF26, RNF05, RNF06 |
| Figuras ICONIX | Robustez Fig 23, Secuencia Fig 41, Prototipo Fig 11 |
| Responsable | Bloque 1 |

## Objetivo

Cualquier ciudadano presenta su documento por internet, sin crear una cuenta, y recibe el número
de expediente para hacer el seguimiento.

## Flujo básico

1. El Administrado elige «Presentar trámite» en la portada pública.
2. El sistema muestra primero la ventana «Términos y condiciones» centrada sobre el formulario; el Administrado marca «He leído y acepto» y pulsa «Aceptar y continuar» (issue #20). Luego ve el formulario mínimo: primero el tipo de persona (natural o jurídica), luego los datos del remitente, asunto, tipo de documento (opcional) y un archivo.
3. Persona natural: escribe su DNI (por defecto) o carné de extranjería, apellidos y nombres; si ya presentó antes, el sistema completa su nombre (el correo y el celular salen ocultos). Persona jurídica: escribe el RUC y la razón social de la entidad (si ya está registrada, el sistema completa la razón social) y los datos de su representante: su DNI en el mismo campo «N.° de documento», apellidos y nombres.
4. El Administrado completa celular, correo y asunto, adjunta un PDF o ZIP, y resuelve la suma de verificación. La aceptación de los términos ya quedó marcada en el paso 2 y puede volver a leerlos con «Ver términos y condiciones».
5. El sistema muestra la ventana «Revisa tus datos antes de enviar», centrada en la pantalla (issue #21); el Administrado pulsa «Confirmar y enviar».
6. El sistema valida los datos y el archivo.
7. El sistema crea o actualiza al Administrado (sin cuenta; en persona jurídica, la entidad con su RUC y razón social), guarda en el Expediente los datos del representante si es persona jurídica, genera el número de Expediente y su código de verificación, guarda el archivo con su huella SHA-256 y registra la fecha y hora de presentación.
8. El sistema registra el primer Movimiento (acción «presentado»), deja el Expediente en estado Presentado y dirigido a Mesa de Partes.
9. El sistema ejecuta CU16 (evento «presentación») y envía el número por correo.
10. El sistema muestra la Constancia de presentación con el número, la fecha y hora, el QR y el archivo enviado.
11. Termina el caso de uso.

## Flujos alternos

- **2a.** El Administrado pulsa «No acepto»: el sistema vuelve a la portada y no muestra el formulario.
- **6a.** Faltan datos, el correo o el archivo: el sistema marca los campos y vuelve al paso 4.
- **6b.** El archivo no es PDF ni ZIP, o pesa demasiado: el sistema lo rechaza y vuelve al paso 4.
- **6c.** Falta el tipo de persona, o en persona jurídica falta el RUC, la razón social o algún dato del representante: el sistema marca el campo y vuelve al paso 3.
- **6d.** La suma de verificación (o la verificación de Cloudflare Turnstile) no es correcta, o el documento, el RUC o el celular no tienen el formato: el sistema marca el campo y vuelve al paso 4.
- **6e.** El mismo documento de identidad ya presentó 5 trámites hoy: el sistema no acepta más hasta el día siguiente.

## Reglas de negocio

- **RN-CU05-01.** No se crea cuenta de acceso para el Administrado.
- **RN-CU05-02.** Tipo de documento y folios no los pone el ciudadano (el tipo es opcional); Mesa de Partes los completa en CU08.
- **RN-CU05-03.** Persona natural: DNI de exactamente 8 dígitos o carné de extranjería (6 a 12 letras o números); el pasaporte no se acepta. Persona jurídica: RUC de 11 dígitos que empieza con 10, 15, 17 o 20, razón social, y representante con DNI de 8 dígitos. En ambos casos, apellido paterno, apellido materno (opcional solo con carné de extranjería), nombres, celular de 9 dígitos que empieza en 9 y correo.
- **RN-CU05-04.** Un solo archivo PDF o ZIP.
- **RN-CU05-05.** El formulario público ya no pide código propio; si llega, se ignora. La columna se conserva: los expedientes que ya lo tienen se siguen consultando con él (CU07) y el registro presencial guarda ahí el código del sello (CU17).
- **RN-CU05-06.** Máximo 5 trámites por documento de identidad al día (RNF05).
- **RN-CU05-07.** El autocompletado por documento solo muestra el nombre; un tercero no puede cambiar el correo ni el celular de otra persona (RNF06, Ley 29733).
- **RN-CU05-09.** La entidad (RUC y razón social) se guarda en el Administrado y no cambia si ya está registrada. El representante (DNI, apellidos, nombres, celular y correo) se guarda en cada Expediente, porque puede ser distinto en cada trámite; los avisos de ese Expediente van a su correo (CU16).
- **RN-CU05-10.** Sin aceptar los términos y condiciones (declaración de veracidad y tratamiento de datos personales, Ley N.° 29733) no se puede enviar el trámite: la ventana del inicio no se cierra con Esc ni con clic fuera, y el servidor rechaza el envío sin la aceptación. Si el envío vuelve con errores, la aceptación se conserva y la ventana no aparece otra vez.
- **RN-CU05-08.** Freno contra envíos automáticos: con `TURNSTILE_SITE_KEY` y `TURNSTILE_SECRET_KEY` se usa Cloudflare Turnstile, verificado en el servidor; sin claves, la pregunta de suma. Si Cloudflare rechaza el token, falta o no responde, no se crea el Expediente (RNF05, issue #22).

## Criterios de aceptación

### CA-CU05-01 Presenta sin cuenta

- **Dado** un ciudadano que nunca presentó un trámite
- **Cuando** completa el formulario mínimo con un PDF y envía
- **Entonces** el Expediente queda Presentado, dirigido a Mesa de Partes, sin tipo ni folios, con 1 archivo; el Administrado se crea sin cuenta y ve su constancia y su seguimiento
- **Cubre:** pasos 6 a 8 y 10, RN-CU05-01, RN-CU05-02
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_presenta_sin_cuenta_y_crea_al_remitente`

### CA-CU05-02 Portada y formulario públicos

- **Dado** un visitante sin sesión
- **Cuando** abre la portada y el formulario
- **Entonces** ve «Presentar trámite» y «Consultar trámite», y el formulario pide el tipo de persona y los datos del remitente, pero no el código propio ni los folios
- **Cubre:** pasos 1 y 2
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_portal_publico_para_invitados`

### CA-CU05-03 Puede indicar el tipo de documento y revisa antes de enviar

- **Dado** el formulario público
- **Cuando** el ciudadano elige el tipo «Solicitud»
- **Entonces** el formulario muestra «¿Qué vas a presentar?», la ventana «Revisa tus datos antes de enviar» y ya no las opciones adicionales; el tipo se guarda y aparece elegido cuando Mesa de Partes lo revisa
- **Cubre:** pasos 2 y 5, RN-CU05-02
- **Prueba:** `tests/Feature/InterfazBandejasFormularioTest.php::test_el_ciudadano_puede_indicar_el_tipo_de_documento`

### CA-CU05-04 Tipo de documento inválido

- **Dado** el formulario público
- **Cuando** llega un tipo de documento que no existe
- **Entonces** el sistema lo rechaza y no crea el Expediente
- **Cubre:** flujo alterno 6a
- **Prueba:** `tests/Feature/InterfazBandejasFormularioTest.php::test_tipo_de_documento_invalido_se_rechaza`

### CA-CU05-05 Exige correo y un PDF o ZIP

- **Dado** el formulario público
- **Cuando** el ciudadano envía sin correo, sin archivo o con una foto JPG
- **Entonces** el sistema marca el campo y no crea el Expediente; con un PDF sí lo crea
- **Cubre:** flujos alternos 6a y 6b, RN-CU05-03, RN-CU05-04
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_formulario_minimo_exige_correo_y_un_pdf_o_zip`

### CA-CU05-06 Valida suma, documento, celular y declaración

- **Dado** el formulario público
- **Cuando** la suma está mal, el DNI tiene 4 dígitos, elige RUC como documento de persona natural, el celular tiene 5 dígitos o no acepta la declaración
- **Entonces** el sistema marca cada campo y no crea ningún Expediente
- **Cubre:** flujo alterno 6d, RN-CU05-03
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_valida_captcha_documento_celular_y_declaracion`

### CA-CU05-07 El código propio ya no se pide

- **Dado** el formulario público
- **Cuando** el ciudadano lo abre, o alguien envía igual un código propio
- **Entonces** no aparecen «Opciones adicionales» ni el campo, y el Expediente se crea sin código propio
- **Cubre:** paso 2, RN-CU05-05
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_formulario_publico_ignora_el_codigo_propio`

### CA-CU05-08 Límite diario por documento

- **Dado** un documento de identidad que ya presentó 5 trámites hoy
- **Cuando** intenta presentar el sexto
- **Entonces** el sistema lo rechaza y siguen siendo 5
- **Cubre:** flujo alterno 6e, RN-CU05-06
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_limite_diario_por_documento`

### CA-CU05-09 El autocompletado protege los datos de contacto

- **Dado** un estudiante que ya presentó antes
- **Cuando** otra persona escribe su DNI y presenta con otro nombre y otro correo
- **Entonces** el autocompletado muestra el nombre con el correo y el celular ocultos (`es***@demo.test`), los datos del estudiante no cambian y el Expediente queda a su nombre
- **Cubre:** paso 3, RN-CU05-07
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_autocompletado_oculta_el_contacto_y_un_tercero_no_lo_cambia`

### CA-CU05-10 Envía el número por correo

- **Dado** un ciudadano que presenta su trámite
- **Cuando** el sistema lo registra
- **Entonces** envía el aviso de presentación con el número a su correo
- **Cubre:** paso 9
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_envia_el_codigo_por_correo_al_presentar`

### CA-CU05-11 Muestra Cloudflare Turnstile cuando hay claves

- **Dado** el sistema con las claves de Turnstile en el `.env`
- **Cuando** el ciudadano abre el formulario
- **Entonces** ve la casilla de verificación de Cloudflare en lugar de la pregunta de suma; sin claves sigue la suma
- **Cubre:** RN-CU05-08
- **Prueba:** `tests/Feature/CaptchaTurnstileTest.php::test_con_claves_muestra_turnstile_y_no_la_suma`

### CA-CU05-12 Cloudflare aprueba la verificación

- **Dado** el formulario con Turnstile
- **Cuando** Cloudflare confirma que el token es válido
- **Entonces** el sistema registra el trámite y envió a Cloudflare el token y la clave secreta
- **Cubre:** paso 6, RN-CU05-08
- **Prueba:** `tests/Feature/CaptchaTurnstileTest.php::test_cloudflare_aprueba_y_se_registra_el_tramite`

### CA-CU05-13 Verificación rechazada, ausente o sin respuesta

- **Dado** el formulario con Turnstile
- **Cuando** Cloudflare rechaza el token, el token falta o Cloudflare no responde
- **Entonces** el sistema marca la verificación y no crea ningún Expediente
- **Cubre:** flujo alterno 6d, RN-CU05-08
- **Prueba:** `tests/Feature/CaptchaTurnstileTest.php::test_cloudflare_rechaza_o_falta_el_token`

### CA-CU05-14 Persona natural: DNI exacto, carné de extranjería y celular

- **Dado** el formulario con «Persona natural» y DNI elegidos por defecto
- **Cuando** falta el tipo de persona, el DNI tiene 7 o 9 dígitos, elige pasaporte o el celular no empieza en 9 o no tiene 9 dígitos
- **Entonces** el sistema marca el campo y no crea el Expediente; con datos correctos lo crea y el remitente queda como ciudadano
- **Cubre:** pasos 2 y 3, flujos alternos 6c y 6d, RN-CU05-03
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_persona_natural_valida_documento_y_celular`

### CA-CU05-15 Persona jurídica: entidad y representante por expediente

- **Dado** una entidad con RUC 20123456789 que presenta por medio de su representante
- **Cuando** presenta un trámite y luego otro representante presenta otro
- **Entonces** la entidad se registra una sola vez con su razón social, sin datos del representante, y cada Expediente guarda a su propio representante
- **Cubre:** pasos 3 y 7, RN-CU05-09
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_persona_juridica_guarda_entidad_y_representante_en_el_expediente`

### CA-CU05-16 Persona jurídica: valida RUC, razón social y representante

- **Dado** el formulario con «Persona jurídica»
- **Cuando** el RUC no tiene 11 dígitos o un prefijo válido, falta la razón social o algún dato del representante
- **Entonces** el sistema marca el campo y no crea ni la entidad ni el Expediente; el tipo de documento de persona natural se ignora (el representante siempre va con DNI)
- **Cubre:** flujos alternos 6c y 6d, RN-CU05-03
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_persona_juridica_valida_ruc_razon_social_y_representante`
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_persona_juridica_valida_datos_del_representante`

### CA-CU05-17 Constancia y panel muestran la entidad y el representante

- **Dado** un trámite de persona jurídica
- **Cuando** se abre la constancia (web y PDF) o el detalle en Mesa de Partes
- **Entonces** se ven el RUC, la razón social y el bloque «Datos del representante»; en persona natural no aparece ese bloque
- **Cubre:** paso 10, RN-CU05-09
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_persona_juridica_en_constancia_y_panel`
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_persona_natural_constancia_sin_representante`

### CA-CU05-18 Términos y condiciones al inicio

- **Dado** un visitante sin sesión
- **Cuando** abre el formulario «Presentar trámite»
- **Entonces** ve la ventana «Términos y condiciones» que se abre sola, con la casilla «He leído y acepto», el botón «Aceptar y continuar» y el enlace «No acepto» a la portada; si el formulario vuelve con errores y ya había aceptado, la ventana no se abre de nuevo; sin aceptar, el servidor responde «Debes aceptar los términos y condiciones» y no crea el Expediente
- **Cubre:** paso 2, flujo alterno 2a, RN-CU05-10
- **Prueba:** `tests/Feature/TerminosCondicionesTest.php::test_terminos_y_condiciones_al_abrir_el_formulario`
