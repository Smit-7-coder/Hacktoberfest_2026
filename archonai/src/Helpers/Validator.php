<?php
declare(strict_types=1);

namespace ArchonAI\Helpers;

/**
 * Server-Side Input Validator & Sanitizer
 */
class Validator {
    /**
     * Validate and extract owner and repo from GitHub URL or owner/repo format
     * Returns ['owner' => '...', 'repo' => '...'] or null if invalid
     */
    public static function parseGitHubRepo(string $input): ?array {
        $input = trim($input);
        
        // Remove trailing slashes and .git suffix
        $input = preg_replace('/\.git$/i', '', rtrim($input, '/'));

        // Match either https://github.com/owner/repo or owner/repo
        $pattern = '/^(?:https?:\/\/github\.com\/)?([a-zA-Z0-9_\-\.]+)\/([a-zA-Z0-9_\-\.]+)$/i';
        if (preg_match($pattern, $input, $matches)) {
            $owner = $matches[1];
            $repo = $matches[2];

            // Filter out reserved GitHub keywords
            $reserved = ['settings', 'features', 'explore', 'orgs', 'marketplace', 'topics', 'trending', 'notifications'];
            if (in_array(strtolower($owner), $reserved, true)) {
                return null;
            }

            return [
                'owner' => $owner,
                'repo' => $repo,
                'full_name' => "{$owner}/{$repo}",
                'url' => "https://github.com/{$owner}/{$repo}"
            ];
        }

        return null;
    }

    public static function email(string $email): bool {
        return (bool)filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    }

    public static function stringLength(string $value, int $min = 1, int $max = 255): bool {
        $len = mb_strlen(trim($value));
        return $len >= $min && $len <= $max;
    }

    public static function sanitize(string $value): string {
        return htmlspecialchars(trim($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
