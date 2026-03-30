<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!defined('APP_BOOTSTRAPPED')) {
    require_once __DIR__ . '/init.php';
}

if (!function_exists('send_app_mail')) {
    function send_app_mail($to, string $subject, string $htmlBody, string $altBody = '', array $embeddedImages = []): bool
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
            app_log('Email sending skipped because mail is disabled.', [
                'subject' => $subject,
                'recipients' => is_array($to) ? array_values(array_filter($to)) : [$to],
            ]);
            return false;
        }

        $legacyConfigFile = base_path('smtp_config.php');
        if (is_file($legacyConfigFile)) {
            $legacyConfig = include $legacyConfigFile;
            if (is_array($legacyConfig)) {
                $config = array_replace($config, array_filter($legacyConfig, static function ($value) {
                    return $value !== null && $value !== '';
                }));
            }
        }

        $host = trim((string)($config['host'] ?? ''));
        if ($host === '') {
            throw new RuntimeException('Mail host is not configured. Set MAIL_HOST in your environment.');
        }

        $username = trim((string)($config['username'] ?? ''));
        $password = (string)($config['password'] ?? '');
        $fromEmail = (string)($config['from_email'] ?? 'no-reply@example.com');
        $fromName = (string)($config['from_name'] ?? 'ICA Tracker');
        $port = (int)($config['port'] ?? 587);
        $encryption = strtolower((string)($config['encryption'] ?? 'tls'));

        $hosts = [$host];
        if (strcasecmp($host, 'smtp.office365.com') === 0 && preg_match('/@(outlook\.com|nmims\.in)$/i', $username)) {
            $hosts[] = 'smtp-mail.outlook.com';
        }
        $hosts = array_values(array_unique($hosts));

        $lastError = '';
        foreach ($hosts as $attemptHost) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = $attemptHost;
                $mail->Port = $port;
                $mail->SMTPAuth = $username !== '';
                if ($mail->SMTPAuth) {
                    $mail->Username = $username;
                    $mail->Password = $password;
                }

                if ($encryption === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($encryption === 'tls') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                }

                $mail->SMTPDebug = !empty($config['debug']) ? 2 : 0;
                $mail->Timeout = 20;

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

                foreach ($embeddedImages as $image) {
                    if (!is_array($image)) {
                        continue;
                    }
                    $imagePath = trim((string)($image['path'] ?? ''));
                    $contentId = trim((string)($image['cid'] ?? ''));
                    if ($imagePath === '' || $contentId === '' || !is_file($imagePath)) {
                        continue;
                    }
                    $imageName = trim((string)($image['name'] ?? basename($imagePath)));
                    $mimeType = trim((string)($image['mime'] ?? 'image/jpeg'));
                    $mail->addEmbeddedImage($imagePath, $contentId, $imageName, 'base64', $mimeType);
                }

                $mail->send();
                return true;
            } catch (Exception $exception) {
                $lastError = $exception->getMessage();
                app_log('Email sending attempt failed.', [
                    'subject' => $subject,
                    'attempt_host' => $attemptHost,
                    'port' => $port,
                    'username' => $username,
                    'from_email' => $fromEmail,
                    'recipients' => is_array($to) ? array_values(array_filter($to)) : [$to],
                    'error' => $lastError,
                    'error_info' => $mail->ErrorInfo,
                ]);
            }
        }

        app_log('Email sending failed.', [
            'subject' => $subject,
            'host' => implode(',', $hosts),
            'port' => $port,
            'username' => $username,
            'from_email' => $fromEmail,
            'recipients' => is_array($to) ? array_values(array_filter($to)) : [$to],
            'error' => $lastError,
        ]);
        return false;
    }
}
