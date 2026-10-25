<?php
declare(strict_types=1);

/**
 * ArchonAI - Dashboard & App Sidebar Component
 */

$activePage = $activePage ?? 'dashboard';
$currentRepoName = $_SESSION['active_repo_name'] ?? 'expressjs/express';
$currentRepoId = $_SESSION['active_repo_id'] ?? 1;
?>
<aside class="dashboard-sidebar">
    <!-- Active Repository Context Box -->
    <div class="mb-4">
        <label class="form-label-archon text-uppercase small text-muted">Active Repository</label>
        <div class="archon-card p-2 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2 text-truncate">
                <i class="bi bi-github text-gradient fs-5"></i>
                <span class="fw-semibold text-truncate small" title="<?= htmlspecialchars($currentRepoName, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($currentRepoName, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <a href="<?= app_url('dashboard.php#switch-repo') ?>" class="text-muted small" title="Switch Repository">
                <i class="bi bi-arrow-repeat"></i>
            </a>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="d-flex flex-column gap-1 mb-auto">
        <span class="text-uppercase text-muted small fw-semibold px-2 mb-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">Intelligence</span>
        
        <a href="<?= app_url('dashboard.php') ?>" class="sidebar-nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?= app_url('pages/explorer.php') ?>" class="sidebar-nav-item <?= $activePage === 'explorer' ? 'active' : '' ?>">
            <i class="bi bi-folder2-open"></i>
            <span>Repository Explorer</span>
        </a>

        <a href="<?= app_url('pages/architecture.php') ?>" class="sidebar-nav-item <?= $activePage === 'architecture' ? 'active' : '' ?>">
            <i class="bi bi-diagram-3"></i>
            <span>Architecture Graph</span>
        </a>

        <a href="<?= app_url('pages/assistant.php') ?>" class="sidebar-nav-item <?= $activePage === 'assistant' ? 'active' : '' ?>">
            <i class="bi bi-chat-left-dots"></i>
            <span>AI Code Assistant</span>
        </a>

        <span class="text-uppercase text-muted small fw-semibold px-2 mt-3 mb-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">Contributions & History</span>

        <a href="<?= app_url('pages/contributions.php') ?>" class="sidebar-nav-item <?= $activePage === 'contributions' ? 'active' : '' ?>">
            <i class="bi bi-trophy"></i>
            <span>Contributions</span>
        </a>

        <a href="<?= app_url('pages/history.php') ?>" class="sidebar-nav-item <?= $activePage === 'history' ? 'active' : '' ?>">
            <i class="bi bi-clock-history"></i>
            <span>Code History</span>
        </a>

        <span class="text-uppercase text-muted small fw-semibold px-2 mt-3 mb-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">System</span>

        <a href="<?= app_url('pages/settings.php') ?>" class="sidebar-nav-item <?= $activePage === 'settings' ? 'active' : '' ?>">
            <i class="bi bi-sliders"></i>
            <span>Model & API Settings</span>
        </a>
    </nav>

    <!-- Bottom System Status -->
    <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 small text-muted">
        <div class="d-flex align-items-center justify-content-between mb-1">
            <span>MySQL / MariaDB</span>
            <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Connected</span>
        </div>
        <div class="d-flex align-items-center justify-content-between">
            <span>AI Model Engine</span>
            <span class="text-info"><i class="bi bi-cpu me-1"></i>Ollama Ready</span>
        </div>
    </div>
</aside>
