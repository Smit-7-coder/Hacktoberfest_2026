<?php
declare(strict_types=1);

/**
 * ArchonAI - Repository Import & Trigger Analysis API
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../src/Helpers/Response.php';
require_once __DIR__ . '/../../src/Helpers/Validator.php';
require_once __DIR__ . '/../../src/Services/RepositoryAnalysisService.php';

use ArchonAI\Helpers\Response;
use ArchonAI\Helpers\Validator;
use ArchonAI\Services\RepositoryAnalysisService;

header('Content-Type: application/json; charset=utf-8');

$userId = auth_id() ?? 1; // Default to demo user if anonymous exploration
$repoInput = trim($_POST['repo'] ?? $_GET['repo'] ?? '');

$parsed = Validator::parseGitHubRepo($repoInput);
if (!$parsed) {
    Response::error("Please provide a valid GitHub repository (e.g. 'expressjs/express' or full URL).", 400);
}

try {
    $service = new RepositoryAnalysisService();
    $result = $service->importAndAnalyze($userId, $parsed['owner'], $parsed['repo']);

    $_SESSION['active_repo_id'] = $result['repo_id'];
    $_SESSION['active_repo_name'] = $result['full_name'];

    Response::success($result, "Repository imported and architecture analyzed successfully.");
} catch (\Throwable $e) {
    Response::error($e->getMessage(), 500);
}
