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
        if (!is_array($u) || !isset($u['id'])) {
            return null;
        }

        return [
            'id' => (int) $u['id'],
            'username' => (string) ($u['username'] ?? ''),
            'role' => (string) ($u['role'] ?? 'Cashier'),
            'tenant_id' => isset($u['tenant_id']) ? (int) $u['tenant_id'] : null,
            'tenant_name' => is_string($u['tenant_name'] ?? null) ? $u['tenant_name'] : null,
        ];
    }

    public static function role(): string
    {
        $u = self::user();
        return $u ? (string) ($u['role'] ?? '') : '';
    }

    public static function isRole(string $role): bool
    {
        return strcasecmp(self::role(), $role) === 0;
    }

    public static function isAdmin(): bool
    {
        return self::isRole('Admin');
    }

    public static function tenantId(): ?int
    {
        $user = self::user();
        if (!$user) {
            return null;
        }

        $role = (string) ($user['role'] ?? '');
        if (strcasecmp($role, 'Admin') === 0) {
            Session::start();
            $ctx = $_SESSION['active_tenant_id'] ?? null;
            if ($ctx === null) {
                return null; // Admin can see across tenants.
            }
            return is_numeric($ctx) ? (int) $ctx : null;
        }

        $tid = $user['tenant_id'] ?? null;
        return is_numeric($tid) ? (int) $tid : null;
    }

    public static function setActiveTenant(?int $tenantId): void
    {
        Session::start();
        if (!self::isAdmin()) {
            return;
        }
        $_SESSION['active_tenant_id'] = $tenantId && $tenantId > 0 ? $tenantId : null;
    }

    public static function attempt(string $username, string $password): bool
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            return false;
        }

        $pdo = Db::pdo();
        $stmt = $pdo->prepare('
            select u.id, u.username, u.password_hash, u.role, u.active, u.tenant_id,
                   t.name as tenant_name, t.active as tenant_active
            from users u
            left join tenants t on t.id = u.tenant_id
            where lower(u.username) = lower(:u)
            limit 1
        ');
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        if (!(bool) ($row['active'] ?? false)) {
            return false;
        }
        $role = (string) ($row['role'] ?? '');
        $tenantActive = (bool) ($row['tenant_active'] ?? true);
        if (!$tenantActive && strcasecmp($role, 'Admin') !== 0) {
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
            'role' => $role !== '' ? $role : 'Cashier',
            'tenant_id' => isset($row['tenant_id']) ? (int) $row['tenant_id'] : null,
            'tenant_name' => is_string($row['tenant_name'] ?? null) ? $row['tenant_name'] : null,
        ];
        $_SESSION['active_tenant_id'] = strcasecmp($role, 'Admin') === 0 ? null : (isset($row['tenant_id']) ? (int) $row['tenant_id'] : null);
        return true;
    }

    public static function logout(): void
    {
        Session::start();
        unset($_SESSION['user']);
        unset($_SESSION['active_tenant_id']);
        Session::regenerate();
    }
}
