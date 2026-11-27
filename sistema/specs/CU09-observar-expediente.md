# CU09 Observar expediente

| Campo | Valor |
|---|---|
| Actor | Operador de Mesa de Partes |
| Paquete | Mesa de Partes |
| Requisitos | RF09, RF17, RNF07 |
| Figuras ICONIX | Robustez Fig 27, Secuencia Fig 45, Prototipo Fig 12 |
| Responsable | Bloque 2 |

## Objetivo

El Operador devuelve al ciudadano un trámite al que le faltan datos o documentos, indicando qué
debe corregir.

## Flujo básico

1. El Operador elige «Observar» en «¿Qué deseas hacer?» de la pantalla Revisión del expediente.
2. El sistema muestra el formulario Observación.
3. El Operador escribe el motivo y, opcionalmente, el detalle de lo que debe corregirse o adjuntarse, y pulsa «Registrar».
4. El sistema bloquea el Expediente, comprueba su estado, guarda la Observación, registra un Movimiento (acción «observado») y cambia el estado a Observado.
5. El sistema ejecuta CU16 para avisar al Administrado qué debe subsanar.
6. El Expediente sale de la bandeja hasta que el Administrado lo subsane (CU06).
7. Termina el caso de uso.

## Flujos alternos

- **3a.** Motivo vacío: el sistema pide completarlo y vuelve al paso 3.
- **4a.** El Expediente ya no está en Mesa de Partes (por ejemplo, ya se derivó): el sistema no deja observarlo.

## Reglas de negocio

- **RN-CU09-01.** El motivo es obligatorio (máximo 255 caracteres); el detalle es opcional.
- **RN-CU09-02.** Solo se observa un Expediente que está en Mesa de Partes.
- **RN-CU09-03.** La observación bloquea la fila del Expediente ante acciones simultáneas (RNF07).

## Criterios de aceptación

### CA-CU09-01 Observa con motivo obligatorio y vuelve tras subsanar

- **Dado** un Expediente presentado en la bandeja
- **Cuando** el Operador intenta observar sin motivo y luego con el motivo «Falta voucher»
- **Entonces** sin motivo se marca el campo; con motivo el Expediente queda Observado y sale de la bandeja hasta que se subsana
- **Cubre:** pasos 3, 4 y 6, flujo alterno 3a, RN-CU09-01
- **Prueba:** `tests/Feature/FlujoMesaAreaTest.php::test_observar_y_volver_a_bandeja_tras_subsanar`

### CA-CU09-02 Guarda el motivo y notifica

- **Dado** un Expediente presentado
- **Cuando** el Operador lo observa con motivo y detalle
- **Entonces** se guarda la Observación con ambos, el último Movimiento es «observado» y se notifica al Administrado
- **Cubre:** pasos 4 y 5
- **Prueba:** `tests/Feature/FlujoMesaAreaTest.php::test_observar_guarda_motivo_y_notifica`

### CA-CU09-03 No se observa un expediente que ya salió de Mesa de Partes

- **Dado** un Expediente que Mesa de Partes ya derivó a Secretaría Académica
- **Cuando** el Operador intenta observarlo
- **Entonces** el sistema le niega la acción (403)
- **Cubre:** flujo alterno 4a, RN-CU09-02
- **Prueba:** `tests/Feature/FlujoMesaAreaTest.php::test_recorrido_completo_hasta_finalizar`

### CA-CU09-04 La pantalla muestra las acciones de una en una

- **Dado** un Expediente recibido
- **Cuando** el Operador abre su revisión
- **Entonces** ve «¿Qué deseas hacer?» con las opciones Derivar y Observar
- **Cubre:** paso 1
- **Prueba:** `tests/Feature/InterfazDerivarTest.php::test_la_pantalla_muestra_acciones_de_a_una_y_derivar_simple`
