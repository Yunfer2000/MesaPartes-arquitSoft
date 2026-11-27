# CU08 Recepcionar expediente

| Campo | Valor |
|---|---|
| Actor | Operador de Mesa de Partes |
| Paquete | Mesa de Partes |
| Requisitos | RF05, RF08, RF17, RNF07 |
| Figuras ICONIX | Robustez Fig 26, Secuencia Fig 44, Prototipo Fig 12 |
| Responsable | Yunfer (Bloque 2) |

## Objetivo

El Operador revisa un trámite que llegó por la Mesa de Partes Virtual, completa los datos que
el ciudadano no pone (tipo de documento, folios y N.° de documento) y lo da por recibido.

## Flujo básico

1. El Operador abre la pantalla Bandeja de Mesa de Partes.
2. El sistema lista los Expedientes en estado Presentado o Subsanado, del más antiguo al más reciente.
3. El Operador elige un Expediente.
4. El sistema muestra la pantalla Revisión del expediente con sus datos, cada DocumentoAdjunto y el formulario de recepción.
5. El Operador completa el tipo de documento, los folios y el N.° de documento, y pulsa «Recepcionar».
6. El sistema valida los datos, bloquea el Expediente y comprueba que siga en estado Presentado o Subsanado.
7. El sistema guarda tipo, folios y N.° de documento, registra la fecha de recepción y un Movimiento (acción «recibido») y cambia el estado a Recibido.
8. El sistema ejecuta CU16 para avisar al Administrado de la recepción.
9. Termina el caso de uso.

## Flujos alternos

- **6a.** Faltan el tipo o los folios (el trámite virtual llega sin ellos): el sistema marca los campos y vuelve al paso 5.
- **6b.** Otro operador ya lo recepcionó: el sistema avisa que la acción no se puede realizar en estado Recibido.
- **8a.** Ya recepcionado, el expediente tiene datos o documentos faltantes: el Operador elige «Observar» y continúa en CU09. Si no, continúa en CU10.

## Reglas de negocio

- **RN-CU08-01.** Solo se recepciona un Expediente en estado Presentado o Subsanado.
- **RN-CU08-02.** El tipo de documento y los folios (1 a 9999) son obligatorios si el Expediente aún no los tiene. El N.° de documento es opcional.
- **RN-CU08-03.** La recepción bloquea la fila del Expediente (`lockForUpdate`): si dos operadores pulsan a la vez, solo uno la registra (RNF07).
- **RN-CU08-04.** Solo el rol Mesa de Partes entra a la bandeja y puede recepcionar.

## Criterios de aceptación

### CA-CU08-01 Recepciona y completa los datos

- **Dado** un Expediente virtual en estado Presentado
- **Cuando** el Operador elige tipo de documento, escribe 4 folios y el N.° de documento y pulsa «Recepcionar»
- **Entonces** el Expediente queda Recibido con esos datos guardados y visibles en la revisión
- **Cubre:** pasos 4, 5 y 7
- **Prueba:** `tests/Feature/RecepcionMesaTest.php::test_mesa_completa_tipo_folios_y_numero_al_recepcionar`

### CA-CU08-02 Sin folios no se recepciona

- **Dado** un Expediente presentado sin folios
- **Cuando** el Operador deja los folios vacíos o escribe 0
- **Entonces** el sistema marca el campo folios y el Expediente sigue Presentado
- **Cubre:** flujo alterno 6a
- **Prueba:** `tests/Feature/RecepcionMesaTest.php::test_si_faltan_los_folios_no_se_recepciona`

### CA-CU08-03 Sin tipo de documento no se recepciona

- **Dado** un Expediente presentado en el que el ciudadano no eligió tipo de documento
- **Cuando** el Operador pulsa «Recepcionar» sin elegir el tipo
- **Entonces** el sistema marca el campo tipo de documento y el Expediente sigue Presentado
- **Cubre:** flujo alterno 6a
- **Prueba:** `tests/Feature/RecepcionMesaTest.php::test_si_falta_el_tipo_no_se_recepciona`

### CA-CU08-04 No se recepciona dos veces

- **Dado** un Expediente que ya fue recepcionado
- **Cuando** el Operador (u otro operador al mismo tiempo) vuelve a pulsar «Recepcionar»
- **Entonces** el sistema muestra un aviso y solo existe un Movimiento «recibido»
- **Cubre:** flujo alterno 6b, RN-CU08-03
- **Prueba:** `tests/Feature/RecepcionMesaTest.php::test_no_se_recepciona_dos_veces`

### CA-CU08-05 Aparece en la bandeja, se recepciona y se notifica

- **Dado** un Expediente presentado por la Mesa de Partes Virtual
- **Cuando** el Operador lo abre desde la bandeja y lo recepciona
- **Entonces** el Expediente queda Recibido con fecha de recepción y el Administrado recibe el aviso (CU16)
- **Cubre:** pasos 1, 2, 3, 7 y 8
- **Prueba:** `tests/Feature/FlujoMesaAreaTest.php::test_recorrido_completo_hasta_finalizar`

### CA-CU08-06 Busca en la bandeja por el código propio

- **Dado** dos Expedientes en la bandeja, uno con el código propio «CONSTANCIA2026»
- **Cuando** el Operador busca «constancia2026» (en minúsculas)
- **Entonces** la bandeja muestra solo ese Expediente
- **Cubre:** paso 2
- **Prueba:** `tests/Feature/RecepcionMesaTest.php::test_bandeja_busca_por_codigo_propio`

### CA-CU08-07 Otro rol no entra a la bandeja

- **Dado** un usuario de un área (por ejemplo, Secretaría Académica)
- **Cuando** intenta abrir las pantallas de Mesa de Partes
- **Entonces** el sistema le niega el acceso
- **Cubre:** RN-CU08-04
- **Prueba:** `tests/Feature/AccesoTest.php::test_un_rol_no_entra_a_pantallas_de_otro`
