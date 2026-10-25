<?php
declare(strict_types=1);

/**
 * ArchonAI — Main Authenticated User Dashboard
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/src/Repositories/RepositoryRepository.php';

require_auth('login.php');

$currentUser = auth_user();
$userId = auth_id();

// Auto-reconcile stale processing analyses (if older than 2 minutes)
try {
    db()->exec(
        "UPDATE analyses 
         SET status = IF((SELECT COUNT(*) FROM analysis_files WHERE analysis_id = analyses.id) > 0, 'completed', 'failed'),
             completed_at = NOW()
         WHERE status = 'processing' AND TIMESTAMPDIFF(MINUTE, started_at, NOW()) >= 2"
    );
} catch (\Throwable $e) {}

$repoRepo = new \ArchonAI\Repositories\RepositoryRepository();
$userRepos = $repoRepo->getByUserId($userId);

// Set default active repo session if available
if (!empty($userRepos) && empty($_SESSION['active_repo_id'])) {
    $_SESSION['active_repo_id'] = $userRepos[0]['id'];
    $_SESSION['active_repo_name'] = $userRepos[0]['full_name'];
}

// Calculate summary metrics
$totalRepos = count($userRepos);
$totalFilesAnalyzed = array_sum(array_column($userRepos, 'files_count'));
$totalDependencies = array_sum(array_column($userRepos, 'dependencies_count'));

$pageTitle = 'Dashboard — ArchonAI';
$activePage = 'dashboard';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <!-- Top Welcome Header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Architecture Intelligence Center</h1>
                <p class="text-secondary small mb-0">Welcome back, <?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') ?>! Monitor codebase structures, inspect graphs, and consult AI.</p>
            </div>
            <a href="<?= app_url('pages/explorer.php') ?>" class="btn btn-archon-primary d-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                <span>Analyze New Repository</span>
            </a>
        </div>

        <!-- Metric Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="archon-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Imported Repositories</span>
                        <div class="feature-icon-box feature-icon-indigo m-0" style="width: 36px; height: 36px; font-size: 1.1rem;">
                            <i class="bi bi-git"></i>
                        </div>
                    </div>
                    <div class="h3 fw-bold mb-0 text-gradient"><?= number_format($totalRepos) ?></div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="archon-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Files Mapped</span>
                        <div class="feature-icon-box feature-icon-cyan m-0" style="width: 36px; height: 36px; font-size: 1.1rem;">
                            <i class="bi bi-file-earmark-code"></i>
                        </div>
                    </div>
                    <div class="h3 fw-bold mb-0 text-light"><?= number_format($totalFilesAnalyzed) ?></div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="archon-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Module Relationships</span>
                        <div class="feature-icon-box feature-icon-blue m-0" style="width: 36px; height: 36px; font-size: 1.1rem;">
                            <i class="bi bi-bezier2"></i>
                        </div>
                    </div>
                    <div class="h3 fw-bold mb-0 text-light"><?= number_format($totalDependencies) ?></div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="archon-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">AI Inference Engine</span>
                        <div class="feature-icon-box feature-icon-pink m-0" style="width: 36px; height: 36px; font-size: 1.1rem;">
                            <i class="bi bi-robot"></i>
                        </div>
                    </div>
                    <div class="h5 fw-bold mb-0 text-info">Ollama Local</div>
                </div>
            </div>
        </div>

        <!-- Quick Import Banner -->
        <div class="archon-card p-4 mb-4">
            <h2 class="h5 fw-bold mb-2">Quick GitHub Repository Import</h2>
            <p class="text-secondary small mb-3">Provide any public GitHub repository to extract its file hierarchy, import linkages, and dependency graphs.</p>
            
            <form action="<?= app_url('pages/explorer.php') ?>" method="GET" class="d-flex flex-wrap gap-2">
                <div class="repo-input-group flex-grow-1">
                    <span class="px-2 text-muted"><i class="bi bi-github"></i></span>
                    <input type="text" name="repo" placeholder="e.g. facebook/react, axios/axios, pallets/flask" required>
                </div>
                <button type="submit" class="btn btn-archon-primary">
                    <i class="bi bi-search me-1"></i> Load & Analyze
                </button>
            </form>
        </div>

        <!-- Saved Repositories Table / Cards -->
        <div class="archon-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h5 fw-bold mb-0">Saved Repositories & Architecture Models</h2>
                <span class="badge-archon"><?= count($userRepos) ?> active</span>
            </div>

            <?php if (empty($userRepos)): ?>
                <div class="text-center py-5">
                    <div class="feature-icon-box feature-icon-indigo mx-auto mb-3">
                        <i class="bi bi-folder-x"></i>
                    </div>
                    <h3 class="h6 fw-bold">No repositories analyzed yet</h3>
                    <p class="text-muted small max-w-400 mx-auto mb-3">Enter any public GitHub repository name or URL above to generate your first architecture map.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0" style="background: transparent;">
                        <thead>
                            <tr class="text-muted small text-uppercase" style="border-bottom: 1px solid var(--border-color);">
                                <th>Repository</th>
                                <th>Language</th>
                                <th>Analysis Status</th>
                                <th>Mapped Files</th>
                                <th>Dependencies</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userRepos as $repo): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-github text-gradient fs-5"></i>
                                            <div>
                                                <div class="fw-semibold text-light"><?= htmlspecialchars($repo['full_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="text-muted small text-truncate" style="max-width: 320px;">
                                                    <?= htmlspecialchars($repo['description'] ?? 'No description provided.', ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-25 text-info border border-secondary border-opacity-50">
                                            <?= htmlspecialchars($repo['primary_language'] ?? 'Multi', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($repo['analysis_status'] === 'completed'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                                <i class="bi bi-check-circle-fill me-1"></i> Completed
                                            </span>
                                        <?php elseif ($repo['analysis_status'] === 'processing'): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">
                                                <span class="spinner-border spinner-border-sm me-1" role="status"></span> Processing
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                                                Queued
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-secondary small font-monospace">
                                        <?= (int)$repo['files_count'] ?> files
                                    </td>
                                    <td class="text-secondary small font-monospace">
                                        <?= (int)$repo['dependencies_count'] ?> edges
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= app_url('pages/explorer.php?repo=' . urlencode($repo['full_name'])) ?>" class="btn btn-outline-secondary" title="File Explorer">
                                                <i class="bi bi-folder2-open"></i>
                                            </a>
                                            <a href="<?= app_url('pages/architecture.php?repo=' . urlencode($repo['full_name'])) ?>" class="btn btn-outline-secondary" title="Architecture Graph">
                                                <i class="bi bi-diagram-3"></i>
                                            </a>
                                            <a href="<?= app_url('pages/assistant.php?repo=' . urlencode($repo['full_name'])) ?>" class="btn btn-outline-secondary" title="AI Code Assistant">
                                                <i class="bi bi-chat-left-dots"></i>
                                            </a>
                                            <a href="<?= app_url('pages/contributions.php?repo=' . urlencode($repo['full_name'])) ?>" class="btn btn-outline-secondary" title="Contributions & Issues">
                                                <i class="bi bi-trophy"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
