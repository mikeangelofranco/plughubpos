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
        echo view('pos', [
            'title' => env('APP_NAME', 'Plughub POS Mobile'),
            'user' => Auth::user(),
            'flash' => Session::flash('success'),
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
        header('Content-Type: application/json; charset=utf-8');
        try {
            $pdo = Db::pdo();
            $stmt = $pdo->query('select id, name from categories order by lower(name)');
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        header('Content-Type: application/json; charset=utf-8');
        try {
            $pdo = Db::pdo();
            $stmt = $pdo->query('
                select id, sku, name, price_cents, category_id
                from products
                where active = true
                order by lower(name)
            ');
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
