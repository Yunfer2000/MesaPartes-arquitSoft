# CU10 Derivar expediente

| Campo | Valor |
|---|---|
| Actor | Operador de Mesa de Partes, Responsable de área |
| Paquete | Mesa de Partes |
| Requisitos | RF10, RF17, RNF07, RNF08 |
| Figuras ICONIX | Robustez Fig 28, Secuencia Fig 46, Prototipo Fig 14 |
| Responsable | Bloque 4 |

## Objetivo

Enviar el Expediente al área que debe atenderlo y, si hace falta, enviar copia para conocimiento
a otras áreas.

## Flujo básico

1. El usuario elige «Derivar» en «¿Qué deseas hacer?» del detalle del Expediente.
2. El sistema muestra el formulario Derivación: un desplegable con las áreas principales (Dirección General, las unidades y las áreas sin jefe) y otro con las subáreas del área elegida, según el organigrama. No hay cuadro de búsqueda.
3. El usuario elige el Área, opcionalmente una de sus subáreas («¿A qué subárea en especial?»), escribe un proveído (la indicación para quien lo recibe) y, si quiere, marca áreas para «Enviar copia a otras áreas». Si eligió una subárea, esa subárea es el Área destino; si no, lo es el Área.
4. El sistema bloquea el Expediente, comprueba su estado y registra un Movimiento (área origen, área destino, usuario, fecha y hora, acción «derivado»); cambia el estado a Derivado.
5. El sistema registra una Copia (no leída) por cada área marcada.
6. El sistema muestra el Expediente en la bandeja «Por atender» del área destino y en «Para conocimiento» de las áreas con copia.
7. El sistema ejecuta CU16 para avisar al Administrado en qué área está su trámite.
8. Termina el caso de uso.

## Flujos alternos

- **3a.** Eligió su propia área como destino: el sistema lo impide con un aviso.
- **3b.** La subárea elegida no pertenece al Área: el sistema avisa «La subárea elegida no pertenece al área.» y no deriva. Si el Área no tiene subáreas, el formulario lo dice y el Expediente llega al Área.
- **3c.** Marcó copia para el área destino o para Mesa de Partes: el sistema avisa y no deriva.
- **4a.** El Expediente no está en un estado que se pueda derivar (no recepcionado, o otro usuario acaba de derivarlo): el sistema avisa y no registra nada.
- **6a.** El área con copia marca «Marcar como leído»: el sistema guarda quién y cuándo lo leyó. Una copia solo se lee, no se atiende.

## Reglas de negocio

- **RN-CU10-01.** Un Expediente tiene una sola área destino que lo atiende; las copias son solo para conocimiento.
- **RN-CU10-02.** No se deriva a la propia área, ni se envía copia al área destino ni a Mesa de Partes.
- **RN-CU10-03.** Solo se deriva un Expediente Recibido (en Mesa de Partes) o En atención (en el área); la derivación bloquea la fila (RNF07).
- **RN-CU10-04.** Las sugerencias del organigrama no bloquean: se puede derivar a cualquier área activa.
- **RN-CU10-05.** Solo el área que recibió la copia puede marcarla como leída.
- **RN-CU10-06.** Ya no se deriva a una persona: se deriva a un área o a una de sus subáreas del organigrama, y todo el personal de esa área o subárea lo ve en su bandeja.

## Criterios de aceptación

### CA-CU10-01 Deriva y lo recibe el área destino

- **Dado** un Expediente recibido en Mesa de Partes
- **Cuando** el Operador lo deriva a Secretaría Académica con un proveído
- **Entonces** queda Derivado, Secretaría es el área actual y el Movimiento sale de Mesa de Partes
- **Cubre:** pasos 3, 4 y 6
- **Prueba:** `tests/Feature/FlujoMesaAreaTest.php::test_recorrido_completo_hasta_finalizar`

### CA-CU10-02 Deriva a un área y envía copia a varias

- **Dado** un Expediente recibido
- **Cuando** el Operador lo deriva a Secretaría con copia a Dirección y a la Unidad Académica
- **Entonces** hay 2 copias no leídas; Dirección lo ve en «Para conocimiento», no puede confirmarlo, lo marca como leído una sola vez; Secretaría sí lo atiende
- **Cubre:** pasos 5 y 6, flujo alterno 6a, RN-CU10-01
- **Prueba:** `tests/Feature/DerivacionMultipleTest.php::test_deriva_a_un_area_y_envia_copia_a_varias`

### CA-CU10-03 Solo el área de la copia la marca

- **Dado** una copia enviada a la Unidad Académica
- **Cuando** Dirección intenta marcarla o abrir el Expediente
- **Entonces** el sistema le niega el acceso y la copia sigue sin leer
- **Cubre:** RN-CU10-05
- **Prueba:** `tests/Feature/DerivacionMultipleTest.php::test_solo_el_area_que_recibio_la_copia_puede_marcarla`

### CA-CU10-04 No envía copia al destino ni a Mesa de Partes

- **Dado** un Expediente recibido
- **Cuando** el Operador marca copia para el área destino o para Mesa de Partes
- **Entonces** el sistema avisa y no crea copias ni Movimiento
- **Cubre:** flujo alterno 3c, RN-CU10-02
- **Prueba:** `tests/Feature/DerivacionMultipleTest.php::test_no_se_envia_copia_al_area_destino_ni_a_mesa_de_partes`

### CA-CU10-05 No deriva a su propia área ni desde un estado inválido

- **Dado** un Expediente sin recepcionar y otro ya recibido
- **Cuando** el Operador deriva el primero, o deriva el segundo a Mesa de Partes
- **Entonces** el sistema avisa en ambos casos y el Expediente sigue Recibido
- **Cubre:** flujos alternos 3a y 4a, RN-CU10-02, RN-CU10-03
- **Prueba:** `tests/Feature/FlujoMesaAreaTest.php::test_no_deriva_a_su_propia_area_ni_desde_estado_invalido`

### CA-CU10-06 Derivar dos veces a la vez falla

- **Dado** un Expediente que un operador acaba de derivar
- **Cuando** un segundo operador intenta derivarlo al mismo tiempo
- **Entonces** el sistema lo rechaza («estado Derivado») y hay un solo Movimiento «derivado»
- **Cubre:** flujo alterno 4a, RN-CU10-03
- **Prueba:** `tests/Feature/DerivacionMultipleTest.php::test_derivar_dos_veces_el_mismo_expediente_falla`

### CA-CU10-07 Un área deriva a otra área

- **Dado** un Expediente en atención en Secretaría
- **Cuando** Secretaría lo deriva a Dirección
- **Entonces** Dirección lo ve en su bandeja y Secretaría ya no puede registrar avances
- **Cubre:** paso 3, flujo básico desde un área
- **Prueba:** `tests/Feature/FlujoMesaAreaTest.php::test_area_rederiva_a_otra_area`

### CA-CU10-08 No deriva a una subárea de otra área

- **Dado** un Expediente recibido
- **Cuando** el Operador elige la Unidad Administrativa y la subárea Tópico (que es de Bienestar), y luego solo la Unidad Administrativa
- **Entonces** primero el sistema avisa «La subárea elegida no pertenece al área.» y no deriva; luego el Expediente llega a la Unidad Administrativa
- **Cubre:** flujo alterno 3b, RN-CU10-06
- **Prueba:** `tests/Feature/MejorasSistemaTest.php::test_sin_subarea_llega_al_area_y_no_acepta_subarea_de_otra_area`

### CA-CU10-09 Sugiere áreas según el organigrama

- **Dado** Mesa de Partes, Bienestar y el Tópico
- **Cuando** el sistema arma la lista de áreas para derivar
- **Entonces** sugiere las áreas cercanas en el organigrama y agrupa todas las demás activas (menos la propia)
- **Cubre:** paso 2, RN-CU10-04
- **Prueba:** `tests/Feature/InterfazDerivarTest.php::test_sugiere_areas_segun_quien_deriva`

### CA-CU10-10 El formulario es simple y muestra las sugerencias

- **Dado** un Expediente recibido
- **Cuando** el Operador abre su revisión
- **Entonces** ve las acciones de a una y «Enviar copia a otras áreas (opcional)»
- **Cubre:** pasos 1 y 2
- **Prueba:** `tests/Feature/InterfazDerivarTest.php::test_la_pantalla_muestra_acciones_de_a_una_y_derivar_simple`

### CA-CU10-11 Deriva a una subárea sin buscador ni personas

- **Dado** un Expediente recibido en Mesa de Partes
- **Cuando** el Operador abre Derivar y lo envía a la Unidad Administrativa, subárea Caja, con un proveído
- **Entonces** no ve el cuadro «Escribe para buscar» ni «¿A una persona en especial?»; ve «¿A qué subárea en especial? (opcional)» con Caja dentro de la Unidad Administrativa; el Expediente queda Derivado y Caja es el área actual
- **Cubre:** pasos 2 y 3, RN-CU10-06
- **Prueba:** `tests/Feature/MejorasSistemaTest.php::test_derivar_muestra_subareas_sin_buscador_ni_personas`, `tests/Feature/MejorasSistemaTest.php::test_deriva_a_una_subarea_y_le_llega_a_esa_subarea`
