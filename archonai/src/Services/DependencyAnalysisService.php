<?php
declare(strict_types=1);

namespace ArchonAI\Services;

/**
 * Dependency Extraction & Graph Analysis Service
 * Parses real import statements across supported languages (JS/TS, Python, PHP, Go).
 */
class DependencyAnalysisService {
    /**
     * Parse code content to extract file-level dependencies
     * Returns an array of dependency descriptors
     */
    public function extractDependencies(string $sourcePath, string $content, array $allRepoFilePaths): array {
        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        $dependencies = [];

        switch ($ext) {
            case 'js':
            case 'jsx':
            case 'ts':
            case 'tsx':
            case 'mjs':
                $dependencies = $this->parseJavaScriptDependencies($sourcePath, $content, $allRepoFilePaths);
                break;

            case 'py':
                $dependencies = $this->parsePythonDependencies($sourcePath, $content, $allRepoFilePaths);
                break;

            case 'php':
                $dependencies = $this->parsePhpDependencies($sourcePath, $content, $allRepoFilePaths);
                break;

            case 'go':
                $dependencies = $this->parseGoDependencies($sourcePath, $content, $allRepoFilePaths);
                break;

            default:
                break;
        }

        return $dependencies;
    }

    /**
     * Parse JS/TS import/require statements
     */
    private function parseJavaScriptDependencies(string $sourcePath, string $content, array $allPaths): array {
        $deps = [];
        $sourceDir = dirname($sourcePath);
        if ($sourceDir === '.') $sourceDir = '';

        // Match: import ... from 'path' OR export ... from 'path' OR require('path')
        $patterns = [
            '/(?:import|export)\s+.*?from\s+[\'"]([^\'"]+)[\'"]/m',
            '/require\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/m',
            '/import\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/m'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $rawImport) {
                    // Only track relative file imports, not npm package names (unless internal)
                    if (str_starts_with($rawImport, '.')) {
                        $resolved = $this->resolveRelativePath($sourceDir, $rawImport, $allPaths);
                        if ($resolved) {
                            $deps[] = [
                                'source' => $sourcePath,
                                'target' => $resolved,
                                'raw' => $rawImport,
                                'type' => 'imports',
                            ];
                        }
                    }
                }
            }
        }

        return $this->uniqueDependencies($deps);
    }

    /**
     * Parse Python imports
     */
    private function parsePythonDependencies(string $sourcePath, string $content, array $allPaths): array {
        $deps = [];
        $sourceDir = dirname($sourcePath);
        if ($sourceDir === '.') $sourceDir = '';

        // Match: from .module import something or import module
        if (preg_match_all('/from\s+(\.+[a-zA-Z0-9_\.]*)\s+import/m', $content, $matches)) {
            foreach ($matches[1] as $relativeModule) {
                $targetFile = $this->resolvePythonModule($sourceDir, $relativeModule, $allPaths);
                if ($targetFile) {
                    $deps[] = [
                        'source' => $sourcePath,
                        'target' => $targetFile,
                        'raw' => $relativeModule,
                        'type' => 'imports',
                    ];
                }
            }
        }

        return $this->uniqueDependencies($deps);
    }

    /**
     * Parse PHP require, include, use statements
     */
    private function parsePhpDependencies(string $sourcePath, string $content, array $allPaths): array {
        $deps = [];
        $sourceDir = dirname($sourcePath);
        if ($sourceDir === '.') $sourceDir = '';

        // Match: require_once 'path' or include 'path'
        if (preg_match_all('/(?:require|include)(?:_once)?\s*\(?\s*[\'"]([^\'"]+)[\'"]\s*\)?/i', $content, $matches)) {
            foreach ($matches[1] as $rawRequire) {
                $resolved = $this->resolveRelativePath($sourceDir, $rawRequire, $allPaths);
                if ($resolved) {
                    $deps[] = [
                        'source' => $sourcePath,
                        'target' => $resolved,
                        'raw' => $rawRequire,
                        'type' => 'requires',
                    ];
                }
            }
        }

        return $this->uniqueDependencies($deps);
    }

    /**
     * Parse Go imports
     */
    private function parseGoDependencies(string $sourcePath, string $content, array $allPaths): array {
        $deps = [];
        // Match Go relative imports or internal packages
        if (preg_match_all('/import\s+[\'"]([^\'"]+)[\'"]/m', $content, $matches)) {
            foreach ($matches[1] as $rawImport) {
                if (str_starts_with($rawImport, '.')) {
                    $resolved = $this->resolveRelativePath(dirname($sourcePath), $rawImport, $allPaths);
                    if ($resolved) {
                        $deps[] = [
                            'source' => $sourcePath,
                            'target' => $resolved,
                            'raw' => $rawImport,
                            'type' => 'imports',
                        ];
                    }
                }
            }
        }
        return $this->uniqueDependencies($deps);
    }

    /**
     * Resolve a relative path (e.g., './router' or '../lib/application') against actual repo files
     */
    private function resolveRelativePath(string $sourceDir, string $rawImport, array $allPaths): ?string {
        $cleanRaw = preg_replace('/^\.\//', '', $rawImport);
        $parts = !empty($sourceDir) ? explode('/', $sourceDir) : [];

        $importSegments = explode('/', $rawImport);
        foreach ($importSegments as $seg) {
            if ($seg === '..') {
                array_pop($parts);
            } elseif ($seg !== '.' && !empty($seg)) {
                $parts[] = $seg;
            }
        }

        $normalized = implode('/', $parts);

        // Try exact match
        if (in_array($normalized, $allPaths, true)) {
            return $normalized;
        }

        // Try with common extensions: .js, .ts, .jsx, .tsx, .php, /index.js, /index.ts
        $candidateExtensions = ['.js', '.ts', '.jsx', '.tsx', '.php', '/index.js', '/index.ts'];
        foreach ($candidateExtensions as $ext) {
            $candidate = $normalized . $ext;
            if (in_array($candidate, $allPaths, true)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Resolve relative python module
     */
    private function resolvePythonModule(string $sourceDir, string $rawModule, array $allPaths): ?string {
        $dots = substr_count($rawModule, '.');
        $modulePath = str_replace('.', '/', ltrim($rawModule, '.'));

        $parts = !empty($sourceDir) ? explode('/', $sourceDir) : [];
        for ($i = 1; $i < $dots; $i++) {
            array_pop($parts);
        }

        if (!empty($modulePath)) {
            $parts[] = $modulePath;
        }

        $normalized = implode('/', $parts);
        $candidatePy = $normalized . '.py';
        if (in_array($candidatePy, $allPaths, true)) {
            return $candidatePy;
        }

        $candidateInit = $normalized . '/__init__.py';
        if (in_array($candidateInit, $allPaths, true)) {
            return $candidateInit;
        }

        return null;
    }

    private function uniqueDependencies(array $deps): array {
        $unique = [];
        $seen = [];
        foreach ($deps as $dep) {
            $key = $dep['source'] . '->' . $dep['target'];
            if (!isset($seen[$key]) && $dep['source'] !== $dep['target']) {
                $seen[$key] = true;
                $unique[] = $dep;
            }
        }
        return $unique;
    }
}
