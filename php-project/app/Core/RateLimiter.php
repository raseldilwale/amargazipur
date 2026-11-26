<?php
declare(strict_types=1);

class RateLimiter
{
    public static function tooManyLoginAttempts(string $ip, int $maxAttempts = 6, int $windowMinutes = 15): bool
    {
        $stmt = db()->prepare('
            SELECT COUNT(*) FROM login_attempts
            WHERE ip_address = ? AND success = 0
            AND attempted_at >= (NOW() - INTERVAL ? MINUTE)
        ');
        $stmt->execute([$ip, $windowMinutes]);
        return (int)$stmt->fetchColumn() >= $maxAttempts;
    }

    public static function recordLoginAttempt(string $ip, ?string $email, bool $success): void
    {
        $stmt = db()->prepare('INSERT INTO login_attempts (ip_address, email, success) VALUES (?, ?, ?)');
        $stmt->execute([$ip, $email, $success ? 1 : 0]);
    }
}
