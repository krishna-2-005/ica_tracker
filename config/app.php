<?php
declare(strict_types=1);

return [
    'name' => (string)env_value('APP_NAME', 'ICA Tracker'),
    'env' => (string)env_value('APP_ENV', 'production'),
    'debug' => env_bool('APP_DEBUG', false),
    'url' => (string)env_value('APP_URL', 'http://localhost/ica_tracker'),
    'timezone' => (string)env_value('APP_TIMEZONE', 'Asia/Kolkata'),
    'locale' => (string)env_value('APP_LOCALE', 'en'),
];
