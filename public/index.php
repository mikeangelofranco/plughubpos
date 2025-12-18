<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

echo view('pos', [
    'title' => env('APP_NAME', 'PlugHub POS'),
]);

