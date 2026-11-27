# CU17 Registrar expediente presencial

| Campo | Valor |
|---|---|
| Actor | Operador de Mesa de Partes |
| Paquete | Mesa de Partes |
| Requisitos | RF05, RF06, RF07, RF19, RF23, RF26, RNF18 |
| Figuras ICONIX | Robustez Fig 35, Secuencia Fig 53, Prototipo Fig 13 |
| Responsable | Yunfer (Bloque 2) |

## Objetivo

El Operador registra en el sistema un documento que el ciudadano trajo en papel a ventanilla,
para que siga el mismo recorrido que un trámite virtual y el ciudadano reciba su cargo.

## Flujo básico

1. El Operador elige «Registro presencial» en la Bandeja de Mesa de Partes.
2. El sistema muestra la pantalla Registro presencial, con los mismos datos del solicitante y la misma carga de archivo que el formulario público (CU05).
3. El Operador elige el tipo de persona (natural o jurídica) y busca al solicitante por su documento de identidad o, en persona jurídica, por el RUC.
4. El sistema muestra sus datos.
5. El Operador completa el celular y el correo; elige tipo de documento y área de destino, escribe el asunto, los folios, el N.° de expediente que Mesa anotó o selló a mano en el papel (con el ícono ⓘ que lo explica) y el N.° de documento, y opcionalmente adjunta el documento escaneado (PDF o ZIP, máximo 10 MB).
6. El Operador pulsa «Registrar».
7. El sistema valida los datos con las mismas reglas que el formulario público.
8. El sistema genera el número y el código de verificación del Expediente, guarda el escaneo si lo hay (en almacenamiento privado, con su huella SHA-256) y registra la fecha de presentación y de recepción.
9. El sistema registra un Movimiento (acción «recibido», con el Operador que lo registró) y deja el Expediente en estado Recibido.
10. El sistema avisa el registro al correo del solicitante o, en persona jurídica, al del representante (CU16).
11. El sistema muestra el Cargo de recepción con el QR de verificación (CU19), para imprimirlo.
12. Termina el caso de uso.

## Flujos alternos

- **3a.** Persona jurídica: el Operador ingresa el RUC y la razón social de la entidad y los datos del representante; el N.° de documento es el DNI del representante, que se guarda en el Expediente.
- **4a.** El solicitante no está registrado: el Operador ingresa sus datos y el sistema lo crea sin cuenta de acceso. Si ya está registrado, sus datos no se cambian desde este formulario.
- **7a.** Faltan datos o folios, el documento, el celular o el correo no tienen el formato correcto, el archivo no es PDF ni ZIP o pesa más de 10 MB, o el tipo de documento o el área no están activos: el sistema marca los campos y vuelve al paso 5.
- **8a.** No hay escaneo: el expediente queda como «Documento físico (sin escaneo)».
- **10a.** El correo no se puede enviar: el Expediente queda registrado y la Notificación se marca como no enviada (CU16 4a).

## Reglas de negocio

- **RN-CU17-01.** El expediente presencial nace en estado Recibido (no pasa por CU08) con modalidad «Presencial».
- **RN-CU17-02.** Los folios son obligatorios; el escaneo es opcional.
- **RN-CU17-03.** El N.° de expediente anotado a mano es opcional, se guarda en mayúsculas (columna codigo_propio), solo lleva letras, números y guiones, y no se repite con otro número a mano ni con un N.° que ya generó el sistema. El sistema igual genera su propio N.° de expediente. Sirve para seguir el documento físico de oficina en oficina y para la consulta (CU07 B1). Los expedientes antiguos conservan su código del sello.
- **RN-CU17-04.** El solicitante nuevo se crea sin cuenta de acceso y como «Ciudadano» (no se pregunta el tipo de solicitante). El celular y el correo son obligatorios. Persona natural: DNI, carné de extranjería o pasaporte (este último solo en el registro presencial). Los números ya registrados se aceptan aunque tengan otro formato.
- **RN-CU17-05.** Todo archivo guardado lleva su huella SHA-256 y el cargo lleva un código de verificación con QR (RF26, RNF18).
- **RN-CU17-06.** El área de destino debe estar activa y ser distinta de Mesa de Partes. Solo el rol Mesa de Partes registra expedientes presenciales. No hay captcha ni límite diario.

## Criterios de aceptación

### CA-CU17-01 Registra a un administrado nuevo

- **Dado** un ciudadano con DNI 28301456 que no está registrado
- **Cuando** el Operador lo busca, ingresa sus datos y registra el documento con su escaneo
- **Entonces** el Expediente queda Recibido, modalidad Presencial, con fecha de recepción; el Administrado no tiene cuenta y se muestra el «Cargo de recepción»
- **Cubre:** pasos 3 a 11, flujo alterno 4a
- **Prueba:** `tests/Feature/FlujoMesaAreaTest.php::test_registro_presencial_de_administrado_nuevo_y_aviso_sin_correo`

### CA-CU17-02 Registra sin escaneo

- **Dado** un documento en papel sin escanear
- **Cuando** el Operador lo registra con 3 folios y su N.° de documento
- **Entonces** el Expediente queda Recibido, sin archivos, y el cargo dice «Documento físico (sin escaneo)»
- **Cubre:** flujo alterno 8a
- **Prueba:** `tests/Feature/RecepcionMesaTest.php::test_registro_presencial_sin_escaneo`

### CA-CU17-03 Exige los folios

- **Dado** el formulario de registro presencial
- **Cuando** el Operador pulsa «Registrar» sin folios
- **Entonces** el sistema marca el campo folios y no crea el Expediente
- **Cubre:** flujo alterno 7a
- **Prueba:** `tests/Feature/RecepcionMesaTest.php::test_registro_presencial_exige_folios`

### CA-CU17-04 Registra el N.° de expediente anotado a mano

- **Dado** el formulario de registro presencial
- **Cuando** el Operador registra un expediente con el N.° «mp-0012», luego otro con el mismo N.°, otro con espacios, otro con un N.° que ya dio el sistema y otro sin N.°
- **Entonces** el formulario muestra el campo con el ícono ⓘ y el ejemplo «EXP-2026-000005»; el primero se guarda como «MP-0012»; los repetidos o con espacios se rechazan; sin N.° se registra igual; los expedientes antiguos siguen mostrando su código en el cargo
- **Cubre:** paso 5, flujo alterno 7a, RN-CU17-03
- **Prueba:** `tests/Feature/RegistroPresencialTest.php::test_numero_de_expediente_a_mano_se_guarda_y_no_se_repite`, `tests/Feature/RegistroPresencialTest.php::test_codigo_del_sello_historico_se_sigue_mostrando_y_buscando`

### CA-CU17-05 El cargo lleva firma electrónica y huella

- **Dado** un documento presencial con escaneo
- **Cuando** el Operador lo registra
- **Entonces** el escaneo guarda su huella SHA-256 y el cargo muestra el código de verificación y el QR que lleva a «Verificar documento»
- **Cubre:** pasos 8 y 11, RN-CU17-05
- **Prueba:** `tests/Feature/RecepcionMesaTest.php::test_cargo_presencial_lleva_codigo_y_huella`

### CA-CU17-06 Persona jurídica con su representante

- **Dado** una entidad con RUC 20123456789 que trae un documento por medio de su representante
- **Cuando** el Operador elige «Jurídica», ingresa el RUC, la razón social y los datos del representante
- **Entonces** la entidad queda registrada con su RUC y el representante se guarda en el Expediente
- **Cubre:** flujo alterno 3a
- **Prueba:** `tests/Feature/RegistroPresencialTest.php::test_registra_persona_juridica_con_su_representante`

### CA-CU17-07 Valida documento y contacto

- **Dado** el formulario de registro presencial
- **Cuando** el Operador escribe un DNI, carné, pasaporte o RUC con otro formato, o deja vacío o mal escrito el celular o el correo
- **Entonces** el sistema marca el campo y no crea el Expediente
- **Cubre:** paso 7, flujo alterno 7a, RN-CU17-04
- **Prueba:** `tests/Feature/RegistroPresencialTest.php::test_valida_formato_de_cada_documento`, `tests/Feature/RegistroPresencialTest.php::test_celular_y_correo_obligatorios_y_con_formato`

### CA-CU17-08 Adjunto PDF o ZIP de hasta 10 MB

- **Dado** un documento escaneado
- **Cuando** el Operador adjunta un PDF o un ZIP, o un archivo de otro tipo o de más de 10 MB
- **Entonces** el PDF o ZIP se guarda en almacenamiento privado con su huella; los demás se rechazan sin crear el Expediente
- **Cubre:** pasos 5 y 8, flujo alterno 7a
- **Prueba:** `tests/Feature/RegistroPresencialTest.php::test_pdf_valido_se_guarda_en_privado_con_su_huella`, `tests/Feature/RegistroPresencialTest.php::test_zip_valido`, `tests/Feature/RegistroPresencialTest.php::test_rechaza_archivos_invalidos_o_mayores_de_10_mb`

### CA-CU17-09 Avisa el registro por correo

- **Dado** un expediente presencial de una persona natural y otro de una persona jurídica
- **Cuando** el Operador los registra
- **Entonces** se guarda la notificación «registro presencial» y el aviso va al correo del solicitante o al del representante; si el envío falla, el Expediente queda registrado y el aviso como no enviado
- **Cubre:** paso 10, flujo alterno 10a
- **Prueba:** `tests/Feature/RegistroPresencialTest.php::test_avisa_el_registro_al_destinatario_correcto`, `tests/Feature/RegistroPresencialTest.php::test_fallo_del_correo_no_impide_el_registro`

### CA-CU17-10 Solo Mesa de Partes

- **Dado** un visitante sin sesión o un usuario de un área o el Administrador
- **Cuando** entra al registro presencial, lo envía o usa su búsqueda
- **Entonces** el sistema lo lleva al ingreso o le niega el acceso, y no crea el Expediente
- **Cubre:** RN-CU17-06
- **Prueba:** `tests/Feature/RegistroPresencialTest.php::test_solo_mesa_de_partes_registra_y_busca`
