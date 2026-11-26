<?php
declare(strict_types=1);

class Auth
{
    private static ?array $cachedUser = null;

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return null;
        }
        $stmt = db()->prepare('
            SELECT u.*, r.slug AS role_slug, r.name AS role_name
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE u.id = ? LIMIT 1
        ');
        $stmt->execute([(int)$userId]);
        $user = $stmt->fetch();
        if (!$user || $user['status'] !== 'active') {
            self::logout();
            return null;
        }

        $catStmt = db()->prepare('SELECT category_slug, status FROM user_categories WHERE user_id = ?');
        $catStmt->execute([(int)$userId]);
        $user['categories'] = $catStmt->fetchAll();

        $permStmt = db()->prepare('
            SELECT p.slug
            FROM permissions p
            JOIN role_permissions rp ON rp.permission_id = p.id
            WHERE rp.role_id = ?
        ');
        $permStmt->execute([(int)$user['role_id']]);
        $user['permissions'] = array_column($permStmt->fetchAll(), 'slug');

        self::$cachedUser = $user;
        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function loginUser(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        self::$cachedUser = null;
        db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$userId]);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        self::$cachedUser = null;
    }

    public static function hasRole(string|array $roles): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }
        $roles = (array)$roles;
        return in_array($user['role_slug'], $roles, true);
    }

    public static function hasApprovedCategory(string $slug): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }
        if ($user['role_slug'] === 'admin') {
            return true;
        }
        foreach ($user['categories'] ?? [] as $cat) {
            if ($cat['category_slug'] === $slug && $cat['status'] === 'approved') {
                return true;
            }
        }
        return false;
    }

    public static function canAccessAdmin(): bool
    {
        return self::hasRole(['admin', 'editor', 'moderator']);
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if (!$user) {
            if (is_json_request()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Authentication required', 'require_auth' => true]);
                exit;
            }
            flash('error', __('auth_required', 'এই কাজটি করতে অনুগ্রহ করে লগইন বা নিবন্ধন করুন।'));
            redirect('/login');
        }
        return $user;
    }

    public static function requireAdmin(string|array $roles = ['admin', 'editor', 'moderator']): array
    {
        $user = self::requireLogin();
        if (!self::hasRole($roles)) {
            http_response_code(403);
            exit('অ্যাক্সেস অনুমোদিত নয় (403 Forbidden)।');
        }
        return $user;
    }
}
