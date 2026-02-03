<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!function_exists('send_app_mail')) {
    function send_app_mail($to, string $subject, string $htmlBody, string $altBody = ''): bool
    {
        $vendorAutoload = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($vendorAutoload)) {
            throw new RuntimeException('Composer autoload not found. Run "composer install" in the project root to install dependencies.');
        }

        static $autoloaded = false;
        if (!$autoloaded) {
            require_once $vendorAutoload;
            $autoloaded = true;
        }

        $configFile = __DIR__ . '/../smtp_config.php';
        if (!file_exists($configFile)) {
            throw new RuntimeException('SMTP configuration file not found. Please copy smtp_config.php and update your credentials.');
        }
        $config = include $configFile;
        if (empty($config['enabled'])) {
            return false;
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->Port = $config['port'] ?? 587;
            $mail->SMTPAuth = !empty($config['username']);
            if (!empty($config['username'])) {
                $mail->Username = $config['username'];
                $mail->Password = $config['password'] ?? '';
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
            $mail->AltBody = $altBody ?: strip_tags($htmlBody);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Email sending failed: ' . $e->getMessage());
            return false;
        }
    }
}
