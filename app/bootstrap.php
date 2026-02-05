<?php
declare(strict_types=1);

use App\Support\ConfigRepository;
use Dotenv\Dotenv;

if (defined('APP_BOOTSTRAPPED')) {
    return;
}

require_once __DIR__ . '/../vendor/autoload.php';

$helpersFile = __DIR__ . '/Support/helpers.php';
if (is_file($helpersFile) && !function_exists('base_path')) {
    require_once $helpersFile;
}

spl_autoload_register(static function (string $class): void {
    if (strpos($class, 'App\\') !== 0) {
        return;
    }

    $relative = substr($class, 4);
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path) && !class_exists($class, false) && !interface_exists($class, false) && !trait_exists($class, false)) {
        require_once $path;
    }
});

if (!class_exists(ConfigRepository::class)) {
    $configRepositoryFile = __DIR__ . '/Support/ConfigRepository.php';
    if (is_file($configRepositoryFile)) {
        require_once $configRepositoryFile;
    }
}

$rootPath = dirname(__DIR__);
if (class_exists(Dotenv::class)) {
    $envFile = $rootPath . DIRECTORY_SEPARATOR . '.env';
    if (is_file($envFile)) {
        Dotenv::createImmutable($rootPath)->safeLoad();
    }
}

$configRepository = ConfigRepository::instance();
$configFiles = glob(config_path('*.php')) ?: [];
$loadedConfig = [];
foreach ($configFiles as $file) {
    $key = basename($file, '.php');
    $value = require $file;
    if (is_array($value)) {
        $loadedConfig[$key] = $value;
    }
}
$configRepository->load($loadedConfig);

$timezone = config('app.timezone', 'UTC');
if (is_string($timezone) && $timezone !== '') {
    @date_default_timezone_set($timezone);
}

if (function_exists('mb_internal_encoding')) {
    @mb_internal_encoding('UTF-8');
}

$logDirectory = storage_path('logs');
if (!is_dir($logDirectory)) {
    @mkdir($logDirectory, 0775, true);
}

$logPath = env_value('LOG_PATH');
if ($logPath === null || $logPath === '') {
    $logPath = $logDirectory . DIRECTORY_SEPARATOR . 'app.log';
} else {
    $trimmed = trim((string)$logPath);
    $hasRoot = preg_match('/^(?:[a-zA-Z]:\\\\|\\\\\\\\|\/)/', $trimmed) === 1;
    $logPath = $hasRoot ? $trimmed : $rootPath . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $trimmed), DIRECTORY_SEPARATOR);
}

@ini_set('log_errors', '1');
@ini_set('error_log', $logPath);

$debug = env_bool('APP_DEBUG', (bool)config('app.debug', false));
if ($debug) {
    error_reporting(E_ALL);
    @ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    @ini_set('display_errors', '0');
}

if (PHP_SAPI !== 'cli') {
    $sessionConfig = config('session', []);
    $cookieParams = [
        'lifetime' => (int)($sessionConfig['lifetime'] ?? 7200),
        'path' => $sessionConfig['path'] ?? '/',
        'domain' => $sessionConfig['domain'] ?? '',
        'secure' => (bool)($sessionConfig['secure'] ?? false),
        'httponly' => (bool)($sessionConfig['http_only'] ?? true),
        'samesite' => $sessionConfig['same_site'] ?? 'Lax',
    ];

    if (!empty($sessionConfig['name'])) {
        @session_name((string)$sessionConfig['name']);
    }

    if (session_status() === PHP_SESSION_NONE) {
        try {
            session_set_cookie_params($cookieParams);
        } catch (\Throwable $exception) {
            app_log('Failed to set session cookie parameters.', [
                'error' => $exception->getMessage(),
            ]);
        }
        session_start();
    }
}

define('APP_BOOTSTRAPPED', true);
