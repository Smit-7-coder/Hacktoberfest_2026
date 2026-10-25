<?php
declare(strict_types=1);

namespace ArchonAI\Services;

use ArchonAI\Helpers\Logger;

require_once __DIR__ . '/../../config/services.php';
require_once __DIR__ . '/../Helpers/Logger.php';

/**
 * Ollama Open-Weight Model Inference Service
 */
class OllamaService {
    private string $baseUrl;
    private string $model;
    private int $timeout;
    private array $options;

    public function __construct() {
        $services = require __DIR__ . '/../../config/services.php';
        $this->baseUrl = $services['ollama']['base_url'] ?? 'http://127.0.0.1:11434';
        $this->model = $services['ollama']['model'] ?? 'codellama';
        $this->timeout = (int)($services['ollama']['timeout'] ?? 60);
        $this->options = $services['ollama']['options'] ?? ['temperature' => 0.2];
    }

    /**
     * Check if Ollama service is reachable and list available models
     */
    public function checkHealth(): array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/api/tags',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return [
                'status' => 'offline',
                'endpoint' => $this->baseUrl,
                'message' => 'Ollama is not running locally. Start it with `ollama serve`.',
                'models' => [],
            ];
        }

        $data = json_decode($response, true);
        $models = [];
        if (isset($data['models']) && is_array($data['models'])) {
            foreach ($data['models'] as $m) {
                $models[] = $m['name'] ?? 'unknown';
            }
        }

        return [
            'status' => 'online',
            'endpoint' => $this->baseUrl,
            'current_model' => $this->model,
            'models' => $models,
        ];
    }

    /**
     * Generate grounded response using Ollama generate API
     */
    public function generateAnswer(string $question, string $repoName, array $contextSnippets): array {
        $health = $this->checkHealth();
        if ($health['status'] !== 'online') {
            return [
                'success' => false,
                'model' => $this->model,
                'answer' => "The local Ollama AI engine is currently unreachable at `{$this->baseUrl}`. Please ensure Ollama is installed and running (`ollama run {$this->model}`). The codebase explorer and dependency graph remain fully functional without the AI service.",
                'citations' => [],
                'is_offline' => true
            ];
        }

        // Construct evidence-grounded prompt
        $contextText = "";
        $citations = [];
        foreach ($contextSnippets as $idx => $snippet) {
            $path = $snippet['path'] ?? "file_{$idx}";
            $summary = $snippet['summary'] ?? '';
            $code = $snippet['code'] ?? '';
            $citations[] = ['path' => $path, 'summary' => $summary];

            $contextText .= "### FILE: {$path}\n";
            if (!empty($summary)) {
                $contextText .= "Summary: {$summary}\n";
            }
            if (!empty($code)) {
                $contextText .= "Code Snippet:\n```\n{$code}\n```\n";
            }
            $contextText .= "\n";
        }

        $systemPrompt = <<<SYS
You are ArchonAI, an expert software architecture intelligence assistant.
Answer the developer's question about repository: {$repoName}.

Strict Grounding Rules:
1. Base your answer ONLY on the provided repository context and files.
2. Cite exact file paths (e.g. `lib/application.js`) whenever referring to code components.
3. If the context does not contain enough evidence to answer conclusively, state what is known from the files and what remains unverified.
4. Never invent nonexistent files, functions, or test suites.
5. Provide clear, concise architecture explanations.
SYS;

        $userPrompt = "REPOSITORY CONTEXT:\n{$contextText}\nDEVELOPER QUESTION: {$question}\n\nProvide an evidence-grounded architecture explanation with file citations.";

        $payload = [
            'model' => $this->model,
            'prompt' => $userPrompt,
            'system' => $systemPrompt,
            'stream' => false,
            'options' => $this->options,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/api/generate',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            Logger::error("Ollama inference error", ['code' => $httpCode, 'error' => $error]);
            return [
                'success' => false,
                'model' => $this->model,
                'answer' => "Inference request to model '{$this->model}' failed or timed out. Please check if the model is pulled (`ollama pull {$this->model}`).",
                'citations' => $citations,
            ];
        }

        $data = json_decode($response, true);
        $rawAnswer = $data['response'] ?? 'No response received from model.';

        return [
            'success' => true,
            'model' => $this->model,
            'answer' => trim($rawAnswer),
            'citations' => $citations,
        ];
    }
}
