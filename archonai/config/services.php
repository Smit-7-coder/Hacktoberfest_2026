<?php
declare(strict_types=1);

/**
 * ArchonAI - External Services Configuration (Ollama AI & GitHub REST API)
 */

require_once __DIR__ . '/app.php';

return [
    'github' => [
        'api_url' => 'https://api.github.com',
        'token' => env('GITHUB_API_TOKEN', ''),
        'user_agent' => 'ArchonAI-Architecture-Intelligence-Hacktoberfest2026',
        'timeout' => 15,
        'rate_limit_per_minute' => 30,
    ],
    'ollama' => [
        'base_url' => rtrim((string)env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'), '/'),
        'model' => env('OLLAMA_MODEL', 'codellama'),
        'timeout' => (int)env('OLLAMA_TIMEOUT', 60),
        'stream' => false,
        'options' => [
            'temperature' => 0.2, // Low temperature for deterministic code analysis
            'top_p' => 0.9,
        ],
    ],
    'analysis' => [
        'supported_extensions' => [
            'php', 'js', 'ts', 'jsx', 'tsx', 'py', 'java', 'go', 'rs', 'rb', 'cpp', 'c', 'h', 'cs', 'json', 'yaml', 'yml', 'md', 'sql'
        ],
        'ignore_directories' => [
            'node_modules', 'vendor', '.git', '.github', 'dist', 'build', 'storage', 'temp', 'tmp', 'cache', '__pycache__', '.next', '.nuxt', 'coverage'
        ],
        'max_file_size_bytes' => 100 * 1024, // 100 KB max per source file inspection
        'max_files_per_analysis' => 300,
    ]
];
