# CU19 Verificar documento

| Campo | Valor |
|---|---|
| Actor | Cualquier persona (sin cuenta) |
| Paquete | Administrado |
| Requisitos | RF26, RNF18 |
| Figuras ICONIX | Robustez CU19 (nueva), Secuencia CU19 (nueva) |
| Responsable | Yunfer (Bloque 2) |

## Objetivo

Cualquier persona comprueba que una constancia, un cargo o una respuesta los emitió el sistema
y que el archivo que tiene no fue modificado.

## Flujo básico

1. La persona abre la pantalla Verificar documento o escanea el QR de su constancia o cargo.
2. La persona ingresa el código de verificación (no importa si usa minúsculas o no pone guiones).
3. El sistema busca el código en las constancias de los Expedientes y en las Respuestas.
4. El sistema muestra el tipo de documento, el número de Expediente, la fecha, quién lo firmó y la huella SHA-256 de cada DocumentoAdjunto.
5. Opcionalmente, la persona sube el PDF que recibió.
6. El sistema calcula la huella del archivo, la compara con las guardadas e indica que el archivo no fue modificado. El archivo no se guarda.
7. Termina el caso de uso.

## Flujos alternos

- **3a.** El código no existe: el sistema avisa y permite probar con otro código.
- **6a.** El archivo no coincide: el sistema avisa que fue modificado o que no pertenece a ese documento.

## Reglas de negocio

- **RN-CU19-01.** El código de verificación tiene el formato `XXXX-XXXX-XXXX` y no usa O, 0, I ni 1.
- **RN-CU19-02.** La constancia del Expediente y cada Respuesta tienen códigos distintos.
- **RN-CU19-03.** La página `/verificar` es pública: no pide cuenta ni documento de identidad.
- **RN-CU19-04.** El archivo subido para comparar no se guarda en el servidor.

## Criterios de aceptación

### CA-CU19-01 Cada documento emitido lleva código y huella

- **Dado** un expediente presentado, recepcionado, derivado y respondido con un PDF
- **Cuando** se revisan sus datos
- **Entonces** la constancia y la respuesta tienen códigos distintos con el formato `XXXX-XXXX-XXXX` y todos los archivos tienen su huella SHA-256
- **Cubre:** RN-CU19-01, RN-CU19-02
- **Prueba:** `tests/Feature/VerificacionTest.php::test_constancias_respuestas_y_archivos_llevan_codigo_y_huella`

### CA-CU19-02 La constancia muestra el código y el QR

- **Dado** un trámite recién presentado
- **Cuando** el ciudadano abre su constancia
- **Entonces** ve «Firma electrónica del sistema», el código de verificación y un QR que apunta a `/verificar/{código}`
- **Cubre:** paso 1
- **Prueba:** `tests/Feature/VerificacionTest.php::test_la_constancia_muestra_el_codigo_y_el_qr`

### CA-CU19-03 Verifica la constancia y compara el archivo

- **Dado** una constancia con su código de verificación
- **Cuando** la persona escribe el código en minúsculas y sin guiones, y luego sube el PDF original y uno cambiado
- **Entonces** el sistema encuentra el documento, muestra «Documento auténtico», el expediente y la huella; con el original dice «el archivo no fue modificado» y con el cambiado dice «No coincide»
- **Cubre:** pasos 2 a 6, flujo alterno 6a
- **Prueba:** `tests/Feature/VerificacionTest.php::test_verificar_constancia_y_comprobar_el_archivo`

### CA-CU19-04 Verifica una respuesta y muestra quién la firmó

- **Dado** una respuesta emitida por Secretaría Académica
- **Cuando** una persona sin sesión abre `/verificar/{código de la respuesta}`
- **Entonces** ve «Respuesta del área», el nombre del área y el número de expediente
- **Cubre:** pasos 3 y 4, RN-CU19-03
- **Prueba:** `tests/Feature/VerificacionTest.php::test_verificar_respuesta_muestra_quien_la_firmo`

### CA-CU19-05 Código que no existe

- **Dado** el código `AAAA-BBBB-CCCC`, que no pertenece a ningún documento
- **Cuando** la persona lo verifica
- **Entonces** el sistema dice «No encontramos ningún documento» y deja probar otro código
- **Cubre:** flujo alterno 3a
- **Prueba:** `tests/Feature/VerificacionTest.php::test_codigo_que_no_existe`
