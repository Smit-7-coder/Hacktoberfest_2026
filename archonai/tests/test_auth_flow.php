<?php
declare(strict_types=1);

/**
 * ArchonAI - Authentication Flow CLI Unit/Integration Test
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';

$userRepo = new \ArchonAI\Repositories\UserRepository();
$demoUser = $userRepo->findByEmail('demo@archonai.dev');

if (!$demoUser) {
    echo "FAIL: Demo user not found in database.\n";
    exit(1);
}

// Verify password verification
if (!password_verify('password123', $demoUser['password_hash'])) {
    echo "FAIL: Demo user password_verify failed.\n";
    exit(1);
}

echo "PASS: Demo user verified: " . $demoUser['email'] . " (ID: " . $demoUser['id'] . ")\n";

// Test new user registration
$testEmail = 'test_' . time() . '@archonai.dev';
$newUserId = $userRepo->create('Test Dev', $testEmail, 'passwordSecret123');
$newUser = $userRepo->findById($newUserId);

if ($newUser && $newUser['email'] === $testEmail) {
    echo "PASS: New user registered and retrieved successfully (ID: {$newUserId})\n";
} else {
    echo "FAIL: New user creation failed.\n";
    exit(1);
}

echo "ALL AUTH TESTS PASSED!\n";
