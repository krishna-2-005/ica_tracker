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
    'enabled' => false,
    'host' => '',
    'port' => 587,
    'encryption' => 'tls',
    'username' => '',
    'password' => '',
    'from_email' => 'no-reply@example.com',
    'from_name' => 'ICA Tracker',
    'debug' => false,
];
