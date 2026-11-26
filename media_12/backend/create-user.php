<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/config.php';

if ($argc < 3) {
    fwrite(STDERR, "Uso: php backend/create-user.php usuario \"Nombre y apellido\"\n");
    exit(1);
}

$username = trim($argv[1]);
$name = trim($argv[2]);
if (!preg_match('/^[a-zA-Z0-9._-]{3,80}$/', $username) || $name === '' || strlen($name) > 120) {
    fwrite(STDERR, "El usuario debe tener entre 3 y 80 caracteres válidos y el nombre no puede estar vacío.\n");
    exit(1);
}

if (PHP_OS_FAMILY === 'Windows') {
    if (($argv[3] ?? '') !== '--password-base64-stdin') {
        fwrite(STDERR, "En Windows ejecutá backend\\create-user.ps1 para ingresar la contraseña de forma oculta.\n");
        exit(1);
    }

    $encodedPassword = fgets(STDIN);
    $password = $encodedPassword === false ? false : base64_decode(trim($encodedPassword), true);
    if (!is_string($password)) {
        fwrite(STDERR, "No se pudo leer la contraseña de forma segura.\n");
        exit(1);
    }
} else {
    fwrite(STDERR, 'Contraseña (mínimo 12 caracteres): ');
    exec('stty -echo', $output, $status);
    if ($status !== 0) {
        fwrite(STDERR, "No se pudo ocultar la contraseña en esta terminal.\n");
        exit(1);
    }

    try {
        $password = fgets(STDIN);
    } finally {
        exec('stty echo');
        fwrite(STDERR, PHP_EOL);
    }

    if ($password === false) {
        fwrite(STDERR, "No se pudo leer la contraseña.\n");
        exit(1);
    }
    $password = rtrim($password, "\r\n");
}

if (strlen($password) < 12) {
    fwrite(STDERR, "La contraseña debe tener al menos 12 caracteres.\n");
    exit(1);
}

try {
    $statement = database()->prepare(
        'INSERT INTO usuarios (usuario, nombre, password_hash) VALUES (:usuario, :nombre, :password_hash)'
    );
    $statement->execute([
        'usuario' => $username,
        'nombre' => $name,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    fwrite(STDOUT, "Cuenta autorizada creada para {$username}.\n");
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        fwrite(STDERR, "Ese nombre de usuario ya existe.\n");
        exit(1);
    }
    throw $exception;
}