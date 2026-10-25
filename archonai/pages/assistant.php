<?php
declare(strict_types=1);

/**
 * ArchonAI — Repository-Aware AI Code Assistant (Module F)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../src/Services/AIService.php';

use ArchonAI\Services\AIService;

$pageTitle = 'AI Code Assistant — ArchonAI';
$activePage = 'assistant';

$repoName = trim($_GET['repo'] ?? $_SESSION['active_repo_name'] ?? 'expressjs/express');
$focusFile = trim($_GET['file'] ?? '');

$aiService = new AIService();
$health = $aiService->checkHealth();

// Fetch previous messages for this repo if user is authenticated
$messages = [];
$userId = auth_id();
if ($userId) {
    $stmt = db()->prepare(
        "SELECT m.* 
         FROM chat_messages m
         JOIN chat_sessions s ON s.id = m.session_id
         JOIN repositories r ON r.id = s.repository_id
         WHERE s.user_id = :user_id AND r.full_name = :repo
         ORDER BY m.created_at ASC"
    );
    $stmt->execute(['user_id' => $userId, 'repo' => $repoName]);
    $messages = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content d-flex flex-column" style="height: calc(100vh - 65px);">
        <!-- Top Context Header -->
        <div class="archon-card p-3 mb-3 flex-shrink-0">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="feature-icon-box feature-icon-cyan m-0" style="width: 42px; height: 42px;">
                        <i class="bi bi-robot fs-5"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h1 class="h5 fw-bold mb-0">Repository Architecture AI</h1>
                            <span class="badge-archon small"><?= htmlspecialchars($repoName, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="text-secondary small">Evidence-grounded code intelligence powered by open-weight models.</div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <?php if ($health['status'] === 'online'): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                            <i class="bi bi-circle-fill me-1 small"></i> Ollama Online (<?= htmlspecialchars($health['current_model'], ENT_QUOTES, 'UTF-8') ?>)
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1" title="<?= htmlspecialchars($health['message'], ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-exclamation-triangle-fill me-1 small"></i> Ollama Local Offline
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Chat History Scroll Container -->
        <div class="archon-card p-4 flex-grow-1 overflow-y-auto mb-3" id="chatContainer">
            <?php if (empty($messages)): ?>
                <!-- Empty State & Quick Starters -->
                <div class="text-center py-4 text-muted" id="chatEmptyState">
                    <div class="feature-icon-box feature-icon-cyan mx-auto mb-3" style="width: 50px; height: 50px;">
                        <i class="bi bi-chat-left-quote fs-4"></i>
                    </div>
                    <h2 class="h5 fw-bold text-light mb-2">Ask Anything About This Architecture</h2>
                    <p class="small text-secondary max-w-500 mx-auto mb-4">
                        Every response is grounded directly in parsed source files and module import relationships. Zero hallucinated dependencies.
                    </p>

                    <div class="d-flex flex-wrap justify-content-center gap-2 max-w-600 mx-auto">
                        <button class="btn btn-sm btn-archon-secondary prompt-chip" data-prompt="Where is authentication or authorization handled?">
                            <i class="bi bi-shield-check me-1"></i> Where is authentication handled?
                        </button>
                        <button class="btn btn-sm btn-archon-secondary prompt-chip" data-prompt="Explain the request lifecycle and middleware routing flow.">
                            <i class="bi bi-diagram-3 me-1"></i> Explain request lifecycle
                        </button>
                        <button class="btn btn-sm btn-archon-secondary prompt-chip" data-prompt="What could break if I modify the core router module?">
                            <i class="bi bi-exclamation-triangle me-1"></i> What could break on change?
                        </button>
                        <button class="btn btn-sm btn-archon-secondary prompt-chip" data-prompt="Where should I add a new custom middleware or plugin?">
                            <i class="bi bi-plus-circle me-1"></i> Where to add a new feature?
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <div id="messagesList" class="d-flex flex-column gap-3">
                <?php foreach ($messages as $msg): 
                    $isUser = ($msg['role'] === 'user');
                    $citations = !empty($msg['citations']) ? json_decode($msg['citations'], true) : [];
                ?>
                    <div class="d-flex <?= $isUser ? 'justify-content-end' : 'justify-content-start' ?>">
                        <div class="p-3 rounded-4 <?= $isUser ? 'bg-primary text-light' : 'bg-secondary bg-opacity-25 border border-secondary border-opacity-50 text-light' ?>" style="max-width: 80%;">
                            <div class="small fw-semibold mb-1 text-uppercase <?= $isUser ? 'text-white-50' : 'text-gradient' ?>" style="font-size: 0.75rem;">
                                <?= $isUser ? 'You' : 'ArchonAI Assistant (' . htmlspecialchars($msg['model_name'] ?? 'Model', ENT_QUOTES, 'UTF-8') . ')' ?>
                            </div>
                            <div class="chat-text small" style="white-space: pre-wrap;"><?= htmlspecialchars($msg['content'], ENT_QUOTES, 'UTF-8') ?></div>

                            <?php if (!$isUser && !empty($citations)): ?>
                                <div class="mt-2 pt-2 border-top border-secondary border-opacity-25">
                                    <span class="text-muted small fw-semibold" style="font-size: 0.72rem;">Evidence Citations:</span>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        <?php foreach ($citations as $cit): ?>
                                            <span class="badge bg-dark border border-secondary text-info font-monospace small" style="font-size: 0.72rem;">
                                                <i class="bi bi-file-earmark-code me-1"></i><?= htmlspecialchars($cit['path'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Question Input Box -->
        <div class="archon-card p-3 flex-shrink-0">
            <form id="chatForm" class="d-flex gap-2">
                <input 
                    type="text" 
                    id="questionInput" 
                    class="form-control form-control-archon flex-grow-1" 
                    placeholder="Ask a question about <?= htmlspecialchars($repoName, ENT_QUOTES, 'UTF-8') ?> (e.g. 'Explain the purpose of lib/application.js')..."
                    value="<?= !empty($focusFile) ? 'Explain the purpose and dependencies of ' . htmlspecialchars($focusFile, ENT_QUOTES, 'UTF-8') : '' ?>"
                    required 
                    autocomplete="off"
                >
                <button type="submit" id="sendBtn" class="btn btn-archon-primary d-flex align-items-center gap-2">
                    <span>Send</span>
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
            <div class="d-flex align-items-center justify-content-between mt-2 text-muted small" style="font-size: 0.75rem;">
                <span>Grounding: Strict file citations enforced.</span>
                <span>Security: Untrusted code execution disabled.</span>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const chatContainer = document.getElementById('chatContainer');
    const messagesList = document.getElementById('messagesList');
    const emptyState = document.getElementById('chatEmptyState');
    const chatForm = document.getElementById('chatForm');
    const questionInput = document.getElementById('questionInput');
    const sendBtn = document.getElementById('sendBtn');
    const repoName = '<?= htmlspecialchars($repoName, ENT_QUOTES, 'UTF-8') ?>';

    // Auto scroll bottom
    if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;

    // Prompt chips
    document.querySelectorAll('.prompt-chip').forEach(btn => {
        btn.addEventListener('click', () => {
            questionInput.value = btn.getAttribute('data-prompt');
            questionInput.focus();
        });
    });

    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const question = questionInput.value.trim();
        if (!question) return;

        if (emptyState) emptyState.remove();

        // 1. Append user message
        appendMessage('user', question);
        questionInput.value = '';
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Thinking...';

        try {
            const formData = new FormData();
            formData.append('question', question);
            formData.append('repo', repoName);

            const response = await fetch('<?= app_url('api/assistant/ask.php') ?>', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                appendMessage('assistant', data.data.answer, data.data.citations, data.data.model);
            } else {
                appendMessage('assistant', 'Error: ' + (data.message || 'Failed to generate answer.'));
            }
        } catch (err) {
            appendMessage('assistant', 'Network error connecting to assistant API: ' + err.message);
        } finally {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<span>Send</span><i class="bi bi-send-fill ms-2"></i>';
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    });

    function appendMessage(role, text, citations = [], model = 'ArchonAI') {
        const isUser = (role === 'user');
        const msgDiv = document.createElement('div');
        msgDiv.className = `d-flex ${isUser ? 'justify-content-end' : 'justify-content-start'}`;

        let citationsHtml = '';
        if (!isUser && citations && citations.length > 0) {
            const tags = citations.map(c => `
                <span class="badge bg-dark border border-secondary text-info font-monospace small" style="font-size: 0.72rem;">
                    <i class="bi bi-file-earmark-code me-1"></i>${escapeHtml(c.path)}
                </span>
            `).join('');
            citationsHtml = `
                <div class="mt-2 pt-2 border-top border-secondary border-opacity-25">
                    <span class="text-muted small fw-semibold" style="font-size: 0.72rem;">Evidence Citations:</span>
                    <div class="d-flex flex-wrap gap-1 mt-1">${tags}</div>
                </div>
            `;
        }

        msgDiv.innerHTML = `
            <div class="p-3 rounded-4 ${isUser ? 'bg-primary text-light' : 'bg-secondary bg-opacity-25 border border-secondary border-opacity-50 text-light'}" style="max-width: 80%;">
                <div class="small fw-semibold mb-1 text-uppercase ${isUser ? 'text-white-50' : 'text-gradient'}" style="font-size: 0.75rem;">
                    ${isUser ? 'You' : `ArchonAI Assistant (${escapeHtml(model)})`}
                </div>
                <div class="chat-text small" style="white-space: pre-wrap;">${escapeHtml(text)}</div>
                ${citationsHtml}
            </div>
        `;
        messagesList.appendChild(msgDiv);
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
