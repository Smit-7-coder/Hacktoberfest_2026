<?php
declare(strict_types=1);

namespace ArchonAI\Repositories;

use PDO;
use ArchonAI\Helpers\Logger;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Helpers/Logger.php';

class RepositoryRepository {
    private PDO $db;

    public function __construct() {
        $this->db = db();
    }

    public function getByUserId(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT r.*, 
                    a.id as latest_analysis_id,
                    a.status as analysis_status,
                    a.summary as analysis_summary,
                    (SELECT COUNT(*) FROM analysis_files af WHERE af.analysis_id = a.id) as files_count,
                    (SELECT COUNT(*) FROM dependencies d WHERE d.analysis_id = a.id) as dependencies_count
             FROM repositories r
             LEFT JOIN analyses a ON a.id = (
                 SELECT id FROM analyses sub 
                 WHERE sub.repository_id = r.id 
                 ORDER BY sub.created_at DESC LIMIT 1
             )
             WHERE r.user_id = :user_id
             ORDER BY r.updated_at DESC"
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function findByIdAndUser(int $repoId, int $userId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM repositories WHERE id = :id AND user_id = :user_id LIMIT 1");
        $stmt->execute(['id' => $repoId, 'user_id' => $userId]);
        $repo = $stmt->fetch();
        return $repo ?: null;
    }

    public function findByFullName(int $userId, string $fullName): ?array {
        $stmt = $this->db->prepare("SELECT * FROM repositories WHERE user_id = :user_id AND full_name = :full_name LIMIT 1");
        $stmt->execute(['user_id' => $userId, 'full_name' => $fullName]);
        $repo = $stmt->fetch();
        return $repo ?: null;
    }

    public function createOrUpdate(int $userId, array $repoData): int {
        $stmt = $this->db->prepare(
            "INSERT INTO repositories 
             (user_id, github_repo_id, full_name, description, default_branch, primary_language, html_url, visibility, created_at, updated_at)
             VALUES (:user_id, :github_repo_id, :full_name, :description, :default_branch, :primary_language, :html_url, :visibility, NOW(), NOW())
             ON DUPLICATE KEY UPDATE 
                description = VALUES(description),
                default_branch = VALUES(default_branch),
                primary_language = VALUES(primary_language),
                html_url = VALUES(html_url),
                visibility = VALUES(visibility),
                updated_at = NOW()"
        );

        $stmt->execute([
            'user_id' => $userId,
            'github_repo_id' => $repoData['id'] ?? time(),
            'full_name' => $repoData['full_name'],
            'description' => $repoData['description'] ?? null,
            'default_branch' => $repoData['default_branch'] ?? 'main',
            'primary_language' => $repoData['language'] ?? null,
            'html_url' => $repoData['html_url'] ?? "https://github.com/{$repoData['full_name']}",
            'visibility' => $repoData['visibility'] ?? 'public',
        ]);

        $existing = $this->findByFullName($userId, $repoData['full_name']);
        return (int)($existing['id'] ?? $this->db->lastInsertId());
    }
}
