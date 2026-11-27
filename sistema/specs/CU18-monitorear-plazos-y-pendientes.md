# CU18 Monitorear plazos y pendientes

| Campo | Valor |
|---|---|
| Actor | Administrador, Dirección General |
| Paquete | Consultas y reportes |
| Requisitos | RF24, RF25, RNF15 |
| Figuras ICONIX | Robustez CU18 (nueva, pendiente), Secuencia CU18 (nueva, pendiente) |
| Responsable | Bloque 5 |

## Objetivo

Que nadie olvide un trámite: cada expediente muestra un semáforo según los días hábiles que lleva
en su área, y la Dirección y el Administrador ven todos los pendientes en un panel.

## Flujo básico

1. El usuario abre la pantalla Pendientes.
2. El sistema lista los Expedientes que todavía alguien debe atender (todos menos Finalizado y Observado), del que más días lleva al que menos.
3. El sistema muestra, para cada uno, el área donde está, los días hábiles desde el último movimiento y el color del semáforo, con contadores por color.
4. El usuario filtra por área o por color.
5. Termina el caso de uso.

## Flujos alternos

- **1a.** Un usuario de Mesa de Partes o de un área abre el panel: el sistema le niega el acceso.

## Reglas de negocio

- **RN-CU18-01.** Semáforo por días hábiles (lunes a viernes) desde el último movimiento: verde de 0 a 3, ámbar de 4 a 5, rojo 6 o más. Los límites están en `config/plazos.php`.
- **RN-CU18-02.** Observado y Finalizado no llevan semáforo (el primero espera al ciudadano).
- **RN-CU18-03.** El semáforo también aparece en las bandejas de Mesa de Partes y de las áreas (columna «Plazo»).
- **RN-CU18-04.** Dirección General ve la lista completa, pero solo abre el detalle de los expedientes que pasaron por ella (RNF15).

## Criterios de aceptación

### CA-CU18-01 El color cambia según los días hábiles

- **Dado** un Expediente presentado un lunes
- **Cuando** se revisa el jueves, el lunes siguiente y el miércoles siguiente
- **Entonces** está verde (3 días), ámbar (5 días, sin contar sábado ni domingo) y rojo (7 días)
- **Cubre:** paso 3, RN-CU18-01
- **Prueba:** `tests/Feature/SemaforoTest.php::test_cambia_de_color_segun_los_dias_habiles`

### CA-CU18-02 Finalizado u observado no llevan semáforo

- **Dado** un Expediente con muchos días
- **Cuando** pasa a Finalizado o a Observado
- **Entonces** no tiene color
- **Cubre:** paso 2, RN-CU18-02
- **Prueba:** `tests/Feature/SemaforoTest.php::test_finalizado_u_observado_no_lleva_semaforo`

### CA-CU18-03 La bandeja de Mesa de Partes muestra el semáforo

- **Dado** un Expediente con 7 días hábiles en Mesa de Partes
- **Cuando** el Operador abre su bandeja
- **Entonces** ve el semáforo rojo
- **Cubre:** RN-CU18-03
- **Prueba:** `tests/Feature/SemaforoTest.php::test_bandeja_de_mesa_de_partes_muestra_el_semaforo`

### CA-CU18-04 Panel de pendientes para el Administrador y Dirección

- **Dado** un Expediente en rojo
- **Cuando** el Administrador y Dirección General abren `/pendientes`
- **Entonces** ambos lo ven con su semáforo, y el panel ofrece el filtro por área
- **Cubre:** pasos 1 a 4
- **Prueba:** `tests/Feature/SemaforoTest.php::test_panel_de_pendientes_para_administrador_y_direccion`

### CA-CU18-05 Otros usuarios no entran al panel

- **Dado** los usuarios de Secretaría Académica y de Mesa de Partes
- **Cuando** abren `/pendientes`
- **Entonces** el sistema les niega el acceso
- **Cubre:** flujo alterno 1a
- **Prueba:** `tests/Feature/SemaforoTest.php::test_otros_usuarios_no_entran_al_panel`
