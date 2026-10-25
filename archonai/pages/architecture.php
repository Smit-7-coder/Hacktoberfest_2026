<?php
declare(strict_types=1);

/**
 * ArchonAI — Interactive Architecture Visualizer (Module E)
 * Powered by Cytoscape.js
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../src/Repositories/AnalysisRepository.php';

$pageTitle = 'Architecture Graph — ArchonAI';
$activePage = 'architecture';

$repoName = trim($_GET['repo'] ?? $_SESSION['active_repo_name'] ?? 'expressjs/express');

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <!-- Top Toolbar -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h1 class="h4 fw-bold mb-1">Architecture Dependency Visualizer</h1>
                <p class="text-secondary small mb-0">
                    Live topology for <span class="text-gradient fw-semibold"><?= htmlspecialchars($repoName, ENT_QUOTES, 'UTF-8') ?></span>. Explore verified file dependencies and module pipelines.
                </p>
            </div>

            <!-- Graph Canvas Controls -->
            <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm" style="max-width: 200px;">
                    <span class="input-group-text bg-secondary border-secondary text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" id="nodeSearchInput" class="form-control form-control-archon py-1" placeholder="Search node...">
                </div>
                <button id="btnZoomIn" class="btn btn-sm btn-archon-secondary" title="Zoom In"><i class="bi bi-zoom-in"></i></button>
                <button id="btnZoomOut" class="btn btn-sm btn-archon-secondary" title="Zoom Out"><i class="bi bi-zoom-out"></i></button>
                <button id="btnFit" class="btn btn-sm btn-archon-secondary" title="Fit to Viewport"><i class="bi bi-arrows-fullscreen"></i></button>
                <button id="btnRelayout" class="btn btn-sm btn-archon-primary" title="Re-organize Layout"><i class="bi bi-arrow-clockwise"></i></button>
                <a href="<?= app_url('pages/report.php?repo=' . urlencode($repoName)) ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Download Architecture PDF Report">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> PDF
                </a>
            </div>
        </div>

        <div class="row g-3">
            <!-- Cytoscape Graph Canvas -->
            <div class="col-lg-8 col-xl-9">
                <div class="position-relative">
                    <div id="cy-container"></div>
                    <!-- Overlay Legend -->
                    <div class="position-absolute bottom-0 start-0 m-3 p-2 rounded bg-dark bg-opacity-75 border border-secondary small d-flex gap-3">
                        <span class="d-flex align-items-center gap-1"><span class="badge rounded-circle bg-primary p-1"></span> Entry Point</span>
                        <span class="d-flex align-items-center gap-1"><span class="badge rounded-circle bg-info p-1"></span> Module</span>
                        <span class="d-flex align-items-center gap-1"><span class="text-secondary">&rarr;</span> Import Link</span>
                    </div>
                    <!-- Loading Spinner -->
                    <div id="graphLoader" class="position-absolute top-50 start-50 translate-middle text-center">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <div class="text-muted small">Rendering architecture graph...</div>
                    </div>
                </div>
            </div>

            <!-- Node Inspector Side Panel -->
            <div class="col-lg-4 col-xl-3">
                <div class="archon-card p-3 h-100" id="nodeInspectorPanel">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                        <h2 class="h6 fw-bold mb-0 text-light"><i class="bi bi-info-circle text-gradient me-2"></i>Node Inspector</h2>
                        <span class="badge bg-secondary text-muted small" id="inspectorBadge">Select Node</span>
                    </div>

                    <div id="inspectorEmptyState" class="text-center py-5 text-muted small">
                        <i class="bi bi-cursor fs-2 d-block mb-2 text-secondary"></i>
                        Click any node in the graph to inspect its imports, dependents, and architectural summary.
                    </div>

                    <div id="inspectorDetails" class="d-none">
                        <div class="mb-3">
                            <label class="form-label-archon">File Path</label>
                            <div class="font-monospace small text-info text-break" id="nodePath"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-archon">Language</label>
                            <div><span class="badge bg-secondary" id="nodeLanguage"></span></div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="p-2 rounded bg-secondary bg-opacity-25 text-center">
                                    <div class="text-muted small">Incoming</div>
                                    <div class="h5 fw-bold mb-0 text-success" id="nodeIncoming">0</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded bg-secondary bg-opacity-25 text-center">
                                    <div class="text-muted small">Outgoing</div>
                                    <div class="h5 fw-bold mb-0 text-primary" id="nodeOutgoing">0</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-archon">Architectural Role</label>
                            <p class="small text-secondary mb-0" id="nodeSummary"></p>
                        </div>

                        <a id="btnAskAssistantForNode" href="<?= app_url('pages/assistant.php') ?>" class="btn btn-archon-primary w-100 small">
                            <i class="bi bi-chat-dots me-1"></i> Ask AI About File
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Cytoscape Graph Logic -->
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const cyContainer = document.getElementById('cy-container');
    const loader = document.getElementById('graphLoader');
    const repoName = '<?= htmlspecialchars($repoName, ENT_QUOTES, 'UTF-8') ?>';

    try {
        const response = await fetch(`<?= app_url('api/analysis/graph.php') ?>?repo=${encodeURIComponent(repoName)}`);
        const json = await response.json();

        if (loader) loader.classList.add('d-none');

        if (!json.success || !json.elements || json.elements.length === 0) {
            cyContainer.innerHTML = `
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-diagram-2 fs-1 d-block mb-2"></i>
                    No dependency graph available for this repository yet.<br>
                    <a href="<?= app_url('pages/explorer.php?repo=') ?>${encodeURIComponent(repoName)}" class="btn btn-sm btn-archon-primary mt-3">Run Analysis</a>
                </div>
            `;
            return;
        }

        const cy = cytoscape({
            container: cyContainer,
            elements: json.elements,
            style: [
                {
                    selector: 'node',
                    style: {
                        'label': 'data(label)',
                        'color': '#f8fafc',
                        'font-size': '11px',
                        'font-family': 'Inter, sans-serif',
                        'text-valign': 'bottom',
                        'text-margin-y': 6,
                        'background-color': '#1e293b',
                        'border-color': '#3b82f6',
                        'border-width': 2,
                        'width': 34,
                        'height': 34,
                        'text-background-color': '#0f172a',
                        'text-background-opacity': 0.8,
                        'text-background-padding': 3,
                        'text-background-shape': 'roundrectangle'
                    }
                },
                {
                    selector: 'node[?is_entry]',
                    style: {
                        'background-color': '#6366f1',
                        'border-color': '#06b6d4',
                        'border-width': 3,
                        'width': 44,
                        'height': 44,
                    }
                },
                {
                    selector: 'node:selected',
                    style: {
                        'border-color': '#ec4899',
                        'border-width': 4,
                        'background-color': '#3b82f6'
                    }
                },
                {
                    selector: 'edge',
                    style: {
                        'width': 2,
                        'line-color': 'rgba(99, 102, 241, 0.45)',
                        'target-arrow-color': 'rgba(6, 182, 212, 0.85)',
                        'target-arrow-shape': 'triangle',
                        'curve-style': 'bezier',
                        'arrow-scale': 1.1
                    }
                },
                {
                    selector: 'edge:selected',
                    style: {
                        'line-color': '#ec4899',
                        'target-arrow-color': '#ec4899',
                        'width': 3
                    }
                }
            ],
            layout: {
                name: 'cose',
                animate: true,
                padding: 40,
                componentSpacing: 100,
                nodeOverlap: 20
            }
        });

        // Controls
        document.getElementById('btnZoomIn')?.addEventListener('click', () => cy.zoom(cy.zoom() * 1.25));
        document.getElementById('btnZoomOut')?.addEventListener('click', () => cy.zoom(cy.zoom() * 0.8));
        document.getElementById('btnFit')?.addEventListener('click', () => cy.fit(null, 40));
        document.getElementById('btnRelayout')?.addEventListener('click', () => {
            cy.layout({ name: 'cose', animate: true, padding: 40 }).run();
        });

        // Node Inspector interaction
        const emptyState = document.getElementById('inspectorEmptyState');
        const detailsState = document.getElementById('inspectorDetails');
        const nodePath = document.getElementById('nodePath');
        const nodeLanguage = document.getElementById('nodeLanguage');
        const nodeIncoming = document.getElementById('nodeIncoming');
        const nodeOutgoing = document.getElementById('nodeOutgoing');
        const nodeSummary = document.getElementById('nodeSummary');
        const askBtn = document.getElementById('btnAskAssistantForNode');

        cy.on('tap', 'node', (evt) => {
            const node = evt.target;
            const data = node.data();

            if (emptyState) emptyState.classList.add('d-none');
            if (detailsState) detailsState.classList.remove('d-none');

            nodePath.textContent = data.full_path;
            nodeLanguage.textContent = data.language;
            nodeIncoming.textContent = data.incoming ?? 0;
            nodeOutgoing.textContent = data.outgoing ?? 0;
            nodeSummary.textContent = data.summary;

            if (askBtn) {
                askBtn.href = `<?= app_url('pages/assistant.php') ?>?repo=${encodeURIComponent(repoName)}&file=${encodeURIComponent(data.full_path)}`;
            }
        });

        // Search highlight
        const searchInput = document.getElementById('nodeSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const term = e.target.value.trim().toLowerCase();
                if (!term) {
                    cy.nodes().style('opacity', 1);
                    return;
                }
                cy.nodes().forEach(n => {
                    const label = n.data('label').toLowerCase();
                    const path = n.data('full_path').toLowerCase();
                    if (label.includes(term) || path.includes(term)) {
                        n.style('opacity', 1);
                        n.select();
                    } else {
                        n.style('opacity', 0.2);
                        n.unselect();
                    }
                });
            });
        }

    } catch (err) {
        if (loader) loader.classList.add('d-none');
        cyContainer.innerHTML = `<div class="alert alert-archon-danger m-4">Failed to load graph: ${err.message}</div>`;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
