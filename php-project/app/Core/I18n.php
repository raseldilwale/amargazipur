<?php
declare(strict_types=1);

class I18n
{
    private static array $translations = [];
    private static string $locale = 'bn';

    public static function init(): void
    {
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['bn', 'en'], true)) {
            $_SESSION['locale'] = $_GET['lang'];
        }
        self::$locale = $_SESSION['locale'] ?? 'bn';
        $file = APP_PATH . '/Lang/' . self::$locale . '.php';
        if (file_exists($file)) {
            self::$translations = require $file;
        }
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function get(string $key, ?string $fallback = null): string
    {
        return self::$translations[$key] ?? $fallback ?? $key;
    }
}

function __(string $key, ?string $fallback = null): string
{
    return I18n::get($key, $fallback);
}
