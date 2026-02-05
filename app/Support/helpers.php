<?php
declare(strict_types=1);

use App\Support\ConfigRepository;

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);
        $clean = trim($path);
        if ($clean === '') {
            return $base;
        }
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($clean, '\\/'));
        return $base . DIRECTORY_SEPARATOR . $normalized;
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        $clean = trim($path);
        if ($clean === '') {
            return base_path('app');
        }
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($clean, '\\/'));
        return base_path('app' . DIRECTORY_SEPARATOR . $normalized);
    }
}

if (!function_exists('config_path')) {
    function config_path(string $path = ''): string
    {
        $clean = trim($path);
        if ($clean === '') {
            return base_path('config');
        }
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($clean, '\\/'));
        return base_path('config' . DIRECTORY_SEPARATOR . $normalized);
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        $clean = trim($path);
        if ($clean === '') {
            return base_path('storage');
        }
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($clean, '\\/'));
        return base_path('storage' . DIRECTORY_SEPARATOR . $normalized);
    }
}

if (!function_exists('env_value')) {
    function env_value(string $key, $default = null)
    {
        if (array_key_exists($key, $_ENV)) {
            $value = $_ENV[$key];
        } elseif (array_key_exists($key, $_SERVER)) {
            $value = $_SERVER[$key];
        } else {
            $value = getenv($key);
        }

        if ($value === false || $value === null) {
            return $default;
        }

        return $value;
    }
}

if (!function_exists('env_bool')) {
    function env_bool(string $key, bool $default = false): bool
    {
        $value = env_value($key);
        if ($value === null) {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }
        $normalized = strtolower(trim((string)$value));
        if ($normalized === '') {
            return $default;
        }
        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }
        return $default;
    }
}

if (!function_exists('env_int')) {
    function env_int(string $key, int $default = 0): int
    {
        $value = env_value($key);
        if ($value === null) {
            return $default;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int)$value;
        }
        return $default;
    }
}

if (!function_exists('env_float')) {
    function env_float(string $key, float $default = 0.0): float
    {
        $value = env_value($key);
        if ($value === null) {
            return $default;
        }
        if (is_float($value) || is_int($value)) {
            return (float)$value;
        }
        if (is_numeric($value)) {
            return (float)$value;
        }
        return $default;
    }
}

if (!function_exists('config')) {
    function config(?string $key = null, $default = null)
    {
        $repository = ConfigRepository::instance();
        if ($key === null) {
            return $repository->all();
        }
        return $repository->get($key, $default);
    }
}

if (!function_exists('app_log')) {
    function app_log(string $message, array $context = []): void
    {
        $logPath = env_value('LOG_PATH');
        if ($logPath === null || $logPath === '') {
            $logPath = storage_path('logs' . DIRECTORY_SEPARATOR . 'app.log');
        } else {
            $trimmed = trim((string)$logPath);
            $hasRoot = preg_match('/^(?:[a-zA-Z]:\\\\|\\\\\\\\|\/)/', $trimmed) === 1;
            $logPath = $hasRoot ? $trimmed : base_path($trimmed);
        }

        $directory = dirname($logPath);
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $entry = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        if (!empty($context)) {
            $encoded = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encoded !== false) {
                $entry .= ' ' . $encoded;
            }
        }
        $entry .= PHP_EOL;
        @file_put_contents($logPath, $entry, FILE_APPEND | LOCK_EX);
    }
}
