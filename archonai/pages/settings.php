<?php
declare(strict_types=1);

/**
 * ArchonAI — Settings & External Services Status (Module B & System)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../src/Services/AIService.php';
require_once __DIR__ . '/../src/Services/GitHubService.php';

use ArchonAI\Services\AIService;
use ArchonAI\Services\GitHubService;

require_auth('login.php');

$pageTitle = 'Settings & Diagnostics — ArchonAI';
$activePage = 'settings';

$aiService = new AIService();
$aiHealth = $aiService->checkHealth();
$dbHealth = Database::testConnection();
$githubService = new GitHubService();
$rateLimitStatus = $githubService->getRateLimitStatus();
$servicesConfig = require __DIR__ . '/../config/services.php';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="mb-4">
            <h1 class="h4 fw-bold mb-1">System Settings & Engine Diagnostics</h1>
            <p class="text-secondary small mb-0">Configure local AI endpoints, inspect database connectivity, and manage API quotas.</p>
        </div>

        <div class="row g-4">
            <!-- 1. Database Connection Status -->
            <div class="col-lg-6">
                <div class="archon-card p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-database text-gradient fs-5"></i>
                            <h2 class="h6 fw-bold mb-0">MariaDB / MySQL Connection</h2>
                        </div>
                        <span class="badge <?= $dbHealth['status'] === 'success' ? 'bg-success' : 'bg-danger' ?>">
                            <?= strtoupper($dbHealth['status']) ?>
                        </span>
                    </div>

                    <table class="table table-sm table-dark table-borderless small mb-0">
                        <tr>
                            <td class="text-muted">Host & Port:</td>
                            <td class="font-monospace text-info"><?= htmlspecialchars($dbHealth['host'] ?? '127.0.0.1:3306', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Database:</td>
                            <td class="font-monospace text-light"><?= htmlspecialchars($dbHealth['active_database'] ?? 'archonai', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Server Version:</td>
                            <td class="font-monospace text-light"><?= htmlspecialchars($dbHealth['server_version'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Active Tables:</td>
                            <td class="font-monospace text-success"><?= (int)($dbHealth['tables_count'] ?? 0) ?> tables initialized</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Character Set:</td>
                            <td class="font-monospace text-light"><?= htmlspecialchars($dbHealth['charset'] ?? 'utf8mb4', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- 2. Ollama AI Model Engine -->
            <div class="col-lg-6">
                <div class="archon-card p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-robot text-gradient fs-5"></i>
                            <h2 class="h6 fw-bold mb-0">Ollama AI Inference Engine</h2>
                        </div>
                        <span class="badge <?= $aiHealth['status'] === 'online' ? 'bg-success' : 'bg-warning text-dark' ?>">
                            <?= strtoupper($aiHealth['status']) ?>
                        </span>
                    </div>

                    <table class="table table-sm table-dark table-borderless small mb-3">
                        <tr>
                            <td class="text-muted">API Endpoint:</td>
                            <td class="font-monospace text-info"><?= htmlspecialchars($aiHealth['endpoint'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Configured Model:</td>
                            <td class="font-monospace text-light"><?= htmlspecialchars($servicesConfig['ollama']['model'] ?? 'codellama', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Installed Models:</td>
                            <td class="font-monospace text-light">
                                <?= !empty($aiHealth['models']) ? implode(', ', $aiHealth['models']) : '<span class="text-muted">None detected</span>' ?>
                            </td>
                        </tr>
                    </table>

                    <div class="p-2 rounded bg-secondary bg-opacity-25 small text-muted">
                        <i class="bi bi-info-circle me-1 text-cyan"></i>
                        To switch or install coding models, run: <code>ollama pull codellama</code> or <code>ollama pull qwen2.5-coder</code> in your terminal.
                    </div>
                </div>
            </div>

            <!-- 3. GitHub REST API Integration & Rate Limit Resolver -->
            <div class="col-lg-6" id="github-token">
                <div class="archon-card p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-github text-gradient fs-5"></i>
                            <h2 class="h6 fw-bold mb-0">GitHub REST API & Rate Limit</h2>
                        </div>
                        <span class="badge <?= !empty(env('GITHUB_API_TOKEN')) ? 'bg-success' : 'bg-warning text-dark' ?>">
                            <?= !empty(env('GITHUB_API_TOKEN')) ? 'Authenticated (5,000 req/hr)' : 'Unauthenticated (60 req/hr)' ?>
                        </span>
                    </div>

                    <div id="tokenUpdateAlert" class="d-none"></div>

                    <table class="table table-sm table-dark table-borderless small mb-3">
                        <tr>
                            <td class="text-muted">Quota Limit:</td>
                            <td class="font-monospace text-light"><?= (int)$rateLimitStatus['limit'] ?> requests/hour</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Remaining Quota:</td>
                            <td class="font-monospace <?= ($rateLimitStatus['remaining'] < 5) ? 'text-danger fw-bold' : 'text-success' ?>">
                                <?= (int)$rateLimitStatus['remaining'] ?> remaining
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Current Token:</td>
                            <td class="font-monospace text-info">
                                <?= !empty(env('GITHUB_API_TOKEN')) ? htmlspecialchars(substr(env('GITHUB_API_TOKEN'), 0, 4) . '••••••••' . substr(env('GITHUB_API_TOKEN'), -4)) : '<span class="text-muted">None configured</span>' ?>
                            </td>
                        </tr>
                    </table>

                    <!-- Interactive Token Setup Form -->
                    <form id="tokenSaveForm" class="mb-3">
                        <label for="tokenInput" class="form-label-archon">Configure Personal Access Token (PAT)</label>
                        <div class="input-group input-group-sm mb-2">
                            <input 
                                type="password" 
                                id="tokenInput" 
                                class="form-control form-control-archon font-monospace" 
                                placeholder="Paste token here (e.g. ghp_xxxxxxxxxxxxxxxxxxxx)"
                                value="<?= htmlspecialchars((string)env('GITHUB_API_TOKEN', ''), ENT_QUOTES, 'UTF-8') ?>"
                            >
                            <button type="submit" id="saveTokenBtn" class="btn btn-archon-primary">
                                <i class="bi bi-save me-1"></i> Save Token
                            </button>
                        </div>
                    </form>

                    <div class="p-2 rounded bg-secondary bg-opacity-25 small text-muted">
                        <div class="fw-semibold text-light mb-1"><i class="bi bi-shield-check text-cyan me-1"></i>How to get a free GitHub Token:</div>
                        <ol class="ps-3 mb-1" style="font-size: 0.78rem;">
                            <li>Log in to GitHub and visit <a href="https://github.com/settings/tokens/new?description=ArchonAI_Development&scopes=public_repo" target="_blank" rel="noopener" class="text-info text-decoration-underline">GitHub Token Generator <i class="bi bi-box-arrow-up-right"></i></a>.</li>
                            <li>Leave the scopes default (no special permissions required for public repositories).</li>
                            <li>Click <strong>Generate token</strong>, copy it, and paste it into the field above.</li>
                        </ol>
                        <div class="text-muted" style="font-size: 0.75rem;">This immediately increases your limit from 60 to 5,000 requests/hour!</div>
                    </div>
                </div>
            </div>

            <!-- 4. Environment Information -->
            <div class="col-lg-6">
                <div class="archon-card p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-cpu text-gradient fs-5"></i>
                            <h2 class="h6 fw-bold mb-0">Environment & PHP Runtime</h2>
                        </div>
                        <span class="badge bg-secondary">PHP <?= PHP_VERSION ?></span>
                    </div>

                    <table class="table table-sm table-dark table-borderless small mb-0">
                        <tr>
                            <td class="text-muted">Operating System:</td>
                            <td class="font-monospace text-light"><?= PHP_OS ?> (<?= php_uname('s') ?>)</td>
                        </tr>
                        <tr>
                            <td class="text-muted">PHP SAPI:</td>
                            <td class="font-monospace text-light"><?= php_sapi_name() ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Loaded Extensions:</td>
                            <td class="font-monospace text-light">PDO, pdo_mysql, curl, mbstring, json</td>
                        </tr>
                        <tr>
                            <td class="text-muted">App Base URL:</td>
                            <td class="font-monospace text-info text-break"><?= htmlspecialchars(app_url(), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('tokenSaveForm');
    const input = document.getElementById('tokenInput');
    const btn = document.getElementById('saveTokenBtn');
    const alertBox = document.getElementById('tokenUpdateAlert');

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const token = input.value.trim();

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...';

            const fd = new FormData();
            fd.append('token', token);

            try {
                const res = await fetch('<?= app_url('api/settings/update_token.php') ?>', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (alertBox) {
                    alertBox.className = `alert ${data.success ? 'alert-archon-success' : 'alert-archon-danger'} p-2 small mb-3`;
                    alertBox.textContent = data.message || 'Updated';
                    alertBox.classList.remove('d-none');
                }

                if (data.success) {
                    setTimeout(() => window.location.reload(), 1200);
                }
            } catch (err) {
                if (alertBox) {
                    alertBox.className = 'alert alert-archon-danger p-2 small mb-3';
                    alertBox.textContent = 'Network error: ' + err.message;
                    alertBox.classList.remove('d-none');
                }
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-save me-1"></i> Save Token';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
