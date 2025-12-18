<?php
declare(strict_types=1);

final class Auth
{
    public static function check(): bool
    {
        Session::start();
        return isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['id']);
    }

    public static function user(): ?array
    {
        Session::start();
        $u = $_SESSION['user'] ?? null;
        return is_array($u) ? $u : null;
    }

    public static function attempt(string $username, string $password): bool
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            return false;
        }

        $pdo = Db::pdo();
        $stmt = $pdo->prepare('select id, username, password_hash, role, active from users where lower(username) = lower(:u) limit 1');
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        if (!(bool) ($row['active'] ?? false)) {
            return false;
        }

        $hash = (string) ($row['password_hash'] ?? '');
        if ($hash === '' || !password_verify($password, $hash)) {
            return false;
        }

        Session::start();
        Session::regenerate();
        $_SESSION['user'] = [
            'id' => (int) $row['id'],
            'username' => (string) $row['username'],
            'role' => (string) ($row['role'] ?? 'User'),
        ];
        return true;
    }

    public static function logout(): void
    {
        Session::start();
        unset($_SESSION['user']);
        Session::regenerate();
    }
}
