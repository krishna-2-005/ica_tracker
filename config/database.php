<?php
declare(strict_types=1);

return [
    'driver' => 'mysqli',
    'host' => (string)env_value('DB_HOST', '127.0.0.1'),
    'port' => env_int('DB_PORT', 3306),
    'database' => (string)env_value('DB_DATABASE', 'ica_tracker'),
    'username' => (string)env_value('DB_USERNAME', 'root'),
    'password' => env_value('DB_PASSWORD', ''),
    'charset' => (string)env_value('DB_CHARSET', 'utf8mb4'),
    'socket' => env_value('DB_SOCKET', null),
    'timeout' => env_int('DB_TIMEOUT', 5),
    'flags' => env_int('DB_FLAGS', 0),
];
