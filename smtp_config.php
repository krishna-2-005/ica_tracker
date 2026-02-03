<?php
if (php_sapi_name() !== 'cli') {
    $scriptPath = isset($_SERVER['SCRIPT_FILENAME']) ? realpath($_SERVER['SCRIPT_FILENAME']) : null;
    if ($scriptPath !== null && realpath(__FILE__) === $scriptPath) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "This file holds the SMTP credentials for ICA Tracker. Edit the values directly in a text editor; the app includes it at runtime to send mail.";
        exit;
    }
}

return [
    'enabled' => true,
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'icatrackerstme@gmail.com',
    'password' => 'Stme!2023@',
    'from_email' => 'icatrackerstme@gmail.com',
    'from_name' => 'ICA Tracker',
    'debug' => false
];
