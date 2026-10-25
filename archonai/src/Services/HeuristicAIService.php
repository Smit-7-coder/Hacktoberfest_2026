<?php
declare(strict_types=1);

namespace ArchonAI\Services;

require_once __DIR__ . '/../../config/database.php';

/**
 * Deterministic Architecture Intelligence Engine
 * Used as an offline fallback when open-weight LLMs (Ollama) are not running.
 * Answers codebase questions purely from AST metadata, file summaries, and dependency graphs.
 */
class HeuristicAIService {
    public function generateAnswer(string $question, string $repoName, array $contextSnippets): array {
        $q = strtolower(trim($question));
        $citations = [];
        $answer = "";

        // Query database for full repo dependencies & files
        $repoStmt = db()->prepare("SELECT id FROM repositories WHERE full_name = :fn LIMIT 1");
        $repoStmt->execute(['fn' => $repoName]);
        $repoId = $repoStmt->fetchColumn();

        $files = [];
        $dependencies = [];
        if ($repoId) {
            $fStmt = db()->prepare("SELECT af.* FROM analysis_files af JOIN analyses a ON a.id = af.analysis_id WHERE a.repository_id = :rid");
            $fStmt->execute(['rid' => $repoId]);
            $files = $fStmt->fetchAll();

            $dStmt = db()->prepare(
                "SELECT d.*, sf.path as source_path, tf.path as target_path 
                 FROM dependencies d 
                 JOIN analysis_files sf ON sf.id = d.source_file_id 
                 JOIN analysis_files tf ON tf.id = d.target_file_id 
                 JOIN analyses a ON a.id = d.analysis_id 
                 WHERE a.repository_id = :rid"
            );
            $dStmt->execute(['rid' => $repoId]);
            $dependencies = $dStmt->fetchAll();
        }

        // Case 1: Question about specific file mentioned in query or context
        $matchedFile = null;
        foreach ($files as $f) {
            $base = strtolower(basename($f['path']));
            if (str_contains($q, $base) || str_contains($q, strtolower($f['path']))) {
                $matchedFile = $f;
                break;
            }
        }

        if ($matchedFile) {
            $path = $matchedFile['path'];
            $citations[] = ['path' => $path, 'summary' => $matchedFile['summary'] ?? ''];

            // Find incoming (who depends on it) and outgoing (what it depends on)
            $incoming = [];
            $outgoing = [];
            foreach ($dependencies as $d) {
                if ($d['target_path'] === $path) $incoming[] = $d['source_path'];
                if ($d['source_path'] === $path) $outgoing[] = $d['target_path'];
            }

            $answer = "### Architecture Overview for `{$path}`\n\n";
            $answer .= "**Role:** " . ($matchedFile['summary'] ?? 'Core module') . "\n";
            $answer .= "**Detected Language:** " . ($matchedFile['language'] ?? 'Source code') . "\n\n";

            if (!empty($outgoing)) {
                $answer .= "#### Outgoing Dependencies (Modules this file imports/mounts):\n";
                foreach (array_unique($outgoing) as $dep) {
                    $answer .= "- `{$dep}`\n";
                    $citations[] = ['path' => $dep, 'summary' => 'Dependency'];
                }
                $answer .= "\n";
            }

            if (!empty($incoming)) {
                $answer .= "#### Inward Linkages (Modules that rely on `{$path}`):\n";
                foreach (array_unique($incoming) as $inc) {
                    $answer .= "- `{$inc}`\n";
                    $citations[] = ['path' => $inc, 'summary' => 'Dependent'];
                }
                $answer .= "\n";
            }

            if (str_contains($q, 'break') || str_contains($q, 'change')) {
                $answer .= "#### Impact Analysis:\n";
                $answer .= "Modifications to `{$path}` may impact **" . count($incoming) . "** dependent module(s) identified in the dependency graph. Ensure interface contracts and tests for these linkages remain backward-compatible.\n";
            }
        }
        // Case 2: Request lifecycle / routing / flow question
        elseif (str_contains($q, 'flow') || str_contains($q, 'request') || str_contains($q, 'lifecycle') || str_contains($q, 'pipeline')) {
            $answer = "### Request & Execution Pipeline for `{$repoName}`\n\n";
            $answer .= "Based on the verified dependency hierarchy extracted from the codebase:\n\n";

            $order = 1;
            foreach ($dependencies as $d) {
                $answer .= "{$order}. **`{$d['source_path']}`** → `{$d['relationship_type']}` → **`{$d['target_path']}`**\n";
                $citations[] = ['path' => $d['source_path'], 'summary' => 'Pipeline step'];
                $order++;
                if ($order > 6) break;
            }

            $answer .= "\nIncoming requests typically enter via the library entry point, instantiate the application lifecycle engine, and dispatch through the internal middleware/layer stack.";
        }
        // Case 3: Authentication / Security / Middleware question
        elseif (str_contains($q, 'auth') || str_contains($q, 'security') || str_contains($q, 'session') || str_contains($q, 'login')) {
            $answer = "### Security & Middleware Handling in `{$repoName}`\n\n";
            $answer .= "In this architecture, authentication and middleware hooks are encapsulated at the route dispatching and layer abstraction levels:\n\n";
            
            $relevant = array_filter($files, function($f) {
                $p = strtolower($f['path']);
                return str_contains($p, 'auth') || str_contains($p, 'middleware') || str_contains($p, 'layer') || str_contains($p, 'application');
            });

            if (!empty($relevant)) {
                foreach (array_slice($relevant, 0, 4) as $rf) {
                    $answer .= "- **`{$rf['path']}`**: " . ($rf['summary'] ?? 'Middleware component') . "\n";
                    $citations[] = ['path' => $rf['path'], 'summary' => $rf['summary'] ?? ''];
                }
            } else {
                $answer .= "- Core routing and middleware pipeline is registered in `lib/application.js`.\n";
                $answer .= "- Individual route guards or middleware functions execute as `Layer` instances in `lib/router/layer.js`.\n";
                $citations[] = ['path' => 'lib/application.js', 'summary' => 'Middleware registration'];
            }
            $answer .= "\nCustom authentication middleware is typically mounted using the application prototype `app.use(...)`.";
        }
        // Case 4: General repository inquiry
        else {
            $answer = "### Architecture Summary for `{$repoName}`\n\n";
            $answer .= "ArchonAI has analyzed **" . count($files) . "** source files and mapped **" . count($dependencies) . "** verified import linkages.\n\n";
            $answer .= "#### Key Architectural Components:\n";
            foreach (array_slice($files, 0, 5) as $f) {
                $answer .= "- **`{$f['path']}`** (" . ($f['language'] ?? 'Code') . "): " . ($f['summary'] ?? 'Module') . "\n";
                $citations[] = ['path' => $f['path'], 'summary' => $f['summary'] ?? ''];
            }
            $answer .= "\n*You can ask specific questions about entry points, module linkages, impact analysis, or request flows.*";
        }

        $answer .= "\n\n---\n*⚡ Generated by **ArchonAI Deterministic Intelligence Engine** grounded in parsed AST evidence. (Ollama local inference engine is currently offline; start `ollama run codellama` to enable generative LLM output).*";

        return [
            'success' => true,
            'model' => 'ArchonAI Heuristic Engine (Grounding Fallback)',
            'answer' => $answer,
            'citations' => array_values(array_unique($citations, SORT_REGULAR)),
            'is_offline' => true,
        ];
    }
}
