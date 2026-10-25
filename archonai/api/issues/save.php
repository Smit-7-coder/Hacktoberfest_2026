<?php
declare(strict_types=1);

/**
 * ArchonAI - Bookmark / Save GitHub Issue API
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../src/Helpers/Response.php';

use ArchonAI\Helpers\Response;

header('Content-Type: application/json; charset=utf-8');

$userId = auth_id() ?? 1;

$repoName = trim($_POST['repo'] ?? '');
$issueId = (int)($_POST['issue_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$url = trim($_POST['issue_url'] ?? '');
$state = trim($_POST['state'] ?? 'open');
$labels = $_POST['labels'] ?? [];
$explanation = trim($_POST['explanation'] ?? '');

if (!$issueId || empty($title) || empty($url)) {
    Response::error("Missing issue parameters.", 400);
}

// Find repository ID
$stmt = db()->prepare("SELECT id FROM repositories WHERE full_name = :full_name LIMIT 1");
$stmt->execute(['full_name' => $repoName]);
$repoId = $stmt->fetchColumn();

if (!$repoId) {
    // Insert repository record if needed
    $insertRepo = db()->prepare("INSERT INTO repositories (user_id, github_repo_id, full_name, html_url, created_at) VALUES (:u, :gid, :fn, :url, NOW())");
    $insertRepo->execute(['u' => $userId, 'gid' => time(), 'fn' => $repoName, 'url' => "https://github.com/{$repoName}"]);
    $repoId = db()->lastInsertId();
}

try {
    $stmt = db()->prepare(
        "INSERT INTO saved_issues (user_id, repository_id, github_issue_id, title, issue_url, state, labels, ai_explanation, created_at)
         VALUES (:u, :r, :gid, :t, :url, :s, :labels, :exp, NOW())
         ON DUPLICATE KEY UPDATE 
            title = VALUES(title),
            state = VALUES(state),
            labels = VALUES(labels),
            ai_explanation = VALUES(ai_explanation)"
    );
    $stmt->execute([
        'u' => $userId,
        'r' => $repoId,
        'gid' => $issueId,
        't' => $title,
        'url' => $url,
        's' => $state,
        'labels' => json_encode($labels),
        'exp' => $explanation,
    ]);

    Response::success(null, "Issue bookmarked to your contribution tracker!");
} catch (\Throwable $e) {
    Response::error("Failed to save issue: " . $e->getMessage(), 500);
}
