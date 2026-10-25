<?php
declare(strict_types=1);

/**
 * ArchonAI - Cytoscape Graph Data API
 * Returns nodes and edges for the specified repository / analysis
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Helpers/Response.php';
require_once __DIR__ . '/../../src/Repositories/AnalysisRepository.php';
require_once __DIR__ . '/../../src/Repositories/RepositoryRepository.php';

use ArchonAI\Helpers\Response;
use ArchonAI\Repositories\AnalysisRepository;
use ArchonAI\Repositories\RepositoryRepository;

$analysisRepo = new AnalysisRepository();
$repoRepo = new RepositoryRepository();

$analysisId = isset($_GET['analysis_id']) ? (int)$_GET['analysis_id'] : null;
$repoName = trim($_GET['repo'] ?? '');

if (!$analysisId && !empty($repoName)) {
    // Look up by repo name
    $stmt = db()->prepare("SELECT id FROM repositories WHERE full_name = :full_name LIMIT 1");
    $stmt->execute(['full_name' => $repoName]);
    $repoId = $stmt->fetchColumn();
    if ($repoId) {
        $latest = $analysisRepo->getLatestByRepoId((int)$repoId);
        if ($latest) {
            $analysisId = (int)$latest['id'];
        }
    }
}

if (!$analysisId) {
    // Fallback to latest available analysis in database
    $stmt = db()->query("SELECT id FROM analyses WHERE status = 'completed' ORDER BY id DESC LIMIT 1");
    $analysisId = (int)$stmt->fetchColumn();
}

if (!$analysisId) {
    Response::error("No analysis found. Please run an analysis first.", 404);
}

$elements = $analysisRepo->getCytoscapeGraph($analysisId);

Response::json([
    'success' => true,
    'analysis_id' => $analysisId,
    'elements' => $elements,
    'node_count' => count(array_filter($elements, fn($el) => isset($el['data']['language']))),
    'edge_count' => count(array_filter($elements, fn($el) => isset($el['data']['source']))),
]);
