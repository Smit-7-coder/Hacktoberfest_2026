<?php
declare(strict_types=1);

/**
 * ArchonAI — User Login Page
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/src/Controllers/AuthController.php';

// Redirect if already logged in
require_guest('dashboard.php');

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController = new \ArchonAI\Controllers\AuthController();
    $result = $authController->login($_POST);

    if ($result['success']) {
        $redirect = $_GET['redirect'] ?? 'dashboard.php';
        header('Location: ' . app_url($redirect));
        exit;
    } else {
        $error = $result['error'] ?? 'Login failed.';
        $email = $_POST['email'] ?? '';
    }
}

$pageTitle = 'Sign In — ArchonAI';
$activePage = 'login';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="text-center mb-4">
            <div class="archon-brand-icon mx-auto mb-3" style="width: 44px; height: 44px;">
                <i class="bi bi-shield-lock-fill fs-4"></i>
            </div>
            <h1 class="h4 fw-bold">Sign In to ArchonAI</h1>
            <p class="text-secondary small">Access your saved repositories, custom chats, and architecture maps.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-archon-danger p-3 mb-3 small d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <form action="<?= app_url('login.php') ?>" method="POST" autocomplete="off">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="emailInput" class="form-label-archon">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-secondary border-secondary text-muted"><i class="bi bi-envelope"></i></span>
                    <input 
                        type="email" 
                        name="email" 
                        id="emailInput" 
                        class="form-control form-control-archon" 
                        placeholder="you@domain.com" 
                        value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" 
                        required 
                        autofocus
                    >
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="passwordInput" class="form-label-archon mb-0">Password</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-secondary border-secondary text-muted"><i class="bi bi-key"></i></span>
                    <input 
                        type="password" 
                        name="password" 
                        id="passwordInput" 
                        class="form-control form-control-archon" 
                        placeholder="••••••••" 
                        required
                    >
                </div>
            </div>

            <button type="submit" class="btn btn-archon-primary w-100 mb-3">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
            </button>

            <!-- Quick Demo Credentials Fill -->
            <button type="button" id="fillDemoCredentials" class="btn btn-archon-secondary w-100 mb-4 small py-2">
                <i class="bi bi-person-badge me-1 text-cyan"></i> Fill Demo Account (demo@archonai.dev)
            </button>
        </form>

        <div class="text-center text-muted small pt-2 border-top border-secondary border-opacity-25">
            Don't have an account yet? 
            <a href="<?= app_url('register.php') ?>" class="text-primary fw-semibold ms-1">Create Account</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const fillBtn = document.getElementById('fillDemoCredentials');
    if (fillBtn) {
        fillBtn.addEventListener('click', () => {
            document.getElementById('emailInput').value = 'demo@archonai.dev';
            document.getElementById('passwordInput').value = 'password123';
            document.getElementById('emailInput').focus();
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
