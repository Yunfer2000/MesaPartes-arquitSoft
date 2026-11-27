# CU06 Subsanar observación

| Campo | Valor |
|---|---|
| Actor | Administrado (sin cuenta) |
| Paquete | Administrado |
| Requisitos | RF06, RF19, RF22 |
| Figuras ICONIX | Robustez Fig 24, Secuencia Fig 42, Prototipo Fig 10 |
| Responsable | Bloque 1 |

## Objetivo

El ciudadano corrige lo que Mesa de Partes le observó, desde la consulta pública, para que su
trámite vuelva a la bandeja de Mesa de Partes.

## Flujo básico

1. El Administrado consulta su Expediente con el N.° de expediente (o su código propio) y su documento de identidad (CU07).
2. El sistema muestra el aviso «Tu trámite tiene una observación pendiente» y la Observación con lo que debe corregir.
3. El Administrado pulsa «Subsanar ahora», escribe la descripción de lo corregido y adjunta los documentos pedidos (hasta 5).
4. El Administrado pulsa «Enviar subsanación».
5. El sistema valida los datos y guarda la Subsanación y cada DocumentoAdjunto nuevo con su huella.
6. El sistema registra un Movimiento (acción «subsanado») y cambia el estado a Subsanado.
7. El sistema vuelve al detalle con un mensaje de éxito; el Expediente aparece otra vez en la Bandeja de Mesa de Partes.
8. Termina el caso de uso.

## Flujos alternos

- **1a.** No consultó antes con su documento de identidad: el sistema no deja subsanar y lo manda a la consulta.
- **5a.** No escribe la descripción o un archivo no es válido: el sistema avisa qué falta y vuelve al paso 3.

## Reglas de negocio

- **RN-CU06-01.** Solo se subsana un Expediente en estado Observado, y solo quien lo consultó con su documento de identidad.
- **RN-CU06-02.** Tras subsanar, la observación y el formulario desaparecen; la subsanación queda en «Subsanaciones realizadas».
- **RN-CU06-03.** El Expediente subsanado vuelve a la Bandeja de Mesa de Partes para CU08.

## Criterios de aceptación

### CA-CU06-01 Subsana sin cuenta

- **Dado** un Expediente observado y consultado con el DNI correcto
- **Cuando** el ciudadano envía la subsanación
- **Entonces** el Expediente queda Subsanado con un Movimiento «subsanado» y vuelve al detalle
- **Cubre:** pasos 2 a 7
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_subsana_observacion_sin_cuenta`

### CA-CU06-02 Vuelve a la bandeja de Mesa de Partes

- **Dado** un Expediente que Mesa de Partes observó (con motivo obligatorio)
- **Cuando** el ciudadano lo subsana
- **Entonces** el Expediente sale de la bandeja mientras está observado y vuelve a aparecer después de subsanar
- **Cubre:** paso 7, RN-CU06-03
- **Prueba:** `tests/Feature/FlujoMesaAreaTest.php::test_observar_y_volver_a_bandeja_tras_subsanar`

### CA-CU06-03 La observación se ve solo mientras está pendiente

- **Dado** un Expediente observado
- **Cuando** el ciudadano lo subsana y Mesa de Partes lo observa de nuevo
- **Entonces** después de subsanar desaparecen la observación y el formulario, y con la nueva observación solo se ve la vigente
- **Cubre:** paso 2, RN-CU06-02
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_observacion_visible_solo_mientras_esta_pendiente`

### CA-CU06-04 Subsanaciones realizadas en su sección

- **Dado** un Expediente que ya fue subsanado una vez
- **Cuando** el ciudadano vuelve a consultarlo
- **Entonces** la subsanación aparece en «Subsanaciones realizadas», separada de cualquier observación pendiente
- **Cubre:** RN-CU06-02
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_subsanaciones_realizadas_en_su_seccion`

### CA-CU06-05 Aviso de observación pendiente con días y enlaces

- **Dado** un Expediente observado un lunes
- **Cuando** el ciudadano lo consulta el mismo día, el jueves y el lunes siguiente
- **Entonces** ve arriba el aviso con los días hábiles transcurridos («hoy», «hace 3 días hábiles», «hace 5 días hábiles») y el enlace «Subsanar ahora» lleva al formulario; tras subsanar, el aviso desaparece
- **Cubre:** pasos 2 y 3
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_aviso_de_observacion_pendiente_con_dias_y_enlaces`

### CA-CU06-06 Sin consultar antes no se subsana

- **Dado** una persona que no consultó el Expediente con su documento
- **Cuando** envía directamente una subsanación
- **Entonces** el sistema la manda a la consulta y no registra nada
- **Cubre:** flujo alterno 1a, RN-CU06-01
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_b1_acceso_directo_sin_autorizacion`
