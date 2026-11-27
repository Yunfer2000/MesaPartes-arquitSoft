# CU04 Gestionar áreas

| Campo | Valor |
|---|---|
| Actor | Administrador |
| Paquete | Administración |
| Requisitos | RF20, RF10, RNF14 |
| Figuras ICONIX | Robustez Fig 22, Secuencia Fig 40, Prototipo Fig 18 |
| Responsable | Bloque 5 |

## Objetivo

El Administrador mantiene la lista de áreas del organigrama (y los tipos de documento) que se
usan para derivar los expedientes.

## Flujo básico

1. El Administrador abre la pantalla Áreas.
2. El sistema lista las Áreas con su estado.
3. El Administrador pulsa «Crear nueva área» (o «Editar» en un área existente); se abre un formulario flotante donde completa nombre, sigla, de qué unidad depende y responsable, y guarda.
4. El sistema valida que el nombre no se repita, guarda el Área y registra la acción en RegistroAuditoría.
5. Termina el caso de uso.

## Flujos alternos

- **3a.** Desactivar un área con expedientes en atención: el sistema lo impide hasta que se deriven.
- **3b.** El área elige depender de sí misma: el sistema lo marca y vuelve al paso 3.
- **4a.** Nombre o sigla repetidos: el sistema avisa dentro del formulario flotante, que sigue abierto, y vuelve al paso 3.

## Reglas de negocio

- **RN-CU04-01.** El nombre del área es único.
- **RN-CU04-02.** Un área no puede depender de sí misma («Depende de» guarda el organigrama 2026).
- **RN-CU04-03.** No se desactiva un área que tiene expedientes pendientes.
- **RN-CU04-04.** El sistema trae cargadas las 28 áreas activas del organigrama 2026; volver a cargarlas no las duplica.
- **RN-CU04-05.** Desde la misma sección el Administrador agrega tipos de documento.
- **RN-CU04-06.** El formulario de área se abre en una ventana flotante desde el botón «Crear nueva área»; la sigla se guarda en mayúsculas y sin espacios, y no se repite (issue #23).
- **RN-CU04-07.** Los tipos de documento se agregan desde el botón «Agregar tipo de documento», en un formulario flotante; el nombre no se repite y un tipo nuevo sale activo en el formulario público (issue #26).

## Criterios de aceptación

### CA-CU04-01 Crea áreas y tipos, y no desactiva un área con pendientes

- **Dado** el Administrador en la pantalla Áreas
- **Cuando** crea «Bienestar Estudiantil», intenta crear otra con el mismo nombre, agrega el tipo «Constancia» e intenta desactivar Secretaría Académica, que tiene un expediente
- **Entonces** la primera se guarda, la repetida se rechaza, el tipo se guarda y Secretaría sigue activa con un aviso; un área sin pendientes sí se desactiva
- **Cubre:** pasos 3 y 4, flujos alternos 3a y 4a, RN-CU04-01, RN-CU04-03, RN-CU04-05
- **Prueba:** `tests/Feature/AdminReportesTest.php::test_areas_y_tipos`

### CA-CU04-02 Asigna de qué unidad depende un área

- **Dado** el Administrador
- **Cuando** crea «Comisión de Titulación» dependiente de la Unidad Académica, y luego intenta que dependa de sí misma
- **Entonces** la dependencia se guarda, y la segunda vez el sistema marca el campo «Depende de»
- **Cubre:** paso 3, flujo alterno 3b, RN-CU04-02
- **Prueba:** `tests/Feature/InterfazDerivarTest.php::test_el_administrador_asigna_de_que_unidad_depende_un_area`

### CA-CU04-03 Vienen cargadas las áreas del organigrama

- **Dado** el sistema recién instalado
- **Cuando** se revisan las áreas
- **Entonces** hay 28 áreas activas (Mesa de Partes, Dirección General, Unidad Académica, los programas de estudio y las demás) y no existe el área genérica «PE»
- **Cubre:** paso 2, RN-CU04-04
- **Prueba:** `tests/Feature/AreasOrganigramaTest.php::test_se_cargan_las_areas_del_organigrama`

### CA-CU04-04 Volver a cargar no duplica

- **Dado** las áreas ya cargadas
- **Cuando** se vuelven a cargar los datos iniciales
- **Entonces** siguen siendo 28 áreas
- **Cubre:** RN-CU04-04
- **Prueba:** `tests/Feature/AreasOrganigramaTest.php::test_volver_a_cargar_no_duplica_areas`

### CA-CU04-05 El organigrama queda guardado

- **Dado** las áreas cargadas
- **Cuando** se revisa de quién depende cada una
- **Entonces** Bienestar depende de Dirección General, Tópico de Bienestar, y Mesa de Partes de la Unidad Administrativa; Dirección General no depende de nadie
- **Cubre:** RN-CU04-02, RN-CU04-04
- **Prueba:** `tests/Feature/InterfazDerivarTest.php::test_el_seeder_guarda_el_organigrama`

### CA-CU04-06 Crea un área desde el botón «Crear nueva área»

- **Dado** el Administrador en la pantalla Áreas
- **Cuando** pulsa «Crear nueva área», escribe «Oficina de Imagen Institucional» con sigla « oii » sin elegir de quién depende ni responsable, y guarda; luego intenta otra área con la sigla «OII»
- **Entonces** la pantalla trae el botón y el formulario flotante cerrado, el área se guarda con sigla «OII» y queda en la auditoría; la segunda se rechaza y el formulario flotante vuelve a abrirse con el error de la sigla
- **Cubre:** pasos 3 y 4, flujo alterno 4a, RN-CU04-06
- **Prueba:** `tests/Feature/PanelAdministradorTest.php::test_crea_un_area_desde_el_boton_crear_nueva_area`

### CA-CU04-07 Agrega un tipo de documento desde su botón

- **Dado** el Administrador en la pantalla Tipos de documento
- **Cuando** pulsa «Agregar tipo de documento», guarda «Constancia de estudios» y luego intenta agregar « Constancia de estudios » otra vez
- **Entonces** la pantalla trae el botón y el formulario flotante cerrado; el tipo se guarda activo, queda en la auditoría y aparece en el formulario público; el repetido se rechaza y el formulario flotante vuelve a abrirse con el error
- **Cubre:** pasos 3 y 4, flujo alterno 4a, RN-CU04-05, RN-CU04-07
- **Prueba:** `tests/Feature/PanelAdministradorTest.php::test_agrega_un_tipo_de_documento_desde_su_boton`
