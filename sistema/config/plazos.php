<?php

/*
| RF24: plazos del semáforo. Se cuentan los días que el expediente lleva
| en el área donde está ahora (desde su último movimiento).
*/
return [
    // Desde este día el expediente pasa a ámbar (antes es verde).
    'ambar' => 4,

    // Desde este día el expediente pasa a rojo (olvidado o vencido).
    'rojo' => 6,

    // true: solo cuenta de lunes a viernes. false: cuenta todos los días.
    'habiles' => true,

    // #32: se avisa cuando un área tiene MÁS de esta cantidad de expedientes en rojo.
    'alerta_area' => 5,
];
