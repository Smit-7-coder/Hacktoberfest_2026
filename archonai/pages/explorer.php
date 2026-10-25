<?php
declare(strict_types=1);

/**
 * ArchonAI — Repository Explorer & Code Viewer (Module C)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../src/Helpers/Validator.php';
require_once __DIR__ . '/../src/Services/GitHubService.php';
require_once __DIR__ . '/../src/Services/RepositoryAnalysisService.php';
require_once __DIR__ . '/../src/Repositories/AnalysisRepository.php';

use ArchonAI\Helpers\Validator;
use ArchonAI\Services\GitHubService;
use ArchonAI\Services\RepositoryAnalysisService;
use ArchonAI\Repositories\AnalysisRepository;

$pageTitle = 'Repository Explorer — ArchonAI';
$activePage = 'explorer';

$repoInput = trim($_GET['repo'] ?? $_SESSION['active_repo_name'] ?? 'expressjs/express');
$parsed = Validator::parseGitHubRepo($repoInput);

$error = null;
$rateLimitNotice = null;
$repoMeta = null;
$files = [];
$selectedFileContent = null;
$selectedFilePath = trim($_GET['file'] ?? '');

$github = new GitHubService();
$analysisRepo = new AnalysisRepository();

if ($parsed) {
    // 1. Check if we already have this repository and its files in MariaDB
    $dbRepo = null;
    try {
        $stmt = db()->prepare("SELECT * FROM repositories WHERE full_name = :full_name LIMIT 1");
        $stmt->execute(['full_name' => $parsed['full_name']]);
        $dbRepo = $stmt->fetch();

        if ($dbRepo) {
            $repoMeta = [
                'id' => $dbRepo['github_repo_id'],
                'full_name' => $dbRepo['full_name'],
                'description' => $dbRepo['description'] ?? 'Imported repository',
                'default_branch' => $dbRepo['default_branch'] ?? 'main',
                'language' => $dbRepo['primary_language'] ?? 'JavaScript',
                'html_url' => $dbRepo['html_url'],
                'visibility' => $dbRepo['visibility'] ?? 'public',
                'stargazers_count' => 0,
                'forks_count' => 0,
            ];

            $latestAnalysis = $analysisRepo->getLatestByRepoId((int)$dbRepo['id']);
            if ($latestAnalysis) {
                $files = $analysisRepo->getFilesByAnalysis((int)$latestAnalysis['id']);
            }
        }
    } catch (\Throwable $e) {}

    // 2. Try fetching live metadata from GitHub API or cache
    try {
        $liveMeta = $github->getRepository($parsed['owner'], $parsed['repo']);
        $repoMeta = array_merge($repoMeta ?? [], $liveMeta);
        $_SESSION['active_repo_name'] = $repoMeta['full_name'];

        // If no files yet in DB, perform analysis
        if (empty($files)) {
            $service = new RepositoryAnalysisService();
            $analysisResult = $service->importAndAnalyze(auth_id() ?? 1, $parsed['owner'], $parsed['repo']);
            $files = $analysisRepo->getFilesByAnalysis($analysisResult['analysis_id']);
        }
    } catch (\Throwable $e) {
        $msg = $e->getMessage();
        if (str_contains(strtolower($msg), 'rate limit')) {
            $rateLimitNotice = "GitHub API rate limit active. Viewing repository in offline / cached mode. You can add a free token in Settings to increase limit to 5,000 req/hr.";
        } else {
            if (!$repoMeta) {
                $error = $msg;
            }
        }
    }

    // 3. Retrieve selected file content
    if ($repoMeta && !empty($files)) {
        if (empty($selectedFilePath)) {
            $selectedFilePath = $files[0]['path'];
        }

        // Uses raw.githubusercontent.com CDN (zero API rate limit consumption)
        $selectedFileContent = $github->getFileContent(
            $parsed['owner'],
            $parsed['repo'],
            $selectedFilePath,
            $repoMeta['default_branch'] ?? 'main'
        );
    }
} else {
    $error = "Invalid repository format. Please provide owner/repository (e.g., 'expressjs/express').";
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <!-- Search & Repository Switch Bar -->
        <div class="archon-card p-3 mb-4">
            <form action="<?= app_url('pages/explorer.php') ?>" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <div class="repo-input-group flex-grow-1">
                    <span class="px-2 text-muted"><i class="bi bi-github"></i></span>
                    <input type="text" name="repo" placeholder="Enter owner/repository" value="<?= htmlspecialchars($repoInput, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <button type="submit" class="btn btn-archon-primary">
                    <i class="bi bi-arrow-repeat me-1"></i> Switch Repo
                </button>
            </form>
        </div>

        <?php if ($rateLimitNotice): ?>
            <div class="alert alert-warning bg-warning bg-opacity-10 border-warning border-opacity-25 text-warning p-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill fs-5"></i>
                    <span class="small"><?= htmlspecialchars($rateLimitNotice, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <a href="<?= app_url('pages/settings.php#github-token') ?>" class="btn btn-sm btn-outline-warning py-1 px-3 small">
                    <i class="bi bi-key-fill me-1"></i> Add Free Token in Settings
                </a>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-archon-danger p-3 mb-4 d-flex align-items-center gap-3">
                <i class="bi bi-exclamation-octagon-fill fs-4"></i>
                <div>
                    <strong>Repository Notice:</strong> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($repoMeta): ?>
            <!-- Repository Header Info -->
            <div class="archon-card p-4 mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h1 class="h4 fw-bold mb-0 text-light"><?= htmlspecialchars($repoMeta['full_name'], ENT_QUOTES, 'UTF-8') ?></h1>
                            <span class="badge bg-secondary"><?= htmlspecialchars($repoMeta['visibility'] ?? 'public', ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-25"><?= htmlspecialchars($repoMeta['language'] ?? 'Code', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <p class="text-secondary small mb-0"><?= htmlspecialchars($repoMeta['description'] ?? 'No description provided.', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="<?= app_url('pages/architecture.php?repo=' . urlencode($repoMeta['full_name'])) ?>" class="btn btn-sm btn-archon-primary">
                            <i class="bi bi-diagram-3 me-1"></i> Architecture Graph
                        </a>
                        <a href="<?= app_url('pages/assistant.php?repo=' . urlencode($repoMeta['full_name'])) ?>" class="btn btn-sm btn-archon-secondary">
                            <i class="bi bi-chat-dots me-1"></i> Ask AI
                        </a>
                        <a href="<?= htmlspecialchars($repoMeta['html_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-box-arrow-up-right me-1"></i> GitHub
                        </a>
                    </div>
                </div>

                <!-- Stats Strip -->
                <div class="d-flex flex-wrap gap-4 mt-3 pt-3 border-top border-secondary border-opacity-25 small text-muted">
                    <span><i class="bi bi-star-fill text-warning me-1"></i> <?= number_format($repoMeta['stargazers_count'] ?? 0) ?> stars</span>
                    <span><i class="bi bi-bezier2 text-cyan me-1"></i> <?= number_format($repoMeta['forks_count'] ?? 0) ?> forks</span>
                    <span><i class="bi bi-git me-1"></i> Branch: <code><?= htmlspecialchars($repoMeta['default_branch'] ?? 'main', ENT_QUOTES, 'UTF-8') ?></code></span>
                    <span><i class="bi bi-files me-1"></i> <?= count($files) ?> files mapped</span>
                </div>
            </div>

            <!-- Two-Column File Tree & Code Viewer -->
            <div class="row g-3">
                <!-- Left Column: File Tree -->
                <div class="col-lg-4">
                    <div class="archon-card p-3 h-100" style="max-height: 700px; display: flex; flex-direction: column;">
                        <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom border-secondary border-opacity-25">
                            <span class="text-uppercase small text-muted fw-semibold">Mapped Files (<?= count($files) ?>)</span>
                        </div>

                        <div class="overflow-y-auto flex-grow-1 pe-1">
                            <ul class="list-unstyled mb-0 font-monospace small">
                                <?php foreach ($files as $f): 
                                    $isSelected = ($f['path'] === $selectedFilePath);
                                    $ext = pathinfo($f['path'], PATHINFO_EXTENSION);
                                ?>
                                    <li class="mb-1">
                                        <a href="<?= app_url('pages/explorer.php?repo=' . urlencode($repoMeta['full_name']) . '&file=' . urlencode($f['path'])) ?>" 
                                           class="d-flex align-items-center gap-2 p-2 rounded text-truncate <?= $isSelected ? 'bg-primary bg-opacity-25 text-info fw-semibold border border-primary border-opacity-50' : 'text-secondary hover-light' ?>"
                                           title="<?= htmlspecialchars($f['path'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="bi <?= in_array($ext, ['js', 'ts', 'jsx', 'tsx', 'py', 'php', 'go']) ? 'bi-file-earmark-code text-cyan' : 'bi-file-earmark-text text-muted' ?>"></i>
                                            <span class="text-truncate"><?= htmlspecialchars($f['path'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Code Viewer -->
                <div class="col-lg-8">
                    <div class="archon-card p-3 h-100" style="max-height: 700px; display: flex; flex-direction: column;">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                            <div class="d-flex align-items-center gap-2 font-monospace small text-info text-truncate">
                                <i class="bi bi-file-code"></i>
                                <span class="fw-semibold text-truncate"><?= htmlspecialchars($selectedFilePath ?: 'Select a file', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <?php if ($selectedFilePath): ?>
                                <a href="<?= app_url('pages/assistant.php?repo=' . urlencode($repoMeta['full_name']) . '&file=' . urlencode($selectedFilePath)) ?>" class="btn btn-sm btn-archon-secondary py-1 px-2 small">
                                    <i class="bi bi-chat-dots me-1"></i> Explain in AI
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="overflow-auto flex-grow-1 rounded bg-black bg-opacity-50 p-3 border border-secondary border-opacity-25">
                            <?php if ($selectedFileContent !== null): ?>
                                <pre class="mb-0 text-light font-monospace small" style="white-space: pre-wrap; tab-size: 4;"><code><?= htmlspecialchars($selectedFileContent, ENT_QUOTES, 'UTF-8') ?></code></pre>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted small">
                                    <i class="bi bi-code-slash fs-1 d-block mb-2 text-secondary"></i>
                                    Select a file from the repository tree to inspect its contents.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
