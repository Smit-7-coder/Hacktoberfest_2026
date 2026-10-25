<?php
declare(strict_types=1);

/**
 * ArchonAI - Application Configuration & Environment Loader
 */

// Load .env file if it exists
if (!function_exists('loadEnv')) {
    function loadEnv(string $envPath): void {
        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            // Ignore comments
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Strip quotes if present
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }

                if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }
}

// Load root .env
$projectRoot = dirname(__DIR__);
loadEnv($projectRoot . '/.env');

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }

        // Cast boolean and null representations
        $lower = strtolower((string)$value);
        if ($lower === 'true') return true;
        if ($lower === 'false') return false;
        if ($lower === 'null') return null;

        return $value;
    }
}

if (!function_exists('app_url')) {
    /**
     * Compute clean base URL or append asset/page path
     */
    function app_url(string $path = ''): string {
        $configured = rtrim((string)env('APP_URL', ''), '/');
        if (!empty($configured) && isset($_SERVER['HTTP_HOST'])) {
            // Check if current host matches configured
            $cleanPath = ltrim($path, '/');
            return empty($cleanPath) ? $configured : $configured . '/' . $cleanPath;
        }

        // Dynamic detection from script name if APP_URL is not set or running via custom vhost
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        
        // Find position of 'archonai' in script path
        $pos = strpos($scriptDir, 'archonai');
        if ($pos !== false) {
            $baseDir = substr($scriptDir, 0, $pos + strlen('archonai'));
        } else {
            $baseDir = $scriptDir;
        }
        $base = rtrim($scheme . $host . $baseDir, '/\\');
        $cleanPath = ltrim($path, '/');
        return empty($cleanPath) ? $base : $base . '/' . $cleanPath;
    }
}

return [
    'name' => env('APP_NAME', 'ArchonAI'),
    'env' => env('APP_ENV', 'development'),
    'debug' => (bool)env('APP_DEBUG', true),
    'url' => env('APP_URL', 'http://localhost/Hacktoberfest_2026/archonai'),
    'version' => '1.0.0-hacktoberfest2026',
    'timezone' => 'UTC',
    'root_path' => $projectRoot,
    'storage_path' => $projectRoot . '/storage',
];
