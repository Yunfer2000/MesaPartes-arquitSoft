<?php

/*
| API REST v1: límites de peticiones por minuto y por IP.
| En producción se dejan los valores por defecto. Para la prueba de carga con k6 (todos los
| usuarios virtuales salen de la misma IP) se suben en el .env del entorno de pruebas.
*/
return [
    'limite_general' => (int) env('API_LIMITE_GENERAL', 60),
    'limite_consultas' => (int) env('API_LIMITE_CONSULTAS', 10),
    'limite_verificaciones' => (int) env('API_LIMITE_VERIFICACIONES', 30),
    'limite_tokens' => (int) env('API_LIMITE_TOKENS', 5),
];
