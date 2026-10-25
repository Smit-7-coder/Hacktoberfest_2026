<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Helpers/Response.php';
require_once __DIR__ . '/../../src/Repositories/AnalysisRepository.php';

use ArchonAI\Helpers\Response;
use ArchonAI\Repositories\AnalysisRepository;

header('Content-Type: application/json; charset=utf-8');

$analysisId = (int)($_GET['id'] ?? 0);
if (!$analysisId) {
    Response::error("Missing analysis ID.", 400);
}

$stmt = db()->prepare("SELECT * FROM analyses WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $analysisId]);
$analysis = $stmt->fetch();

if (!$analysis) {
    Response::error("Analysis record not found.", 404);
}

Response::success($analysis);
