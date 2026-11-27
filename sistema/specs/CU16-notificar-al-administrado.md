# CU16 Notificar al administrado

| Campo | Valor |
|---|---|
| Actor | Sistema (Servicio de correo) |
| Paquete | Mesa de Partes |
| Requisitos | RF17 |
| Figuras ICONIX | Robustez Fig 34, Secuencia Fig 52 |
| Responsable | Bloque 3 |

## Objetivo

El sistema avisa al ciudadano por correo cada vez que su trámite avanza, para que no tenga que
estar consultando.

## Flujo básico

1. Otro caso de uso pide el aviso con un evento: presentación (CU05), registro presencial (CU17), recepción (CU08), observación (CU09), derivación (CU10) o respuesta (CU13).
2. El sistema arma el mensaje según el evento, con el número del Expediente (y el área, en la derivación).
3. El sistema guarda la Notificación del Expediente.
4. El sistema envía el mensaje al correo del Administrado (en persona jurídica, al correo del representante de ese Expediente) mediante el Servicio de correo y la marca como enviada.
5. Termina el caso de uso.

## Flujos alternos

- **4a.** El envío falla o el Administrado no tiene correo (registros presenciales antiguos): la Notificación queda guardada, se marca como no enviada y el fallo queda en el log.

## Reglas de negocio

- **RN-CU16-01.** Eventos: presentación, registro presencial, recepción, observación, derivación y respuesta. Los avances (CU12) no generan correo.
- **RN-CU16-02.** El aviso de presentación lleva el N.° de expediente para la consulta.
- **RN-CU16-03.** El correo es obligatorio en lo virtual y en lo presencial; los registros presenciales antiguos pueden no tenerlo.
- **RN-CU16-04.** En un Expediente de persona jurídica, el aviso va al correo del representante guardado en ese Expediente, no a la entidad.

## Criterios de aceptación

### CA-CU16-01 Avisa en cada etapa del trámite

- **Dado** un trámite que se presenta, se recepciona, se deriva y se responde
- **Cuando** termina el recorrido
- **Entonces** existen las notificaciones presentación, recepción, derivación y respuesta, todas enviadas, y salieron 4 correos
- **Cubre:** pasos 1 a 4, RN-CU16-01
- **Prueba:** `tests/Feature/FlujoMesaAreaTest.php::test_recorrido_completo_hasta_finalizar`

### CA-CU16-02 El aviso de presentación lleva el número

- **Dado** un ciudadano que presenta su trámite
- **Cuando** el sistema lo registra
- **Entonces** guarda la notificación «presentación» con el N.° de expediente en el mensaje y envía el correo
- **Cubre:** paso 2, RN-CU16-02
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_envia_el_codigo_por_correo_al_presentar`

### CA-CU16-03 Avisa la observación

- **Dado** un trámite presentado
- **Cuando** Mesa de Partes lo observa
- **Entonces** se guarda la notificación «observación», enviada, con el N.° de expediente
- **Cubre:** pasos 1 a 4
- **Prueba:** `tests/Feature/FlujoMesaAreaTest.php::test_observar_guarda_motivo_y_notifica`

### CA-CU16-04 Sin correo queda como no enviada

- **Dado** un expediente presencial de una persona sin correo
- **Cuando** Mesa de Partes lo deriva
- **Entonces** la notificación «derivación» se guarda marcada como no enviada
- **Cubre:** flujo alterno 4a, RN-CU16-03
- **Prueba:** `tests/Feature/FlujoMesaAreaTest.php::test_registro_presencial_de_administrado_nuevo_y_aviso_sin_correo`

### CA-CU16-05 Persona jurídica: avisa al representante del expediente

- **Dado** dos trámites de la misma entidad, presentados por representantes distintos
- **Cuando** el sistema avisa la presentación y luego la recepción
- **Entonces** cada aviso llega al correo del representante de su propio Expediente, saludándolo por su nombre
- **Cubre:** paso 4, RN-CU16-04
- **Prueba:** `tests/Feature/TramiteExternoTest.php::test_persona_juridica_notifica_al_representante_del_expediente`
