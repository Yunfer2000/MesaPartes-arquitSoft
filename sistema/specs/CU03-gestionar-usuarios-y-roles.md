# CU03 Gestionar usuarios y roles

| Campo | Valor |
|---|---|
| Actor | Administrador |
| Paquete | Administración |
| Requisitos | RF03, RNF05, RNF14 |
| Figuras ICONIX | Robustez Fig 21, Secuencia Fig 39, Prototipo Fig 18 |
| Responsable | Bloque 5 |

## Objetivo

El Administrador crea, edita y desactiva las cuentas del personal y les asigna su rol y su área.
Cada cambio queda en la auditoría.

## Flujo básico

1. El Administrador abre la pantalla Usuarios.
2. El sistema lista los Usuarios internos con su Rol y Área.
3. El Administrador pulsa «Crear nuevo usuario» (o «Editar» en un usuario existente); se abre un formulario flotante donde completa nombres, apellidos, correo, Rol, Área y contraseña, y guarda.
4. El sistema valida los datos, guarda el Usuario y registra la acción en RegistroAuditoría.
5. Termina el caso de uso.

## Flujos alternos

- **3a.** Desactivar: el sistema desactiva el Usuario sin borrar su historial y lo registra en la auditoría.
- **3b.** El Administrador intenta desactivarse a sí mismo: el sistema lo impide con un aviso.
- **4a.** Correo repetido: el sistema avisa y vuelve al paso 3.
- **4b.** Rol Área sin área elegida: el sistema pide el área y vuelve al paso 3.
- **4c.** Datos con errores: el formulario flotante sigue abierto con los mensajes, sin perder lo escrito.

## Reglas de negocio

- **RN-CU03-01.** El correo es único entre los usuarios.
- **RN-CU03-02.** Un usuario con rol Área debe pertenecer a un área.
- **RN-CU03-03.** Los usuarios no se borran: se desactivan, y un usuario inactivo no puede ingresar (CU02).
- **RN-CU03-04.** Crear, editar y desactivar quedan en RegistroAuditoría, visible en la pantalla Auditoría.
- **RN-CU03-05.** El menú del Administrador muestra primero Usuarios y Áreas, luego Tipos de documento, Pendientes, Búsqueda, Reportes y Auditoría (issue #24).
- **RN-CU03-06.** El formulario de usuario se abre en una ventana flotante desde el botón «Crear nuevo usuario»; la contraseña inicial es obligatoria y tiene al menos 8 caracteres (issue #25).

## Criterios de aceptación

### CA-CU03-01 Crea, edita y desactiva con auditoría

- **Dado** el Administrador en la pantalla Usuarios
- **Cuando** crea a «Ana» con rol Área y área UA, cambia su nombre a «Ana María» y la desactiva
- **Entonces** los cambios se guardan, Ana queda inactiva y la auditoría registra «crear usuario», «editar usuario» y «desactivar usuario»
- **Cubre:** pasos 1 a 4, flujo alterno 3a, RN-CU03-03, RN-CU03-04
- **Prueba:** `tests/Feature/AdminReportesTest.php::test_crea_edita_y_desactiva_usuarios_con_auditoria`

### CA-CU03-02 Valida correo repetido, área obligatoria y no desactivarse a sí mismo

- **Dado** el formulario de nuevo usuario
- **Cuando** el Administrador elige rol Área sin área, usa un correo que ya existe o intenta desactivar su propia cuenta
- **Entonces** el sistema marca el área o el correo, o muestra un aviso, y no guarda el cambio
- **Cubre:** flujos alternos 3b, 4a y 4b, RN-CU03-01, RN-CU03-02
- **Prueba:** `tests/Feature/AdminReportesTest.php::test_crea_edita_y_desactiva_usuarios_con_auditoria`

### CA-CU03-03 Solo el Administrador entra

- **Dado** un usuario de Secretaría Académica
- **Cuando** abre `/admin/usuarios`
- **Entonces** el sistema le niega el acceso
- **Cubre:** RN-CU02-03
- **Prueba:** `tests/Feature/AccesoTest.php::test_un_rol_no_entra_a_pantallas_de_otro`

### CA-CU03-04 El menú del Administrador empieza por Usuarios y Áreas

- **Dado** el Administrador con sesión iniciada
- **Cuando** mira el menú lateral
- **Entonces** las opciones salen en este orden: Usuarios, Áreas, Tipos de documento, Pendientes, Búsqueda, Reportes y Auditoría, y al ingresar llega a Usuarios
- **Cubre:** paso 1, RN-CU03-05
- **Prueba:** `tests/Feature/PanelAdministradorTest.php::test_el_menu_del_administrador_empieza_por_usuarios_y_areas`

### CA-CU03-05 Crea un usuario desde el botón «Crear nuevo usuario»

- **Dado** el Administrador en la pantalla Usuarios
- **Cuando** pulsa «Crear nuevo usuario», registra a «Rosa Huamaní» con rol Mesa de Partes, área Mesa de Partes y una contraseña de 8 caracteres; luego intenta otro usuario con contraseña corta
- **Entonces** la pantalla trae el botón y el formulario flotante cerrado; Rosa queda activa, puede ingresar con su contraseña y la auditoría registra «crear usuario»; el segundo se rechaza y el formulario flotante vuelve a abrirse con el error de la contraseña
- **Cubre:** pasos 3 y 4, flujo alterno 4c, RN-CU03-04, RN-CU03-06
- **Prueba:** `tests/Feature/PanelAdministradorTest.php::test_crea_un_usuario_desde_el_boton_crear_nuevo_usuario`
