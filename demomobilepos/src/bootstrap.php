<?php
declare(strict_types=1);

require __DIR__ . '/Env.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/Db.php';
require __DIR__ . '/Session.php';
require __DIR__ . '/Csrf.php';
require __DIR__ . '/Auth.php';
require __DIR__ . '/ErrorHandler.php';

$root = dirname(__DIR__);
Env::load($root . '/.env');

date_default_timezone_set((string) env('APP_TIMEZONE', 'UTC'));

error_reporting(E_ALL);
ini_set('display_errors', env('APP_DEBUG', false) ? '1' : '0');

ErrorHandler::register();
