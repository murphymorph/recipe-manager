<?php

declare(strict_types=1);

namespace App;

/**
 * ErrorHandler - Centralized error and exception handling
 * 
 * Handles all PHP errors and exceptions in a consistent manner.
 */
class ErrorHandler
{
    /**
     * Setup error handling
     */
    public static function setup(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }

    /**
     * Handle PHP errors
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Internal Server Error',
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ]);
        exit(1);
    }

    /**
     * Handle exceptions
     */
    public static function handleException(Throwable $exception): void
    {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Internal Server Error',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);
        exit(1);
    }
}