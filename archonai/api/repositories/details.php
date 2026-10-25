<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Helpers/Response.php';
require_once __DIR__ . '/../../src/Services/GitHubService.php';
require_once __DIR__ . '/../../src/Helpers/Validator.php';

use ArchonAI\Helpers\Response;
use ArchonAI\Helpers\Validator;
use ArchonAI\Services\GitHubService;

header('Content-Type: application/json; charset=utf-8');

$repoName = trim($_GET['repo'] ?? '');
$parsed = Validator::parseGitHubRepo($repoName);
if (!$parsed) {
    Response::error("Invalid repository identifier.", 400);
}

try {
    $github = new GitHubService();
    $meta = $github->getRepository($parsed['owner'], $parsed['repo']);
    Response::success($meta);
} catch (\Throwable $e) {
    Response::error($e->getMessage(), 500);
}
