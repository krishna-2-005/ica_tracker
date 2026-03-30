<?php
if (php_sapi_name() !== 'cli') {
    $scriptPath = isset($_SERVER['SCRIPT_FILENAME']) ? realpath($_SERVER['SCRIPT_FILENAME']) : null;
    if ($scriptPath !== null && realpath(__FILE__) === $scriptPath) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Configure mail settings via the .env file or config/mail.php. This legacy file returns placeholders and is provided for backward compatibility.";
        exit;
    }
}

return [
    'enabled' => true,
    'host' => 'smtp-mail.outlook.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'kuchurusai.krishna34@nmims.in',
    'password' => 'Anitha@1984',
    'from_email' => 'kuchurusai.krishna34@nmims.in',
    'from_name' => 'ICA Tracker',
    'debug' => false,
];

