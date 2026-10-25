-- ==============================================================================
-- ArchonAI Development Seed Data
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Demo user: admin@archonai.local / Password: password123
-- Hash generated via password_hash('password123', PASSWORD_BCRYPT)
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `created_at`) VALUES
(1, 'Demo Architect', 'demo@archonai.dev', '$2y$10$KAzMIQ3VrmyoLr/fjpHHoeuue1hZW9dcXyoOkfH/rR1UQOYBK8GIO', NOW())
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Demo Repository: expressjs/express
INSERT INTO `repositories` (`id`, `user_id`, `github_repo_id`, `full_name`, `description`, `default_branch`, `primary_language`, `html_url`, `visibility`, `created_at`) VALUES
(1, 1, 237159, 'expressjs/express', 'Fast, unopinionated, minimalist web framework for node.', 'master', 'JavaScript', 'https://github.com/expressjs/express', 'public', NOW())
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- Demo Analysis
INSERT INTO `analyses` (`id`, `repository_id`, `status`, `summary`, `model_name`, `started_at`, `completed_at`, `created_at`) VALUES
(1, 1, 'completed', 'Express is a minimal and flexible Node.js web application framework. Entry point is lib/express.js which exports application and router factories. Middleware pipeline is orchestrated by router/index.js and router/layer.js.', 'codellama', NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

-- Demo Analysis Files
INSERT INTO `analysis_files` (`id`, `analysis_id`, `path`, `language`, `file_sha`, `summary`, `created_at`) VALUES
(1, 1, 'lib/express.js', 'JavaScript', 'e1f2a3b4', 'Main library entry point exporting createApplication function and middleware prototypes.', NOW()),
(2, 1, 'lib/application.js', 'JavaScript', 'a1b2c3d4', 'Application prototype managing app settings, engines, routing delegation, and HTTP server lifecycle.', NOW()),
(3, 1, 'lib/router/index.js', 'JavaScript', 'b2c3d4e5', 'Core routing engine handling route dispatching, middleware stack execution, and URL param parsing.', NOW()),
(4, 1, 'lib/router/layer.js', 'JavaScript', 'c3d4e5f6', 'Layer abstraction representing an individual middleware or route handler in the routing stack.', NOW()),
(5, 1, 'lib/request.js', 'JavaScript', 'd4e5f6a7', 'Extends Node.js http.IncomingMessage with convenience helpers for headers, query params, and body parsing.', NOW()),
(6, 1, 'lib/response.js', 'JavaScript', 'e5f6a7b8', 'Extends Node.js http.ServerResponse with send, json, status, render, and cookie dispatch methods.', NOW())
ON DUPLICATE KEY UPDATE `path` = VALUES(`path`);

-- Demo Dependencies
INSERT INTO `dependencies` (`id`, `analysis_id`, `source_file_id`, `target_file_id`, `relationship_type`) VALUES
(1, 1, 1, 2, 'imports'),
(2, 1, 2, 3, 'delegates_to'),
(3, 1, 3, 4, 'orchestrates'),
(4, 1, 2, 5, 'mounts'),
(5, 1, 2, 6, 'mounts')
ON DUPLICATE KEY UPDATE `relationship_type` = VALUES(`relationship_type`);

-- Demo Chat Session
INSERT INTO `chat_sessions` (`id`, `user_id`, `repository_id`, `title`, `created_at`) VALUES
(1, 1, 1, 'Understanding Middleware Pipeline', NOW())
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- Demo Chat Messages
INSERT INTO `chat_messages` (`id`, `session_id`, `role`, `content`, `citations`, `model_name`, `created_at`) VALUES
(1, 1, 'user', 'Where does Express initialize the routing stack and middleware layers?', NULL, NULL, NOW()),
(2, 1, 'assistant', 'The routing stack is initialized inside `lib/application.js` within the `app.lazyrouter()` method, which instantiates `lib/router/index.js`. Each middleware registered via `app.use()` is encapsulated as a `Layer` instance defined in `lib/router/layer.js`.', '[{"path": "lib/application.js", "lines": "150-165"}, {"path": "lib/router/index.js", "lines": "45-60"}, {"path": "lib/router/layer.js", "lines": "30-50"}]', 'codellama', NOW())
ON DUPLICATE KEY UPDATE `content` = VALUES(`content`);

-- Demo Saved Issue
INSERT INTO `saved_issues` (`id`, `user_id`, `repository_id`, `github_issue_id`, `title`, `issue_url`, `state`, `labels`, `ai_explanation`, `created_at`) VALUES
(1, 1, 1, 5120, 'Clarify error handling middleware arity in documentation', 'https://github.com/expressjs/express/issues/5120', 'open', '["documentation", "good first issue"]', 'Recommended beginner issue: Involves updating JSDoc comments and error-handling guides to clarify why Express requires exactly 4 arguments (err, req, res, next) for error-handling middleware.', NOW())
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

SET FOREIGN_KEY_CHECKS = 1;
