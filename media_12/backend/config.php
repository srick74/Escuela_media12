<?php
declare(strict_types=1);

$localConfigPath = __DIR__ . '/config.local.php';
$localConfig = is_file($localConfigPath) ? require $localConfigPath : [];

$settings = [
    'host' => $localConfig['host'] ?? (getenv('DB_HOST') ?: '127.0.0.1'),
    'port' => $localConfig['port'] ?? (getenv('DB_PORT') ?: '3306'),
    'database' => $localConfig['database'] ?? (getenv('DB_NAME') ?: ''),
    'username' => $localConfig['username'] ?? (getenv('DB_USER') ?: ''),
    'password' => $localConfig['password'] ?? (getenv('DB_PASSWORD') ?: ''),
];

function database(): PDO
{
    global $settings;

    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    if ($settings['database'] === '' || $settings['username'] === '') {
        throw new RuntimeException('Falta configurar la conexión a la base de datos.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $settings['host'],
        $settings['port'],
        $settings['database']
    );

    $connection = new PDO($dsn, $settings['username'], $settings['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $connection;
}