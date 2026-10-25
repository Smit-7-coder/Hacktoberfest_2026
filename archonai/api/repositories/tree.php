<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Helpers/Response.php';
require_once __DIR__ . '/../../src/Repositories/AnalysisRepository.php';

use ArchonAI\Helpers\Response;
use ArchonAI\Repositories\AnalysisRepository;

header('Content-Type: application/json; charset=utf-8');

$analysisId = (int)($_GET['analysis_id'] ?? 0);
$repoName = trim($_GET['repo'] ?? '');

$analysisRepo = new AnalysisRepository();

if (!$analysisId && !empty($repoName)) {
    $stmt = db()->prepare("SELECT id FROM repositories WHERE full_name = :fn LIMIT 1");
    $stmt->execute(['fn' => $repoName]);
    $repoId = $stmt->fetchColumn();
    if ($repoId) {
        $latest = $analysisRepo->getLatestByRepoId((int)$repoId);
        if ($latest) $analysisId = (int)$latest['id'];
    }
}

if (!$analysisId) {
    Response::error("Analysis ID or repository not found.", 404);
}

$files = $analysisRepo->getFilesByAnalysis($analysisId);
Response::success(['analysis_id' => $analysisId, 'files' => $files]);
