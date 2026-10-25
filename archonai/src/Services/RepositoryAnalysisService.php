<?php
declare(strict_types=1);

namespace ArchonAI\Services;

use ArchonAI\Repositories\RepositoryRepository;
use ArchonAI\Repositories\AnalysisRepository;
use ArchonAI\Helpers\Logger;

require_once __DIR__ . '/GitHubService.php';
require_once __DIR__ . '/DependencyAnalysisService.php';
require_once __DIR__ . '/../Repositories/RepositoryRepository.php';
require_once __DIR__ . '/../Repositories/AnalysisRepository.php';

class RepositoryAnalysisService {
    private GitHubService $github;
    private DependencyAnalysisService $dependencyAnalyzer;
    private RepositoryRepository $repoRepo;
    private AnalysisRepository $analysisRepo;

    public function __construct() {
        $this->github = new GitHubService();
        $this->dependencyAnalyzer = new DependencyAnalysisService();
        $this->repoRepo = new RepositoryRepository();
        $this->analysisRepo = new AnalysisRepository();
    }

    /**
     * Import and run architecture analysis for a repository
     */
    public function importAndAnalyze(int $userId, string $owner, string $repoName): array {
        @set_time_limit(180);

        // 1. Fetch metadata from GitHub
        $repoMeta = $this->github->getRepository($owner, $repoName);

        // 2. Save repository record for user
        $repoId = $this->repoRepo->createOrUpdate($userId, [
            'id' => $repoMeta['id'],
            'full_name' => $repoMeta['full_name'],
            'description' => $repoMeta['description'] ?? '',
            'default_branch' => $repoMeta['default_branch'] ?? 'main',
            'language' => $repoMeta['language'] ?? 'Unknown',
            'html_url' => $repoMeta['html_url'],
            'visibility' => $repoMeta['visibility'] ?? 'public',
        ]);

        // 3. Create analysis job record
        $analysisId = $this->analysisRepo->createAnalysis($repoId, 'processing');

        try {
            // 4. Fetch tree
            $defaultBranch = $repoMeta['default_branch'] ?? 'main';
            $treeData = $this->github->getTree($owner, $repoName, $defaultBranch);
            $treeItems = $treeData['tree'] ?? [];

            // 5. Filter supported source files (ignore node_modules, vendor, binaries, etc.)
            $supportedExtensions = [
                'js' => 'JavaScript',
                'ts' => 'TypeScript',
                'jsx' => 'JavaScript (React)',
                'tsx' => 'TypeScript (React)',
                'py' => 'Python',
                'php' => 'PHP',
                'go' => 'Go',
                'rs' => 'Rust',
                'json' => 'JSON',
                'md' => 'Markdown',
                'sql' => 'SQL',
            ];

            $ignoredDirs = ['node_modules/', 'vendor/', '.git/', 'dist/', 'build/', '.next/', '.nuxt/', '__pycache__/'];

            $validFiles = [];
            $allPaths = [];

            foreach ($treeItems as $item) {
                if (($item['type'] ?? '') !== 'blob') continue;

                $path = $item['path'];
                
                // Check ignored dirs
                $isIgnored = false;
                foreach ($ignoredDirs as $ign) {
                    if (str_starts_with($path, $ign) || str_contains($path, '/' . $ign)) {
                        $isIgnored = true;
                        break;
                    }
                }
                if ($isIgnored) continue;

                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (isset($supportedExtensions[$ext])) {
                    $validFiles[] = [
                        'path' => $path,
                        'language' => $supportedExtensions[$ext],
                        'sha' => $item['sha'] ?? '',
                        'summary' => $this->inferFileRole($path),
                    ];
                    $allPaths[] = $path;
                }

                // Cap files to prevent excessive API calls
                if (count($validFiles) >= 200) {
                    break;
                }
            }

            // 6. Save files to database
            $pathIdMap = $this->analysisRepo->saveFiles($analysisId, $validFiles);

            // 7. Parse imports for top priority source files (with time budget)
            $allDependencies = [];
            $inspectedCount = 0;
            $timeStart = microtime(true);

            foreach ($validFiles as $file) {
                // If inspection takes more than 12 seconds, break to ensure timely completion
                if (microtime(true) - $timeStart > 12) {
                    break;
                }

                $ext = strtolower(pathinfo($file['path'], PATHINFO_EXTENSION));
                if (in_array($ext, ['js', 'ts', 'jsx', 'tsx', 'py', 'php', 'go'], true)) {
                    if ($inspectedCount < 20) {
                        try {
                            $codeContent = $this->github->getFileContent($owner, $repoName, $file['path'], $defaultBranch);
                            if (!empty($codeContent)) {
                                $deps = $this->dependencyAnalyzer->extractDependencies($file['path'], $codeContent, $allPaths);
                                $allDependencies = array_merge($allDependencies, $deps);
                            }
                        } catch (\Throwable $e) {
                            Logger::warning("Could not fetch file content for import extraction: " . $file['path']);
                        }
                        $inspectedCount++;
                    }
                }
            }

            // 8. Save dependencies
            $this->analysisRepo->saveDependencies($analysisId, $allDependencies, $pathIdMap);

            // 9. Mark completed
            $summary = sprintf(
                "Repository %s contains %d mapped files with primary language %s. Detected %d verified import edges across core modules.",
                $repoMeta['full_name'],
                count($validFiles),
                $repoMeta['language'] ?? 'Multi-language',
                count($allDependencies)
            );

            $this->analysisRepo->updateAnalysis($analysisId, 'completed', $summary);

            return [
                'success' => true,
                'repo_id' => $repoId,
                'analysis_id' => $analysisId,
                'full_name' => $repoMeta['full_name'],
                'files_count' => count($validFiles),
                'dependencies_count' => count($allDependencies),
                'summary' => $summary,
            ];
        } catch (\Throwable $e) {
            Logger::error("Repository analysis failed: " . $e->getMessage(), ['repo' => "{$owner}/{$repoName}"]);
            $this->analysisRepo->updateAnalysis($analysisId, 'failed', null, $e->getMessage());
            throw $e;
        }
    }

    private function inferFileRole(string $path): string {
        $base = basename($path);
        if (in_array($base, ['index.js', 'main.py', 'app.py', 'server.js', 'index.php', 'main.go'], true)) {
            return 'Application entry point';
        }
        if (in_array($base, ['package.json', 'composer.json', 'requirements.txt', 'go.mod', 'Cargo.toml'], true)) {
            return 'Package & Dependency configuration';
        }
        if (str_contains($path, 'test') || str_contains($path, 'spec')) {
            return 'Test suite / verification';
        }
        if (str_contains($path, 'route') || str_contains($path, 'controller')) {
            return 'HTTP routing & request controller';
        }
        if (str_contains($path, 'model') || str_contains($path, 'entity')) {
            return 'Data schema & entity definition';
        }
        return 'Source module';
    }
}
