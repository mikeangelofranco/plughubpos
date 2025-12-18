<?php
declare(strict_types=1);

// Router for PHP's built-in server:
//   php -S 127.0.0.1:8000 public/router.php
//
// Serve static files directly when they exist; otherwise, route through index.php.
//
// Note: When starting the server without `-t public`, the docroot is the current
// working directory, so `return false;` would look for `/assets/...` under the repo
// root (and 404). To avoid this, we serve files from `public/` ourselves.

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (is_string($path)) {
    if ($path === '/favicon.ico') {
        http_response_code(204);
        exit;
    }

    $file = __DIR__ . $path;
    if ($path !== '/' && is_file($file)) {
        $ext = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
        $types = [
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'map' => 'application/json; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'woff2' => 'font/woff2',
            'woff' => 'font/woff',
            'ttf' => 'font/ttf',
        ];

        if (isset($types[$ext])) {
            header('Content-Type: ' . $types[$ext]);
        } else {
            header('Content-Type: application/octet-stream');
        }

        header('Content-Length: ' . (string) filesize($file));
        readfile($file);
        exit;
    }
}

require __DIR__ . '/index.php';
