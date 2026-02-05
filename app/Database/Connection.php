<?php
declare(strict_types=1);

namespace App\Database;

use mysqli;
use RuntimeException;
use Throwable;
use function app_log;
use function config;

final class Connection
{
    private static ?mysqli $instance = null;

    public static function getInstance(): mysqli
    {
        if (self::$instance instanceof mysqli) {
            try {
                if (@self::$instance->ping()) {
                    return self::$instance;
                }
            } catch (Throwable $exception) {
                app_log('Database connection ping failed. Reconnecting.', [
                    'error' => $exception->getMessage(),
                ]);
            }

            try {
                @self::$instance->close();
            } catch (Throwable $exception) {
                // Ignore errors during close; a fresh connection will be established below.
            }

            self::$instance = null;
        }

        $config = config('database', []);
        $host = (string)($config['host'] ?? '127.0.0.1');
        $port = (int)($config['port'] ?? 3306);
        $database = (string)($config['database'] ?? '');
        $username = (string)($config['username'] ?? '');
        $password = (string)($config['password'] ?? '');
        $charset = (string)($config['charset'] ?? 'utf8mb4');
        $socket = $config['socket'] ?? null;
        $timeout = (int)($config['timeout'] ?? 5);
        $flags = (int)($config['flags'] ?? 0);

        $mysqli = mysqli_init();
        if (!$mysqli) {
            throw new RuntimeException('Failed to initialize the MySQL client.');
        }

        if ($timeout > 0) {
            $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, $timeout);
        }

        $mysqli->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);

        $connected = @$mysqli->real_connect(
            $host,
            $username,
            $password,
            $database !== '' ? $database : null,
            $port,
            $socket ?: null,
            $flags
        );

        if ($connected === false) {
            $error = $mysqli->connect_error ?? 'Unknown error';
            $code = $mysqli->connect_errno;
            throw new RuntimeException('Database connection failed: ' . $error, $code > 0 ? $code : 0);
        }

        if (!$mysqli->set_charset($charset)) {
            app_log('Unable to set database charset.', [
                'charset' => $charset,
                'error' => $mysqli->error,
            ]);
        }

        self::$instance = $mysqli;
        return self::$instance;
    }

    public static function disconnect(): void
    {
        if (self::$instance instanceof mysqli) {
            self::$instance->close();
            self::$instance = null;
        }
    }
}
