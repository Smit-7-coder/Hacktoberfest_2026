<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../src/Helpers/Response.php';

use ArchonAI\Helpers\Response;

header('Content-Type: application/json; charset=utf-8');

$userId = auth_id();
if (!$userId) {
    Response::error("Authentication required.", 401);
}

$repoName = trim($_GET['repo'] ?? '');
$stmt = db()->prepare(
    "SELECT m.* 
     FROM chat_messages m
     JOIN chat_sessions s ON s.id = m.session_id
     JOIN repositories r ON r.id = s.repository_id
     WHERE s.user_id = :user_id " . (!empty($repoName) ? "AND r.full_name = :repo " : "") . "
     ORDER BY m.created_at ASC"
);
$params = ['user_id' => $userId];
if (!empty($repoName)) $params['repo'] = $repoName;
$stmt->execute($params);
$messages = $stmt->fetchAll();

Response::success($messages);
