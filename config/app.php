<?php
return [
    'name' => getenv('APP_NAME') ?: 'PG Estética Facial e Corporal',
    'url' => rtrim(getenv('APP_URL') ?: 'http://localhost:8000', '/'),
    'timezone' => getenv('APP_TIMEZONE') ?: 'America/Maceio',
    'whatsapp' => getenv('CLINIC_WHATSAPP') ?: '5582999999999',
    'currency' => 'BRL',
    'slot_interval' => (int)(getenv('SLOT_INTERVAL') ?: 30),
];
