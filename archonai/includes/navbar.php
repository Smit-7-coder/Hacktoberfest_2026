<?php
declare(strict_types=1);

/**
 * ArchonAI - Navigation Bar Component
 */

$activePage = $activePage ?? '';
$isLoggedIn = is_authenticated();
$currentUser = auth_user();
?>
<nav class="archon-navbar">
    <div class="container-fluid px-lg-5 px-3">
        <div class="d-flex align-items-center justify-content-between w-100">
            <!-- Brand Logo -->
            <a href="<?= app_url() ?>" class="archon-brand text-decoration-none">
                <div class="archon-brand-icon">
                    <i class="bi bi-diagram-3-fill"></i>
                </div>
                <span>Archon<span class="text-gradient">AI</span></span>
            </a>

            <!-- Navigation Links -->
            <div class="d-none d-md-flex align-items-center gap-1">
                <a href="<?= app_url() ?>" class="nav-link-archon <?= $activePage === 'home' ? 'active' : '' ?>">Home</a>
                <a href="<?= app_url('dashboard.php') ?>" class="nav-link-archon <?= $activePage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
                <a href="<?= app_url('pages/explorer.php') ?>" class="nav-link-archon <?= $activePage === 'explorer' ? 'active' : '' ?>">Explorer</a>
                <a href="<?= app_url('pages/architecture.php') ?>" class="nav-link-archon <?= $activePage === 'architecture' ? 'active' : '' ?>">Architecture</a>
                <a href="<?= app_url('pages/assistant.php') ?>" class="nav-link-archon <?= $activePage === 'assistant' ? 'active' : '' ?>">AI Assistant</a>
                <a href="<?= app_url('pages/contributions.php') ?>" class="nav-link-archon <?= $activePage === 'contributions' ? 'active' : '' ?>">Contributions</a>
            </div>

            <!-- User Auth Controls -->
            <div class="d-flex align-items-center gap-2">
                <?php if ($isLoggedIn && $currentUser): ?>
                    <div class="dropdown">
                        <button class="btn btn-archon-secondary d-flex align-items-center gap-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle text-gradient fs-5"></i>
                            <span class="d-none d-sm-inline"><?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end bg-dark border-secondary">
                            <li><span class="dropdown-item-text text-muted small"><?= htmlspecialchars($currentUser['email'], ENT_QUOTES, 'UTF-8') ?></span></li>
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <li><a class="dropdown-item text-light" href="<?= app_url('dashboard.php') ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item text-light" href="<?= app_url('pages/settings.php') ?>"><i class="bi bi-gear me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <li><a class="dropdown-item text-danger" href="<?= app_url('logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= app_url('login.php') ?>" class="btn btn-archon-secondary">Sign In</a>
                    <a href="<?= app_url('register.php') ?>" class="btn btn-archon-primary">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
