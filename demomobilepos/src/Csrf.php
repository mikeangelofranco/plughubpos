<?php
declare(strict_types=1);

final class Csrf
{
    public static function token(): string
    {
        Session::start();
        if (!isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf']) || strlen($_SESSION['_csrf']) < 16) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function validate(?string $token): bool
    {
        Session::start();
        $expected = $_SESSION['_csrf'] ?? null;
        if (!is_string($expected) || $expected === '') {
            return false;
        }
        if (!is_string($token) || $token === '') {
            return false;
        }
        return hash_equals($expected, $token);
    }
}

