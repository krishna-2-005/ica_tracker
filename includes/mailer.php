<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!defined('APP_BOOTSTRAPPED')) {
    require_once __DIR__ . '/init.php';
}

if (!function_exists('send_app_mail')) {
    function send_app_mail($to, string $subject, string $htmlBody, string $altBody = ''): bool
    {
        $vendorAutoload = base_path('vendor/autoload.php');
        if (!is_file($vendorAutoload)) {
            throw new RuntimeException('Composer autoload not found. Run composer install in the project root to install dependencies.');
        }

        static $autoloaded = false;
        if (!$autoloaded) {
            require_once $vendorAutoload;
            $autoloaded = true;
        }

        $config = config('mail', []);

        if (empty($config['enabled'])) {
            return false;
        }

        $legacyConfigFile = base_path('smtp_config.php');
        if (is_file($legacyConfigFile)) {
            $legacyConfig = include $legacyConfigFile;
            if (is_array($legacyConfig)) {
                $config = array_replace($legacyConfig, array_filter($config, static function ($value) {
                    return $value !== null && $value !== '';
                }));
            }
        }

        $host = $config['host'] ?? '';
        if ($host === '') {
            throw new RuntimeException('Mail host is not configured. Set MAIL_HOST in your environment.');
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = (int)($config['port'] ?? 587);
            $username = $config['username'] ?? '';
            $password = $config['password'] ?? '';
            $mail->SMTPAuth = $username !== '';
            if ($mail->SMTPAuth) {
                $mail->Username = $username;
                $mail->Password = $password;
            }

            $encryption = strtolower((string)($config['encryption'] ?? 'tls'));
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->SMTPDebug = !empty($config['debug']) ? 2 : 0;

            $fromEmail = $config['from_email'] ?? 'no-reply@example.com';
            $fromName = $config['from_name'] ?? 'ICA Tracker';
            $mail->setFrom($fromEmail, $fromName);

            if (is_array($to)) {
                foreach ($to as $recipient) {
                    if (!empty($recipient)) {
                        $mail->addAddress($recipient);
                    }
                }
            } elseif (!empty($to)) {
                $mail->addAddress($to);
            } else {
                throw new InvalidArgumentException('Recipient email address is required.');
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $altBody !== '' ? $altBody : strip_tags($htmlBody);

            $mail->send();
            return true;
        } catch (Exception $exception) {
            app_log('Email sending failed.', [
                'error' => $exception->getMessage(),
            ]);
            return false;
        }
    }
}
