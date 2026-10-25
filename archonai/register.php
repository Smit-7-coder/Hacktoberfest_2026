<?php
declare(strict_types=1);

/**
 * ArchonAI — User Registration Page
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/src/Controllers/AuthController.php';

require_guest('dashboard.php');

$error = null;
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController = new \ArchonAI\Controllers\AuthController();
    $result = $authController->register($_POST);

    if ($result['success']) {
        header('Location: ' . app_url('dashboard.php'));
        exit;
    } else {
        $error = $result['error'] ?? 'Registration failed.';
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
    }
}

$pageTitle = 'Create Account — ArchonAI';
$activePage = 'register';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="text-center mb-4">
            <div class="archon-brand-icon mx-auto mb-3" style="width: 44px; height: 44px;">
                <i class="bi bi-person-plus-fill fs-4"></i>
            </div>
            <h1 class="h4 fw-bold">Create an Account</h1>
            <p class="text-secondary small">Start mapping, visualizing, and analyzing software architectures.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-archon-danger p-3 mb-3 small d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <form action="<?= app_url('register.php') ?>" method="POST" autocomplete="off">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="nameInput" class="form-label-archon">Full Name</label>
                <input 
                    type="text" 
                    name="name" 
                    id="nameInput" 
                    class="form-control form-control-archon" 
                    placeholder="Ada Lovelace" 
                    value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" 
                    required 
                    autofocus
                >
            </div>

            <div class="mb-3">
                <label for="emailInput" class="form-label-archon">Email Address</label>
                <input 
                    type="email" 
                    name="email" 
                    id="emailInput" 
                    class="form-control form-control-archon" 
                    placeholder="developer@example.com" 
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" 
                    required
                >
            </div>

            <div class="mb-3">
                <label for="passwordInput" class="form-label-archon">Password (min 8 chars)</label>
                <input 
                    type="password" 
                    name="password" 
                    id="passwordInput" 
                    class="form-control form-control-archon" 
                    placeholder="••••••••" 
                    required
                >
            </div>

            <div class="mb-4">
                <label for="confirmPasswordInput" class="form-label-archon">Confirm Password</label>
                <input 
                    type="password" 
                    name="confirm_password" 
                    id="confirmPasswordInput" 
                    class="form-control form-control-archon" 
                    placeholder="••••••••" 
                    required
                >
            </div>

            <button type="submit" class="btn btn-archon-primary w-100 mb-3">
                <i class="bi bi-check2-circle me-1"></i> Register Account
            </button>
        </form>

        <div class="text-center text-muted small pt-2 border-top border-secondary border-opacity-25">
            Already have an account? 
            <a href="<?= app_url('login.php') ?>" class="text-primary fw-semibold ms-1">Sign In</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
