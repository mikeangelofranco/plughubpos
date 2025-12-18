<?php
declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = Db::pdo();
    $row = $pdo->query('select now() as now')->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'db' => [
            'connected' => true,
            'now' => $row['now'] ?? null,
        ],
        'app_env' => env('APP_ENV', 'unknown'),
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'db' => [
            'connected' => false,
        ],
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_SLASHES);
}

