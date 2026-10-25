/**
 * ArchonAI - Frontend Core Application Logic
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Setup sample repo chip clicks
    const repoInput = document.getElementById('repoUrlInput');
    const chips = document.querySelectorAll('.sample-repo-chip');

    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            const repo = chip.getAttribute('data-repo');
            if (repo && repoInput) {
                repoInput.value = repo;
                repoInput.focus();
            }
        });
    });

    // 2. Validate GitHub repo input on landing form
    const analyzeForm = document.getElementById('analyzeRepoForm');
    if (analyzeForm && repoInput) {
        analyzeForm.addEventListener('submit', (e) => {
            const rawVal = repoInput.value.trim();
            const pattern = /^(?:https?:\/\/github\.com\/)?([a-zA-Z0-9_\-\.]+)\/([a-zA-Z0-9_\-\.]+)$/i;

            if (!rawVal || !pattern.test(rawVal)) {
                e.preventDefault();
                showAppAlert('Please enter a valid GitHub repository (e.g., "expressjs/express" or "https://github.com/pallets/flask")', 'danger');
                repoInput.focus();
                return;
            }

            const submitBtn = analyzeForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Analyzing...';
            }
        });
    }
});

/**
 * Global alert helper
 */
function showAppAlert(message, type = 'info') {
    const alertBox = document.getElementById('appGlobalAlert');
    if (!alertBox) {
        alert(message);
        return;
    }

    const typeClass = type === 'danger' ? 'alert-archon-danger' : 'alert-archon-success';
    alertBox.className = `alert ${typeClass} alert-dismissible fade show mb-4`;
    alertBox.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="bi ${type === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill'} me-2 fs-5"></i>
            <div>${escapeHtml(message)}</div>
            <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    alertBox.classList.remove('d-none');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
