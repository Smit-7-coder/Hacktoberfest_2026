<?php
declare(strict_types=1);

/**
 * ArchonAI — Secure User Logout
 */

require_once __DIR__ . '/includes/auth.php';

log_out_user();

header('Location: ' . app_url('login.php?logged_out=1'));
exit;
