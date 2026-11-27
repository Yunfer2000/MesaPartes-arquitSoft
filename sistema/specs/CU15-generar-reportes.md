# CU15 Generar reportes

| Campo | Valor |
|---|---|
| Actor | Administrador, Dirección General, Responsable de área |
| Paquete | Consultas y reportes |
| Requisitos | RF21, RNF15 |
| Figuras ICONIX | Robustez Fig 33, Secuencia Fig 51, Prototipo Fig 17 |
| Responsable | Bloque 5 |

## Objetivo

Ver cuántos trámites se presentaron, recibieron, derivaron, observaron y finalizaron en un
periodo, y descargarlo.

## Flujo básico

1. El usuario abre la pantalla Reportes.
2. El usuario elige el periodo (por defecto, el mes actual) y opcionalmente un Área.
3. El sistema calcula los totales desde Expediente y Movimiento (presentados, recibidos, derivados, observados, finalizados y pendientes) y muestra las derivaciones por área y el tiempo promedio de atención. Ya no muestra el panel «Estado actual de los expedientes», que repetía los contadores.
4. El usuario puede imprimirlo en PDF desde el navegador o descargarlo en Excel (.xlsx).
5. Termina el caso de uso.

## Flujos alternos

- **3a.** Periodo sin datos: el sistema muestra el reporte con totales en cero.

## Reglas de negocio

- **RN-CU15-01.** Un Responsable de área solo ve los datos de su área, aunque elija otra.
- **RN-CU15-02.** La descarga es un archivo Excel (.xlsx) con los totales, las derivaciones por área y el tiempo promedio.

## Criterios de aceptación

### CA-CU15-01 Reporte, Excel, periodo vacío y datos por área

- **Dado** un Expediente presentado, recibido y derivado a Secretaría Académica este mes
- **Cuando** el Administrador abre el reporte, lo descarga en Excel y elige un periodo de 2020; y luego Dirección abre el reporte
- **Entonces** el Administrador ve 1 presentado, 1 recibido y 1 derivado; la descarga es un .xlsx; el periodo de 2020 da todo en cero; y Dirección ve 0 presentados
- **Cubre:** pasos 2 a 4, flujo alterno 3a, RN-CU15-01, RN-CU15-02
- **Prueba:** `tests/Feature/AdminReportesTest.php::test_reportes_y_descarga_excel`

### CA-CU15-02 Sin panel repetido y Excel con los totales

- **Dado** un Expediente derivado a Secretaría Académica este mes
- **Cuando** el Administrador abre el reporte y pulsa «Descargar en Excel»
- **Entonces** no ve «Estado actual de los expedientes» ni la palabra CSV; el archivo .xlsx abre y trae «Presentados 1» y «Derivados 1»
- **Cubre:** pasos 3 y 4, RN-CU15-02
- **Prueba:** `tests/Feature/MejorasSistemaTest.php::test_reporte_sin_panel_repetido_y_descarga_en_excel`
