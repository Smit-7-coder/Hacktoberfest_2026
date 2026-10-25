<?php
declare(strict_types=1);

/**
 * ArchonAI — Public Landing Page (Module A)
 */

$pageTitle = 'ArchonAI — Understand Any Codebase. Discover What Connects.';
$activePage = 'home';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Section -->
<section class="hero-wrapper">
    <div class="container text-center px-3">
        <!-- Hacktoberfest & Open Source Pill -->
        <div class="mb-3">
            <span class="badge-archon">
                <i class="bi bi-stars text-gradient"></i> AI-Powered Software Architecture Intelligence
            </span>
            <span class="badge-hacktober ms-2">
                <i class="bi bi-balloon-heart-fill me-1"></i> Hacktoberfest 2026
            </span>
        </div>

        <!-- Main Headline -->
        <h1 class="hero-title">
            Understand Any Codebase.<br>
            <span class="text-gradient">Discover What Connects.</span>
        </h1>

        <!-- Subtitle -->
        <p class="hero-subtitle">
            ArchonAI transforms unfamiliar GitHub repositories into interactive architecture graphs, module maps, and evidence-grounded AI explanations.
        </p>

        <!-- Repo Analysis Form Card -->
        <div class="repo-search-card">
            <!-- Global Feedback Alert (Hidden by default) -->
            <div id="appGlobalAlert" class="d-none"></div>

            <form id="analyzeRepoForm" action="<?= app_url('pages/explorer.php') ?>" method="GET">
                <div class="repo-input-group mb-3">
                    <span class="px-2 text-muted"><i class="bi bi-github fs-5"></i></span>
                    <input 
                        type="text" 
                        name="repo" 
                        id="repoUrlInput" 
                        placeholder="Enter GitHub URL or owner/repository (e.g. expressjs/express)" 
                        value="expressjs/express" 
                        required 
                        autocomplete="off"
                    >
                    <button type="submit" class="btn btn-archon-primary text-nowrap d-flex align-items-center gap-2">
                        <span>Analyze Repository</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </form>

            <!-- Quick Suggestions Chips -->
            <div class="d-flex align-items-center justify-content-center flex-wrap gap-2 pt-2">
                <span class="text-muted small me-1">Try exploring:</span>
                <span class="sample-repo-chip" data-repo="expressjs/express">
                    <i class="bi bi-box"></i> expressjs/express
                </span>
                <span class="sample-repo-chip" data-repo="pallets/flask">
                    <i class="bi bi-box"></i> pallets/flask
                </span>
                <span class="sample-repo-chip" data-repo="axios/axios">
                    <i class="bi bi-box"></i> axios/axios
                </span>
                <span class="sample-repo-chip" data-repo="gin-gonic/gin">
                    <i class="bi bi-box"></i> gin-gonic/gin
                </span>
            </div>
        </div>
    </div>
</section>

<!-- Feature Showcase Section -->
<section class="py-5">
    <div class="container px-lg-5 px-3">
        <div class="text-center mb-5">
            <span class="badge-archon mb-2">Core Capabilities</span>
            <h2 class="display-6 fw-bold">Engineered for Rapid Codebase Comprehension</h2>
            <p class="text-secondary max-w-600 mx-auto">Stop reading thousands of disconnected lines. Get a bird’s-eye view and zero-in on critical dependencies.</p>
        </div>

        <div class="row g-4">
            <!-- Feature 1: Repository Explorer -->
            <div class="col-md-6 col-lg-3">
                <div class="archon-card archon-card-interactive p-4 h-100">
                    <div class="feature-icon-box feature-icon-indigo">
                        <i class="bi bi-diagram-3-fill"></i>
                    </div>
                    <h3 class="h5 fw-bold mb-2">Architecture Visualizer</h3>
                    <p class="text-muted small mb-0">
                        Interactive Cytoscape.js graphs mapping module boundaries, cross-file imports, and application topology with live filters.
                    </p>
                </div>
            </div>

            <!-- Feature 2: Evidence-Grounded AI -->
            <div class="col-md-6 col-lg-3">
                <div class="archon-card archon-card-interactive p-4 h-100">
                    <div class="feature-icon-box feature-icon-cyan">
                        <i class="bi bi-cpu-fill"></i>
                    </div>
                    <h3 class="h5 fw-bold mb-2">Evidence-Based AI</h3>
                    <p class="text-muted small mb-0">
                        Chat with open-weight coding models (CodeLlama, Qwen Coder) citing real file paths and lines. Zero unverified claims.
                    </p>
                </div>
            </div>

            <!-- Feature 3: Deep AST Analysis -->
            <div class="col-md-6 col-lg-3">
                <div class="archon-card archon-card-interactive p-4 h-100">
                    <div class="feature-icon-box feature-icon-blue">
                        <i class="bi bi-braces-asterisk"></i>
                    </div>
                    <h3 class="h5 fw-bold mb-2">Module Flow Extraction</h3>
                    <p class="text-muted small mb-0">
                        Automated detection of entry points, configuration schemas, route declarations, and critical pipeline flows.
                    </p>
                </div>
            </div>

            <!-- Feature 4: Contribution Navigator -->
            <div class="col-md-6 col-lg-3">
                <div class="archon-card archon-card-interactive p-4 h-100">
                    <div class="feature-icon-box feature-icon-pink">
                        <i class="bi bi-patch-check-fill"></i>
                    </div>
                    <h3 class="h5 fw-bold mb-2">Contribution Navigator</h3>
                    <p class="text-muted small mb-0">
                        Find beginner-friendly GitHub issues with AI-assisted file suggestions and concrete step-by-step contribution paths.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-5 border-top border-bottom border-secondary border-opacity-10 bg-secondary bg-opacity-25">
    <div class="container px-lg-5 px-3">
        <div class="text-center mb-5">
            <span class="badge-archon mb-2">Workflow</span>
            <h2 class="display-6 fw-bold">How ArchonAI Works</h2>
            <p class="text-secondary">From GitHub repository URL to comprehensive architectural understanding in seconds.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="archon-card p-4 h-100">
                    <div class="step-number">01</div>
                    <h4 class="h5 fw-bold mb-2">Import Repository</h4>
                    <p class="text-muted small mb-0">
                        Provide any public GitHub URL. ArchonAI fetches repository trees, language composition, and commit histories via GitHub REST API.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="archon-card p-4 h-100">
                    <div class="step-number">02</div>
                    <h4 class="h5 fw-bold mb-2">Graph & Relationship Engine</h4>
                    <p class="text-muted small mb-0">
                        Deterministic syntax analysis parses imports, classes, and function linkages to build a verifiable dependency matrix.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="archon-card p-4 h-100">
                    <div class="step-number">03</div>
                    <h4 class="h5 fw-bold mb-2">Explore with Grounded AI</h4>
                    <p class="text-muted small mb-0">
                        Inspect modules in 2D graphs, query the AI assistant for architectural intent, and grab curated issues ready to contribute.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Technology Stack Section -->
<section class="py-5">
    <div class="container px-lg-5 px-3">
        <div class="text-center mb-5">
            <span class="badge-archon mb-2">Native Architecture</span>
            <h2 class="display-6 fw-bold">Lightweight, Reliable, Native Stack</h2>
            <p class="text-secondary">Built to run locally on Windows XAMPP without heavyweight frameworks or bloated microservices.</p>
        </div>

        <div class="row g-3 justify-content-center">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="archon-card p-3 text-center">
                    <i class="bi bi-filetype-php fs-2 text-primary mb-2"></i>
                    <div class="fw-semibold small">Native PHP 8.2+</div>
                    <div class="text-muted" style="font-size: 0.75rem;">Fast Backend</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="archon-card p-3 text-center">
                    <i class="bi bi-database-fill fs-2 text-info mb-2"></i>
                    <div class="fw-semibold small">MariaDB / MySQL</div>
                    <div class="text-muted" style="font-size: 0.75rem;">InnoDB & PDO</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="archon-card p-3 text-center">
                    <i class="bi bi-bezier2 fs-2 text-warning mb-2"></i>
                    <div class="fw-semibold small">Cytoscape.js</div>
                    <div class="text-muted" style="font-size: 0.75rem;">Interactive Graphs</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="archon-card p-3 text-center">
                    <i class="bi bi-robot fs-2 text-cyan mb-2"></i>
                    <div class="fw-semibold small">Ollama AI</div>
                    <div class="text-muted" style="font-size: 0.75rem;">Local Inference</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="archon-card p-3 text-center">
                    <i class="bi bi-bootstrap-fill fs-2 text-indigo mb-2"></i>
                    <div class="fw-semibold small">Bootstrap 5</div>
                    <div class="text-muted" style="font-size: 0.75rem;">Responsive UI</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="archon-card p-3 text-center">
                    <i class="bi bi-github fs-2 text-light mb-2"></i>
                    <div class="fw-semibold small">GitHub REST API</div>
                    <div class="text-muted" style="font-size: 0.75rem;">Repo Intelligence</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Hacktoberfest 2026 Call to Action -->
<section class="py-5 mb-4">
    <div class="container px-lg-5 px-3">
        <div class="archon-card p-4 p-md-5 text-center position-relative overflow-hidden" style="background: radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.15) 0%, rgba(16, 23, 41, 0.9) 100%);">
            <span class="badge-hacktober mb-3">Hacktoberfest 2026</span>
            <h2 class="display-6 fw-bold mb-3">Join the ArchonAI Open-Source Community</h2>
            <p class="text-secondary max-w-600 mx-auto mb-4">
                We welcome contributions across repository parsers, AI prompt engineering, Cytoscape graph styling, and UI enhancements. Check out our open issues and start your PR!
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="<?= app_url('pages/contributions.php') ?>" class="btn btn-archon-primary">
                    <i class="bi bi-search me-1"></i> Explore Issues
                </a>
                <a href="<?= app_url('CONTRIBUTING.md') ?>" class="btn btn-archon-secondary">
                    <i class="bi bi-journal-code me-1"></i> Contributor Guide
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
