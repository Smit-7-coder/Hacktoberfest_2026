<?php
declare(strict_types=1);

/**
 * ArchonAI — Contribution Navigator (Module G)
 * GitHub Issues, Good First Issues, and AI Guidance
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../src/Helpers/Validator.php';
require_once __DIR__ . '/../src/Services/GitHubService.php';

use ArchonAI\Helpers\Validator;
use ArchonAI\Services\GitHubService;

$pageTitle = 'Contribution Navigator — ArchonAI';
$activePage = 'contributions';

$repoName = trim($_GET['repo'] ?? $_SESSION['active_repo_name'] ?? 'expressjs/express');
$parsed = Validator::parseGitHubRepo($repoName);
$labelFilter = trim($_GET['label'] ?? 'good first issue');

$github = new GitHubService();
$issues = [];
$error = null;

if ($parsed) {
    try {
        $issues = $github->getIssues($parsed['owner'], $parsed['repo'], $labelFilter ?: null, 25);
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

// Fetch user's saved issues from DB
$userId = auth_id() ?? 1;
$savedStmt = db()->prepare(
    "SELECT si.*, r.full_name as repo_name 
     FROM saved_issues si 
     JOIN repositories r ON r.id = si.repository_id 
     WHERE si.user_id = :u 
     ORDER BY si.created_at DESC"
);
$savedStmt->execute(['u' => $userId]);
$savedIssues = $savedStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <!-- Header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h1 class="h4 fw-bold mb-0">Open-Source Contribution Navigator</h1>
                    <span class="badge-hacktober small">Hacktoberfest 2026</span>
                </div>
                <p class="text-secondary small mb-0">
                    Discover suitable beginner tasks for <span class="text-gradient fw-semibold"><?= htmlspecialchars($repoName, ENT_QUOTES, 'UTF-8') ?></span> with AI-assisted file recommendations.
                </p>
            </div>

            <!-- Filter Pills -->
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= app_url('pages/contributions.php?repo=' . urlencode($repoName) . '&label=good+first+issue') ?>" 
                   class="btn btn-sm <?= $labelFilter === 'good first issue' ? 'btn-archon-primary' : 'btn-archon-secondary' ?>">
                    <i class="bi bi-star me-1"></i> Good First Issues
                </a>
                <a href="<?= app_url('pages/contributions.php?repo=' . urlencode($repoName) . '&label=documentation') ?>" 
                   class="btn btn-sm <?= $labelFilter === 'documentation' ? 'btn-archon-primary' : 'btn-archon-secondary' ?>">
                    <i class="bi bi-book me-1"></i> Documentation
                </a>
                <a href="<?= app_url('pages/contributions.php?repo=' . urlencode($repoName) . '&label=help+wanted') ?>" 
                   class="btn btn-sm <?= $labelFilter === 'help wanted' ? 'btn-archon-primary' : 'btn-archon-secondary' ?>">
                    <i class="bi bi-hand-thumbs-up me-1"></i> Help Wanted
                </a>
                <a href="<?= app_url('pages/contributions.php?repo=' . urlencode($repoName) . '&label=') ?>" 
                   class="btn btn-sm <?= empty($labelFilter) ? 'btn-archon-primary' : 'btn-archon-secondary' ?>">
                    All Open Issues
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-archon-danger p-3 mb-4 d-flex align-items-center gap-3">
                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <!-- Nav Tabs (Live GitHub Issues vs Saved Bookmarks) -->
        <ul class="nav nav-pills mb-4 gap-2">
            <li class="nav-item">
                <button class="nav-link active bg-secondary bg-opacity-25 text-light border border-secondary" id="tab-issues-btn" data-bs-toggle="pill" data-bs-target="#tab-issues">
                    <i class="bi bi-github me-1"></i> Open Issues (<?= count($issues) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link bg-secondary bg-opacity-25 text-light border border-secondary" id="tab-saved-btn" data-bs-toggle="pill" data-bs-target="#tab-saved">
                    <i class="bi bi-bookmark-star-fill text-warning me-1"></i> Bookmarked Tracker (<?= count($savedIssues) ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- TAB 1: Live GitHub Issues -->
            <div class="tab-pane fade show active" id="tab-issues">
                <?php if (empty($issues)): ?>
                    <div class="archon-card p-5 text-center text-muted">
                        <i class="bi bi-check2-circle fs-1 text-success d-block mb-2"></i>
                        <h2 class="h6 fw-bold">No issues found matching "<?= htmlspecialchars($labelFilter, ENT_QUOTES, 'UTF-8') ?>"</h2>
                        <p class="small text-secondary mb-3">Try clearing the label filter to view all open issues.</p>
                        <a href="<?= app_url('pages/contributions.php?repo=' . urlencode($repoName) . '&label=') ?>" class="btn btn-sm btn-archon-primary">View All Open Issues</a>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($issues as $issue): 
                            $rawLabels = $issue['labels'] ?? [];
                            $issueLabels = [];
                            foreach ($rawLabels as $l) {
                                if (is_array($l) && isset($l['name'])) {
                                    $issueLabels[] = (string)$l['name'];
                                } elseif (is_string($l)) {
                                    $issueLabels[] = $l;
                                }
                            }
                        ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="archon-card p-3 h-100 d-flex flex-column">
                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                        <span class="badge bg-secondary text-info font-monospace small">#<?= (int)$issue['number'] ?></span>
                                        <button class="btn btn-sm btn-outline-warning p-1 bookmark-issue-btn" 
                                                data-issue-id="<?= (int)$issue['number'] ?>"
                                                data-title="<?= htmlspecialchars($issue['title'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-url="<?= htmlspecialchars($issue['html_url'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-labels='<?= json_encode($issueLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'
                                                title="Bookmark Issue">
                                            <i class="bi bi-bookmark"></i>
                                        </button>
                                    </div>

                                    <h2 class="h6 fw-bold mb-2 text-light line-clamp-2" title="<?= htmlspecialchars($issue['title'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($issue['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </h2>

                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        <?php foreach (array_slice($issueLabels, 0, 3) as $lblText): ?>
                                            <span class="badge bg-dark border border-secondary text-light small" style="font-size: 0.7rem;">
                                                <?= htmlspecialchars($lblText, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- AI Recommendation Box -->
                                    <div class="p-2 rounded bg-secondary bg-opacity-25 border border-secondary border-opacity-25 small mb-3 mt-auto">
                                        <div class="text-gradient fw-semibold mb-1" style="font-size: 0.75rem;">
                                            <i class="bi bi-lightbulb-fill me-1"></i> AI Investigation Hint:
                                        </div>
                                        <div class="text-secondary" style="font-size: 0.78rem;">
                                            <?php if (str_contains(strtolower($issue['title']), 'doc') || str_contains(strtolower($issue['title']), 'readme')): ?>
                                                Check <code>README.md</code>, docs directory, and JSDoc comments in entry points.
                                            <?php elseif (str_contains(strtolower($issue['title']), 'router') || str_contains(strtolower($issue['title']), 'route')): ?>
                                                Investigate routing layer in <code>lib/router/</code> and test cases.
                                            <?php else: ?>
                                                Inspect entry point prototypes and test suites before making code edits.
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-25">
                                        <span class="text-muted small" style="font-size: 0.75rem;">
                                            <i class="bi bi-chat-left me-1"></i> <?= (int)($issue['comments'] ?? 0) ?> comments
                                        </span>
                                        <a href="<?= htmlspecialchars($issue['html_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-archon-secondary py-1 px-2 small">
                                            <span>View on GitHub</span>
                                            <i class="bi bi-box-arrow-up-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 2: Bookmarked Issues -->
            <div class="tab-pane fade" id="tab-saved">
                <?php if (empty($savedIssues)): ?>
                    <div class="archon-card p-5 text-center text-muted">
                        <i class="bi bi-bookmark-plus fs-1 text-warning d-block mb-2"></i>
                        <h2 class="h6 fw-bold">No bookmarked issues yet</h2>
                        <p class="small text-secondary">Click the bookmark icon on any issue in the Open Issues tab to track it here.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive archon-card p-3">
                        <table class="table table-dark table-hover align-middle mb-0" style="background: transparent;">
                            <thead>
                                <tr class="text-muted small text-uppercase" style="border-bottom: 1px solid var(--border-color);">
                                    <th>Repository</th>
                                    <th>Issue Title</th>
                                    <th>Status</th>
                                    <th>Bookmarked At</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($savedIssues as $si): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($si['repo_name'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td>
                                            <div class="fw-semibold text-light"><?= htmlspecialchars($si['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="text-muted small font-monospace">#<?= (int)$si['github_issue_id'] ?></div>
                                        </td>
                                        <td><span class="badge bg-success bg-opacity-25 text-success"><?= htmlspecialchars($si['state'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td class="text-muted small"><?= htmlspecialchars($si['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end">
                                            <a href="<?= htmlspecialchars($si['issue_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const bookmarkButtons = document.querySelectorAll('.bookmark-issue-btn');
    const repoName = '<?= htmlspecialchars($repoName, ENT_QUOTES, 'UTF-8') ?>';

    bookmarkButtons.forEach(btn => {
        btn.addEventListener('click', async () => {
            const issueId = btn.getAttribute('data-issue-id');
            const title = btn.getAttribute('data-title');
            const url = btn.getAttribute('data-url');
            let labels = [];
            try { labels = JSON.parse(btn.getAttribute('data-labels') || '[]'); } catch(e){}

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

            const formData = new FormData();
            formData.append('repo', repoName);
            formData.append('issue_id', issueId);
            formData.append('title', title);
            formData.append('issue_url', url);
            formData.append('state', 'open');
            labels.forEach(l => formData.append('labels[]', l));

            try {
                const response = await fetch('<?= app_url('api/issues/save.php') ?>', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    btn.className = 'btn btn-sm btn-warning p-1';
                    btn.innerHTML = '<i class="bi bi-bookmark-fill text-dark"></i>';
                    alert('Issue successfully bookmarked to your contribution tracker!');
                } else {
                    alert('Error: ' + res.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-bookmark"></i>';
                }
            } catch(err) {
                alert('Network error saving bookmark: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-bookmark"></i>';
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
