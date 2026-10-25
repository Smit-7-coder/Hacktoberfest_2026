<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$pdo = db();
$sql = file_get_contents(__DIR__ . '/seed.sql');
$pdo->exec($sql);

// Ensure demo user has proper password hash
$hash = password_hash('password123', PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE email = 'demo@archonai.dev'");
$stmt->execute(['hash' => $hash]);

echo "Seeded successfully and updated demo password!\n";
