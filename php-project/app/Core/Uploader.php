<?php
declare(strict_types=1);

class Uploader
{
    private const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    public static function uploadImage(array $file, string $subDir = 'general'): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('ফাইল আপলোড করতে সমস্যা হয়েছে।');
        }
        if (($file['size'] ?? 0) > UPLOAD_MAX_BYTES) {
            throw new RuntimeException('ছবির আকার সর্বোচ্চ ৫ মেগাবাইটের মধ্যে হতে হবে।');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!isset(self::ALLOWED_MIMES[$mime])) {
            throw new RuntimeException('শুধুমাত্র JPG, PNG অথবা WEBP ছবি আপলোড করা যাবে।');
        }

        $ext = self::ALLOWED_MIMES[$mime];
        $safeDir = preg_replace('/[^a-z0-9_-]/i', '', $subDir) ?: 'general';
        $targetDir = UPLOAD_PATH . '/' . $safeDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destPath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new RuntimeException('সার্ভারে ফাইল সংরক্ষণ করা যায়নি।');
        }

        return 'uploads/' . $safeDir . '/' . $filename;
    }

    public static function uploadMultiple(array $files, string $subDir = 'general', int $maxFiles = 4): array
    {
        $paths = [];
        if (!isset($files['name']) || !is_array($files['name'])) {
            return $paths;
        }
        $count = min(count($files['name']), $maxFiles);
        for ($i = 0; $i < $count; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $single = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
            $uploaded = self::uploadImage($single, $subDir);
            if ($uploaded) {
                $paths[] = $uploaded;
            }
        }
        return $paths;
    }
}
