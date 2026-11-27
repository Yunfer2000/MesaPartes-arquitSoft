# Resultado de la prueba de carga k6

- Usuarios virtuales máximos: 10
- Peticiones totales: 996 (3.3 por segundo)
- Peticiones fallidas: 52.81 %
- Consulta de expediente: p95 = 64 ms, máximo = 119 ms (meta: p95 < 3000 ms)
- Catálogo de tipos: p95 = 61 ms (meta: p95 < 1000 ms)
- Errores funcionales: 52.81 %

| Umbral | Resultado |
|---|---|
| tiempo_consulta_expediente: p(95)<3000 | Cumple |
| errores_funcionales: rate<0.01 | No cumple |
| http_req_failed: rate<0.01 | No cumple |
| tiempo_catalogo_tipos: p(95)<1000 | Cumple |
