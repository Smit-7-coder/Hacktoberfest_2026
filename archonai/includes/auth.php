<?php
declare(strict_types=1);

/**
 * ArchonAI - Authentication & Session Helper
 */

require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/../config/database.php';

function auth_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function auth_id(): ?int {
    return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
}

function is_authenticated(): bool {
    return !empty($_SESSION['user']['id']);
}

function require_auth(string $redirectPath = 'login.php'): void {
    if (!is_authenticated()) {
        $loginUrl = app_url($redirectPath);
        header("Location: {$loginUrl}");
        exit;
    }
}

function require_guest(string $redirectPath = 'dashboard.php'): void {
    if (is_authenticated()) {
        $dashboardUrl = app_url($redirectPath);
        header("Location: {$dashboardUrl}");
        exit;
    }
}

function log_in_user(array $user): void {
    // Prevent session fixation attack
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
    ];
}

function log_out_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}
