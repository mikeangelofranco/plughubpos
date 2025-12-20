<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

Session::start();

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) ? $path : '/';
if ($path !== '/') {
    $path = rtrim($path, '/');
    if ($path === '') {
        $path = '/';
    }
}

switch ($path) {
    case '/':
        if (!Auth::check()) {
            redirect('/login');
        }

        $pdo = Db::pdo();
        $user = Auth::user();
        $tenantId = Auth::tenantId();
        $tenants = [];
        $tenantName = $user['tenant_name'] ?? null;
        $role = $user['role'] ?? '';

        if (Auth::isAdmin()) {
            $stmt = $pdo->query('select id, name, active from tenants order by lower(name)');
            $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if ($tenantId !== null) {
                $match = array_values(array_filter($tenants, static fn ($t) => (int) ($t['id'] ?? 0) === (int) $tenantId));
                if ($match) {
                    $tenantName = (string) ($match[0]['name'] ?? 'Tenant');
                } else {
                    Auth::setActiveTenant(null);
                    $tenantId = null;
                    $tenantName = 'All Tenants';
                }
            } else {
                $tenantName = 'All Tenants';
            }
        } elseif ($tenantName === null && $tenantId !== null) {
            $stmt = $pdo->prepare('select name from tenants where id = :id limit 1');
            $stmt->execute([':id' => $tenantId]);
            $tenantName = (string) ($stmt->fetchColumn() ?: 'Tenant');
        }

        echo view('pos', [
            'title' => env('APP_NAME', 'Plughub POS Mobile'),
            'user' => $user,
            'flash' => Session::flash('success'),
            'flash_error' => Session::flash('error'),
            'tenant_id' => $tenantId,
            'tenant_name' => $tenantName,
            'tenants' => $tenants,
            'role' => $role,
        ]);
        break;

    case '/login':
        if (Auth::check()) {
            redirect('/');
        }

        $error = null;
        $username = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = (string) ($_POST['username'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            $csrf = $_POST['_csrf'] ?? null;

            if (!Csrf::validate(is_string($csrf) ? $csrf : null)) {
                $error = 'Invalid session. Please try again.';
            } elseif (trim($username) === '' || $password === '') {
                $error = 'Username and password are required.';
            } else {
                try {
                    $ok = Auth::attempt($username, $password);
                    if ($ok) {
                        Session::flash('success', 'Login successful.');
                        redirect('/');
                    }
                    $error = 'Invalid username or password.';
                } catch (PDOException $e) {
                    $error = env('APP_DEBUG', false) ? $e->getMessage() : 'Could not sign in. Please try again.';
                }
            }
        }

        echo view('login', [
            'title' => env('APP_NAME', 'Plughub POS Mobile'),
            'error' => $error,
            'username' => $username,
        ]);
        break;

    case '/logout':
        Auth::logout();
        Session::flash('success', 'Logged out.');
        redirect('/login');

    case '/tenant-config':
        if (!Auth::check()) {
            redirect('/login');
        }
        if (!Auth::isAdmin()) {
            Session::flash('error', 'Only admins can access Tenant Configuration.');
            redirect('/');
        }

        $pdo = Db::pdo();
        $flash = Session::flash('success');
        $flashError = Session::flash('error');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['_csrf'] ?? null;
            if (!Csrf::validate(is_string($csrf) ? $csrf : null)) {
                $flashError = 'Invalid session. Please try again.';
            } else {
                $action = (string) ($_POST['action'] ?? '');
                if ($action === 'add_tenant') {
                    $name = trim((string) ($_POST['name'] ?? ''));
                    $slugInput = trim((string) ($_POST['slug'] ?? ''));
                    $address = trim((string) ($_POST['address'] ?? ''));
                    $contact = trim((string) ($_POST['contact_number'] ?? ''));
                    if ($name === '') {
                        $flashError = 'Tenant name is required.';
                    } else {
                        $slug = strtolower($slugInput !== '' ? $slugInput : $name);
                        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug) ?? $slug;
                        $slug = trim($slug, '-');
                        if ($slug === '') {
                            $slug = 'tenant-' . bin2hex(random_bytes(3));
                        }
                        try {
                            $stmt = $pdo->prepare('insert into tenants (name, slug, address, contact_number, active) values (:n, :s, :a, :c, true)');
                            $stmt->execute([
                                ':n' => $name,
                                ':s' => $slug,
                                ':a' => $address !== '' ? $address : null,
                                ':c' => $contact !== '' ? $contact : null,
                            ]);
                            Session::flash('success', 'Tenant created.');
                            redirect('/tenant-config');
                        } catch (Throwable $e) {
                            $flashError = env('APP_DEBUG', false) ? $e->getMessage() : 'Could not create tenant (name/slug may already exist).';
                        }
                    }
                } elseif ($action === 'update_tenant') {
                    $id = (int) ($_POST['id'] ?? 0);
                    $name = trim((string) ($_POST['name'] ?? ''));
                    $slugInput = trim((string) ($_POST['slug'] ?? ''));
                    $address = trim((string) ($_POST['address'] ?? ''));
                    $contact = trim((string) ($_POST['contact_number'] ?? ''));
                    if ($id <= 0) {
                        $flashError = 'Invalid tenant.';
                    } elseif ($name === '') {
                        $flashError = 'Tenant name is required.';
                    } else {
                        $slug = strtolower($slugInput !== '' ? $slugInput : $name);
                        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug) ?? $slug;
                        $slug = trim($slug, '-');
                        if ($slug === '') {
                            $slug = 'tenant-' . bin2hex(random_bytes(3));
                        }
                        try {
                            $stmt = $pdo->prepare('
                                update tenants
                                set name = :n, slug = :s, address = :a, contact_number = :c
                                where id = :id
                            ');
                            $stmt->execute([
                                ':n' => $name,
                                ':s' => $slug,
                                ':a' => $address !== '' ? $address : null,
                                ':c' => $contact !== '' ? $contact : null,
                                ':id' => $id,
                            ]);
                            Session::flash('success', 'Tenant updated.');
                            redirect('/tenant-config');
                        } catch (Throwable $e) {
                            $flashError = env('APP_DEBUG', false) ? $e->getMessage() : 'Could not update tenant.';
                        }
                    }
                } elseif ($action === 'add_user') {
                    $username = trim((string) ($_POST['username'] ?? ''));
                    $password = (string) ($_POST['password'] ?? '');
                    $role = trim((string) ($_POST['role'] ?? ''));
                    $rawTenant = $_POST['tenant_id'] ?? '';
                    $tenantId = is_string($rawTenant) && $rawTenant !== '' ? (int) $rawTenant : null;
                    $fullName = trim((string) ($_POST['full_name'] ?? ''));
                    $contact = trim((string) ($_POST['contact_number'] ?? ''));

                    $validRoles = ['Admin', 'Manager', 'Cashier', 'Readonly'];
                    if ($username === '' || $password === '') {
                        $flashError = 'Username and password are required.';
                    } elseif (!in_array($role, $validRoles, true)) {
                        $flashError = 'Select a valid role.';
                    } elseif (strcasecmp($role, 'Admin') !== 0 && $tenantId === null) {
                        $flashError = 'Select a tenant for this user.';
                    } else {
                        try {
                            $hash = password_hash($password, PASSWORD_BCRYPT);
                            $stmt = $pdo->prepare('
                                insert into users (tenant_id, username, password_hash, role, full_name, contact_number, active)
                                values (:tid, :u, :p, :r, :fn, :cn, true)
                            ');
                            $stmt->execute([
                                ':tid' => $tenantId,
                                ':u' => $username,
                                ':p' => $hash,
                                ':r' => $role,
                                ':fn' => $fullName !== '' ? $fullName : null,
                                ':cn' => $contact !== '' ? $contact : null,
                            ]);
                            Session::flash('success', 'User created.');
                            redirect('/tenant-config');
                        } catch (Throwable $e) {
                            $flashError = env('APP_DEBUG', false) ? $e->getMessage() : 'Could not create user (username may already exist).';
                        }
                    }
                } elseif ($action === 'update_user') {
                    $id = (int) ($_POST['id'] ?? 0);
                    $username = trim((string) ($_POST['username'] ?? ''));
                    $role = trim((string) ($_POST['role'] ?? ''));
                    $rawTenant = $_POST['tenant_id'] ?? '';
                    $tenantId = is_string($rawTenant) && $rawTenant !== '' ? (int) $rawTenant : null;
                    $fullName = trim((string) ($_POST['full_name'] ?? ''));
                    $contact = trim((string) ($_POST['contact_number'] ?? ''));
                    $validRoles = ['Admin', 'Manager', 'Cashier', 'Readonly'];
                    if ($id <= 0) {
                        $flashError = 'Invalid user.';
                    } elseif ($username === '') {
                        $flashError = 'Username is required.';
                    } elseif (!in_array($role, $validRoles, true)) {
                        $flashError = 'Select a valid role.';
                    } elseif (strcasecmp($role, 'Admin') !== 0 && $tenantId === null) {
                        $flashError = 'Select a tenant for this user.';
                    } else {
                        try {
                            $stmt = $pdo->prepare('
                                update users
                                set username = :u, role = :r, tenant_id = :tid, full_name = :fn, contact_number = :cn
                                where id = :id
                            ');
                            $stmt->execute([
                                ':u' => $username,
                                ':r' => $role,
                                ':tid' => $tenantId,
                                ':fn' => $fullName !== '' ? $fullName : null,
                                ':cn' => $contact !== '' ? $contact : null,
                                ':id' => $id,
                            ]);
                            Session::flash('success', 'User updated.');
                            redirect('/tenant-config');
                        } catch (Throwable $e) {
                            $flashError = env('APP_DEBUG', false) ? $e->getMessage() : 'Could not update user.';
                        }
                    }
                } elseif ($action === 'delete_user') {
                    $id = (int) ($_POST['id'] ?? 0);
                    if ($id <= 0) {
                        $flashError = 'Invalid user.';
                    } else {
                        try {
                            $stmt = $pdo->prepare('delete from users where id = :id');
                            $stmt->execute([':id' => $id]);
                            Session::flash('success', 'User deleted.');
                            redirect('/tenant-config');
                        } catch (Throwable $e) {
                            $flashError = env('APP_DEBUG', false) ? $e->getMessage() : 'Could not delete user.';
                        }
                    }
                }
            }
        }

        $tenants = [];
        $users = [];
        try {
            $stmt = $pdo->query('select id, name, slug, active, address, contact_number, created_at from tenants order by lower(name)');
            $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $stmt = $pdo->query('
                select u.id, u.username, u.role, u.active, u.tenant_id, u.created_at, u.full_name, u.contact_number, t.name as tenant_name
                from users u
                left join tenants t on t.id = u.tenant_id
                order by lower(u.username)
            ');
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            $flashError = $flashError ?: (env('APP_DEBUG', false) ? $e->getMessage() : 'Could not load configuration data.');
        }

        $tenantQuery = trim((string) ($_GET['tenant_q'] ?? ''));
        $userQuery = trim((string) ($_GET['user_q'] ?? ''));

        if ($tenantQuery !== '') {
            $q = strtolower($tenantQuery);
            $tenants = array_values(array_filter($tenants, static function (array $t) use ($q): bool {
                $name = strtolower((string) ($t['name'] ?? ''));
                $addr = strtolower((string) ($t['address'] ?? ''));
                $contact = strtolower((string) ($t['contact_number'] ?? ''));
                return str_contains($name, $q) || str_contains($addr, $q) || str_contains($contact, $q);
            }));
        }

        if ($userQuery !== '') {
            $q = strtolower($userQuery);
            $users = array_values(array_filter($users, static function (array $u) use ($q): bool {
                $username = strtolower((string) ($u['username'] ?? ''));
                $full = strtolower((string) ($u['full_name'] ?? ''));
                $contact = strtolower((string) ($u['contact_number'] ?? ''));
                $tenant = strtolower((string) ($u['tenant_name'] ?? ''));
                return str_contains($username, $q) || str_contains($full, $q) || str_contains($contact, $q) || str_contains($tenant, $q);
            }));
        }

        echo view('tenant_config', [
            'title' => 'Tenant Configuration',
            'flash' => $flash,
            'flash_error' => $flashError,
            'tenants' => $tenants,
            'users' => $users,
            'tenant_q' => $tenantQuery,
            'user_q' => $userQuery,
        ]);
        break;

    case '/manage-users':
        if (!Auth::check()) {
            redirect('/login');
        }
        if (Auth::isAdmin()) {
            redirect('/tenant-config');
        }
        $role = Auth::role();
        if (strcasecmp($role, 'Manager') !== 0) {
            Session::flash('error', 'Only managers can manage users.');
            redirect('/');
        }
        $tenantId = Auth::tenantId();
        if ($tenantId === null) {
            Session::flash('error', 'No tenant context found.');
            redirect('/');
        }

        $pdo = Db::pdo();
        $flash = Session::flash('success');
        $flashError = Session::flash('error');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['_csrf'] ?? null;
            if (!Csrf::validate(is_string($csrf) ? $csrf : null)) {
                $flashError = 'Invalid session. Please try again.';
            } else {
                $action = (string) ($_POST['action'] ?? '');
                $allowedRoles = ['Manager', 'Cashier', 'Readonly'];
                if ($action === 'add_user') {
                    $username = trim((string) ($_POST['username'] ?? ''));
                    $password = (string) ($_POST['password'] ?? '');
                    $roleValue = trim((string) ($_POST['role'] ?? ''));
                    $fullName = trim((string) ($_POST['full_name'] ?? ''));
                    $contact = trim((string) ($_POST['contact_number'] ?? ''));
                    if ($username === '' || $password === '') {
                        $flashError = 'Username and password are required.';
                    } elseif (!in_array($roleValue, $allowedRoles, true)) {
                        $flashError = 'Select a valid role.';
                    } else {
                        try {
                            $hash = password_hash($password, PASSWORD_BCRYPT);
                            $stmt = $pdo->prepare('
                                insert into users (tenant_id, username, password_hash, role, full_name, contact_number, active)
                                values (:tid, :u, :p, :r, :fn, :cn, true)
                            ');
                            $stmt->execute([
                                ':tid' => $tenantId,
                                ':u' => $username,
                                ':p' => $hash,
                                ':r' => $roleValue,
                                ':fn' => $fullName !== '' ? $fullName : null,
                                ':cn' => $contact !== '' ? $contact : null,
                            ]);
                            Session::flash('success', 'User created.');
                            redirect('/manage-users');
                        } catch (Throwable $e) {
                            $flashError = env('APP_DEBUG', false) ? $e->getMessage() : 'Could not create user.';
                        }
                    }
                } elseif ($action === 'update_user') {
                    $id = (int) ($_POST['id'] ?? 0);
                    $username = trim((string) ($_POST['username'] ?? ''));
                    $roleValue = trim((string) ($_POST['role'] ?? ''));
                    $fullName = trim((string) ($_POST['full_name'] ?? ''));
                    $contact = trim((string) ($_POST['contact_number'] ?? ''));
                    if ($id <= 0) {
                        $flashError = 'Invalid user.';
                    } elseif ($username === '') {
                        $flashError = 'Username is required.';
                    } elseif (!in_array($roleValue, $allowedRoles, true)) {
                        $flashError = 'Select a valid role.';
                    } else {
                        try {
                            $stmt = $pdo->prepare('
                                update users
                                set username = :u, role = :r, full_name = :fn, contact_number = :cn
                                where id = :id and tenant_id = :tid
                            ');
                            $stmt->execute([
                                ':u' => $username,
                                ':r' => $roleValue,
                                ':fn' => $fullName !== '' ? $fullName : null,
                                ':cn' => $contact !== '' ? $contact : null,
                                ':id' => $id,
                                ':tid' => $tenantId,
                            ]);
                            if ($stmt->rowCount() < 1) {
                                $flashError = 'User not found in your tenant.';
                            } else {
                                Session::flash('success', 'User updated.');
                                redirect('/manage-users');
                            }
                        } catch (Throwable $e) {
                            $flashError = env('APP_DEBUG', false) ? $e->getMessage() : 'Could not update user.';
                        }
                    }
                } elseif ($action === 'delete_user') {
                    $id = (int) ($_POST['id'] ?? 0);
                    if ($id <= 0) {
                        $flashError = 'Invalid user.';
                    } else {
                        try {
                            $stmt = $pdo->prepare('delete from users where id = :id and tenant_id = :tid');
                            $stmt->execute([':id' => $id, ':tid' => $tenantId]);
                            if ($stmt->rowCount() < 1) {
                                $flashError = 'User not found in your tenant.';
                            } else {
                                Session::flash('success', 'User deleted.');
                                redirect('/manage-users');
                            }
                        } catch (Throwable $e) {
                            $flashError = env('APP_DEBUG', false) ? $e->getMessage() : 'Could not delete user.';
                        }
                    }
                }
            }
        }

        $tenantName = null;
        $users = [];
        try {
            $stmt = $pdo->prepare('select name from tenants where id = :id limit 1');
            $stmt->execute([':id' => $tenantId]);
            $tenantName = (string) ($stmt->fetchColumn() ?: 'Tenant');

            $stmt = $pdo->prepare('
                select id, username, role, full_name, contact_number, active
                from users
                where tenant_id = :tid
                order by lower(username)
            ');
            $stmt->execute([':tid' => $tenantId]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            $flashError = $flashError ?: (env('APP_DEBUG', false) ? $e->getMessage() : 'Could not load users.');
        }

        $userQuery = trim((string) ($_GET['q'] ?? ''));
        if ($userQuery !== '') {
            $q = strtolower($userQuery);
            $users = array_values(array_filter($users, static function (array $u) use ($q): bool {
                $username = strtolower((string) ($u['username'] ?? ''));
                $full = strtolower((string) ($u['full_name'] ?? ''));
                $contact = strtolower((string) ($u['contact_number'] ?? ''));
                return str_contains($username, $q) || str_contains($full, $q) || str_contains($contact, $q);
            }));
        }

        echo view('manage_users', [
            'title' => 'Manage Users',
            'flash' => $flash,
            'flash_error' => $flashError,
            'users' => $users,
            'tenant_name' => $tenantName,
            'tenant_id' => $tenantId,
            'query' => $userQuery,
        ]);
        break;

    case '/switch-tenant':
        if (!Auth::check() || !Auth::isAdmin()) {
            redirect('/login');
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/');
        }
        $csrf = $_POST['_csrf'] ?? null;
        if (!Csrf::validate(is_string($csrf) ? $csrf : null)) {
            Session::flash('error', 'Invalid session. Please try again.');
            redirect('/');
        }
        $rawTenantId = $_POST['tenant_id'] ?? '';
        $tenantId = null;
        if (is_string($rawTenantId) && trim($rawTenantId) !== '') {
            $tid = (int) $rawTenantId;
            $tenantId = $tid > 0 ? $tid : null;
        }
        try {
            $pdo = Db::pdo();
            if ($tenantId !== null) {
                $stmt = $pdo->prepare('select id, name from tenants where id = :id limit 1');
                $stmt->execute([':id' => $tenantId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    Session::flash('error', 'Tenant not found.');
                    redirect('/');
                }
                Auth::setActiveTenant((int) $row['id']);
                Session::flash('success', 'Switched to tenant: ' . (string) $row['name']);
            } else {
                Auth::setActiveTenant(null);
                Session::flash('success', 'Viewing all tenants.');
            }
        } catch (Throwable $e) {
            Session::flash('error', env('APP_DEBUG', false) ? $e->getMessage() : 'Could not switch tenant.');
        }
        redirect('/');

    case '/api/health':
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
                'error' => env('APP_DEBUG', false) ? $e->getMessage() : 'Database error',
            ], JSON_UNESCAPED_SLASHES);
        }
        break;

    case '/api/categories':
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_SLASHES);
            break;
        }
        header('Content-Type: application/json; charset=utf-8');
        try {
            $pdo = Db::pdo();
            $tenantId = Auth::tenantId();
            $params = [];
            $sql = 'select id, name, tenant_id from categories where active = true';
            if ($tenantId !== null) {
                $sql .= ' and (tenant_id = :tid or tenant_id is null)';
                $params[':tid'] = $tenantId;
            }
            $sql .= ' order by lower(name)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            echo json_encode([
                'ok' => true,
                'categories' => $categories,
            ], JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => env('APP_DEBUG', false) ? $e->getMessage() : 'Could not load categories',
            ], JSON_UNESCAPED_SLASHES);
        }
        break;

    case '/api/products':
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_SLASHES);
            break;
        }
        header('Content-Type: application/json; charset=utf-8');
        try {
            $pdo = Db::pdo();
            $tenantId = Auth::tenantId();
            $params = [];
            $sql = '
                select id, sku, name, price_cents, category_id, tenant_id
                from products
                where active = true
            ';
            if ($tenantId !== null) {
                $sql .= ' and (tenant_id = :tid or tenant_id is null)';
                $params[':tid'] = $tenantId;
            }
            $sql .= ' order by lower(name)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            echo json_encode([
                'ok' => true,
                'products' => $products,
            ], JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => env('APP_DEBUG', false) ? $e->getMessage() : 'Could not load products',
            ], JSON_UNESCAPED_SLASHES);
        }
        break;

    default:
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo view('not_found', [
            'title' => env('APP_NAME', 'Plughub POS Mobile'),
            'path' => $path,
        ]);
        break;
}
