# Validación de atributos de calidad

En el Sprint 1 se definieron los atributos de calidad (AC01 a AC08) y sus escenarios (EQ01 a EQ07). En este sprint cada uno se comprueba con una herramienta y deja una evidencia.

## 1. Resumen

| Escenario | Atributo | Herramienta | Prueba | Medida esperada | Resultado |
|---|---|---|---|---|---|
| EQ01 | Rendimiento | **k6** | `pruebas/k6/carga-api.js` hasta 2,000 usuarios virtuales | Consulta p95 de 3 s o menos | 2,000 usuarios: p95 = 6.15 s. **No cumple** con el servidor de desarrollo (ver 3.3); se repite en el Sprint 3 con el servidor de producción |
| EQ01 | Rendimiento | PHPUnit | `ConsultaApiTest::test_la_consulta_usa_pocas_consultas_sql_sin_n_mas_1` | 5 consultas SQL o menos | 4 consultas. Cumple |
| AC03 | Escalabilidad | k6 + caché | Catálogo en la misma prueba de carga | p95 de 1 s o menos, 0 consultas SQL en caché | 66,943 peticiones atendidas sin errores a 222 por segundo; p95 = 6.15 s. 0 consultas SQL en caché: cumple. Tiempo: no cumple con el servidor de desarrollo |
| EQ02 | Seguridad | PHPUnit y Postman | 401 sin token, 403 sin rol, 429 al pasar el límite, sin correo en el JSON | Ninguna petición no autorizada obtiene datos | Cumple (pruebas en verde) |
| EQ03 | Disponibilidad | k6 | Tasa de errores durante la carga | Menos de 1 % de errores | **0.00 % de errores** con 2,000 usuarios. Cumple |
| EQ04 | Trazabilidad | PHPUnit | `ConsultaApiTest::test_el_recorrido_se_actualiza_al_derivar`, `DerivacionMultipleTest` | El 100 % de las derivaciones tiene su movimiento | Cumple |
| EQ05 | Integridad | PHPUnit | `RecepcionMesaTest::test_no_se_recepciona_dos_veces` (bloqueo de fila) | Nunca dos recepciones | Cumple |
| EQ06 | Autenticidad | PHPUnit y Postman | `VerificacionApiTest`, `VerificacionTest` | Código válido = auténtico con huella SHA-256 | Cumple |
| AC07 | Mantenibilidad | PHPUnit, CI y cobertura | 165 pruebas en GitHub Actions; `ContratoOpenApiTest` y `EspecificacionesTest` | Todo en verde en cada push | Cumple. 165 pruebas, cobertura de líneas 96.89 % |
| Contrato | Interoperabilidad | Postman y Redocly | Colección con validación del esquema JSON; `redocly lint` | Las respuestas siguen `openapi.yaml` | Postman: 15 peticiones, 28 pruebas, 0 errores, respuesta promedio 316 ms en la PC y 44 ms en GitHub Actions. Cumple |
| EQ07 | Usabilidad | Prueba con usuarios | Tarea guiada: presentar y consultar un trámite desde el celular | Lo completa sin ayuda | Se aplica en el Sprint 3 con el sistema desplegado |

## 2. Prueba funcional y de contrato con Postman

Archivo: [`pruebas/postman/MesaDePartes-API-v1.postman_collection.json`](../../pruebas/postman/MesaDePartes-API-v1.postman_collection.json)

| Carpeta | Peticiones | Qué comprueba |
|---|---|---|
| 1. Token | Pedir token | 201 y guarda el token para las siguientes |
| 2. Tipos de documento | Listar, registrar, consultar, datos inválidos, sin token, actualizar, eliminar, consultar eliminado | 200, 201, 422, 401, 200, 204, 404 y que el JSON cumpla el esquema del contrato |
| 3. Consultas y verificaciones | Consultar expediente, DNI que no coincide, verificar código inexistente | 200 con recorrido, 404 sin decir qué dato falló, 404 con `autentico = false` |
| 4. Cerrar sesión | Revocar token | 204 |

Cómo correrla:

1. Levantar el sistema (`php artisan serve`) con los datos de prueba: `php artisan migrate --seed` y `php artisan db:seed --class=CargaK6Seeder`.
2. En Postman: **Import**, elegir el archivo de la colección.
3. En la pestaña **Variables** de la colección, poner en `numeroExpediente` el número que mostró el seeder (por ejemplo `EXP-2026-000001`).
4. Clic derecho en la colección, **Run collection**, **Run**. Tomar captura de la pantalla de resultados.

### Resultado de la ejecución en la PC (26/11/2026, servidor `php artisan serve`)

| Medida | Valor |
|---|---|
| Peticiones | 15 |
| Pruebas | 28 (27 pasaron en la primera corrida) |
| Errores | 0 |
| Tiempo promedio de respuesta | 316 ms |
| Duración total | 6.5 s |

La única prueba que no pasó en la primera corrida fue el tiempo de `POST /tokens` (2,379 ms frente a un límite inicial de 1 s). Tiene dos causas esperadas: es la **primera petición** de la corrida (Laravel arranca y PHP compila el código) y la contraseña se comprueba con **bcrypt**, que es lento a propósito para frenar ataques de fuerza bruta. El límite se ajustó a 3 s, el mismo de RNF04. Las demás peticiones respondieron entre 54 y 284 ms.

En GitHub Actions el trabajo `pruebas-postman` hace lo mismo con **Newman** (Postman por línea de comandos) en cada push y deja el artefacto `resultados-postman`.

## 3. Prueba de carga con k6 (hasta 2,000 usuarios virtuales)

Archivo: [`pruebas/k6/carga-api.js`](../../pruebas/k6/carga-api.js)

### 3.1 Diseño de la prueba

| Etapa | Usuarios virtuales | Duración |
|---|---|---|
| Calentamiento | 100 (5 %) | 30 s |
| Carga baja | 500 (25 %) | 1 min |
| Carga media | 1,000 (50 %) | 1 min |
| Carga máxima | 2,000 (100 %) | 1 min |
| Sostenida | 2,000 | 1 min |
| Bajada | 0 | 30 s |

Mezcla de peticiones, parecida al uso real:

| Petición | Porcentaje | Por qué |
|---|---|---|
| `GET /tipos-documento` | 50 % | Lo pide cada visita al formulario |
| `POST /consultas` | 40 % | Los ciudadanos revisan su trámite |
| `GET /verificaciones/{codigo}` | 10 % | Terceros verifican constancias |

Umbrales (si uno falla, k6 termina con error):

| Métrica | Umbral | Escenario |
|---|---|---|
| `tiempo_consulta_expediente` p95 | menos de 3,000 ms | EQ01 / RNF04 |
| `tiempo_catalogo_tipos` p95 | menos de 1,000 ms | AC03 (caché) |
| `http_req_failed` | menos de 1 % | EQ03 |
| `errores_funcionales` | menos de 1 % | Respuestas con el contenido correcto |

### 3.2 Cómo correrla

**Opción A: en GitHub (recomendada).** En el repositorio, pestaña **Actions**, flujo **CI - Mesa de Partes Virtual**, botón **Run workflow**, marcar `carga_k6` y dejar `max_vus` en 2000. Al terminar, el resumen muestra la tabla de resultados y se descarga el artefacto `resultados-k6`.

**Opción B: en la PC.**

```powershell
winget install k6 --source winget
cd sistema
# Subir los límites para que la prueba no reciba 429 (todas las peticiones salen de la misma IP)
$ruta = (Resolve-Path .env).Path
$texto = [IO.File]::ReadAllText($ruta) -replace '(?m)^API_LIMITE_(GENERAL|CONSULTAS|VERIFICACIONES)=.*$', 'API_LIMITE_$1=1000000'
[IO.File]::WriteAllText($ruta, $texto, (New-Object Text.UTF8Encoding $false))
php artisan db:seed --class=CargaK6Seeder
php artisan serve
# En otra ventana de PowerShell, desde la raíz del repositorio:
k6 run -e MAX_VUS=2000 pruebas/k6/carga-api.js
```

En una PC con `php artisan serve` conviene empezar con `-e MAX_VUS=200`. El servidor de desarrollo de PHP no es un servidor de producción; los 2,000 usuarios se corren en GitHub Actions o sobre el sistema desplegado en el Sprint 3.

### 3.3 Resultados

Se hicieron tres corridas. Las tres sirven como evidencia, cada una de un atributo distinto.

| Corrida | Dónde | Usuarios | Peticiones | Por segundo | Errores | Consulta p95 | Catálogo p95 | Qué demuestra |
|---|---|---|---|---|---|---|---|---|
| 1 | PC, límites normales | 200 | 15,326 | 50.8 | 99.5 % (429) | 781 ms | 808 ms | El límite de peticiones protege la API (EQ02, ver 3.4) |
| 2 | PC, `php artisan serve` en Windows | 200 | 3,266 | 10.8 | 50.4 % | 16.8 s | 16.9 s | Punto de quiebre del servidor de desarrollo de Windows, que atiende una petición a la vez |
| 3 | GitHub Actions, Linux con 16 procesos PHP | **2,000** | **66,943** | **221.9** | **0.00 %** | 6.15 s | 6.15 s | Con 2,000 usuarios el sistema no pierde ninguna petición; el tiempo de espera sube por el límite del servidor de prueba |

Umbrales de la corrida 3 (27/11/2026, ejecución manual #5 del CI):

| Umbral | Resultado |
|---|---|
| `http_req_failed` menos de 1 % | **Cumple** (0.00 %) |
| `errores_funcionales` menos de 1 % | **Cumple** (0.00 %) |
| `tiempo_consulta_expediente` p95 menos de 3 s | No cumple (6.15 s) |
| `tiempo_catalogo_tipos` p95 menos de 1 s | No cumple (6.15 s) |

**Interpretación.** El sistema respondió bien a las 66,943 peticiones, sin errores. Que el catálogo, que sale de la caché y no consulta la base de datos, tarde lo mismo que la consulta (6.15 s) muestra que el cuello de botella no es el código ni MySQL: es la **cola de espera del servidor de prueba**. `php artisan serve` llegó a unas 222 peticiones por segundo, y 2,000 usuarios con 1 a 2 s entre clics piden más de 1,000 por segundo. Por eso las peticiones esperan su turno.

Con ese techo, el servidor de prueba atiende sin cola a unos **350 usuarios activos a la vez** (estimación: 222 peticiones por segundo por 1.6 s por usuario). Es más de lo que necesita el instituto en horario normal.

**Decisión para el Sprint 3.** El despliegue usará un servidor web de producción (Nginx o Apache con PHP-FPM y OPcache), que atiende muchas peticiones en paralelo. La misma prueba k6 con 2,000 usuarios se repetirá sobre ese servidor para validar EQ01 con la meta de 3 s.

El CI marca esta corrida en rojo porque k6 termina con el código 99 cuando un umbral no se cumple. Es el comportamiento esperado: así el CI avisa que la meta de tiempo todavía no se alcanza.

### 3.4 Por qué los límites de peticiones no cuentan como fallo

En uso normal, cada IP puede hacer 10 consultas por minuto. k6 lanza todas las peticiones desde una sola máquina, así que sin subir los límites casi todas recibirían 429. Esto **confirma que la protección funciona** (EQ02), pero no mide el rendimiento. Por eso la prueba de carga sube los límites con variables de entorno, y la prueba de seguridad (`ConsultaApiTest::test_limita_a_10_consultas_por_minuto`) comprueba el límite real.

Evidencia: la primera corrida en la PC (26/11/2026, 200 usuarios virtuales, límites normales) hizo 15,326 peticiones y el 99.5 % recibió 429. La API siguió respondiendo rápido (p95 de la consulta = 781 ms) mientras rechazaba el exceso: el límite protege al sistema de una avalancha de peticiones desde una misma IP.

## 4. Otras validaciones

| Validación | Herramienta | Dónde |
|---|---|---|
| Contrato OpenAPI sin errores | Redocly CLI | Trabajo `contrato-openapi` del CI |
| Cada ruta documentada | PHPUnit | `ContratoOpenApiTest` |
| Funciona con el motor de producción | PHPUnit sobre MySQL 8 | Trabajo `pruebas-mysql` del CI |
| Especificaciones al día con el código | PHPUnit | `EspecificacionesTest` |
