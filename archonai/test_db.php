<?php
declare(strict_types=1);

/**
 * ArchonAI - Phase 1 Connectivity Verification Utility
 * Safe diagnostic script: never outputs credentials or connection strings.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/database.php';

$result = Database::testConnection();

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
