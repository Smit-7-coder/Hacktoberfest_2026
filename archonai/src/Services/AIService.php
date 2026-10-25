<?php
declare(strict_types=1);

namespace ArchonAI\Services;

require_once __DIR__ . '/OllamaService.php';
require_once __DIR__ . '/HeuristicAIService.php';

/**
 * AI Provider Abstraction Interface & Adapter
 * Automatically routes to Ollama if online, or falls back to
 * the Deterministic Architecture Intelligence Engine when offline.
 */
interface AIProviderInterface {
    public function checkHealth(): array;
    public function generateAnswer(string $question, string $repoName, array $contextSnippets): array;
}

class AIService {
    private AIProviderInterface|OllamaService $provider;
    private HeuristicAIService $heuristicFallback;

    public function __construct(?AIProviderInterface $provider = null) {
        $this->provider = $provider ?? new OllamaService();
        $this->heuristicFallback = new HeuristicAIService();
    }

    public function checkHealth(): array {
        return $this->provider->checkHealth();
    }

    public function ask(string $question, string $repoName, array $contextSnippets): array {
        // First try the configured primary provider (e.g. Ollama)
        $result = $this->provider->generateAnswer($question, $repoName, $contextSnippets);

        // If provider is offline or failed, seamlessly provide grounded heuristic answer
        if (!empty($result['is_offline']) || empty($result['success'])) {
            return $this->heuristicFallback->generateAnswer($question, $repoName, $contextSnippets);
        }

        return $result;
    }
}
