<?php
declare(strict_types=1);

class CSRF
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . e(self::token()) . '">';
    }

    public static function verify(?string $submitted = null): bool
    {
        $token = $submitted ?? ($_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $expected = $_SESSION['_csrf_token'] ?? '';
        return is_string($token) && $expected !== '' && hash_equals($expected, $token);
    }

    public static function requireValid(): void
    {
        if (!self::verify()) {
            http_response_code(419);
            if (is_json_request()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Invalid CSRF token']);
                exit;
            }
            flash('error', __('csrf_error', 'নিরাপত্তা টোকেন যাচাই ব্যর্থ হয়েছে। অনুগ্রহ করে পুনরায় চেষ্টা করুন।'));
            redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }
}
