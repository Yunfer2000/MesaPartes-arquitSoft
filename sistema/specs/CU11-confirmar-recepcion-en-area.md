# CU11 Confirmar recepción en área

| Campo | Valor |
|---|---|
| Actor | Responsable de área |
| Paquete | Área |
| Requisitos | RF11, RNF07, RNF08 |
| Figuras ICONIX | Robustez Fig 29, Secuencia Fig 47, Prototipo Fig 15 |
| Responsable | Bloque 4 |

## Objetivo

El área deja constancia de que recibió el Expediente derivado y empieza a atenderlo.

## Flujo básico

1. El Responsable abre la pantalla Bandeja del área.
2. El sistema muestra las pestañas «Por atender» y «Para conocimiento», cada una con su número.
3. El Responsable elige un Expediente derivado a su Área y pulsa «Confirmar recepción».
4. El sistema bloquea el Expediente y registra en el Movimiento la confirmación (usuario, fecha y hora); cambia el estado a En atención.
5. Termina el caso de uso.

## Flujos alternos

- **3a.** El Expediente no corresponde al área: el Responsable lo confirma y lo deriva al área correcta con CU10.
- **3b.** El Expediente es de otra área, o el área solo tiene una copia: el sistema no deja confirmarlo.
- **4a.** Otra persona del área ya lo confirmó: el sistema avisa y no duplica la confirmación.

## Reglas de negocio

- **RN-CU11-01.** Solo confirma el área destino del último Movimiento.
- **RN-CU11-02.** La confirmación bloquea la fila del Expediente (RNF07).
- **RN-CU11-03.** La confirmación aparece en el seguimiento público como la burbuja «En atención».

## Criterios de aceptación

### CA-CU11-01 Confirma la recepción

- **Dado** un Expediente derivado a Secretaría Académica
- **Cuando** Dirección intenta confirmarlo y luego Secretaría lo confirma desde su bandeja
- **Entonces** a Dirección se le niega; con Secretaría queda En atención y el Movimiento queda confirmado
- **Cubre:** pasos 1, 3 y 4, flujo alterno 3b, RN-CU11-01
- **Prueba:** `tests/Feature/FlujoMesaAreaTest.php::test_recorrido_completo_hasta_finalizar`

### CA-CU11-02 Confirmar dos veces no duplica

- **Dado** un Expediente que Secretaría ya confirmó
- **Cuando** vuelve a pulsar «Confirmar recepción»
- **Entonces** el sistema muestra un aviso
- **Cubre:** flujo alterno 4a, RN-CU11-02
- **Prueba:** `tests/Feature/DerivacionMultipleTest.php::test_derivar_dos_veces_el_mismo_expediente_falla`

### CA-CU11-03 Una copia no se confirma

- **Dado** un Expediente con copia para Dirección
- **Cuando** Dirección intenta confirmarlo
- **Entonces** el sistema le niega la acción
- **Cubre:** flujo alterno 3b
- **Prueba:** `tests/Feature/DerivacionMultipleTest.php::test_deriva_a_un_area_y_envia_copia_a_varias`

### CA-CU11-04 La bandeja tiene pestañas con su número

- **Dado** el usuario de Dirección General
- **Cuando** abre su bandeja
- **Entonces** ve «Por atender» y «Para conocimiento» con su cuenta, y sin copias dice «No tienes copias pendientes de leer.»
- **Cubre:** paso 2
- **Prueba:** `tests/Feature/InterfazBandejasFormularioTest.php::test_bandeja_del_area_tiene_pestanas_con_su_numero`
