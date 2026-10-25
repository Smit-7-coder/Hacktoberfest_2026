<?php
declare(strict_types=1);

/**
 * ArchonAI - Save GitHub API Token to .env
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../src/Helpers/Response.php';

use ArchonAI\Helpers\Response;

header('Content-Type: application/json; charset=utf-8');

$token = trim($_POST['token'] ?? '');
$envFile = dirname(__DIR__, 2) . '/.env';

if (!file_exists($envFile)) {
    Response::error(".env file not found.", 500);
}

try {
    $envContent = file_get_contents($envFile);
    
    if (preg_match('/^GITHUB_API_TOKEN=.*$/m', $envContent)) {
        $newEnv = preg_replace('/^GITHUB_API_TOKEN=.*$/m', 'GITHUB_API_TOKEN="' . addslashes($token) . '"', $envContent);
    } else {
        $newEnv = $envContent . "\nGITHUB_API_TOKEN=\"" . addslashes($token) . "\"\n";
    }

    file_put_contents($envFile, $newEnv, LOCK_EX);
    $_ENV['GITHUB_API_TOKEN'] = $token;
    $_SERVER['GITHUB_API_TOKEN'] = $token;
    putenv("GITHUB_API_TOKEN={$token}");

    // Clear rate limit cache to refresh immediately
    $rateLimitCache = dirname(__DIR__, 2) . '/storage/cache/ratelimit.json';
    if (file_exists($rateLimitCache)) {
        @unlink($rateLimitCache);
    }

    Response::success([
        'token_set' => !empty($token),
        'preview' => !empty($token) ? substr($token, 0, 4) . '...' . substr($token, -4) : 'none'
    ], !empty($token) ? "GitHub API token updated! Your rate limit is now increased to 5,000 requests/hour." : "GitHub API token cleared.");
} catch (\Throwable $e) {
    Response::error("Failed to update .env: " . $e->getMessage(), 500);
}
