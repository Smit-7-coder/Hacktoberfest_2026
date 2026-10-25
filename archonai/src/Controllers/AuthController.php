<?php
declare(strict_types=1);

namespace ArchonAI\Controllers;

use ArchonAI\Repositories\UserRepository;
use ArchonAI\Helpers\Validator;
use ArchonAI\Helpers\Logger;

require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Helpers/Validator.php';
require_once __DIR__ . '/../Helpers/Logger.php';
require_once __DIR__ . '/../../includes/auth.php';

class AuthController {
    private UserRepository $userRepo;

    public function __construct() {
        $this->userRepo = new UserRepository();
    }

    /**
     * Handle user registration with full validation
     */
    public function register(array $data): array {
        if (!verify_csrf_token($data['csrf_token'] ?? null)) {
            return ['success' => false, 'error' => 'Security token expired. Please try again.'];
        }

        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = (string)($data['password'] ?? '');
        $confirmPassword = (string)($data['confirm_password'] ?? '');

        if (!Validator::stringLength($name, 2, 100)) {
            return ['success' => false, 'error' => 'Name must be between 2 and 100 characters.'];
        }

        if (!Validator::email($email)) {
            return ['success' => false, 'error' => 'Please provide a valid email address.'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'error' => 'Password must be at least 8 characters long.'];
        }

        if ($password !== $confirmPassword) {
            return ['success' => false, 'error' => 'Password confirmation does not match.'];
        }

        if ($this->userRepo->emailExists($email)) {
            return ['success' => false, 'error' => 'An account with this email already exists.'];
        }

        try {
            $userId = $this->userRepo->create($name, $email, $password);
            $user = $this->userRepo->findById($userId);

            if ($user) {
                log_in_user($user);
                return ['success' => true, 'user' => $user];
            }

            return ['success' => false, 'error' => 'Could not complete registration. Please try again.'];
        } catch (\Throwable $e) {
            Logger::error("Registration error: " . $e->getMessage(), ['email' => $email]);
            return ['success' => false, 'error' => 'A technical error occurred during registration.'];
        }
    }

    /**
     * Handle user login
     */
    public function login(array $data): array {
        if (!verify_csrf_token($data['csrf_token'] ?? null)) {
            return ['success' => false, 'error' => 'Security token expired. Please try again.'];
        }

        $email = trim($data['email'] ?? '');
        $password = (string)($data['password'] ?? '');

        if (empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'Please enter both your email and password.'];
        }

        $user = $this->userRepo->findByEmail($email);
        if (!$user) {
            return ['success' => false, 'error' => 'Invalid email or password credentials.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            Logger::warning("Failed login attempt", ['email' => $email]);
            return ['success' => false, 'error' => 'Invalid email or password credentials.'];
        }

        log_in_user($user);
        Logger::info("User logged in successfully", ['user_id' => $user['id'], 'email' => $user['email']]);

        return ['success' => true, 'user' => $user];
    }
}
