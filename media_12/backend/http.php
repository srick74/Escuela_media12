<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function start_admin_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('media12_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function json_body(): array
{
    try {
        $body = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        json_response(['error' => 'El contenido enviado no es JSON válido.'], 400);
    }

    if (!is_array($body)) {
        json_response(['error' => 'El contenido enviado no es válido.'], 400);
    }

    return $body;
}

function require_authenticated_user(): array
{
    start_admin_session();
    $user = $_SESSION['admin_user'] ?? null;
    if (!is_array($user)) {
        json_response(['error' => 'Necesitás iniciar sesión.'], 401);
    }

    if ((int) ($_SESSION['last_activity'] ?? 0) < time() - 1800) {
        $_SESSION = [];
        session_destroy();
        json_response(['error' => 'La sesión venció. Ingresá nuevamente.'], 401);
    }

    $statement = database()->prepare(
        'SELECT id, usuario, nombre FROM usuarios WHERE id = :id AND activo = 1 LIMIT 1'
    );
    $statement->execute(['id' => $user['id']]);
    $account = $statement->fetch();
    if (!$account) {
        $_SESSION = [];
        session_destroy();
        json_response(['error' => 'La cuenta no está autorizada.'], 401);
    }

    $_SESSION['admin_user'] = [
        'id' => (int) $account['id'],
        'username' => $account['usuario'],
        'name' => $account['nombre'],
    ];
    $_SESSION['last_activity'] = time();

    return $_SESSION['admin_user'];
}

function require_csrf_token(): void
{
    $expected = $_SESSION['csrf_token'] ?? '';
    $received = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!is_string($expected) || $expected === '' || !is_string($received) || !hash_equals($expected, $received)) {
        json_response(['error' => 'La sesión venció. Actualizá la página e intentá otra vez.'], 403);
    }
}

function log_api_exception(Throwable $exception): void
{
    error_log($exception->getMessage());
    json_response(['error' => 'Ocurrió un error del servidor.'], 500);
}