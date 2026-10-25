-- ==============================================================================
-- ArchonAI Database Schema
-- Target Engine: MySQL / MariaDB (InnoDB, UTF-8 utf8mb4)
-- Database: archonai
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- Table 1: users
-- Purpose: Store registered user accounts. Passwords hashed with password_hash().
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table 2: repositories
-- Purpose: Store imported GitHub repositories linked to users.
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `repositories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `github_repo_id` BIGINT UNSIGNED NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `default_branch` VARCHAR(100) NOT NULL DEFAULT 'main',
    `primary_language` VARCHAR(100) DEFAULT NULL,
    `html_url` VARCHAR(255) NOT NULL,
    `visibility` VARCHAR(50) NOT NULL DEFAULT 'public',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_repo` (`user_id`, `github_repo_id`),
    INDEX `idx_repo_fullname` (`full_name`),
    CONSTRAINT `fk_repos_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table 3: analyses
-- Purpose: Track repository architecture analysis runs and summary intelligence.
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `analyses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `repository_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('queued', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'queued',
    `summary` LONGTEXT DEFAULT NULL,
    `model_name` VARCHAR(100) DEFAULT NULL,
    `error_message` TEXT DEFAULT NULL,
    `started_at` TIMESTAMP NULL DEFAULT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_repo_analysis_status` (`repository_id`, `status`, `created_at`),
    CONSTRAINT `fk_analyses_repo` FOREIGN KEY (`repository_id`) REFERENCES `repositories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table 4: analysis_files
-- Purpose: Store parsed source code metadata and module summaries.
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `analysis_files` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `analysis_id` BIGINT UNSIGNED NOT NULL,
    `path` VARCHAR(500) NOT NULL,
    `language` VARCHAR(50) DEFAULT NULL,
    `file_sha` VARCHAR(100) DEFAULT NULL,
    `summary` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_analysis_filepath` (`analysis_id`, `path`(255)),
    INDEX `idx_file_lang` (`analysis_id`, `language`),
    CONSTRAINT `fk_files_analysis` FOREIGN KEY (`analysis_id`) REFERENCES `analyses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table 5: dependencies
-- Purpose: Store verified file-to-file imports and module dependencies.
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `dependencies` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `analysis_id` BIGINT UNSIGNED NOT NULL,
    `source_file_id` BIGINT UNSIGNED NOT NULL,
    `target_file_id` BIGINT UNSIGNED NOT NULL,
    `relationship_type` VARCHAR(50) NOT NULL DEFAULT 'imports',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_dependency_pair` (`analysis_id`, `source_file_id`, `target_file_id`, `relationship_type`),
    INDEX `idx_dep_analysis` (`analysis_id`),
    CONSTRAINT `fk_dep_analysis` FOREIGN KEY (`analysis_id`) REFERENCES `analyses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dep_source` FOREIGN KEY (`source_file_id`) REFERENCES `analysis_files` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dep_target` FOREIGN KEY (`target_file_id`) REFERENCES `analysis_files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table 6: chat_sessions
-- Purpose: Group AI repository assistant conversations by user and repository.
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chat_sessions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `repository_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL DEFAULT 'Architecture Discussion',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_repo_chat` (`user_id`, `repository_id`),
    CONSTRAINT `fk_chat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_chat_repo` FOREIGN KEY (`repository_id`) REFERENCES `repositories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table 7: chat_messages
-- Purpose: Store user queries and evidence-backed AI responses with file citations.
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `role` ENUM('user', 'assistant') NOT NULL,
    `content` LONGTEXT NOT NULL,
    `citations` JSON DEFAULT NULL,
    `model_name` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_session_time` (`session_id`, `created_at`),
    CONSTRAINT `fk_msg_session` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table 8: saved_issues
-- Purpose: Store bookmarked GitHub issues with AI contribution recommendations.
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `saved_issues` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `repository_id` BIGINT UNSIGNED NOT NULL,
    `github_issue_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `issue_url` VARCHAR(255) NOT NULL,
    `state` VARCHAR(50) NOT NULL DEFAULT 'open',
    `labels` JSON DEFAULT NULL,
    `ai_explanation` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_saved_issue` (`user_id`, `repository_id`, `github_issue_id`),
    INDEX `idx_user_saved_issues` (`user_id`, `repository_id`),
    CONSTRAINT `fk_issues_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_issues_repo` FOREIGN KEY (`repository_id`) REFERENCES `repositories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
