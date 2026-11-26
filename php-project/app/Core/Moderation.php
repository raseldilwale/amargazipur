<?php
declare(strict_types=1);

class Moderation
{
    /**
     * Scans text against active moderation_rules in DB.
     * Uses word-boundary checks for 'word' rules to reduce false positives.
     */
    public static function checkContent(string $text): array
    {
        $rules = db()->query('SELECT keyword, match_type, severity FROM moderation_rules WHERE is_active = 1')->fetchAll();
        $normalized = mb_strtolower($text, 'UTF-8');

        foreach ($rules as $rule) {
            $kw = mb_strtolower(trim($rule['keyword']), 'UTF-8');
            if ($kw === '') {
                continue;
            }

            $matched = false;
            if ($rule['match_type'] === 'word') {
                // Unicode word boundary check
                $pattern = '/(?<![\p{L}\p{N}])' . preg_quote($kw, '/') . '(?![\p{L}\p{N}])/u';
                $matched = (bool)preg_match($pattern, $normalized);
            } elseif ($rule['match_type'] === 'phrase') {
                $matched = str_contains($normalized, $kw);
            } elseif ($rule['match_type'] === 'regex') {
                $matched = @preg_match('/' . $rule['keyword'] . '/iu', $text) === 1;
            }

            if ($matched) {
                return [
                    'flagged' => true,
                    'severity' => $rule['severity'],
                    'keyword' => $rule['keyword'],
                ];
            }
        }

        // Heuristic: excessive external URLs (> 3 links) triggers manual review
        if (preg_match_all('/https?:\/\/\S+/i', $text) > 3) {
            return [
                'flagged' => true,
                'severity' => 'flag',
                'keyword' => 'excessive_links',
            ];
        }

        return ['flagged' => false, 'severity' => null, 'keyword' => null];
    }

    public static function log(string $targetType, int $targetId, ?int $userId, ?string $rule, string $action, string $notes = ''): void
    {
        $stmt = db()->prepare('INSERT INTO moderation_logs (target_type, target_id, user_id, matched_rule, action_taken, notes) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$targetType, $targetId, $userId, $rule, $action, $notes]);
    }
}
