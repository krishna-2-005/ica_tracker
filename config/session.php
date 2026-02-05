<?php
declare(strict_types=1);

return [
    'name' => (string)env_value('SESSION_NAME', 'ica_tracker_session'),
    'lifetime' => env_int('SESSION_LIFETIME', 7200),
    'path' => (string)env_value('SESSION_PATH', '/'),
    'domain' => (string)env_value('SESSION_DOMAIN', ''),
    'secure' => env_bool('SESSION_SECURE', false),
    'http_only' => env_bool('SESSION_HTTP_ONLY', true),
    'same_site' => (string)env_value('SESSION_SAME_SITE', 'Lax'),
];
