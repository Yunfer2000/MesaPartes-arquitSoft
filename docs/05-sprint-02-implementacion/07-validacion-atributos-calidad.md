# Validación de atributos de calidad

En el Sprint 1 se definieron los atributos de calidad (AC01 a AC08) y sus escenarios (EQ01 a EQ07). En este sprint cada uno se comprueba con una herramienta y deja una evidencia.

## 1. Resumen

| Escenario | Atributo | Herramienta | Prueba | Medida esperada | Resultado |
|---|---|---|---|---|---|
| EQ01 | Rendimiento | **k6** | `pruebas/k6/carga-api.js` hasta 2,000 usuarios virtuales | Consulta p95 de 3 s o menos | *Completar con resultados-k6.md* |
| EQ01 | Rendimiento | PHPUnit | `ConsultaApiTest::test_la_consulta_usa_pocas_consultas_sql_sin_n_mas_1` | 5 consultas SQL o menos | 4 consultas. Cumple |
| AC03 | Escalabilidad | k6 + caché | Catálogo en la misma prueba de carga | p95 de 1 s o menos, 0 consultas SQL en caché | *Completar con resultados-k6.md* |
| EQ02 | Seguridad | PHPUnit y Postman | 401 sin token, 403 sin rol, 429 al pasar el límite, sin correo en el JSON | Ninguna petición no autorizada obtiene datos | Cumple (pruebas en verde) |
| EQ03 | Disponibilidad | k6 | Tasa de errores durante la carga | Menos de 1 % de errores | *Completar con resultados-k6.md* |
| EQ04 | Trazabilidad | PHPUnit | `ConsultaApiTest::test_el_recorrido_se_actualiza_al_derivar`, `DerivacionMultipleTest` | El 100 % de las derivaciones tiene su movimiento | Cumple |
| EQ05 | Integridad | PHPUnit | `RecepcionMesaTest::test_no_se_recepciona_dos_veces` (bloqueo de fila) | Nunca dos recepciones | Cumple |
| EQ06 | Autenticidad | PHPUnit y Postman | `VerificacionApiTest`, `VerificacionTest` | Código válido = auténtico con huella SHA-256 | Cumple |
| AC07 | Mantenibilidad | PHPUnit, CI y cobertura | 164 pruebas en GitHub Actions; `ContratoOpenApiTest` y `EspecificacionesTest` | Todo en verde en cada push | Cumple. Cobertura: *completar* |
| Contrato | Interoperabilidad | Postman y Redocly | Colección con validación del esquema JSON; `redocly lint` | Las respuestas siguen `openapi.yaml` | Postman: 15 peticiones, 28 pruebas, 0 errores, respuesta promedio 316 ms. Cumple |
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
$env:API_LIMITE_GENERAL = "1000000"; $env:API_LIMITE_CONSULTAS = "1000000"; $env:API_LIMITE_VERIFICACIONES = "1000000"
php artisan db:seed --class=CargaK6Seeder
php artisan serve
# En otra ventana de PowerShell, desde la raíz del repositorio:
k6 run -e MAX_VUS=2000 pruebas/k6/carga-api.js
```

En una PC con `php artisan serve` conviene empezar con `-e MAX_VUS=200`. El servidor de desarrollo de PHP no es un servidor de producción; los 2,000 usuarios se corren en GitHub Actions o sobre el sistema desplegado en el Sprint 3.

### 3.3 Resultados

*Pegar aquí la tabla de `resultados-k6.md` y una captura del resumen de GitHub Actions.*

| Métrica | Valor |
|---|---|
| Peticiones totales | |
| Peticiones por segundo | |
| Consulta p95 | |
| Catálogo p95 | |
| Errores | |

### 3.4 Por qué los límites de peticiones no cuentan como fallo

En uso normal, cada IP puede hacer 10 consultas por minuto. k6 lanza todas las peticiones desde una sola máquina, así que sin subir los límites casi todas recibirían 429. Esto **confirma que la protección funciona** (EQ02), pero no mide el rendimiento. Por eso la prueba de carga sube los límites con variables de entorno, y la prueba de seguridad (`ConsultaApiTest::test_limita_a_10_consultas_por_minuto`) comprueba el límite real.

## 4. Otras validaciones

| Validación | Herramienta | Dónde |
|---|---|---|
| Contrato OpenAPI sin errores | Redocly CLI | Trabajo `contrato-openapi` del CI |
| Cada ruta documentada | PHPUnit | `ContratoOpenApiTest` |
| Funciona con el motor de producción | PHPUnit sobre MySQL 8 | Trabajo `pruebas-mysql` del CI |
| Especificaciones al día con el código | PHPUnit | `EspecificacionesTest` |
