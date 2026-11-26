<?php
declare(strict_types=1);

require_once __DIR__ . '/http.php';

start_admin_session();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? 'status';

try {
    if ($method === 'GET' && $action === 'status') {
        $user = $_SESSION['admin_user'] ?? null;
        if (!is_array($user)) {
            json_response(['authenticated' => false]);
        }

        if ((int) ($_SESSION['last_activity'] ?? 0) < time() - 1800) {
            $_SESSION = [];
            session_destroy();
            json_response(['authenticated' => false]);
        }

        $statement = database()->prepare(
            'SELECT id, usuario, nombre FROM usuarios WHERE id = :id AND activo = 1 LIMIT 1'
        );
        $statement->execute(['id' => $user['id']]);
        $account = $statement->fetch();
        if (!$account) {
            $_SESSION = [];
            session_destroy();
            json_response(['authenticated' => false]);
        }

        $_SESSION['admin_user'] = [
            'id' => (int) $account['id'],
            'username' => $account['usuario'],
            'name' => $account['nombre'],
        ];
        $_SESSION['last_activity'] = time();
        $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
        json_response([
            'authenticated' => true,
            'user' => $_SESSION['admin_user'],
            'csrfToken' => $_SESSION['csrf_token'],
        ]);
    }

    if ($method === 'POST' && $action === 'login') {
        $body = json_body();
        $username = trim((string) ($body['username'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        $windowStarted = (int) ($_SESSION['login_window_started'] ?? 0);
        $failedAttempts = (int) ($_SESSION['login_failed_attempts'] ?? 0);
        if (time() - $windowStarted > 900) {
            $failedAttempts = 0;
            $_SESSION['login_window_started'] = time();
        }
        if ($failedAttempts >= 5) {
            json_response(['error' => 'Demasiados intentos. Esperá 15 minutos para volver a probar.'], 429);
        }

        $statement = database()->prepare(
            'SELECT id, usuario, nombre, password_hash FROM usuarios WHERE usuario = :usuario AND activo = 1 LIMIT 1'
        );
        $statement->execute(['usuario' => $username]);
        $account = $statement->fetch();

        if (!$account || !password_verify($password, $account['password_hash'])) {
            $_SESSION['login_window_started'] ??= time();
            $_SESSION['login_failed_attempts'] = $failedAttempts + 1;
            json_response(['error' => 'Usuario o contraseña incorrectos.'], 401);
        }

        session_regenerate_id(true);
        $_SESSION['admin_user'] = [
            'id' => (int) $account['id'],
            'username' => $account['usuario'],
            'name' => $account['nombre'],
        ];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['last_activity'] = time();
        unset($_SESSION['login_window_started'], $_SESSION['login_failed_attempts']);

        $update = database()->prepare('UPDATE usuarios SET ultimo_acceso = CURRENT_TIMESTAMP WHERE id = :id');
        $update->execute(['id' => $account['id']]);

        json_response([
            'authenticated' => true,
            'user' => $_SESSION['admin_user'],
            'csrfToken' => $_SESSION['csrf_token'],
        ]);
    }

    if ($method === 'POST' && $action === 'logout') {
        require_authenticated_user();
        require_csrf_token();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $cookie = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $cookie['path'],
                'domain' => $cookie['domain'],
                'secure' => $cookie['secure'],
                'httponly' => $cookie['httponly'],
                'samesite' => 'Strict',
            ]);
        }

        session_destroy();
        json_response(['authenticated' => false]);
    }

    json_response(['error' => 'Método o acción no permitidos.'], 405);
} catch (Throwable $exception) {
    log_api_exception($exception);
}