# CU14 Buscar expedientes

| Campo | Valor |
|---|---|
| Actor | Personal interno (Mesa de Partes, Área, Administrador) |
| Paquete | Consultas y reportes |
| Requisitos | RF18, RF14, RNF15 |
| Figuras ICONIX | Robustez Fig 32, Secuencia Fig 50, Prototipo Fig 16 |
| Responsable | Bloque 5 |

## Objetivo

El personal encuentra expedientes con filtros y abre su historial, viendo solo lo que su rol le
permite.

## Flujo básico

1. El usuario abre «Historial de expedientes» (antes «Búsqueda»). El sistema muestra los 15 últimos Expedientes que el usuario puede ver, del más reciente al más antiguo.
2. El usuario escribe en la búsqueda básica el N.° de expediente o el documento del Administrado (DNI, carné, RUC o DNI del representante). Si necesita más, pulsa «Búsqueda por filtros» y usa nombre del Administrado, asunto, rango de fechas, Área, TipoDocumento o estado (issue #30).
3. El sistema lista los Expedientes que cumplen los filtros y que el usuario puede ver según su Rol.
4. El usuario elige uno y el sistema muestra su detalle con el historial de Movimientos.
5. Termina el caso de uso.

## Flujos alternos

- **3a.** Sin resultados: el sistema dice «No se encontraron» y conserva los filtros.
- **4a.** El usuario abre un Expediente que su área nunca recibió: el sistema le niega el acceso.

## Reglas de negocio

- **RN-CU14-03.** La búsqueda básica encuentra por parte del N.° de expediente (por ejemplo «000012») o por el documento exacto del Administrado o de su representante. Los filtros quedan plegados detrás del botón «Búsqueda por filtros» y se despliegan solos cuando hay alguno en uso.
- **RN-CU14-01.** Mesa de Partes y el Administrador ven todos los expedientes; un área solo ve los que pasaron por ella (RNF15).
- **RN-CU14-02.** Sin sesión no se entra a la búsqueda del personal.
- **RN-CU14-04.** Sin búsqueda, la pantalla muestra los 15 últimos Expedientes visibles para el rol; al buscar, muestra los resultados en su lugar.

## Criterios de aceptación

### CA-CU14-01 Busca según el rol

- **Dado** un Expediente derivado a Secretaría Académica
- **Cuando** el Administrador busca por número y por estado Finalizado, Secretaría busca por asunto y Dirección busca por asunto
- **Entonces** el Administrador lo encuentra y abre su historial, por estado Finalizado no hay resultados, Secretaría lo encuentra y Dirección no lo ve ni lo abre; sin sesión se pide ingresar
- **Cubre:** pasos 2 a 4, flujos alternos 3a y 4a, RN-CU14-01, RN-CU14-02
- **Prueba:** `tests/Feature/AdminReportesTest.php::test_busqueda_segun_rol`

### CA-CU14-02 Búsqueda básica por expediente o DNI y botón de filtros

- **Dado** un Expediente de persona natural y otro de persona jurídica
- **Cuando** el Administrador escribe en la búsqueda básica parte del número, el DNI, el RUC o el DNI del representante, o un DNI que no existe, y luego abre «Búsqueda por filtros»
- **Entonces** encuentra cada Expediente solo con su dato, con el DNI inexistente dice «No se encontraron», los filtros están plegados en la búsqueda básica y abiertos cuando se usa un filtro
- **Cubre:** pasos 2 y 3, flujo alterno 3a, RN-CU14-03
- **Prueba:** `tests/Feature/BusquedaBasicaTest.php::test_busqueda_basica_por_expediente_o_documento`
- **Prueba:** `tests/Feature/BusquedaBasicaTest.php::test_boton_busqueda_por_filtros`

### CA-CU14-03 Historial con los 15 últimos expedientes

- **Dado** 17 Expedientes presentados, y uno de ellos derivado a Secretaría Académica
- **Cuando** Mesa de Partes abre «Historial de expedientes», luego busca el primero; y Secretaría abre el historial
- **Entonces** Mesa ve «Últimos 15 expedientes» empezando por el más reciente, sin el primero, con la búsqueda básica y el botón de filtros; al buscar ve el primero; Secretaría solo ve el que pasó por su área
- **Cubre:** pasos 1 a 3, RN-CU14-01, RN-CU14-04
- **Prueba:** `tests/Feature/MejorasSistemaTest.php::test_historial_muestra_los_15_ultimos_y_mantiene_la_busqueda`, `tests/Feature/MejorasSistemaTest.php::test_un_area_solo_ve_en_el_historial_lo_que_paso_por_ella`
