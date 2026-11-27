// Prueba de carga y escalabilidad (EQ01 Rendimiento y AC03 Escalabilidad) · Sprint 2
// Sube por etapas hasta 2,000 usuarios virtuales concurrentes contra la API REST v1.
//
// Uso:
//   k6 run pruebas/k6/carga-api.js
//   k6 run -e BASE_URL=http://127.0.0.1:8000 -e MAX_VUS=500 pruebas/k6/carga-api.js   (prueba corta)
//
// Antes: en el .env del sistema sube los límites API_LIMITE_* (todos los usuarios virtuales
// salen de la misma IP) y crea el expediente de prueba con
//   php artisan db:seed --class=CargaK6Seeder
import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Rate } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const MAX_VUS = parseInt(__ENV.MAX_VUS || '2000', 10);
const NUMERO = __ENV.NUMERO || `EXP-${new Date().getFullYear()}-000001`;
const DNI = __ENV.DNI || '70451236';

const tiempoConsulta = new Trend('tiempo_consulta_expediente', true);
const tiempoCatalogo = new Trend('tiempo_catalogo_tipos', true);
const errores = new Rate('errores_funcionales');

export const options = {
    stages: [
        { duration: '30s', target: Math.round(MAX_VUS * 0.05) },  // calentamiento: 100
        { duration: '1m', target: Math.round(MAX_VUS * 0.25) },   // 500
        { duration: '1m', target: Math.round(MAX_VUS * 0.5) },    // 1,000
        { duration: '1m', target: MAX_VUS },                      // 2,000
        { duration: '1m', target: MAX_VUS },                      // se mantiene en 2,000
        { duration: '30s', target: 0 },                           // bajada
    ],
    thresholds: {
        // EQ01 / RNF04: la consulta de un expediente responde en 3 s o menos
        tiempo_consulta_expediente: ['p(95)<3000'],
        tiempo_catalogo_tipos: ['p(95)<1000'],
        http_req_failed: ['rate<0.01'],
        errores_funcionales: ['rate<0.01'],
    },
    summaryTrendStats: ['avg', 'min', 'med', 'p(90)', 'p(95)', 'max'],
};

const cabeceras = { headers: { 'Content-Type': 'application/json', Accept: 'application/json' } };

export default function () {
    const azar = Math.random();

    if (azar < 0.5) {
        // 50 %: el portal y otros sistemas piden el catálogo (sale de la caché)
        group('GET /tipos-documento', () => {
            const r = http.get(`${BASE_URL}/api/v1/tipos-documento?activo=1`, cabeceras);
            tiempoCatalogo.add(r.timings.duration);
            errores.add(!check(r, {
                'catálogo 200': (res) => res.status === 200,
                'catálogo trae data': (res) => Array.isArray(res.json('data')),
            }));
        });
    } else if (azar < 0.9) {
        // 40 %: administrados consultan su expediente (EQ01)
        group('POST /consultas', () => {
            const r = http.post(`${BASE_URL}/api/v1/consultas`, JSON.stringify({ numero: NUMERO, numero_documento: DNI }), cabeceras);
            tiempoConsulta.add(r.timings.duration);
            errores.add(!check(r, {
                'consulta 200': (res) => res.status === 200,
                'consulta trae recorrido': (res) => res.status === 200 && res.json('data.recorrido').length > 0,
            }));
        });
    } else {
        // 10 %: verificación de un código que no existe (404 esperado)
        group('GET /verificaciones', () => {
            const r = http.get(`${BASE_URL}/api/v1/verificaciones/AAAA-BBBB-CCCC`, Object.assign({ responseCallback: http.expectedStatuses(404) }, cabeceras));
            errores.add(!check(r, { 'verificación 404': (res) => res.status === 404 }));
        });
    }

    sleep(1 + Math.random()); // tiempo de "lectura" de una persona real: 1 a 2 s
}

export function handleSummary(data) {
    const m = data.metrics;
    const v = (nombre, stat) => (m[nombre] && m[nombre].values[stat] !== undefined ? m[nombre].values[stat] : 0);
    const resumen = [
        '# Resultado de la prueba de carga k6',
        '',
        `- Usuarios virtuales máximos: ${v('vus_max', 'max') || MAX_VUS}`,
        `- Peticiones totales: ${v('http_reqs', 'count')} (${v('http_reqs', 'rate').toFixed(1)} por segundo)`,
        `- Peticiones fallidas: ${(v('http_req_failed', 'rate') * 100).toFixed(2)} %`,
        `- Consulta de expediente: p95 = ${v('tiempo_consulta_expediente', 'p(95)').toFixed(0)} ms, máximo = ${v('tiempo_consulta_expediente', 'max').toFixed(0)} ms (meta: p95 < 3000 ms)`,
        `- Catálogo de tipos: p95 = ${v('tiempo_catalogo_tipos', 'p(95)').toFixed(0)} ms (meta: p95 < 1000 ms)`,
        `- Errores funcionales: ${(v('errores_funcionales', 'rate') * 100).toFixed(2)} %`,
        '',
        '| Umbral | Resultado |',
        '|---|---|',
        ...Object.entries(m).filter(([, x]) => x.thresholds).flatMap(([n, x]) =>
            Object.entries(x.thresholds).map(([t, r]) => `| ${n}: ${t} | ${r.ok ? 'Cumple' : 'No cumple'} |`)),
        '',
    ].join('\n');

    return {
        stdout: resumen + '\n',
        'resultados-k6.md': resumen,
        'resultados-k6.json': JSON.stringify(data, null, 2),
    };
}
