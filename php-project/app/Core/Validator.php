<?php
declare(strict_types=1);

class Validator
{
    public static function cleanString(?string $value, int $maxLength = 5000): string
    {
        $val = trim((string)($value ?? ''));
        if (mb_strlen($val, 'UTF-8') > $maxLength) {
            $val = mb_substr($val, 0, $maxLength, 'UTF-8');
        }
        return $val;
    }

    public static function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isPhone(string $phone): bool
    {
        return (bool)preg_match('/^[+0-9\s\-]{7,20}$/', $phone);
    }
}
