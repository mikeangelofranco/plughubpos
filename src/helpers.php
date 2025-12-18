<?php
declare(strict_types=1);

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    if (!is_string($value)) {
        return $value;
    }

    $lower = strtolower($value);
    if (in_array($lower, ['true', 'false'], true)) {
        return $lower === 'true';
    }
    if ($lower === 'null') {
        return null;
    }

    return $value;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function view(string $name, array $data = []): string
{
    $base = dirname(__DIR__);
    $file = $base . '/views/' . $name . '.php';
    if (!is_file($file)) {
        throw new RuntimeException("View not found: {$name}");
    }

    extract($data, EXTR_SKIP);

    ob_start();
    require $file;
    return (string) ob_get_clean();
}

