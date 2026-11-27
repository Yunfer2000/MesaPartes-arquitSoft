# CU12 Actualizar estado del expediente

| Campo | Valor |
|---|---|
| Actor | Responsable de área |
| Paquete | Área |
| Requisitos | RF15, RF14, RNF08 |
| Figuras ICONIX | Robustez Fig 30, Secuencia Fig 48, Prototipo Fig 15 |
| Responsable | Bloque 4 |

## Objetivo

El área registra los avances de la atención para que quede constancia y el ciudadano vea que su
trámite se está moviendo.

## Flujo básico

1. El Responsable abre el Expediente desde la Bandeja del área.
2. El sistema muestra la pantalla Atención del expediente.
3. El Responsable pulsa «Registrar avance», escribe una nota y guarda.
4. El sistema registra un Movimiento (acción «avance») visible para el Administrado en CU07 como la burbuja «Avance».
5. Termina el caso de uso.

## Flujos alternos

- **1a.** El Expediente ya salió del área: el sistema no muestra «Registrar avance».
- **3a.** Nota vacía: el sistema pide completarla.

## Reglas de negocio

- **RN-CU12-01.** Solo registra avances el área donde está el Expediente, en estado En atención.
- **RN-CU12-02.** El avance no cambia el estado ni genera correo.

## Criterios de aceptación

### CA-CU12-01 Registra un avance con nota obligatoria

- **Dado** un Expediente En atención en Secretaría Académica
- **Cuando** el Responsable guarda un avance sin nota y luego con la nota «Se pidió el sílabo»
- **Entonces** sin nota se marca el campo; con nota el último Movimiento es «avance»
- **Cubre:** pasos 3 y 4, flujo alterno 3a
- **Prueba:** `tests/Feature/FlujoMesaAreaTest.php::test_recorrido_completo_hasta_finalizar`

### CA-CU12-02 El ciudadano ve el avance

- **Dado** un Expediente con un avance registrado
- **Cuando** el ciudadano consulta su trámite
- **Entonces** ve la burbuja «Avance» en su recorrido y el estado sigue En atención
- **Cubre:** paso 4, RN-CU12-02
- **Prueba:** `tests/Feature/FlujoMesaAreaTest.php::test_avance_se_ve_en_la_consulta_publica`

### CA-CU12-03 Sin el expediente en el área no hay avances

- **Dado** un Expediente que Secretaría derivó a Dirección
- **Cuando** Secretaría abre el Expediente
- **Entonces** no ve «Registrar avance»
- **Cubre:** flujo alterno 1a, RN-CU12-01
- **Prueba:** `tests/Feature/FlujoMesaAreaTest.php::test_area_rederiva_a_otra_area`
