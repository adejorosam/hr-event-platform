<?php

return [
    'host' => env('RABBITMQ_HOST', 'rabbitmq'),
    'port' => env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'exchange' => env('RABBITMQ_EXCHANGE', 'hr_events'),
    'queue' => env('RABBITMQ_QUEUE', 'hub_service_events'),
];
