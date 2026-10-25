<?php
declare(strict_types=1);

/**
 * ArchonAI — Code History & Commit Explorer (Module H)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../src/Helpers/Validator.php';
require_once __DIR__ . '/../src/Services/GitHubService.php';

use ArchonAI\Helpers\Validator;
use ArchonAI\Services\GitHubService;

$pageTitle = 'Code History — ArchonAI';
$activePage = 'history';

$repoName = trim($_GET['repo'] ?? $_SESSION['active_repo_name'] ?? 'expressjs/express');
$parsed = Validator::parseGitHubRepo($repoName);
$selectedSha = trim($_GET['commit'] ?? '');

$github = new GitHubService();
$commits = [];
$commitDetails = null;
$error = null;

if ($parsed) {
    try {
        $commits = $github->getCommits($parsed['owner'], $parsed['repo'], 15);
        
        if (!empty($selectedSha)) {
            $commitDetails = $github->getCommit($parsed['owner'], $parsed['repo'], $selectedSha);
        } elseif (!empty($commits)) {
            $selectedSha = $commits[0]['sha'];
            $commitDetails = $github->getCommit($parsed['owner'], $parsed['repo'], $selectedSha);
        }
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <!-- Header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h4 fw-bold mb-1">Code History & Change Explorer</h1>
                <p class="text-secondary small mb-0">
                    Track evolution and recent architecture modifications for <span class="text-gradient fw-semibold"><?= htmlspecialchars($repoName, ENT_QUOTES, 'UTF-8') ?></span>.
                </p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-archon-danger p-3 mb-4 d-flex align-items-center gap-3">
                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <!-- Left: Commits List -->
            <div class="col-lg-5">
                <div class="archon-card p-3 h-100" style="max-height: 750px; display: flex; flex-direction: column;">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom border-secondary border-opacity-25">
                        <span class="text-uppercase small text-muted fw-semibold">Recent Commits</span>
                        <span class="badge bg-secondary"><?= count($commits) ?> commits</span>
                    </div>

                    <div class="overflow-y-auto flex-grow-1 pe-1">
                        <div class="list-group list-group-flush">
                            <?php foreach ($commits as $c): 
                                $sha = $c['sha'];
                                $shortSha = substr($sha, 0, 7);
                                $msg = $c['commit']['message'] ?? '';
                                $firstLine = strtok($msg, "\n");
                                $author = $c['commit']['author']['name'] ?? 'Developer';
                                $date = date('M d, Y H:i', strtotime($c['commit']['author']['date'] ?? 'now'));
                                $isSelected = ($sha === $selectedSha);
                            ?>
                                <a href="<?= app_url('pages/history.php?repo=' . urlencode($repoName) . '&commit=' . urlencode($sha)) ?>" 
                                   class="list-group-item list-group-item-action bg-transparent border-secondary border-opacity-25 p-3 rounded mb-2 <?= $isSelected ? 'border-primary border-opacity-75 bg-primary bg-opacity-10' : '' ?>">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="badge bg-dark border border-secondary text-info font-monospace small"><?= htmlspecialchars($shortSha, ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="text-muted small" style="font-size: 0.75rem;"><?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="fw-semibold text-light small line-clamp-2 mb-1">
                                        <?= htmlspecialchars($firstLine, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="text-secondary small d-flex align-items-center gap-1" style="font-size: 0.78rem;">
                                        <i class="bi bi-person"></i> <?= htmlspecialchars($author, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Commit Diffs & Details -->
            <div class="col-lg-7">
                <div class="archon-card p-3 h-100" style="max-height: 750px; display: flex; flex-direction: column;">
                    <?php if ($commitDetails): 
                        $fullSha = $commitDetails['sha'];
                        $stats = $commitDetails['stats'] ?? ['total' => 0, 'additions' => 0, 'deletions' => 0];
                        $filesChanged = $commitDetails['files'] ?? [];
                    ?>
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                            <div>
                                <div class="font-monospace small text-info fw-semibold"><?= htmlspecialchars($fullSha, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted small">
                                    <span class="text-success">+<?= (int)$stats['additions'] ?></span> / 
                                    <span class="text-danger">-<?= (int)$stats['deletions'] ?></span> across 
                                    <span><?= count($filesChanged) ?> changed files</span>
                                </div>
                            </div>
                            <a href="<?= htmlspecialchars($commitDetails['html_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-archon-secondary py-1 px-2 small">
                                <i class="bi bi-box-arrow-up-right me-1"></i> View on GitHub
                            </a>
                        </div>

                        <!-- Commit Message Full -->
                        <div class="p-3 rounded bg-secondary bg-opacity-25 mb-3">
                            <pre class="mb-0 text-light font-monospace small" style="white-space: pre-wrap;"><?= htmlspecialchars($commitDetails['commit']['message'], ENT_QUOTES, 'UTF-8') ?></pre>
                        </div>

                        <!-- Changed Files & Diffs -->
                        <div class="overflow-y-auto flex-grow-1">
                            <h2 class="h6 fw-bold mb-2 text-uppercase text-muted small">Changed Files</h2>
                            <?php foreach ($filesChanged as $file): ?>
                                <div class="p-2 rounded bg-black bg-opacity-40 border border-secondary border-opacity-25 mb-2">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="font-monospace small text-cyan fw-semibold"><?= htmlspecialchars($file['filename'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="badge bg-secondary small"><?= htmlspecialchars($file['status'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <?php if (!empty($file['patch'])): ?>
                                        <pre class="m-0 p-2 rounded bg-black text-light font-monospace" style="font-size: 0.75rem; max-height: 200px; overflow: auto; white-space: pre-wrap;"><code><?= htmlspecialchars($file['patch'], ENT_QUOTES, 'UTF-8') ?></code></pre>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted small">
                            <i class="bi bi-clock-history fs-1 d-block mb-2"></i>
                            Select a commit to view changed files and diffs.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
