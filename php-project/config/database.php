<?php
declare(strict_types=1);

// =========================================================================
// AmarGazipur — cPanel MySQL ডেটাবেস কনফিগারেশন
// শুধু নিচের ৪টি লাইনে আপনার cPanel থেকে তৈরি করা ডেটাবেসের তথ্য বসান:
// =========================================================================

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'YOUR_CPANEL_DB_NAME');
if (!defined('DB_USER')) define('DB_USER', 'YOUR_CPANEL_DB_USER');
if (!defined('DB_PASS')) define('DB_PASS', 'YOUR_CPANEL_DB_PASSWORD');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// =========================================================================
// নিচের অংশে কোনো পরিবর্তনের প্রয়োজন নেই
// =========================================================================

class Database
{
    private static ?PDO $instance = null;

    public static function connect(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        if (DB_NAME === 'YOUR_CPANEL_DB_NAME' || DB_USER === 'YOUR_CPANEL_DB_USER') {
            self::renderSetupNotice('আপনি এখনো <code>config/database.php</code> ফাইলে আপনার cPanel ডেটাবেসের নাম ও পাসওয়ার্ড বসাননি।');
        }

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            return self::$instance;
        } catch (PDOException $e) {
            error_log('AmarGazipur DB Error: ' . $e->getMessage());
            self::renderSetupNotice('MySQL ডেটাবেসে সংযোগ করা যাচ্ছে না। অনুগ্রহ করে <code>config/database.php</code> ফাইলে <strong>DB_NAME</strong>, <strong>DB_USER</strong> ও <strong>DB_PASS</strong> সঠিক আছে কিনা যাচাই করুন।');
        }
    }

    public static function ensureSchemaInitialized(string $rootPath): void
    {
        $pdo = self::connect();
        $hasUsers = (bool)$pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
        if ($hasUsers) {
            $hasUsernameCol = (bool)$pdo->query("SHOW COLUMNS FROM `users` LIKE 'username'")->fetchColumn();
            if (!$hasUsernameCol) {
                $pdo->exec('ALTER TABLE `users` ADD COLUMN `username` VARCHAR(80) DEFAULT NULL AFTER `full_name`, ADD UNIQUE KEY `uniq_users_username` (`username`)');
            }
            $catCol = $pdo->query("SHOW COLUMNS FROM `user_categories` LIKE 'category_slug'")->fetch();
            if ($catCol && !str_contains((string)($catCol['Type'] ?? ''), 'job_poster')) {
                $pdo->exec("ALTER TABLE `user_categories` MODIFY COLUMN `category_slug` ENUM('general_user','student','journalist','landlord','business_owner','educational_institution','blood_donor','organization','job_poster') NOT NULL");
            }
            $hasJobsTable = (bool)$pdo->query("SHOW TABLES LIKE 'job_listings'")->fetchColumn();
            if (!$hasJobsTable) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `job_listings` (
                      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                      `user_id` BIGINT UNSIGNED NOT NULL,
                      `company_name` VARCHAR(180) NOT NULL,
                      `job_title` VARCHAR(180) NOT NULL,
                      `job_type` ENUM('full_time','part_time','contract','internship','remote') NOT NULL DEFAULT 'full_time',
                      `salary` VARCHAR(120) NOT NULL,
                      `location` VARCHAR(180) NOT NULL,
                      `upazila` VARCHAR(80) NOT NULL DEFAULT 'Gazipur Sadar',
                      `contact_info` VARCHAR(160) NOT NULL,
                      `deadline` VARCHAR(40) DEFAULT NULL,
                      `description` TEXT NOT NULL,
                      `image_path` VARCHAR(255) DEFAULT NULL,
                      `status` ENUM('published','pending_moderation','closed','rejected') NOT NULL DEFAULT 'published',
                      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      KEY `idx_jl_status_upazila` (`status`, `upazila`),
                      KEY `idx_jl_user` (`user_id`),
                      CONSTRAINT `fk_jl_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
            $jobsDir = $rootPath . '/uploads/jobs';
            if (!is_dir($jobsDir)) {
                @mkdir($jobsDir, 0755, true);
            }
            return;
        }

        $schemaRaw = file_get_contents($rootPath . '/database/schema.sql');
        $schemaSql = preg_replace('/^\s*--.*$/m', '', $schemaRaw) ?? $schemaRaw;
        $statements = array_filter(array_map('trim', preg_split('/;\s*[\r\n]+/', $schemaSql)));
        foreach ($statements as $stmtSql) {
            if ($stmtSql !== '') {
                $pdo->exec($stmtSql);
            }
        }

        // Ensure upload & log directories exist
        foreach (['uploads/posts', 'uploads/news', 'uploads/marketplace', 'uploads/rentals', 'uploads/jobs', 'uploads/education', 'uploads/businesses', 'uploads/ads', 'storage/logs'] as $dir) {
            $full = $rootPath . '/' . $dir;
            if (!is_dir($full)) {
                @mkdir($full, 0755, true);
            }
        }

        // Seed default Admin account (username: admin, password: admin) & sample resident author
        $adminHash = '$2y$12$87E.VcYUMzy018S/1I60RuX0wa4fG4hmCaph6mc/tM6O/JqVnxXJq';
        $pdo->prepare('INSERT IGNORE INTO users (id, role_id, full_name, username, email, password_hash, upazila, bio, status, email_verified_at) VALUES (1, 1, ?, ?, ?, ?, "Gazipur Sadar", ?, "active", NOW())')
            ->execute(['AmarGazipur Admin', 'admin', 'admin@amargazipur.com', $adminHash, 'আমার গাজীপুর প্রধান অ্যাডমিনিস্ট্রেটর']);
        $pdo->exec("INSERT IGNORE INTO user_categories (user_id, category_slug, status) VALUES (1, 'general_user', 'approved'), (1, 'journalist', 'approved'), (1, 'landlord', 'approved'), (1, 'business_owner', 'approved'), (1, 'educational_institution', 'approved'), (1, 'blood_donor', 'approved'), (1, 'organization', 'approved'), (1, 'job_poster', 'approved')");

        $seedPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $pdo->prepare('INSERT IGNORE INTO users (id, role_id, full_name, username, email, password_hash, upazila, bio, status, email_verified_at) VALUES (2, 4, ?, ?, ?, ?, "Gazipur Sadar", ?, "active", NOW())')
            ->execute(['তানভীর আহমেদ', 'tanvir', 'tanvir.gazipur@example.local', $seedPass, 'গাজীপুর জেলা প্রতিনিধি ও নাগরিক সাংবাদিক']);
        $pdo->exec("INSERT IGNORE INTO user_categories (user_id, category_slug, status) VALUES (2, 'journalist', 'approved'), (2, 'landlord', 'approved'), (2, 'blood_donor', 'approved'), (2, 'job_poster', 'approved')");

        $pdo->exec("INSERT IGNORE INTO news (category_id, author_id, slug, title, summary, content, featured_image, upazila, is_featured, views_count, status, approved_by, approved_at) VALUES
            (2, 2, 'chandna-chowrasta-flyover-traffic-2026', 'চান্দনা চৌরাস্তা ফ্লাইওভারে স্বস্তি ফিরছে গাজীপুরের যাতায়াতে', 'ঢাকা-ময়মনসিংহ মহাসড়কের গাজীপুর অংশে উড়ালসড়ক ও উন্নত ট্রাফিক ব্যবস্থাপনায় কমেছে দীর্ঘ যানজট।', 'গাজীপুর জেলার ব্যস্ততম কেন্দ্র চান্দনা চৌরাস্তা ও টঙ্গী বাজার এলাকায় নিয়মিত যানজট নিরসনে নতুন ট্রাফিক লেন ও ফ্লাইওভার সংযোগ সড়ক কার্যকর ভূমিকা রাখছে।', 'assets/images/news_gazipur_infrastructure.jpg', 'Gazipur Sadar', 1, 1240, 'published', 1, NOW()),
            (3, 2, 'duet-bsmrau-research-symposium-gazipur', 'ডুয়েট ও গাজীপুর কৃষি বিশ্ববিদ্যালয়ে যৌথ গবেষণা সম্মেলন', 'গাজীপুরের দুটি শীর্ষ বিশ্ববিদ্যালয়ের গবেষকরা পরিবেশবান্ধব শিল্প ও স্মার্ট কৃষি উদ্ভাবন উপস্থাপন করেছেন।', 'শিববাড়ী ও সালনা ক্যাম্পাসের শিক্ষক-শিক্ষার্থীদের অংশগ্রহণে দিনব্যাপী গবেষণা প্রদর্শনী অনুষ্ঠিত হয়েছে।', 'assets/images/campus_gazipur_education.jpg', 'Gazipur Sadar', 1, 860, 'published', 1, NOW())");

        $pdo->exec("INSERT IGNORE INTO posts (user_id, category, upazila, title, content, status) VALUES
            (2, 'সাধারণ আলোচনা', 'Gazipur Sadar', 'আমার গাজীপুর কমিউনিটিতে সবাইকে স্বাগতম', 'গাজীপুর জেলার নাগরিকদের তথ্য, সংবাদ, বাসা ভাড়া ও জরুরি রক্তদাতা সেবার জন্য আমাদের এই ডিজিটাল প্ল্যাটফর্ম।', 'published')");

        $pdo->exec("INSERT IGNORE INTO rental_listings (user_id, category, title, description, monthly_rent, advance_amount, location, upazila, bedrooms, bathrooms, floor_number, facilities, contact_phone, hide_contact_public, status) VALUES
            (2, 'family_apartment', '৩ বেডরুমের আধুনিক ফ্যামিলি ফ্ল্যাট ভাড়া হবে — চেরাগআলী', 'দক্ষিণমুখী খোলামেলা ৩ বেড, ৩ বাথ, ২৪ ঘণ্টা গ্যাস ও লিফট সুবিধা রয়েছে।', 18500, 35000, 'চেরাগআলী, টঙ্গী', 'Tongi', 3, 3, '৫ম তলা', 'লিফট, গ্যাস, সিসিটিভি', '01711-000101', 1, 'published')");

        $pdo->exec("INSERT IGNORE INTO job_listings (user_id, company_name, job_title, job_type, salary, location, upazila, contact_info, deadline, description, status) VALUES
            (2, 'গাজীপুর টেক সলিউশনস লিঃ', 'জুনিয়র সফটওয়্যার সাপোর্ট ইঞ্জিনিয়ার', 'full_time', '২৫,০০০ - ৩৫,০০০ টাকা', 'চান্দনা চৌরাস্তা, গাজীপুর সদর', 'Gazipur Sadar', '01711-000101 / hr@gazipurtech.local', '2026-12-31', 'স্নাতক/ডিপ্লোমা ইন কম্পিউটার সায়েন্স ও নেটওয়ার্কিং জ্ঞানসম্পন্ন প্রার্থীদের অগ্রাধিকার দেওয়া হবে।', 'published'),
            (2, 'ভাওয়াল অ্যাপারেলস ইন্ডাস্ট্রিজ', 'কোয়ালিটি কন্ট্রোল (QC) অফিসার', 'full_time', '১৮,০০০ - ২২,০০০ টাকা', 'মাওনা চৌরাস্তা, শ্রীপুর', 'Sreepur', '01812-000202', '2026-12-25', 'গার্মেন্টস কোয়ালিটি সেকশনে কমপক্ষে ১ বছরের অভিজ্ঞতাসম্পন্ন প্রার্থী প্রয়োজন।', 'published')");

        $pdo->exec("INSERT IGNORE INTO education_institutions (user_id, type, name, upazila, address, phone, website, courses, description, status) VALUES
            (2, 'university', 'ঢাকা প্রকৌশল ও প্রযুক্তি বিশ্ববিদ্যালয় (ডুয়েট)', 'Gazipur Sadar', 'শিববাড়ী, গাজীপুর সদর-১৭০৭', '02-49274003', 'https://www.duet.ac.bd', 'CSE, EEE, Civil, Mechanical, Architecture', 'বাংলাদেশের অন্যতম শীর্ষস্থানীয় প্রকৌশল বিশ্ববিদ্যালয় গাজীপুরে অবস্থিত।', 'published')");

        $pdo->exec("INSERT IGNORE INTO blood_donors (user_id, donor_name, blood_group, area, upazila, phone, contact_preference, consent_public_contact, is_available) VALUES
            (2, 'তানভীর আহমেদ', 'O+', 'শিববাড়ী মোড়', 'Gazipur Sadar', '01711-000101', 'call', 1, 1)");
    }

    private static function renderSetupNotice(string $messageHtml): never
    {
        http_response_code(503);
        echo '<!doctype html><html lang="bn"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>AmarGazipur — ডেটাবেস কনফিগারেশন</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light py-5"><div class="container" style="max-width:620px;"><div class="card border-0 shadow-sm p-4"><h1 class="h4 fw-bold mb-3">AmarGazipur ডেটাবেস সংযোগ প্রয়োজন</h1><p class="mb-3">' . $messageHtml . '</p><div class="bg-light border rounded p-3 small">cPanel File Manager &rarr; <strong>config/database.php</strong> ফাইলটি Edit করে আপনার MySQL <strong>DB_NAME</strong>, <strong>DB_USER</strong> ও <strong>DB_PASS</strong> বসিয়ে Save করুন এবং এই পেজটি Refresh করুন — ডেটাবেস ও ওয়েবসাইট স্বয়ংক্রিয়ভাবে চালু হয়ে যাবে।</div></div></div></body></html>';
        exit;
    }
}

function db(): PDO
{
    return Database::connect();
}
