<?php
declare(strict_types=1);

require_once __DIR__ . '/http.php';

function is_sequential_array(array $value): bool
{
    return $value === [] || array_keys($value) === range(0, count($value) - 1);
}

function validate_schedules($courses): array
{
    if (!is_array($courses) || !is_sequential_array($courses) || count($courses) < 1 || count($courses) > 100) {
        throw new InvalidArgumentException('La lista de cursos no es válida.');
    }

    $validated = [];
    $courseNames = [];
    foreach ($courses as $course) {
        if (!is_array($course) || !is_string($course['course'] ?? null) || !is_array($course['schedule'] ?? null)) {
            throw new InvalidArgumentException('Cada curso debe tener nombre y horarios.');
        }

        $courseName = trim($course['course']);
        $courseKey = strtolower($courseName);
        if ($courseName === '' || strlen($courseName) > 100 || isset($courseNames[$courseKey])) {
            throw new InvalidArgumentException('Los nombres de curso deben ser válidos y no repetirse.');
        }
        $courseNames[$courseKey] = true;

        $blocks = $course['schedule'];
        if (!is_sequential_array($blocks) || count($blocks) < 1 || count($blocks) > 24) {
            throw new InvalidArgumentException('Cada curso debe tener entre 1 y 24 bloques horarios.');
        }

        $validatedBlocks = [];
        foreach ($blocks as $block) {
            if (!is_array($block) || !is_string($block['time'] ?? null) || !is_array($block['days'] ?? null)) {
                throw new InvalidArgumentException('Cada bloque debe tener un horario y cinco días.');
            }

            if (count($block['days']) !== 5 || !is_sequential_array($block['days'])) {
                throw new InvalidArgumentException('Cada bloque debe tener exactamente cinco días.');
            }

            $time = trim($block['time']);
            if ($time === '' || strlen($time) > 40) {
                throw new InvalidArgumentException('El horario de cada bloque es obligatorio y no debe superar 40 caracteres.');
            }

            $days = [];
            foreach ($block['days'] as $day) {
                if (!is_string($day) || strlen($day) > 120) {
                    throw new InvalidArgumentException('Cada materia debe ser texto de hasta 120 caracteres.');
                }
                $days[] = trim($day);
            }

            $validatedBlocks[] = ['time' => $time, 'days' => $days];
        }

        $validated[] = ['course' => $courseName, 'schedule' => $validatedBlocks];
    }

    return $validated;
}

function read_schedules(PDO $connection): array
{
    $select = $connection->prepare('SELECT contenido FROM horarios_publicos WHERE id = 1');
    $select->execute();
    $content = $select->fetchColumn();

    if ($content === false) {
        $seedPath = dirname(__DIR__) . '/js/horarios-publicos.json';
        $seedContent = file_get_contents($seedPath);
        if ($seedContent === false) {
            throw new RuntimeException('No se pudo leer el archivo inicial de horarios.');
        }

        $seed = validate_schedules(json_decode($seedContent, true, 512, JSON_THROW_ON_ERROR));
        $insert = $connection->prepare('INSERT IGNORE INTO horarios_publicos (id, contenido) VALUES (1, :contenido)');
        $insert->execute([
            'contenido' => json_encode($seed, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);

        $select->execute();
        $content = $select->fetchColumn();
        if ($content === false) {
            throw new RuntimeException('No se pudieron inicializar los horarios.');
        }
    }

    return validate_schedules(json_decode((string) $content, true, 512, JSON_THROW_ON_ERROR));
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $connection = database();

    if ($method === 'GET') {
        $select = $connection->prepare('SELECT actualizado_en FROM horarios_publicos WHERE id = 1');
        $select->execute();
        $updatedAt = $select->fetchColumn();
        $schedules = read_schedules($connection);

        json_response([
            'cursos' => $schedules,
            'actualizadoEn' => $updatedAt === false ? null : $updatedAt,
        ]);
    }

    if ($method === 'PUT') {
        $user = require_authenticated_user();
        require_csrf_token();
        $body = json_body();
        try {
            $schedules = validate_schedules($body['cursos'] ?? null);
        } catch (InvalidArgumentException $exception) {
            json_response(['error' => $exception->getMessage()], 422);
        }
        $content = json_encode($schedules, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        read_schedules($connection);
        $update = $connection->prepare(
            'UPDATE horarios_publicos SET contenido = :contenido, actualizado_por = :usuario WHERE id = 1'
        );
        $update->execute(['contenido' => $content, 'usuario' => $user['id']]);

        json_response([
            'cursos' => $schedules,
            'actualizadoEn' => date('Y-m-d H:i:s'),
        ]);
    }

    header('Allow: GET, PUT');
    json_response(['error' => 'Método no permitido.'], 405);
} catch (Throwable $exception) {
    log_api_exception($exception);
}
