<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

Session::start();

$apiJson = static function (array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
};

$ensureSalesColumns = static function (PDO $pdo): void {
    // Make sure the new sale columns exist when running against older DBs.
    $ddl = [
        "alter table orders add column if not exists receipt_no text",
        "alter table orders add column if not exists transaction_id text",
        "alter table orders add column if not exists discount_cents integer not null default 0",
        "alter table orders add column if not exists change_cents integer not null default 0",
        "alter table orders add column if not exists amount_received_cents integer not null default 0",
        "alter table orders add column if not exists payment_method text not null default 'cash'",
        "alter table orders add column if not exists cashier_name text",
        "alter table orders add column if not exists cashier_username text",
    ];
    foreach ($ddl as $sql) {
        try {
            $pdo->exec($sql);
        } catch (Throwable) {
            // Ignore; likely already applied or insufficient perms.
        }
    }
};

$ensureInventorySchema = static function (PDO $pdo): void {
    $ddl = [
        "alter table products add column if not exists qty_on_hand integer not null default 0",
        "alter table products add column if not exists cost_cents integer not null default 0",
        "create table if not exists inventory_movements (
            id bigserial primary key,
            product_id bigint not null references products(id) on delete cascade,
            tenant_id bigint null references tenants(id) on delete set null,
            change_qty integer not null,
            reason text not null,
            ref_type text null,
            ref_id bigint null,
            created_by bigint null references users(id) on delete set null,
            created_at timestamptz not null default now()
        )",
        "alter table inventory_movements add column if not exists tenant_id bigint references tenants(id) on delete set null",
        "alter table inventory_movements add column if not exists ref_type text",
        "alter table inventory_movements add column if not exists ref_id bigint",
        "alter table inventory_movements add column if not exists created_by bigint references users(id) on delete set null",
        "alter table inventory_movements add column if not exists note text",
        "create index if not exists inventory_movements_product_idx on inventory_movements(product_id)",
        "create index if not exists inventory_movements_tenant_idx on inventory_movements(tenant_id)",
        "create index if not exists inventory_movements_ref_idx on inventory_movements(ref_type, ref_id)",
        "create index if not exists inventory_movements_created_idx on inventory_movements(created_at desc)"
    ];
    foreach ($ddl as $sql) {
        try {
            $pdo->exec($sql);
        } catch (Throwable) {
            // safe fallback; ignore if missing perms
        }
    }
};

$loadSale = static function (PDO $pdo, string $identifier, ?int $tenantId = null): ?array {
    $params = [];
    $clauses = ["o.status = 'paid'"];
    if ($tenantId !== null) {
        $clauses[] = 'o.tenant_id = :tid';
        $params[':tid'] = $tenantId;
    }
    $token = trim(strtolower($identifier));
    if ($token === '') {
        return null;
    }

    if (ctype_digit($token)) {
        $clauses[] = 'o.id = :id';
        $params[':id'] = (int) $token;
    } else {
        $clauses[] = '(lower(o.transaction_id) = :tok or lower(o.receipt_no) = :tok)';
        $params[':tok'] = $token;
    }

    $sql = "
        select o.id, o.tenant_id,
               coalesce(o.transaction_id, concat('TXN-', o.id)) as transaction_id,
               coalesce(o.receipt_no, concat('RCPT-', o.id)) as receipt_no,
               o.status,
               o.subtotal_cents, o.discount_cents, o.tax_cents, o.total_cents,
               o.amount_received_cents, o.change_cents, o.payment_method,
               o.cashier_name, o.cashier_username, o.created_by,
               o.created_at, o.paid_at,
               t.name as tenant_name, t.address as tenant_address, t.contact_number as tenant_contact
        from orders o
        left join tenants t on t.id = o.tenant_id
        where " . implode(' and ', $clauses) . "
        limit 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        return null;
    }
    $orderId = (int) $order['id'];

    $stmt = $pdo->prepare('
        select name_snapshot as name, sku_snapshot as sku, unit_price_cents, qty, line_total_cents
        from order_items
        where order_id = :id
        order by id
    ');
    $stmt->execute([':id' => $orderId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $stmt = $pdo->prepare('select method, amount_cents from payments where order_id = :id');
    $stmt->execute([':id' => $orderId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $paid = array_reduce($payments, static function (int $carry, array $p): int {
        return $carry + (int) ($p['amount_cents'] ?? 0);
    }, 0);

    $amountReceived = isset($order['amount_received_cents']) ? (int) $order['amount_received_cents'] : $paid;
    $total = (int) ($order['total_cents'] ?? 0);
    $change = (int) ($order['change_cents'] ?? max(0, $amountReceived - $total));

    return [
        'id' => $orderId,
        'tenant_id' => isset($order['tenant_id']) ? (int) $order['tenant_id'] : null,
        'tenant_name' => is_string($order['tenant_name'] ?? null) ? $order['tenant_name'] : null,
        'tenant_address' => is_string($order['tenant_address'] ?? null) ? $order['tenant_address'] : null,
        'tenant_contact' => is_string($order['tenant_contact'] ?? null) ? $order['tenant_contact'] : null,
        'transaction_id' => (string) ($order['transaction_id'] ?? ''),
        'receipt_no' => (string) ($order['receipt_no'] ?? ''),
        'status' => (string) ($order['status'] ?? ''),
        'subtotal_cents' => (int) ($order['subtotal_cents'] ?? 0),
        'discount_cents' => (int) ($order['discount_cents'] ?? 0),
        'tax_cents' => (int) ($order['tax_cents'] ?? 0),
        'total_cents' => $total,
        'amount_received_cents' => $amountReceived,
        'change_cents' => $change,
        'payment_method' => strtolower((string) ($order['payment_method'] ?? 'cash')),
        'cashier_name' => is_string($order['cashier_name'] ?? null) ? $order['cashier_name'] : null,
        'cashier_username' => is_string($order['cashier_username'] ?? null) ? $order['cashier_username'] : null,
        'created_by' => isset($order['created_by']) ? (int) $order['created_by'] : null,
        'created_at' => $order['created_at'] ?? null,
        'paid_at' => $order['paid_at'] ?? null,
        'items' => array_map(static function (array $i): array {
            return [
                'name' => (string) ($i['name'] ?? ''),
                'sku' => (string) ($i['sku'] ?? ''),
                'unit_price_cents' => (int) ($i['unit_price_cents'] ?? 0),
                'qty' => (int) ($i['qty'] ?? 0),
                'line_total_cents' => (int) ($i['line_total_cents'] ?? 0),
            ];
        }, $items),
        'payments' => array_map(static function (array $p): array {
            return [
                'method' => strtolower((string) ($p['method'] ?? '')),
                'amount_cents' => (int) ($p['amount_cents'] ?? 0),
            ];
        }, $payments),
    ];
};

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
        $tenantAddress = null;
        $tenantContact = null;
        $role = $user['role'] ?? '';

        if (Auth::isAdmin()) {
            $stmt = $pdo->query('select id, name, active, address, contact_number from tenants order by lower(name)');
            $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if ($tenantId !== null) {
                $match = array_values(array_filter($tenants, static fn ($t) => (int) ($t['id'] ?? 0) === (int) $tenantId));
                if ($match) {
                    $tenantName = (string) ($match[0]['name'] ?? 'Tenant');
                    $tenantAddress = is_string($match[0]['address'] ?? null) ? $match[0]['address'] : null;
                    $tenantContact = is_string($match[0]['contact_number'] ?? null) ? $match[0]['contact_number'] : null;
                } else {
                    Auth::setActiveTenant(null);
                    $tenantId = null;
                    $tenantName = 'All Tenants';
                }
            } else {
                $tenantName = 'All Tenants';
            }
        } elseif ($tenantName === null && $tenantId !== null) {
            $stmt = $pdo->prepare('select name, address, contact_number from tenants where id = :id limit 1');
            $stmt->execute([':id' => $tenantId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $tenantName = (string) ($row['name'] ?? 'Tenant');
            $tenantAddress = is_string($row['address'] ?? null) ? $row['address'] : null;
            $tenantContact = is_string($row['contact_number'] ?? null) ? $row['contact_number'] : null;
        }

        echo view('pos', [
            'title' => 'Mobile POS',
            'user' => $user,
            'flash' => Session::flash('success'),
            'flash_error' => Session::flash('error'),
            'tenant_id' => $tenantId,
            'tenant_name' => $tenantName,
            'tenant_address' => $tenantAddress,
            'tenant_contact' => $tenantContact,
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
            'title' => 'Mobile POS',
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
                      Session::flash('error', 'Tenant creation is disabled in this demo.');
                      redirect('/tenant-config');
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

    case '/sales-history':
        if (!Auth::check()) {
            redirect('/login');
        }

        $pdo = Db::pdo();
        $user = Auth::user();
        $tenantId = Auth::tenantId();
        $tenantName = $user['tenant_name'] ?? null;
        $tenantAddress = null;
        $tenantContact = null;
        $role = $user['role'] ?? '';

        if (Auth::isAdmin()) {
            $stmt = $pdo->query('select id, name, address, contact_number from tenants order by lower(name)');
            $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if ($tenantId !== null) {
                $match = array_values(array_filter($tenants, static fn ($t) => (int) ($t['id'] ?? 0) === (int) $tenantId));
                if ($match) {
                    $tenantName = (string) ($match[0]['name'] ?? 'Tenant');
                    $tenantAddress = is_string($match[0]['address'] ?? null) ? $match[0]['address'] : null;
                    $tenantContact = is_string($match[0]['contact_number'] ?? null) ? $match[0]['contact_number'] : null;
                } else {
                    Auth::setActiveTenant(null);
                    $tenantId = null;
                    $tenantName = 'All Tenants';
                }
            } else {
                $tenantName = 'All Tenants';
            }
        } elseif ($tenantName === null && $tenantId !== null) {
            $stmt = $pdo->prepare('select name, address, contact_number from tenants where id = :id limit 1');
            $stmt->execute([':id' => $tenantId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $tenantName = (string) ($row['name'] ?? 'Tenant');
            $tenantAddress = is_string($row['address'] ?? null) ? $row['address'] : null;
            $tenantContact = is_string($row['contact_number'] ?? null) ? $row['contact_number'] : null;
        }

        echo view('sales_history', [
            'title' => 'Sales History',
            'user' => $user,
            'tenant_id' => $tenantId,
            'tenant_name' => $tenantName,
            'tenant_address' => $tenantAddress,
            'tenant_contact' => $tenantContact,
            'role' => $role,
        ]);
        break;

    case '/reports':
        if (!Auth::check()) {
            redirect('/login');
        }
        $role = Auth::role();
        if (!(Auth::isAdmin() || strcasecmp($role, 'Manager') === 0)) {
            Session::flash('error', 'Reports are available to managers and admins only.');
            redirect('/');
        }

        $pdo = Db::pdo();
        $user = Auth::user();
        $tenantId = Auth::tenantId();
        $tenantName = $user['tenant_name'] ?? null;
        $tenantAddress = null;
        $tenantContact = null;

        if (Auth::isAdmin()) {
            $stmt = $pdo->query('select id, name, address, contact_number from tenants order by lower(name)');
            $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if ($tenantId !== null) {
                $match = array_values(array_filter($tenants, static fn ($t) => (int) ($t['id'] ?? 0) === (int) $tenantId));
                if ($match) {
                    $tenantName = (string) ($match[0]['name'] ?? 'Tenant');
                    $tenantAddress = is_string($match[0]['address'] ?? null) ? $match[0]['address'] : null;
                    $tenantContact = is_string($match[0]['contact_number'] ?? null) ? $match[0]['contact_number'] : null;
                } else {
                    Auth::setActiveTenant(null);
                    $tenantId = null;
                    $tenantName = 'All Tenants';
                }
            } else {
                $tenantName = 'All Tenants';
            }
        } elseif ($tenantName === null && $tenantId !== null) {
            $stmt = $pdo->prepare('select name, address, contact_number from tenants where id = :id limit 1');
            $stmt->execute([':id' => $tenantId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $tenantName = (string) ($row['name'] ?? 'Tenant');
            $tenantAddress = is_string($row['address'] ?? null) ? $row['address'] : null;
            $tenantContact = is_string($row['contact_number'] ?? null) ? $row['contact_number'] : null;
        }

        echo view('reports', [
            'title' => 'Reports',
            'user' => $user,
            'tenant_id' => $tenantId,
            'tenant_name' => $tenantName,
            'tenant_address' => $tenantAddress,
            'tenant_contact' => $tenantContact,
            'role' => $role,
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

    case '/inventory':
        if (!Auth::check()) {
            redirect('/login');
        }
        if (!(Auth::isAdmin() || strcasecmp(Auth::role(), 'Manager') === 0)) {
            Session::flash('error', 'Only admins or managers can access Inventory.');
            redirect('/');
        }
        $pdo = Db::pdo();
        $ensureInventorySchema($pdo);
        $tenantId = Auth::tenantId();
        $tenantName = 'All Tenants';
        if ($tenantId !== null) {
            $stmt = $pdo->prepare('select name from tenants where id = :id limit 1');
            $stmt->execute([':id' => $tenantId]);
            $tenantName = (string) ($stmt->fetchColumn() ?: 'Tenant');
        }
        $categories = [];
        $products = [];
        try {
            $catParams = [];
            $catSql = 'select id, name from categories where active = true';
            if ($tenantId !== null) {
                $catSql .= ' and (tenant_id = :tid or tenant_id is null)';
                $catParams[':tid'] = $tenantId;
            }
            $catSql .= ' order by lower(name)';
            $stmt = $pdo->prepare($catSql);
            $stmt->execute($catParams);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $prodParams = [];
            try {
                $prodSql = '
                    select p.id, p.sku, p.name, p.price_cents, p.cost_cents, p.category_id, p.tenant_id,
                           coalesce(m.qty, p.qty_on_hand) as qty_on_hand,
                           c.name as category_name
                    from products p
                    left join (
                        select product_id, sum(change_qty) as qty
                        from inventory_movements
                        group by product_id
                    ) m on m.product_id = p.id
                    left join categories c on c.id = p.category_id
                    where p.active = true
                ';
                if ($tenantId !== null) {
                    $prodSql .= ' and (p.tenant_id = :tid or p.tenant_id is null)';
                    $prodParams[':tid'] = $tenantId;
                }
                $prodSql .= ' order by lower(p.name)';
                $stmt = $pdo->prepare($prodSql);
                $stmt->execute($prodParams);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) {
                $prodSql = '
                    select p.id, p.sku, p.name, p.price_cents, p.category_id, p.tenant_id, p.qty_on_hand,
                           c.name as category_name
                    from products p
                    left join categories c on c.id = p.category_id
                    where p.active = true
                ';
                if ($tenantId !== null) {
                    $prodSql .= ' and (p.tenant_id = :tid or p.tenant_id is null)';
                    $prodParams = [':tid' => $tenantId];
                } else {
                    $prodParams = [];
                }
                $prodSql .= ' order by lower(p.name)';
                $stmt = $pdo->prepare($prodSql);
                $stmt->execute($prodParams);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
        } catch (Throwable $e) {
            Session::flash('error', env('APP_DEBUG', false) ? $e->getMessage() : 'Could not load inventory.');
        }

        echo view('inventory', [
            'title' => 'Products',
            'tenant_name' => $tenantName,
            'tenant_id' => $tenantId,
            'categories' => $categories,
            'products' => $products,
            'flash' => Session::flash('success'),
            'flash_error' => Session::flash('error'),
        ]);
        break;

    case '/api/inventory/categories':
        if (!Auth::check()) {
            $apiJson(['ok' => false, 'error' => 'Unauthorized'], 401);
            break;
        }
        $role = strtolower((string) Auth::role());
        if (!in_array($role, ['admin', 'manager'], true)) {
            $apiJson(['ok' => false, 'error' => 'Forbidden'], 403);
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $apiJson(['ok' => false, 'error' => 'Method not allowed'], 405);
            break;
        }
        $tenantId = Auth::tenantId();
        if ($tenantId === null) {
            $apiJson(['ok' => false, 'error' => 'Select a tenant before creating categories.'], 400);
            break;
        }
        $raw = (string) file_get_contents('php://input');
        $payload = json_decode($raw, true);
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            $apiJson(['ok' => false, 'error' => 'Category name is required.'], 400);
            break;
        }
        try {
            $pdo = Db::pdo();
            $ensureInventorySchema($pdo);
            $stmt = $pdo->prepare('insert into categories (tenant_id, name, active) values (:tid, :n, true) returning id, name, tenant_id');
            $stmt->execute([':tid' => $tenantId, ':n' => $name]);
            $cat = $stmt->fetch(PDO::FETCH_ASSOC);
            $apiJson(['ok' => true, 'category' => $cat]);
        } catch (Throwable $e) {
            $apiJson(['ok' => false, 'error' => env('APP_DEBUG', false) ? $e->getMessage() : 'Could not create category'], 500);
        }
        break;

    case '/api/inventory/products':
        if (!Auth::check()) {
            $apiJson(['ok' => false, 'error' => 'Unauthorized'], 401);
            break;
        }
        $role = strtolower((string) Auth::role());
        if (!in_array($role, ['admin', 'manager'], true)) {
            $apiJson(['ok' => false, 'error' => 'Forbidden'], 403);
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $apiJson(['ok' => false, 'error' => 'Method not allowed'], 405);
            break;
        }
        $tenantId = Auth::tenantId();
        if ($tenantId === null) {
            $apiJson(['ok' => false, 'error' => 'Select a tenant before creating products.'], 400);
            break;
        }
        $raw = (string) file_get_contents('php://input');
        $payload = json_decode($raw, true);
        $name = trim((string) ($payload['name'] ?? ''));
        $sku = trim((string) ($payload['sku'] ?? ''));
        $priceCents = (int) ($payload['price_cents'] ?? 0);
        $costCents = (int) ($payload['cost_cents'] ?? 0);
        $qty = (int) ($payload['qty_on_hand'] ?? 0);
        $categoryId = isset($payload['category_id']) && $payload['category_id'] !== '' ? (int) $payload['category_id'] : null;
        if ($name === '') {
            $apiJson(['ok' => false, 'error' => 'Product name is required.'], 400);
            break;
        }
        if ($priceCents <= 0 || $costCents < 0) {
            $apiJson(['ok' => false, 'error' => 'Price must be greater than zero and cost must be non-negative.'], 400);
            break;
        }
        if ($qty < 0) {
            $apiJson(['ok' => false, 'error' => 'Quantity cannot be negative.'], 400);
            break;
        }
        if ($sku === '') {
            $base = strtoupper(preg_replace('/[^A-Z0-9]+/', '-', $name) ?? '');
            $base = trim($base, '-');
            if ($base === '') {
                $base = 'SKU';
            }
            $sku = substr($base, 0, 12) . '-' . random_int(1000, 9999);
        }
        try {
            $pdo = Db::pdo();
            $ensureInventorySchema($pdo);
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('
                insert into products (tenant_id, sku, name, category_id, price_cents, cost_cents, qty_on_hand, active)
                values (:tid, :sku, :name, :cat, :price, :cost, 0, true)
                returning id
            ');
            $stmt->execute([
                ':tid' => $tenantId,
                ':sku' => $sku,
                ':name' => $name,
                ':cat' => $categoryId,
                ':price' => $priceCents,
                ':cost' => $costCents,
            ]);
            $pid = (int) ($stmt->fetchColumn() ?: 0);
            if ($pid <= 0) {
                throw new RuntimeException('Could not create product.');
            }
            if ($qty !== 0) {
                $stmtMove = $pdo->prepare('
                    insert into inventory_movements (product_id, tenant_id, change_qty, reason, ref_type, ref_id, created_by)
                    values (:pid, :tid, :chg, :rsn, :rtype, :rid, :cb)
                ');
                $user = Auth::user();
                $cb = isset($user['id']) ? (int) $user['id'] : null;
                $stmtMove->execute([
                    ':pid' => $pid,
                    ':tid' => $tenantId,
                    ':chg' => $qty,
                    ':rsn' => 'adjustment',
                    ':rtype' => 'product_seed',
                    ':rid' => null,
                    ':cb' => $cb,
                ]);
            }
            $pdo->commit();
            $apiJson(['ok' => true, 'product_id' => $pid]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            $apiJson(['ok' => false, 'error' => env('APP_DEBUG', false) ? $e->getMessage() : 'Could not create product'], 500);
        }
        break;

    case '/api/inventory/adjust':
        if (!Auth::check()) {
            $apiJson(['ok' => false, 'error' => 'Unauthorized'], 401);
            break;
        }
        $role = strtolower((string) Auth::role());
        if (!in_array($role, ['admin', 'manager'], true)) {
            $apiJson(['ok' => false, 'error' => 'Forbidden'], 403);
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $apiJson(['ok' => false, 'error' => 'Method not allowed'], 405);
            break;
        }
        $tenantId = Auth::tenantId();
        if ($tenantId === null) {
            $apiJson(['ok' => false, 'error' => 'Select a tenant before adjusting stock.'], 400);
            break;
        }
        $raw = (string) file_get_contents('php://input');
        $payload = json_decode($raw, true);
        $productId = isset($payload['product_id']) ? (int) $payload['product_id'] : 0;
        $type = strtolower(trim((string) ($payload['adjustment_type'] ?? '')));
        $qty = (int) ($payload['quantity'] ?? 0);
        $reason = trim((string) ($payload['reason'] ?? ''));
        $note = trim((string) ($payload['note'] ?? ''));
        $validTypes = ['add', 'remove', 'correction'];
        if ($productId <= 0) {
            $apiJson(['ok' => false, 'error' => 'Product is required.'], 400);
            break;
        }
        if (!in_array($type, $validTypes, true)) {
            $apiJson(['ok' => false, 'error' => 'Select a valid adjustment type.'], 400);
            break;
        }
        if ($qty <= 0) {
            $apiJson(['ok' => false, 'error' => 'Quantity must be greater than zero.'], 400);
            break;
        }
        if ($reason === '') {
            $apiJson(['ok' => false, 'error' => 'Reason is required.'], 400);
            break;
        }
        try {
            $pdo = Db::pdo();
            $ensureInventorySchema($pdo);
            $pdo->beginTransaction();
            $prodSql = '
                select p.id, p.tenant_id, p.name, p.sku, p.price_cents, p.cost_cents, p.category_id,
                       coalesce(m.qty, p.qty_on_hand, 0) as qty_on_hand
                from products p
                left join (
                    select product_id, sum(change_qty) as qty
                    from inventory_movements
                    group by product_id
                ) m on m.product_id = p.id
                where p.id = :id and (p.tenant_id = :tid or p.tenant_id is null)
                limit 1
            ';
            $stmt = $pdo->prepare($prodSql);
            $stmt->execute([':id' => $productId, ':tid' => $tenantId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$product) {
                $pdo->rollBack();
                $apiJson(['ok' => false, 'error' => 'Product not found for this tenant.'], 404);
                break;
            }
            $current = (int) ($product['qty_on_hand'] ?? 0);
            $newStock = $current;
            $change = 0;
            if ($type === 'add') {
                $change = $qty;
                $newStock = $current + $qty;
            } elseif ($type === 'remove') {
                if ($qty > $current) {
                    $pdo->rollBack();
                    $apiJson(['ok' => false, 'error' => 'Cannot remove more than current stock.'], 400);
                    break;
                }
                $change = -1 * $qty;
                $newStock = $current - $qty;
            } else { // correction
                $newStock = $qty;
                $change = $newStock - $current;
            }

            $user = Auth::user();
            $cb = isset($user['id']) ? (int) $user['id'] : null;

            $stmtMove = $pdo->prepare('
                insert into inventory_movements (product_id, tenant_id, change_qty, reason, ref_type, ref_id, created_by, note)
                values (:pid, :tid, :chg, :rsn, :rtype, :rid, :cb, :note)
                returning id
            ');
            $stmtMove->execute([
                ':pid' => $productId,
                ':tid' => $tenantId,
                ':chg' => $change,
                ':rsn' => $reason,
                ':rtype' => 'manual_adjustment',
                ':rid' => null,
                ':cb' => $cb,
                ':note' => $note !== '' ? $note : null,
            ]);
            $movementId = (int) ($stmtMove->fetchColumn() ?: 0);

            $stmtUpdate = $pdo->prepare('update products set qty_on_hand = :qty where id = :id');
            $stmtUpdate->execute([':qty' => $newStock, ':id' => $productId]);

            $pdo->commit();
            $apiJson([
                'ok' => true,
                'product' => [
                    'id' => $productId,
                    'name' => (string) ($product['name'] ?? ''),
                    'sku' => (string) ($product['sku'] ?? ''),
                    'price_cents' => (int) ($product['price_cents'] ?? 0),
                    'cost_cents' => (int) ($product['cost_cents'] ?? 0),
                    'qty_on_hand' => $newStock,
                    'category_id' => $product['category_id'] ?? null,
                ],
                'movement' => [
                    'id' => $movementId,
                    'previous_stock' => $current,
                    'new_stock' => $newStock,
                    'change_qty' => $change,
                    'reason' => $reason,
                    'note' => $note !== '' ? $note : null,
                ],
            ]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            $apiJson(['ok' => false, 'error' => env('APP_DEBUG', false) ? $e->getMessage() : 'Could not adjust stock'], 500);
        }
        break;

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
            $ensureInventorySchema($pdo);
            $tenantId = Auth::tenantId();
            $params = [];
            try {
                $sql = '
                    select p.id, p.sku, p.name, p.price_cents, p.cost_cents, p.category_id, p.tenant_id,
                           coalesce(m.qty, p.qty_on_hand) as qty_on_hand
                    from products p
                    left join (
                        select product_id, sum(change_qty) as qty
                        from inventory_movements
                        group by product_id
                    ) m on m.product_id = p.id
                    where p.active = true
                ';
                if ($tenantId !== null) {
                    $sql .= ' and (p.tenant_id = :tid or p.tenant_id is null)';
                    $params[':tid'] = $tenantId;
                }
                $sql .= ' order by lower(p.name)';
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) {
                // fallback if inventory tables/columns not present
                $params = [];
                $sql = '
                    select id, sku, name, price_cents, category_id, tenant_id, qty_on_hand
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
            }
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

    case '/api/sales':
        if (!Auth::check()) {
            $apiJson(['ok' => false, 'error' => 'Unauthorized'], 401);
            break;
        }
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method === 'GET') {
            try {
                $pdo = Db::pdo();
                $ensureSalesColumns($pdo);
                $tenantId = Auth::tenantId();
                $params = [];
                $where = ["o.status = 'paid'"];
                if ($tenantId !== null) {
                    $where[] = 'o.tenant_id = :tid';
                    $params[':tid'] = $tenantId;
                }
                $qRaw = (string) ($_GET['q'] ?? '');
                $q = trim($qRaw);
                if ($q !== '') {
                    $where[] = '(lower(o.transaction_id) like :q or lower(o.receipt_no) like :q or lower(coalesce(o.cashier_name, o.cashier_username, \'\')) like :q or lower(o.cashier_username) like :q)';
                    $params[':q'] = '%' . strtolower($q) . '%';
                }

                $from = trim((string) ($_GET['from'] ?? ''));
                $to = trim((string) ($_GET['to'] ?? ''));
                if ($from !== '') {
                    $where[] = "coalesce(o.paid_at, o.created_at) >= :from";
                    $params[':from'] = $from . ' 00:00:00';
                }
                if ($to !== '') {
                    $where[] = "coalesce(o.paid_at, o.created_at) <= :to";
                    $params[':to'] = $to . ' 23:59:59';
                }
                $cashier = trim((string) ($_GET['cashier'] ?? ''));
                if ($cashier !== '' && strtolower($cashier) !== 'all') {
                    $where[] = '(lower(coalesce(o.cashier_username, o.cashier_name, \'\')) = :cashier)';
                    $params[':cashier'] = strtolower($cashier);
                }
                $payment = trim((string) ($_GET['payment'] ?? ''));
                if ($payment !== '' && strtolower($payment) !== 'all') {
                    $where[] = 'lower(o.payment_method) = :pm';
                    $params[':pm'] = strtolower($payment);
                }

                $sql = "
                    select o.id,
                           coalesce(o.transaction_id, concat('TXN-', o.id)) as transaction_id,
                           coalesce(o.receipt_no, concat('RCPT-', o.id)) as receipt_no,
                           o.total_cents, o.discount_cents, o.subtotal_cents,
                           o.amount_received_cents, o.change_cents, o.payment_method,
                           coalesce(o.paid_at, o.created_at) as paid_at, o.cashier_name, o.cashier_username,
                           o.tenant_id, t.name as tenant_name,
                           oi.items_count
                    from orders o
                    join (
                        select order_id, sum(qty) as items_count
                        from order_items
                        group by order_id
                        having sum(qty) > 0
                    ) oi on oi.order_id = o.id
                    left join tenants t on t.id = o.tenant_id
                    where " . implode(' and ', $where) . "
                    order by coalesce(o.paid_at, o.created_at) desc
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $sales = array_map(static function (array $r): array {
                    return [
                        'id' => (int) ($r['id'] ?? 0),
                        'transaction_id' => (string) ($r['transaction_id'] ?? ''),
                        'receipt_no' => (string) ($r['receipt_no'] ?? ''),
                        'subtotal_cents' => (int) ($r['subtotal_cents'] ?? 0),
                        'discount_cents' => (int) ($r['discount_cents'] ?? 0),
                        'total_cents' => (int) ($r['total_cents'] ?? 0),
                        'amount_received_cents' => (int) ($r['amount_received_cents'] ?? 0),
                        'change_cents' => (int) ($r['change_cents'] ?? 0),
                        'payment_method' => strtolower((string) ($r['payment_method'] ?? 'cash')),
                        'paid_at' => $r['paid_at'] ?? null,
                        'cashier_name' => is_string($r['cashier_name'] ?? null) ? $r['cashier_name'] : null,
                        'cashier_username' => is_string($r['cashier_username'] ?? null) ? $r['cashier_username'] : null,
                        'tenant_id' => isset($r['tenant_id']) ? (int) $r['tenant_id'] : null,
                        'tenant_name' => is_string($r['tenant_name'] ?? null) ? $r['tenant_name'] : null,
                        'items' => (int) ($r['items_count'] ?? 0),
                    ];
                }, $rows);

                $apiJson(['ok' => true, 'sales' => $sales]);
            } catch (Throwable $e) {
                $apiJson([
                    'ok' => false,
                    'error' => env('APP_DEBUG', false) ? $e->getMessage() : 'Could not load sales',
                ], 500);
            }
            break;
        }

        if ($method === 'POST') {
            $role = strtolower((string) Auth::role());
            if ($role === 'readonly') {
                $apiJson(['ok' => false, 'error' => 'Read-only users cannot record sales.'], 403);
                break;
            }
            $tenantId = Auth::tenantId();
            if ($tenantId === null) {
                $apiJson(['ok' => false, 'error' => 'Select a tenant before recording sales.'], 400);
                break;
            }

            $raw = (string) file_get_contents('php://input');
            $payload = json_decode($raw, true);
            if (!is_array($payload)) {
                $apiJson(['ok' => false, 'error' => 'Invalid request body.'], 400);
                break;
            }

            $itemsInput = is_array($payload['items'] ?? null) ? $payload['items'] : [];
            $items = [];
            $subtotal = 0;
            foreach ($itemsInput as $i) {
                if (!is_array($i)) {
                    continue;
                }
                $qty = (int) ($i['qty'] ?? 0);
                $price = (int) ($i['price_cents'] ?? ($i['unit_price_cents'] ?? 0));
                $name = trim((string) ($i['name'] ?? ''));
                $sku = trim((string) ($i['sku'] ?? ''));
                $productId = isset($i['product_id']) && is_numeric($i['product_id']) ? (int) $i['product_id'] : null;
                if ($qty <= 0 || $price < 0 || $name === '') {
                    continue;
                }
                $line = $qty * $price;
                $subtotal += $line;
                $items[] = [
                    'product_id' => $productId,
                    'name' => $name,
                    'sku' => $sku !== '' ? $sku : 'NA',
                    'unit_price_cents' => $price,
                    'qty' => $qty,
                    'line_total_cents' => $line,
                ];
            }

            if (!$items || $subtotal <= 0) {
                $apiJson(['ok' => false, 'error' => 'Add at least one item to the sale.'], 400);
                break;
            }

            $discount = (int) ($payload['discount_cents'] ?? 0);
            if ($discount < 0) {
                $discount = 0;
            }
            if ($discount > $subtotal) {
                $discount = $subtotal;
            }
            $computedTotal = max(0, $subtotal - $discount);
            $total = (int) ($payload['total_cents'] ?? $computedTotal);
            if ($total < 0) {
                $total = 0;
            }
            if ($total !== $computedTotal) {
                $total = $computedTotal;
            }
            if ($total <= 0) {
                $apiJson(['ok' => false, 'error' => 'Total amount must be greater than zero.'], 400);
                break;
            }

            $amountReceived = (int) ($payload['amount_received_cents'] ?? 0);
            if ($amountReceived < $total) {
                $apiJson(['ok' => false, 'error' => 'Amount received is less than total due.'], 400);
                break;
            }
            $change = (int) ($payload['change_cents'] ?? ($amountReceived - $total));
            if ($change < 0) {
                $change = 0;
            }

            $methodInput = strtolower((string) ($payload['payment_method'] ?? 'cash'));
            $allowedMethods = ['cash', 'qr', 'card', 'transfer', 'mobile_money'];
            $paymentMethod = in_array($methodInput, $allowedMethods, true) ? $methodInput : 'cash';

            $pdo = Db::pdo();
            try {
                $ensureSalesColumns($pdo);
                $ensureInventorySchema($pdo);
                $pdo->beginTransaction();
                $now = new DateTimeImmutable('now');
                $rand = strtoupper(bin2hex(random_bytes(3)));
                $receiptNo = 'RCPT-' . $now->format('ymd') . '-' . substr($rand, 0, 4);
                $txn = 'TXN-' . ($tenantId ?? 'ALL') . '-' . $now->format('YmdHis') . '-' . substr($rand, 0, 6);

                $user = Auth::user();
                $cashierName = trim((string) ($user['full_name'] ?? ''));
                if ($cashierName === '') {
                    $cashierName = (string) ($user['username'] ?? 'Cashier');
                }
                $cashierUsername = is_string($user['username'] ?? null) ? $user['username'] : null;
                $createdBy = isset($user['id']) ? (int) $user['id'] : null;

                $stmt = $pdo->prepare('
                    insert into orders (
                        tenant_id, status, subtotal_cents, tax_cents, total_cents, discount_cents,
                        amount_received_cents, change_cents, payment_method, receipt_no, transaction_id,
                        cashier_name, cashier_username, created_by, paid_at
                    ) values (
                        :tid, :status, :subtotal, 0, :total, :discount,
                        :received, :change, :pm, :receipt, :txn,
                        :cname, :cuser, :created_by, now()
                    )
                    returning id
                ');
                $stmt->execute([
                    ':tid' => $tenantId,
                    ':status' => 'paid',
                    ':subtotal' => $subtotal,
                    ':total' => $total,
                    ':discount' => $discount,
                    ':received' => $amountReceived,
                    ':change' => $change,
                    ':pm' => $paymentMethod,
                    ':receipt' => $receiptNo,
                    ':txn' => $txn,
                    ':cname' => $cashierName,
                    ':cuser' => $cashierUsername,
                    ':created_by' => $createdBy,
                ]);
                $orderId = (int) ($stmt->fetchColumn() ?: 0);
                if ($orderId <= 0) {
                    throw new RuntimeException('Could not create order.');
                }

                $stmtItem = $pdo->prepare('
                    insert into order_items (order_id, product_id, name_snapshot, sku_snapshot, unit_price_cents, qty, line_total_cents)
                    values (:oid, :pid, :name, :sku, :price, :qty, :line)
                ');
                $stmtStock = $pdo->prepare('
                    select coalesce(m.qty, p.qty_on_hand, 0) as qty_on_hand
                    from products p
                    left join (
                        select product_id, sum(change_qty) as qty
                        from inventory_movements
                        group by product_id
                    ) m on m.product_id = p.id
                    where p.id = :pid and (p.tenant_id = :tid or p.tenant_id is null)
                    limit 1
                ');
                $stmtUpdateProd = $pdo->prepare('update products set qty_on_hand = :qty where id = :id');
                foreach ($items as $i) {
                    $stmtItem->execute([
                        ':oid' => $orderId,
                        ':pid' => $i['product_id'],
                        ':name' => $i['name'],
                        ':sku' => $i['sku'],
                        ':price' => $i['unit_price_cents'],
                        ':qty' => $i['qty'],
                        ':line' => $i['line_total_cents'],
                    ]);
                }

                $stmtPay = $pdo->prepare('insert into payments (order_id, method, amount_cents) values (:oid, :method, :amount)');
                $stmtPay->execute([
                    ':oid' => $orderId,
                    ':method' => $paymentMethod,
                    ':amount' => $amountReceived,
                ]);

                // inventory movements (subtract sold qty)
                $stmtMove = $pdo->prepare('
                    insert into inventory_movements (product_id, tenant_id, change_qty, reason, ref_type, ref_id, created_by)
                    values (:pid, :tid, :chg, :rsn, :rtype, :rid, :cb)
                ');
                foreach ($items as $i) {
                    $pid = $i['product_id'] ?? null;
                    if ($pid === null) {
                        continue;
                    }
                    // Ensure product stock reflects the sale
                    $stmtStock->execute([':pid' => $pid, ':tid' => $tenantId]);
                    $stockRow = $stmtStock->fetch(PDO::FETCH_ASSOC) ?: [];
                    $currentStock = (int) ($stockRow['qty_on_hand'] ?? 0);
                    $soldQty = (int) ($i['qty'] ?? 0);
                    $newStock = max(0, $currentStock - $soldQty);
                    $stmtUpdateProd->execute([':qty' => $newStock, ':id' => $pid]);

                    $stmtMove->execute([
                        ':pid' => $pid,
                        ':tid' => $tenantId,
                        ':chg' => -1 * (int) ($i['qty'] ?? 0),
                        ':rsn' => 'sale',
                        ':rtype' => 'order',
                        ':rid' => $orderId,
                        ':cb' => $createdBy,
                    ]);
                }

                $pdo->commit();
                $sale = $loadSale($pdo, (string) $orderId, $tenantId);
                $apiJson(['ok' => true, 'sale' => $sale]);
            } catch (Throwable $e) {
                $pdo->rollBack();
                $apiJson([
                    'ok' => false,
                    'error' => env('APP_DEBUG', false) ? $e->getMessage() : 'Could not record sale',
                ], 500);
            }
            break;
        }

        http_response_code(405);
        header('Allow: GET, POST');
        $apiJson(['ok' => false, 'error' => 'Method not allowed'], 405);
        break;

    case '/api/report-items':
        if (!Auth::check()) {
            $apiJson(['ok' => false, 'error' => 'Unauthorized'], 401);
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            header('Allow: GET');
            $apiJson(['ok' => false, 'error' => 'Method not allowed'], 405);
            break;
        }
        try {
            $pdo = Db::pdo();
            $tenantId = Auth::tenantId();
            $params = [];
            $where = ["o.status = 'paid'"];
            if ($tenantId !== null) {
                $where[] = 'o.tenant_id = :tid';
                $params[':tid'] = $tenantId;
            }

            $from = trim((string) ($_GET['from'] ?? ''));
            $to = trim((string) ($_GET['to'] ?? ''));
            if ($from !== '') {
                $where[] = "coalesce(o.paid_at, o.created_at) >= :from";
                $params[':from'] = $from . ' 00:00:00';
            }
            if ($to !== '') {
                $where[] = "coalesce(o.paid_at, o.created_at) <= :to";
                $params[':to'] = $to . ' 23:59:59';
            }

            $cashier = trim((string) ($_GET['cashier'] ?? ''));
            if ($cashier !== '' && strtolower($cashier) !== 'all') {
                $where[] = '(lower(coalesce(o.cashier_username, o.cashier_name, \'\')) = :cashier)';
                $params[':cashier'] = strtolower($cashier);
            }

            $payment = trim((string) ($_GET['payment'] ?? ''));
            if ($payment !== '' && strtolower($payment) !== 'all') {
                $where[] = 'lower(o.payment_method) = :pm';
                $params[':pm'] = strtolower($payment);
            }

            $aggregateParam = strtolower(trim((string) ($_GET['aggregate'] ?? '1')));
            $aggregate = !in_array($aggregateParam, ['0', 'false', 'no', 'off'], true);

            $sqlBase = "
                with item_rows as (
                    select
                        coalesce(oi.name_snapshot, p_id.name, p_sku.name) as product,
                        coalesce(oi.sku_snapshot, p_id.sku, p_sku.sku) as sku,
                        coalesce(c_id.name, c_sku.name, 'Uncategorized') as category,
                        coalesce(p_id.cost_cents, p_sku.cost_cents, 0) as cost_cents,
                        oi.unit_price_cents,
                        oi.qty,
                        oi.line_total_cents,
                        o.discount_cents,
                        nullif(o.subtotal_cents, 0) as order_subtotal,
                        coalesce(o.paid_at, o.created_at) as sold_at,
                        coalesce(nullif(o.cashier_name, ''), nullif(o.cashier_username, ''), 'Unknown') as cashier
                    from order_items oi
                    join orders o on o.id = oi.order_id
                    left join products p_id on p_id.id = oi.product_id
                    left join products p_sku on p_sku.sku is not null
                        and lower(p_sku.sku) = lower(coalesce(oi.sku_snapshot, p_id.sku))
                        and (p_sku.tenant_id is null or p_sku.tenant_id = o.tenant_id)
                    left join categories c_id on c_id.id = p_id.category_id
                    left join categories c_sku on c_sku.id = p_sku.category_id
                    where " . implode(' and ', $where) . "
                )
            ";

            if ($aggregate) {
                $sql = $sqlBase . "
                    select
                        product,
                        sku,
                        category,
                        avg(cost_cents)::bigint as cost_cents,
                        avg(unit_price_cents)::bigint as avg_price_cents,
                        sum(qty) as qty_sold,
                        sum(line_total_cents) as gross_cents,
                        sum(
                            case
                                when order_subtotal is null or order_subtotal = 0 then 0
                                else (line_total_cents::numeric / order_subtotal) * discount_cents
                            end
                        )::bigint as discount_cents,
                        sum(
                            case
                                when order_subtotal is null or order_subtotal = 0 then line_total_cents
                                else line_total_cents - (line_total_cents::numeric / order_subtotal) * discount_cents
                            end
                        )::bigint as net_cents,
                        max(sold_at) as last_sold,
                        string_agg(distinct cashier, ', ' order by cashier)
                            filter (where cashier is not null and cashier <> '') as cashiers
                    from item_rows
                    group by product, sku, category
                    order by gross_cents desc
                ";
            } else {
                $sql = $sqlBase . "
                    select
                        product,
                        sku,
                        category,
                        cost_cents,
                        unit_price_cents as avg_price_cents,
                        qty as qty_sold,
                        line_total_cents as gross_cents,
                        (
                            case
                                when order_subtotal is null or order_subtotal = 0 then 0
                                else (line_total_cents::numeric / order_subtotal) * discount_cents
                            end
                        )::bigint as discount_cents,
                        (
                            case
                                when order_subtotal is null or order_subtotal = 0 then line_total_cents
                                else line_total_cents - (line_total_cents::numeric / order_subtotal) * discount_cents
                            end
                        )::bigint as net_cents,
                        sold_at as last_sold,
                        cashier
                    from item_rows
                    order by sold_at desc nulls last, product
                ";
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $items = array_map(static function (array $r): array {
                    $gross = (int) ($r['gross_cents'] ?? 0);
                    $qty = (int) ($r['qty_sold'] ?? 0);
                    $avgPrice = (int) ($r['avg_price_cents'] ?? 0);
                    $cost = (int) ($r['cost_cents'] ?? 0);
                    $discount = (int) ($r['discount_cents'] ?? 0);
                    $net = (int) ($r['net_cents'] ?? ($gross - $discount));
                    $cashiers = trim((string) ($r['cashiers'] ?? ''));
                    $cashierSingle = trim((string) ($r['cashier'] ?? ''));
                    $cashierDisplay = $cashiers !== '' ? $cashiers : ($cashierSingle !== '' ? $cashierSingle : 'Multiple');
                    return [
                        'product' => (string) ($r['product'] ?? ''),
                        'sku' => (string) ($r['sku'] ?? ''),
                        'category' => (string) ($r['category'] ?? 'Uncategorized'),
                        'cost_cents' => $cost,
                        'unit_price_cents' => $avgPrice,
                        'qty_sold' => $qty,
                        'gross_cents' => $gross,
                        'discount_cents' => $discount,
                        'net_cents' => $net,
                        'cashier' => $cashierDisplay,
                        'cashiers' => $cashierDisplay,
                        'last_sold' => $r['last_sold'] ?? null,
                    ];
                }, $rows);

            $apiJson(['ok' => true, 'items' => $items]);
        } catch (Throwable $e) {
            $apiJson([
                'ok' => false,
                'error' => env('APP_DEBUG', false) ? $e->getMessage() : 'Could not load items report',
            ], 500);
        }
        break;

    default:
        if (str_starts_with($path, '/api/sales/')) {
            if (!Auth::check()) {
                $apiJson(['ok' => false, 'error' => 'Unauthorized'], 401);
                break;
            }
            $id = substr($path, strlen('/api/sales/'));
            $id = is_string($id) ? trim($id) : '';
            if ($id === '') {
                $apiJson(['ok' => false, 'error' => 'Sale not found'], 404);
                break;
            }
            try {
                $pdo = Db::pdo();
                $ensureSalesColumns($pdo);
                $tenantId = Auth::tenantId();
                $sale = $loadSale($pdo, $id, $tenantId);
                if (!$sale) {
                    $apiJson(['ok' => false, 'error' => 'Sale not found'], 404);
                    break;
                }
                $apiJson(['ok' => true, 'sale' => $sale]);
            } catch (Throwable $e) {
                $apiJson([
                    'ok' => false,
                    'error' => env('APP_DEBUG', false) ? $e->getMessage() : 'Could not load sale',
                ], 500);
            }
            break;
        }

        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo view('not_found', [
            'title' => 'Mobile POS',
            'path' => $path,
        ]);
        break;
}


