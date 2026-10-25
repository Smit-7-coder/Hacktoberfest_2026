<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../src/Helpers/Response.php';
require_once __DIR__ . '/../../src/Helpers/Validator.php';
require_once __DIR__ . '/../../src/Services/RepositoryAnalysisService.php';

use ArchonAI\Helpers\Response;
use ArchonAI\Helpers\Validator;
use ArchonAI\Services\RepositoryAnalysisService;

header('Content-Type: application/json; charset=utf-8');

$repoInput = trim($_POST['repo'] ?? '');
$parsed = Validator::parseGitHubRepo($repoInput);
if (!$parsed) {
    Response::error("Invalid repository identifier.", 400);
}

try {
    $service = new RepositoryAnalysisService();
    $result = $service->importAndAnalyze(auth_id() ?? 1, $parsed['owner'], $parsed['repo']);
    Response::success($result, "Analysis triggered successfully.");
} catch (\Throwable $e) {
    Response::error($e->getMessage(), 500);
}
