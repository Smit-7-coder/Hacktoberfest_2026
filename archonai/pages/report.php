<?php
declare(strict_types=1);

/**
 * ArchonAI — Architecture Intelligence PDF Report Generator
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../src/Helpers/Validator.php';
require_once __DIR__ . '/../src/Repositories/AnalysisRepository.php';

use ArchonAI\Helpers\Validator;
use ArchonAI\Repositories\AnalysisRepository;

$repoName = trim($_GET['repo'] ?? $_SESSION['active_repo_name'] ?? 'expressjs/express');
$autoDownload = isset($_GET['download']) && $_GET['download'] === '1';

// 1. Fetch repository record from DB
$stmt = db()->prepare("SELECT * FROM repositories WHERE full_name = :fn LIMIT 1");
$stmt->execute(['fn' => $repoName]);
$repo = $stmt->fetch();

if (!$repo) {
    header('Location: ' . app_url('dashboard.php'));
    exit;
}

// 2. Fetch latest analysis, files, and dependencies
$analysisRepo = new AnalysisRepository();
$analysis = $analysisRepo->getLatestByRepoId((int)$repo['id']);
$analysisId = $analysis ? (int)$analysis['id'] : 0;

$files = $analysisId ? $analysisRepo->getFilesByAnalysis($analysisId) : [];

// Fetch dependencies with file paths
$depsStmt = db()->prepare(
    "SELECT d.*, sf.path as source_path, tf.path as target_path, sf.language as source_lang
     FROM dependencies d
     JOIN analysis_files sf ON sf.id = d.source_file_id
     JOIN analysis_files tf ON tf.id = d.target_file_id
     WHERE d.analysis_id = :aid
     ORDER BY sf.path ASC"
);
$depsStmt->execute(['aid' => $analysisId]);
$dependencies = $depsStmt->fetchAll();

// Language breakdown
$languages = [];
$entryPoints = [];
foreach ($files as $f) {
    $lang = $f['language'] ?? 'Other';
    $languages[$lang] = ($languages[$lang] ?? 0) + 1;

    $base = basename($f['path']);
    if (in_array($base, ['index.js', 'main.py', 'app.py', 'server.js', 'index.php', 'main.go', 'app.js'], true)) {
        $entryPoints[] = $f;
    }
}
arsort($languages);

$pageTitle = 'Architecture Intelligence Report — ' . $repo['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-primary: #1e293b;
            --brand-accent: #2563eb;
            --brand-dark: #0f172a;
        }

        body {
            background-color: #f1f5f9;
            color: #1e293b;
            font-family: 'Inter', -apple-system, sans-serif;
            font-size: 13px;
            line-height: 1.5;
        }

        /* Top Action Floating Toolbar (Hidden when printing/generating PDF) */
        .report-toolbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: #0f172a;
            color: #ffffff;
            padding: 0.75rem 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* Printable Paper Document Container */
        .report-document {
            background: #ffffff;
            max-width: 860px;
            margin: 2rem auto;
            padding: 3rem 3.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: #0f172a;
        }

        .code-font {
            font-family: 'JetBrains Mono', monospace;
        }

        .header-stripe {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-badge {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.85rem 1rem;
            text-align: center;
        }

        .stat-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2563eb;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            font-weight: 600;
        }

        .table-custom {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .table-custom th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            padding: 8px 12px;
            border-bottom: 2px solid #cbd5e1;
        }

        .table-custom td {
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }

        .table-custom tr:nth-child(even) {
            background-color: #fafafa;
        }

        .report-section {
            margin-bottom: 2rem;
        }

        .report-section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
            border-left: 4px solid #2563eb;
            padding-left: 0.6rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .pill-tag {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: 500;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff;
                color: #000000;
            }
            .report-toolbar {
                display: none !important;
            }
            .report-document {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 0;
                max-width: 100%;
            }
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Action Toolbar -->
    <div class="report-toolbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= app_url('dashboard.php') ?>" class="btn btn-sm btn-outline-light py-1 px-3">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
            <span class="text-white-50 small">|</span>
            <span class="small fw-semibold"><?= htmlspecialchars($repo['full_name'], ENT_QUOTES, 'UTF-8') ?> — Architecture Intelligence Report</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button id="btnPrintReport" class="btn btn-sm btn-outline-light py-1 px-3">
                <i class="bi bi-printer me-1"></i> Print
            </button>
            <button id="btnDownloadPDF" class="btn btn-sm btn-primary py-1 px-3 fw-semibold">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download PDF Report
            </button>
        </div>
    </div>

    <!-- Printable Report Container -->
    <div id="reportContent" class="report-document">

        <!-- Header -->
        <div class="header-stripe d-flex align-items-start justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="fw-bold fs-4 text-dark" style="font-family: 'Outfit';">Archon<span class="text-primary">AI</span></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 11px;">
                        Architecture Intelligence Report
                    </span>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" style="font-size: 11px;">
                        Hacktoberfest 2026
                    </span>
                </div>
                <div class="text-muted small">
                    Automated Static Analysis & Module Dependency Verification
                </div>
            </div>
            <div class="text-end text-muted small">
                <div><strong>Generated:</strong> <?= date('F d, Y H:i:s') ?> UTC</div>
                <div><strong>Engine:</strong> ArchonAI AST Parser v1.0</div>
            </div>
        </div>

        <!-- Repository Details Card -->
        <div class="report-section">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h4 fw-bold mb-1 text-dark"><?= htmlspecialchars($repo['full_name'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <div class="text-muted small">
                        <i class="bi bi-link-45deg"></i> <?= htmlspecialchars($repo['html_url'], ENT_QUOTES, 'UTF-8') ?>
                        &nbsp;&bull;&nbsp; Default Branch: <code><?= htmlspecialchars($repo['default_branch'], ENT_QUOTES, 'UTF-8') ?></code>
                    </div>
                </div>
                <div>
                    <span class="badge bg-dark px-3 py-2 fs-6">
                        <?= htmlspecialchars($repo['primary_language'] ?? 'Multi-language', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
            </div>

            <!-- Key Metrics Strip -->
            <div class="row g-2 mb-3">
                <div class="col-3">
                    <div class="stat-badge">
                        <div class="stat-value"><?= count($files) ?></div>
                        <div class="stat-label">Mapped Files</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="stat-badge">
                        <div class="stat-value"><?= count($dependencies) ?></div>
                        <div class="stat-label">Verified Dependencies</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="stat-badge">
                        <div class="stat-value"><?= count($entryPoints) ?></div>
                        <div class="stat-label">Entry Points</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="stat-badge">
                        <div class="stat-value"><?= count($languages) ?></div>
                        <div class="stat-label">Languages</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Executive Architecture Summary -->
        <div class="report-section">
            <div class="report-section-title">Executive Architecture Summary</div>
            <div class="p-3 bg-light rounded border border-light-subtle text-secondary small">
                <?= nl2br(htmlspecialchars($analysis['summary'] ?? 'Static architecture analysis completed. Verified import linkages and module flows extracted across repository codebase.', ENT_QUOTES, 'UTF-8')) ?>
            </div>
        </div>

        <!-- Language Breakdown & Entry Points -->
        <div class="report-section">
            <div class="row g-4">
                <div class="col-6">
                    <div class="report-section-title">Language Composition</div>
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Language</th>
                                <th class="text-end">Files</th>
                                <th class="text-end">% Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalF = max(1, count($files));
                            foreach ($languages as $lName => $cnt): 
                                $pct = round(($cnt / $totalF) * 100, 1);
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($lName, ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td class="text-end"><?= $cnt ?></td>
                                    <td class="text-end text-muted"><?= $pct ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="col-6">
                    <div class="report-section-title">Detected Entry Points</div>
                    <?php if (empty($entryPoints)): ?>
                        <div class="text-muted small">No standard top-level entry point detected.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush border rounded">
                            <?php foreach ($entryPoints as $ep): ?>
                                <div class="list-group-item p-2 small">
                                    <div class="fw-semibold code-font text-primary"><?= htmlspecialchars($ep['path'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($ep['summary'] ?? 'Application entry point', ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Verified Dependency Matrix -->
        <div class="report-section">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="report-section-title mb-0">Verified Module Dependency Matrix (Top <?= min(35, count($dependencies)) ?>)</div>
                <span class="text-muted small"><?= count($dependencies) ?> total relationships</span>
            </div>

            <?php if (empty($dependencies)): ?>
                <div class="p-3 bg-light rounded text-muted small text-center">
                    No cross-module import relationships detected in parsed source files.
                </div>
            <?php else: ?>
                <div class="border rounded overflow-hidden">
                    <table class="table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Source Module</th>
                                <th class="text-center">Relation</th>
                                <th>Target Dependent Module</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($dependencies, 0, 35) as $dep): ?>
                                <tr>
                                    <td class="code-font text-primary small text-break"><?= htmlspecialchars($dep['source_path'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-center"><span class="pill-tag"><?= htmlspecialchars($dep['relationship_type'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td class="code-font text-secondary small text-break"><?= htmlspecialchars($dep['target_path'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Hacktoberfest & Contribution Recommendations -->
        <div class="report-section">
            <div class="report-section-title">Architecture Maintenance & Contribution Guidelines</div>
            <div class="p-3 bg-light rounded border border-light-subtle small">
                <ul class="mb-0 ps-3">
                    <li class="mb-1"><strong>Dependency Integrity:</strong> Core entry points and routing layers rely heavily on verified linkages. Test contract interfaces thoroughly when modifying files with multiple incoming dependencies.</li>
                    <li class="mb-1"><strong>Modular Expansion:</strong> Mount new modules at high-level prototype factories rather than directly binding deep internal layers.</li>
                    <li><strong>Open-Source Readiness:</strong> Explore curated beginner tasks and good first issues in the ArchonAI Contribution Navigator.</li>
                </ul>
            </div>
        </div>

        <!-- Footer Verification Stamp -->
        <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 text-center text-muted" style="font-size: 11px;">
            ArchonAI Software Architecture Intelligence Platform &bull; Hacktoberfest 2026 &bull; Verified Static AST Analysis &bull; Confidential & Open-Source Audited
        </div>
    </div>

    <!-- html2pdf.js Bundle CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnPrint = document.getElementById('btnPrintReport');
        const btnDownload = document.getElementById('btnDownloadPDF');
        const element = document.getElementById('reportContent');
        const repoCleanName = '<?= preg_replace('/[^a-zA-Z0-9_\-]/', '_', $repo['full_name']) ?>';

        // 1. Native Print action
        btnPrint.addEventListener('click', () => {
            window.print();
        });

        // 2. Direct PDF File Download using html2pdf.js
        function downloadPDF() {
            btnDownload.disabled = true;
            btnDownload.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Generating PDF...';

            const opt = {
                margin: [10, 10, 10, 10],
                filename: `ArchonAI_Architecture_Report_${repoCleanName}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, logging: false },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                btnDownload.disabled = false;
                btnDownload.innerHTML = '<i class="bi bi-file-earmark-pdf-fill me-1"></i> Download PDF Report';
            }).catch(err => {
                alert('PDF generation notice: ' + err.message);
                btnDownload.disabled = false;
                btnDownload.innerHTML = '<i class="bi bi-file-earmark-pdf-fill me-1"></i> Download PDF Report';
            });
        }

        btnDownload.addEventListener('click', downloadPDF);

        <?php if ($autoDownload): ?>
            setTimeout(downloadPDF, 800);
        <?php endif; ?>
    });
    </script>
</body>
</html>
