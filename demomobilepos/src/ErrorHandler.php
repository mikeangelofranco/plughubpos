<?php
declare(strict_types=1);

final class ErrorHandler
{
    public static function register(): void
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $e): void {
            $debug = (bool) env('APP_DEBUG', false);
            http_response_code(500);

            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            $wantsJson = str_contains($accept, 'application/json')
                || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');

            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'error' => $debug ? $e->getMessage() : 'Server error',
                ], JSON_UNESCAPED_SLASHES);
                return;
            }

            header('Content-Type: text/html; charset=utf-8');
            if ($debug) {
                echo '<pre style="white-space:pre-wrap;">' . htmlspecialchars((string) $e, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
            } else {
                echo '<h1>Something went wrong</h1><p>Please try again.</p>';
            }
        });
    }
}

