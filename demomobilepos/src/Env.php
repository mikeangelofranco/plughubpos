<?php
declare(strict_types=1);

final class Env
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            if ($key === '') {
                continue;
            }

            $raw = trim(substr($line, $pos + 1));
            $value = self::parseValue($raw);

            if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER) && getenv($key) === false) {
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv($key . '=' . $value);
            }
        }
    }

    private static function parseValue(string $raw): string
    {
        if ($raw === '') {
            return '';
        }

        $first = $raw[0];
        if (($first === '"' || $first === "'") && strlen($raw) >= 2) {
            $q = $first;
            $end = strrpos($raw, $q);
            if ($end !== false && $end > 0) {
                $raw = substr($raw, 1, $end - 1);
            }
        }

        $raw = preg_replace('/\s+#.*$/', '', $raw) ?? $raw;

        return str_replace(
            ['\\n', '\\r', '\\t'],
            ["\n", "\r", "\t"],
            $raw
        );
    }
}

