<?php
declare(strict_types=1);

final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = env('DB_HOST', '127.0.0.1');
        $port = env('DB_PORT', '5432');
        $name = env('DB_NAME', 'plughub_possystem');
        $user = env('DB_USER', 'plughub');
        $pass = env('DB_PASSWORD', '');

        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;options=--client_encoding=UTF8', $host, $port, $name);
        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$pdo;
    }
}

