<?php
declare(strict_types=1);

/**
 * ArchonAI — Custom 404 Not Found Page
 */

http_response_code(404);

$pageTitle = 'Page Not Found — ArchonAI';
$activePage = '404';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="auth-wrapper text-center">
    <div class="archon-card p-5" style="max-width: 520px;">
        <div class="display-1 fw-bold text-gradient mb-2">404</div>
        <h1 class="h4 fw-bold mb-3">Architecture Path Not Found</h1>
        <p class="text-secondary small mb-4">
            The page, repository, or architectural module you requested does not exist or has been relocated.
        </p>
        <div class="d-flex justify-content-center gap-2">
            <a href="<?= app_url() ?>" class="btn btn-archon-primary">
                <i class="bi bi-house me-1"></i> Return Home
            </a>
            <a href="<?= app_url('dashboard.php') ?>" class="btn btn-archon-secondary">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
