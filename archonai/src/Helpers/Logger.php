<?php
declare(strict_types=1);

namespace ArchonAI\Helpers;

/**
 * Safe Application Logger (never records sensitive credentials or tokens)
 */
class Logger {
    public static function log(string $level, string $message, array $context = []): void {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Scrub sensitive keys from context
        $sensitiveKeys = ['password', 'password_hash', 'token', 'authorization', 'secret', 'key'];
        array_walk_recursive($context, function (&$value, $key) use ($sensitiveKeys) {
            if (is_string($key) && in_array(strtolower($key), $sensitiveKeys, true)) {
                $value = '********';
            }
        });

        $timestamp = date('Y-m-d H:i:s');
        $logFile = $logDir . '/app_' . date('Y-m-d') . '.log';
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';
        $entry = sprintf("[%s] [%s] %s%s\n", $timestamp, strtoupper($level), $message, $contextStr);

        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void {
        self::log('INFO', $message, $context);
    }

    public static function error(string $message, array $context = []): void {
        self::log('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void {
        self::log('WARNING', $message, $context);
    }
}
