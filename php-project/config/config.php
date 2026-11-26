<?php
declare(strict_types=1);

/**
 * AmarGazipur - Core Application Configuration
 */

if (!defined('SITE_NAME')) define('SITE_NAME', 'AmarGazipur');
if (!defined('SITE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    define('SITE_URL', $scheme . '://' . $host . $scriptDir);
}

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
if (!defined('APP_PATH')) define('APP_PATH', ROOT_PATH . '/app');
if (!defined('UPLOAD_PATH')) define('UPLOAD_PATH', ROOT_PATH . '/uploads');
if (!defined('STORAGE_PATH')) define('STORAGE_PATH', ROOT_PATH . '/storage');

if (!defined('UPLOAD_MAX_BYTES')) define('UPLOAD_MAX_BYTES', 5 * 1024 * 1024);
if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', 7200);
if (!defined('APP_DEBUG')) define('APP_DEBUG', false);
