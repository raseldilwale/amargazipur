<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function tr(string $bn, string $en): string
{
    return I18n::locale() === 'en' ? $en : $bn;
}

function url(string $path = ''): string
{
    $base = rtrim(SITE_URL, '/');
    $path = '/' . ltrim($path, '/');
    return $path === '/' ? $base . '/' : $base . $path;
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    $target = str_starts_with($path, 'http') ? $path : url($path);
    header('Location: ' . $target);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $val = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $val;
}

function slugify(string $text): string
{
    $text = trim(mb_strtolower($text, 'UTF-8'));
    $text = preg_replace('/[^\p{L}\p{N}\s-]+/u', '', $text) ?? '';
    $text = preg_replace('/[\s-]+/', '-', $text) ?? '';
    $slug = trim($text, '-');
    return ($slug !== '' ? $slug : 'item') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
}

function gazipur_upazilas(): array
{
    return [
        'Gazipur Sadar' => ['bn' => 'গাজীপুর সদর', 'en' => 'Gazipur Sadar'],
        'Tongi'         => ['bn' => 'টঙ্গী', 'en' => 'Tongi'],
        'Kaliakoir'     => ['bn' => 'কালিয়াকৈর', 'en' => 'Kaliakoir'],
        'Kaliganj'      => ['bn' => 'কালীগঞ্জ', 'en' => 'Kaliganj'],
        'Kapasia'       => ['bn' => 'কাপাসিয়া', 'en' => 'Kapasia'],
        'Sreepur'       => ['bn' => 'শ্রীপুর', 'en' => 'Sreepur'],
    ];
}

function account_categories(): array
{
    return [
        'general_user'            => ['bn' => 'সাধারণ নাগরিক', 'en' => 'General User'],
        'student'                 => ['bn' => 'শিক্ষার্থী', 'en' => 'Student'],
        'journalist'              => ['bn' => 'সাংবাদিক', 'en' => 'Journalist'],
        'landlord'                => ['bn' => 'বাড়িওয়ালা / প্রপার্টি মালিক', 'en' => 'Landlord / Property Owner'],
        'business_owner'          => ['bn' => 'ব্যবসা প্রতিষ্ঠান মালিক', 'en' => 'Business Owner'],
        'educational_institution' => ['bn' => 'শিক্ষা প্রতিষ্ঠান', 'en' => 'Educational Institution'],
        'blood_donor'             => ['bn' => 'রক্তদাতা', 'en' => 'Blood Donor'],
        'organization'            => ['bn' => 'সামাজিক সংগঠন', 'en' => 'Organization'],
        'job_poster'              => ['bn' => 'চাকরিদাতা / নিয়োগদাতা', 'en' => 'Job Poster / Employer'],
    ];
}

function format_bdt(float|int|string $amount): string
{
    return '৳ ' . number_format((float)$amount, 0, '.', ',');
}

function get_setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
            foreach ($rows as $row) {
                $cache[$row['setting_key']] = (string)$row['setting_value'];
            }
        } catch (Throwable) {
            return $default;
        }
    }
    return $cache[$key] ?? $default;
}

function is_json_request(): bool
{
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}
