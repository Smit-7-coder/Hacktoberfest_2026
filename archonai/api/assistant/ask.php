<?php
declare(strict_types=1);

/**
 * ArchonAI - AI Code Assistant Q&A API
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../src/Helpers/Response.php';
require_once __DIR__ . '/../../src/Services/AIService.php';
require_once __DIR__ . '/../../src/Repositories/AnalysisRepository.php';

use ArchonAI\Helpers\Response;
use ArchonAI\Services\AIService;
use ArchonAI\Repositories\AnalysisRepository;

header('Content-Type: application/json; charset=utf-8');

$question = trim($_POST['question'] ?? '');
$repoName = trim($_POST['repo'] ?? $_SESSION['active_repo_name'] ?? 'expressjs/express');

if (empty($question)) {
    Response::error("Question cannot be empty.", 400);
}

// 1. Fetch repository context from database
$analysisRepo = new AnalysisRepository();
$stmt = db()->prepare("SELECT id FROM repositories WHERE full_name = :full_name LIMIT 1");
$stmt->execute(['full_name' => $repoName]);
$repoId = $stmt->fetchColumn();

$contextSnippets = [];
if ($repoId) {
    $latest = $analysisRepo->getLatestByRepoId((int)$repoId);
    if ($latest) {
        $files = $analysisRepo->getFilesByAnalysis((int)$latest['id']);
        
        // Match files mentioned in the question or select key entry points
        $lowerQ = strtolower($question);
        foreach ($files as $file) {
            $base = strtolower(basename($file['path']));
            if (str_contains($lowerQ, $base) || in_array($base, ['index.js', 'app.js', 'application.js', 'router.js'], true)) {
                $contextSnippets[] = [
                    'path' => $file['path'],
                    'summary' => $file['summary'] ?? '',
                    'language' => $file['language'] ?? '',
                ];
            }
        }

        // If no specific match, provide top 5 files
        if (empty($contextSnippets)) {
            foreach (array_slice($files, 0, 5) as $file) {
                $contextSnippets[] = [
                    'path' => $file['path'],
                    'summary' => $file['summary'] ?? '',
                    'language' => $file['language'] ?? '',
                ];
            }
        }
    }
}

// 2. Query AI Service
$aiService = new AIService();
$aiResult = $aiService->ask($question, $repoName, $contextSnippets);

// 3. Save chat history if session/user exists
$userId = auth_id();
if ($userId && $repoId) {
    try {
        // Find or create session
        $sessionStmt = db()->prepare(
            "SELECT id FROM chat_sessions WHERE user_id = :user_id AND repository_id = :repo_id ORDER BY updated_at DESC LIMIT 1"
        );
        $sessionStmt->execute(['user_id' => $userId, 'repo_id' => $repoId]);
        $sessionId = $sessionStmt->fetchColumn();

        if (!$sessionId) {
            $createSession = db()->prepare(
                "INSERT INTO chat_sessions (user_id, repository_id, title, created_at, updated_at)
                 VALUES (:user_id, :repo_id, :title, NOW(), NOW())"
            );
            $shortTitle = mb_substr($question, 0, 50) . (mb_strlen($question) > 50 ? '...' : '');
            $createSession->execute([
                'user_id' => $userId,
                'repo_id' => $repoId,
                'title' => $shortTitle,
            ]);
            $sessionId = db()->lastInsertId();
        }

        // Save user message
        $msgStmt = db()->prepare(
            "INSERT INTO chat_messages (session_id, role, content, citations, model_name, created_at)
             VALUES (:session_id, 'user', :content, NULL, NULL, NOW())"
        );
        $msgStmt->execute(['session_id' => $sessionId, 'content' => $question]);

        // Save assistant message
        $asstStmt = db()->prepare(
            "INSERT INTO chat_messages (session_id, role, content, citations, model_name, created_at)
             VALUES (:session_id, 'assistant', :content, :citations, :model, NOW())"
        );
        $asstStmt->execute([
            'session_id' => $sessionId,
            'content' => $aiResult['answer'],
            'citations' => json_encode($aiResult['citations']),
            'model' => $aiResult['model'] ?? 'codellama',
        ]);
    } catch (\Throwable $e) {
        // Non-fatal logging for history saving
        error_log("Failed to persist chat message: " . $e->getMessage());
    }
}

Response::success([
    'answer' => $aiResult['answer'],
    'citations' => $aiResult['citations'],
    'model' => $aiResult['model'] ?? 'codellama',
    'is_offline' => $aiResult['is_offline'] ?? false,
]);
