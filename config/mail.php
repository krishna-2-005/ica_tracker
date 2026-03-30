<?php
declare(strict_types=1);

return [
    'enabled' => env_bool('MAIL_ENABLED', false),
    'host' => (string)env_value('MAIL_HOST', 'smtp-mail.outlook.com'),
    'port' => env_int('MAIL_PORT', 587),
    'username' => (string)env_value('MAIL_USERNAME', 'kuchurusai.krishna34@nmims.in'),
    'password' => env_value('MAIL_PASSWORD', ''),
    'encryption' => (string)env_value('MAIL_ENCRYPTION', 'tls'),
    'from_email' => (string)env_value('MAIL_FROM_ADDRESS', 'kuchurusai.krishna34@nmims.in'),
    'from_name' => (string)env_value('MAIL_FROM_NAME', 'ICA Tracker'),
    'debug' => env_bool('MAIL_DEBUG', false),
];
