<?php
declare(strict_types=1);

/**
 * ArchonAI - Global HTML Header Component
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'ArchonAI — Intelligence for Software Architecture';
$activePage = $activePage ?? '';
$isLoggedIn = is_authenticated();
$currentUser = auth_user();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ArchonAI — AI-powered software architecture intelligence platform. Understand unfamiliar codebases, explore dependencies, and discover open-source contribution opportunities.">
    <meta name="theme-color" content="#0a0d14">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Bootstrap 5 CSS (Dark Mode Compatible) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom ArchonAI Stylesheet -->
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
</head>
<body>
