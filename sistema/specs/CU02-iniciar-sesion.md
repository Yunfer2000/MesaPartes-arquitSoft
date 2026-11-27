# CU02 Iniciar sesión

| Campo | Valor |
|---|---|
| Actor | Personal interno (Mesa de Partes, Área, Administrador) |
| Paquete | Acceso y seguridad |
| Requisitos | RF02, RNF05, RNF17 |
| Figuras ICONIX | Robustez Fig 20, Secuencia Fig 38, Prototipo Fig 9 |
| Responsable | Bloque 5 |

## Objetivo

El personal de la institución entra al sistema con su correo y contraseña y llega a la pantalla
de su rol. El ciudadano ya no inicia sesión: presenta y consulta sin cuenta.

## Flujo básico

1. El usuario abre la pantalla Inicio de sesión (en el subdominio del personal).
2. El usuario ingresa su correo y contraseña y pulsa «Ingresar».
3. El sistema valida las credenciales contra el Usuario y obtiene su Rol.
4. El sistema muestra la pantalla que corresponde al Rol: Bandeja de Mesa de Partes, Bandeja del área o Usuarios (Administrador).
5. Termina el caso de uso.

## Flujos alternos

- **3a.** Credenciales incorrectas: el sistema muestra un mensaje de error y vuelve al paso 2.
- **3b.** Usuario inactivo: el sistema no lo deja entrar.
- **3c.** Cinco intentos fallidos seguidos: el sistema bloquea el ingreso por unos segundos y dice cuánto esperar.

## Reglas de negocio

- **RN-CU02-01.** Ya no existen el registro de cuentas ni «Mis trámites» (CU01 eliminado).
- **RN-CU02-02.** Con `APP_STAFF_DOMAIN`, el login y las pantallas del personal solo responden en ese subdominio; en el dominio público dan 404 y no hay enlaces de login (RNF17).
- **RN-CU02-03.** Cada rol solo entra a sus pantallas; sin sesión, el sistema pide ingresar.
- **RN-CU02-04.** Máximo 5 intentos fallidos por correo y dirección IP antes del bloqueo temporal (RNF05).
- **RN-CU02-05.** La pantalla de ingreso es solo para el personal: no ofrece presentar ni consultar trámites (issue #33).

## Criterios de aceptación

### CA-CU02-01 Cada rol entra a su pantalla

- **Dado** los usuarios de Mesa de Partes, Secretaría Académica y Administración
- **Cuando** cada uno ingresa con su correo y contraseña correctos
- **Entonces** el sistema lo lleva a `/mesa-de-partes`, `/area` o `/admin/usuarios`, según su rol
- **Cubre:** pasos 2 a 4
- **Prueba:** `tests/Feature/AccesoTest.php::test_cada_rol_entra_a_su_pantalla`

### CA-CU02-02 Credenciales incorrectas o cuenta inactiva

- **Dado** una contraseña equivocada, o un usuario desactivado
- **Cuando** intenta ingresar
- **Entonces** el sistema muestra el error en el campo correo y no inicia sesión
- **Cubre:** flujos alternos 3a y 3b
- **Prueba:** `tests/Feature/AccesoTest.php::test_credenciales_incorrectas_y_cuenta_inactiva`

### CA-CU02-03 Bloqueo tras cinco intentos fallidos

- **Dado** un usuario que se equivocó de contraseña 5 veces seguidas
- **Cuando** intenta ingresar otra vez, incluso con la contraseña correcta
- **Entonces** el sistema responde «Demasiados intentos» y no inicia sesión
- **Cubre:** flujo alterno 3c, RN-CU02-04
- **Prueba:** `tests/Feature/AccesoTest.php::test_bloquea_tras_cinco_intentos_fallidos`

### CA-CU02-04 Ya no hay cuentas de ciudadano

- **Dado** la página de inicio de sesión
- **Cuando** alguien busca `/registro` o `/mis-tramites`
- **Entonces** el sistema responde 404 y el login no muestra «Crear cuenta»
- **Cubre:** RN-CU02-01
- **Prueba:** `tests/Feature/AccesoTest.php::test_ya_no_existe_el_registro_de_cuentas`

### CA-CU02-05 Un rol no entra a pantallas de otro

- **Dado** un usuario de Secretaría Académica con sesión iniciada
- **Cuando** abre las pantallas de Mesa de Partes o de Administración, y luego cierra sesión y abre su bandeja
- **Entonces** el sistema le niega el acceso (403) y, sin sesión, lo manda a ingresar
- **Cubre:** RN-CU02-03
- **Prueba:** `tests/Feature/AccesoTest.php::test_un_rol_no_entra_a_pantallas_de_otro`

### CA-CU02-06 La página pública no muestra el login

- **Dado** el dominio público y el subdominio del personal configurados
- **Cuando** alguien abre `/ingresar`, `/mesa-de-partes` o `/admin/usuarios` en el dominio público
- **Entonces** el sistema responde 404 y la portada no muestra enlaces de login
- **Cubre:** RN-CU02-02
- **Prueba:** `tests/Feature/SubdominioTest.php::test_la_pagina_publica_no_muestra_ni_abre_el_login`

### CA-CU02-07 El personal ingresa por su subdominio

- **Dado** el subdominio del personal
- **Cuando** el operador de Mesa de Partes abre la raíz, ingresa y vuelve a la raíz
- **Entonces** primero va al login, luego a su bandeja, y la raíz lo lleva a su bandeja
- **Cubre:** pasos 1 a 4, RN-CU02-02
- **Prueba:** `tests/Feature/SubdominioTest.php::test_el_personal_ingresa_por_su_subdominio`

### CA-CU02-08 Sin sesión el subdominio pide ingresar

- **Dado** una persona sin sesión en el subdominio del personal
- **Cuando** abre `/mesa-de-partes`
- **Entonces** el sistema la lleva a `/ingresar`
- **Cubre:** RN-CU02-03
- **Prueba:** `tests/Feature/SubdominioTest.php::test_sin_sesion_el_subdominio_pide_ingresar`

### CA-CU02-09 El login no ofrece presentar trámites

- **Dado** la pantalla de ingreso del personal
- **Cuando** alguien la abre
- **Entonces** ve «Acceso del personal» y no aparecen «Presentar trámite» ni el enlace al formulario público
- **Cubre:** RN-CU02-05
- **Prueba:** `tests/Feature/LoginPersonalTest.php::test_el_login_no_muestra_presentar_tramite`
