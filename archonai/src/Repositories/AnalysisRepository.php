<?php
declare(strict_types=1);

namespace ArchonAI\Repositories;

use PDO;
use ArchonAI\Helpers\Logger;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Helpers/Logger.php';

class AnalysisRepository {
    private PDO $db;

    public function __construct() {
        $this->db = db();
    }

    public function createAnalysis(int $repoId, string $status = 'processing', ?string $model = 'codellama'): int {
        $stmt = $this->db->prepare(
            "INSERT INTO analyses (repository_id, status, model_name, started_at, created_at)
             VALUES (:repo_id, :status, :model, NOW(), NOW())"
        );
        $stmt->execute([
            'repo_id' => $repoId,
            'status' => $status,
            'model' => $model,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateAnalysis(int $analysisId, string $status, ?string $summary = null, ?string $errorMessage = null): void {
        $stmt = $this->db->prepare(
            "UPDATE analyses 
             SET status = :status, 
                 summary = COALESCE(:summary, summary), 
                 error_message = :err,
                 completed_at = IF(:status = 'completed', NOW(), completed_at)
             WHERE id = :id"
        );
        $stmt->execute([
            'id' => $analysisId,
            'status' => $status,
            'summary' => $summary,
            'err' => $errorMessage,
        ]);
    }

    public function getLatestByRepoId(int $repoId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM analyses WHERE repository_id = :repo_id ORDER BY created_at DESC LIMIT 1");
        $stmt->execute(['repo_id' => $repoId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function saveFiles(int $analysisId, array $files): array {
        $insertedMap = [];
        $stmt = $this->db->prepare(
            "INSERT INTO analysis_files (analysis_id, path, language, file_sha, summary, created_at)
             VALUES (:analysis_id, :path, :language, :file_sha, :summary, NOW())
             ON DUPLICATE KEY UPDATE 
                language = VALUES(language),
                file_sha = VALUES(file_sha),
                summary = VALUES(summary)"
        );

        foreach ($files as $f) {
            $stmt->execute([
                'analysis_id' => $analysisId,
                'path' => $f['path'],
                'language' => $f['language'] ?? 'Unknown',
                'file_sha' => $f['sha'] ?? null,
                'summary' => $f['summary'] ?? null,
            ]);
        }

        // Return map of [path => id]
        $mapStmt = $this->db->prepare("SELECT id, path FROM analysis_files WHERE analysis_id = :analysis_id");
        $mapStmt->execute(['analysis_id' => $analysisId]);
        while ($row = $mapStmt->fetch()) {
            $insertedMap[$row['path']] = (int)$row['id'];
        }

        return $insertedMap;
    }

    public function saveDependencies(int $analysisId, array $deps, array $pathIdMap): void {
        if (empty($deps)) return;

        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO dependencies (analysis_id, source_file_id, target_file_id, relationship_type)
             VALUES (:analysis_id, :source_id, :target_id, :rel_type)"
        );

        foreach ($deps as $dep) {
            $sourceId = $pathIdMap[$dep['source']] ?? null;
            $targetId = $pathIdMap[$dep['target']] ?? null;

            if ($sourceId && $targetId && $sourceId !== $targetId) {
                $stmt->execute([
                    'analysis_id' => $analysisId,
                    'source_id' => $sourceId,
                    'target_id' => $targetId,
                    'rel_type' => $dep['type'] ?? 'imports',
                ]);
            }
        }
    }

    public function getFilesByAnalysis(int $analysisId): array {
        $stmt = $this->db->prepare("SELECT * FROM analysis_files WHERE analysis_id = :id ORDER BY path ASC");
        $stmt->execute(['id' => $analysisId]);
        return $stmt->fetchAll();
    }

    /**
     * Build Cytoscape.js compatible graph elements (nodes and edges)
     */
    public function getCytoscapeGraph(int $analysisId): array {
        $elements = [];

        // Fetch nodes (files)
        $filesStmt = $this->db->prepare(
            "SELECT id, path, language, summary,
                    (SELECT COUNT(*) FROM dependencies WHERE source_file_id = analysis_files.id) as outgoing_count,
                    (SELECT COUNT(*) FROM dependencies WHERE target_file_id = analysis_files.id) as incoming_count
             FROM analysis_files 
             WHERE analysis_id = :id"
        );
        $filesStmt->execute(['id' => $analysisId]);
        $files = $filesStmt->fetchAll();

        foreach ($files as $file) {
            $isEntryPoint = in_array(basename($file['path']), ['index.js', 'main.py', 'app.py', 'server.js', 'index.php', 'main.go'], true);
            $elements[] = [
                'data' => [
                    'id' => 'node_' . $file['id'],
                    'file_id' => $file['id'],
                    'label' => basename($file['path']),
                    'full_path' => $file['path'],
                    'language' => $file['language'] ?? 'code',
                    'summary' => $file['summary'] ?? 'Source module',
                    'is_entry' => $isEntryPoint,
                    'incoming' => (int)$file['incoming_count'],
                    'outgoing' => (int)$file['outgoing_count'],
                ]
            ];
        }

        // Fetch edges (dependencies)
        $depsStmt = $this->db->prepare(
            "SELECT d.id, d.source_file_id, d.target_file_id, d.relationship_type,
                    sf.path as source_path, tf.path as target_path
             FROM dependencies d
             JOIN analysis_files sf ON sf.id = d.source_file_id
             JOIN analysis_files tf ON tf.id = d.target_file_id
             WHERE d.analysis_id = :id"
        );
        $depsStmt->execute(['id' => $analysisId]);
        $deps = $depsStmt->fetchAll();

        foreach ($deps as $dep) {
            $elements[] = [
                'data' => [
                    'id' => 'edge_' . $dep['id'],
                    'source' => 'node_' . $dep['source_file_id'],
                    'target' => 'node_' . $dep['target_file_id'],
                    'label' => $dep['relationship_type'],
                    'type' => $dep['relationship_type'],
                ]
            ];
        }

        return $elements;
    }
}
