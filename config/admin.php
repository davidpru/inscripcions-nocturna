<?php

return [
    'username' => env('ADMIN_USERNAME', 'uectortosa'),
    'password' => env('ADMIN_PASSWORD', 'NeusPresi222'),
    
    // IPs permitidas para ver inscripciones abiertas (modo prueba)
    'test_ips' => [
        '127.0.0.1',      // localhost
        '::1',            // localhost IPv6
        // Agrega aquí tu IP pública para pruebas
    ],
];
