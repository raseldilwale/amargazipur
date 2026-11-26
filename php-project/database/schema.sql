-- AmarGazipur Complete MySQL / MariaDB Database Schema
-- Compatible with MySQL 8.0+ and MariaDB 10.4+ (cPanel Shared Hosting)
-- Charset: utf8mb4_unicode_ci

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(80) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `module` VARCHAR(60) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_permissions_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` INT UNSIGNED NOT NULL DEFAULT 4,
  `full_name` VARCHAR(150) NOT NULL,
  `username` VARCHAR(80) DEFAULT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `upazila` VARCHAR(80) DEFAULT 'Gazipur Sadar',
  `bio` TEXT DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending','active','suspended','rejected') NOT NULL DEFAULT 'active',
  `email_verified_at` DATETIME DEFAULT NULL,
  `terms_accepted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_username` (`username`),
  UNIQUE KEY `uniq_users_email` (`email`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_role` (`role_id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `category_slug` ENUM('general_user','student','journalist','landlord','business_owner','educational_institution','blood_donor','organization','job_poster') NOT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` BIGINT UNSIGNED DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `review_note` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_cat` (`user_id`, `category_slug`),
  KEY `idx_uc_status` (`status`),
  CONSTRAINT `fk_uc_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uc_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `posts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `category` VARCHAR(80) NOT NULL DEFAULT 'সাধারণ আলোচনা',
  `upazila` VARCHAR(80) NOT NULL DEFAULT 'Gazipur Sadar',
  `title` VARCHAR(220) NOT NULL,
  `content` TEXT NOT NULL,
  `status` ENUM('published','pending_moderation','hidden','rejected') NOT NULL DEFAULT 'published',
  `moderation_reason` VARCHAR(255) DEFAULT NULL,
  `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_posts_status_created` (`status`, `created_at`),
  KEY `idx_posts_category` (`category`),
  KEY `idx_posts_user` (`user_id`),
  CONSTRAINT `fk_posts_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `post_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` BIGINT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pi_post` (`post_id`),
  CONSTRAINT `fk_pi_post` FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `comments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `body` TEXT NOT NULL,
  `status` ENUM('published','pending_moderation','hidden') NOT NULL DEFAULT 'published',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comments_post` (`post_id`),
  KEY `idx_comments_user` (`user_id`),
  CONSTRAINT `fk_comments_post` FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `comment_replies` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `comment_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `body` TEXT NOT NULL,
  `status` ENUM('published','hidden') NOT NULL DEFAULT 'published',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cr_comment` (`comment_id`),
  CONSTRAINT `fk_cr_comment` FOREIGN KEY (`comment_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cr_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `likes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_like_post_user` (`post_id`, `user_id`),
  CONSTRAINT `fk_likes_post` FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_likes_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `news_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(80) NOT NULL,
  `name_bn` VARCHAR(100) NOT NULL,
  `name_en` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_nc_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `news` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `author_id` BIGINT UNSIGNED NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `summary` VARCHAR(500) NOT NULL,
  `content` LONGTEXT NOT NULL,
  `featured_image` VARCHAR(255) DEFAULT NULL,
  `meta_title` VARCHAR(255) DEFAULT NULL,
  `meta_description` VARCHAR(320) DEFAULT NULL,
  `upazila` VARCHAR(80) DEFAULT 'Gazipur Sadar',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('pending','published','rejected','archived') NOT NULL DEFAULT 'pending',
  `approved_by` BIGINT UNSIGNED DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `rejection_reason` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_news_slug` (`slug`),
  KEY `idx_news_status_created` (`status`, `created_at`),
  KEY `idx_news_cat` (`category_id`),
  CONSTRAINT `fk_news_cat` FOREIGN KEY (`category_id`) REFERENCES `news_categories`(`id`),
  CONSTRAINT `fk_news_author` FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_news_approver` FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `marketplace_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(80) NOT NULL,
  `name_bn` VARCHAR(100) NOT NULL,
  `name_en` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mc_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `marketplace_listings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(220) NOT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `item_condition` ENUM('new','like_new','used','refurbished') NOT NULL DEFAULT 'used',
  `location` VARCHAR(150) NOT NULL,
  `upazila` VARCHAR(80) NOT NULL DEFAULT 'Gazipur Sadar',
  `contact_phone` VARCHAR(40) NOT NULL,
  `images_json` JSON DEFAULT NULL,
  `status` ENUM('published','pending_moderation','sold','hidden','rejected') NOT NULL DEFAULT 'published',
  `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ml_status` (`status`),
  KEY `idx_ml_cat` (`category_id`),
  CONSTRAINT `fk_ml_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ml_cat` FOREIGN KEY (`category_id`) REFERENCES `marketplace_categories`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rental_listings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `category` ENUM('family_apartment','bachelor_room','sublet','hostel','mess','shop','office','commercial') NOT NULL DEFAULT 'family_apartment',
  `title` VARCHAR(220) NOT NULL,
  `description` TEXT NOT NULL,
  `monthly_rent` DECIMAL(10,2) NOT NULL,
  `advance_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `location` VARCHAR(160) NOT NULL,
  `upazila` VARCHAR(80) NOT NULL DEFAULT 'Gazipur Sadar',
  `bedrooms` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `bathrooms` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `floor_number` VARCHAR(40) DEFAULT NULL,
  `facilities` VARCHAR(255) DEFAULT NULL,
  `available_from` DATE DEFAULT NULL,
  `contact_phone` VARCHAR(40) NOT NULL,
  `hide_contact_public` TINYINT(1) NOT NULL DEFAULT 1,
  `images_json` JSON DEFAULT NULL,
  `status` ENUM('published','pending_moderation','rented','hidden','rejected') NOT NULL DEFAULT 'published',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rl_status_cat` (`status`, `category`),
  KEY `idx_rl_upazila` (`upazila`),
  CONSTRAINT `fk_rl_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `education_institutions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `type` ENUM('school','college','university','madrasa','coaching','technical','training') NOT NULL DEFAULT 'school',
  `name` VARCHAR(200) NOT NULL,
  `upazila` VARCHAR(80) NOT NULL DEFAULT 'Gazipur Sadar',
  `address` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `courses` TEXT DEFAULT NULL,
  `description` TEXT NOT NULL,
  `logo_path` VARCHAR(255) DEFAULT NULL,
  `cover_image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending','published','rejected') NOT NULL DEFAULT 'published',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_edu_type_upazila` (`type`, `upazila`),
  CONSTRAINT `fk_edu_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blood_donors` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `donor_name` VARCHAR(140) NOT NULL,
  `blood_group` ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `area` VARCHAR(120) NOT NULL,
  `upazila` VARCHAR(80) NOT NULL,
  `phone` VARCHAR(40) NOT NULL,
  `contact_preference` ENUM('call','sms','message') NOT NULL DEFAULT 'call',
  `consent_public_contact` TINYINT(1) NOT NULL DEFAULT 0,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `last_donation_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bd_group_upazila` (`blood_group`, `upazila`),
  CONSTRAINT `fk_bd_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blood_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `patient_name` VARCHAR(140) NOT NULL,
  `blood_group` ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `units_needed` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `hospital_name` VARCHAR(180) NOT NULL,
  `upazila` VARCHAR(80) NOT NULL,
  `needed_date` DATE NOT NULL,
  `contact_phone` VARCHAR(40) NOT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('open','fulfilled','closed') NOT NULL DEFAULT 'open',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_br_status_group` (`status`, `blood_group`),
  CONSTRAINT `fk_br_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `businesses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `category` ENUM('restaurants','shopping','hospitals','clinics','education','technology','transport','services','other') NOT NULL DEFAULT 'other',
  `name` VARCHAR(200) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `upazila` VARCHAR(80) NOT NULL DEFAULT 'Gazipur Sadar',
  `phone` VARCHAR(40) NOT NULL,
  `hours` VARCHAR(120) DEFAULT NULL,
  `description` TEXT NOT NULL,
  `map_coords` VARCHAR(120) DEFAULT NULL,
  `logo_path` VARCHAR(255) DEFAULT NULL,
  `cover_image` VARCHAR(255) DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('pending','published','rejected') NOT NULL DEFAULT 'published',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bus_cat_featured` (`category`, `is_featured`),
  CONSTRAINT `fk_bus_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `advertisements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `advertiser_id` BIGINT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(180) NOT NULL,
  `placement` ENUM('home_banner','sidebar','news_inline','marketplace_top') NOT NULL DEFAULT 'home_banner',
  `banner_image` VARCHAR(255) NOT NULL,
  `target_url` VARCHAR(255) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` ENUM('pending','active','paused','expired','rejected') NOT NULL DEFAULT 'pending',
  `impressions` INT UNSIGNED NOT NULL DEFAULT 0,
  `clicks` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ads_placement_status` (`placement`, `status`),
  CONSTRAINT `fk_ads_user` FOREIGN KEY (`advertiser_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(180) NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user_read` (`user_id`, `is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporter_id` BIGINT UNSIGNED NOT NULL,
  `target_type` ENUM('post','comment','marketplace','rental','job','news','user') NOT NULL,
  `target_id` BIGINT UNSIGNED NOT NULL,
  `reason` VARCHAR(120) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `status` ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
  `resolved_by` BIGINT UNSIGNED DEFAULT NULL,
  `resolved_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reports_status` (`status`),
  CONSTRAINT `fk_reports_user` FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `moderation_rules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `keyword` VARCHAR(120) NOT NULL,
  `match_type` ENUM('word','phrase','regex') NOT NULL DEFAULT 'word',
  `severity` ENUM('flag','block') NOT NULL DEFAULT 'flag',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mr_keyword` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `moderation_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `target_type` VARCHAR(40) NOT NULL,
  `target_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `matched_rule` VARCHAR(120) DEFAULT NULL,
  `action_taken` VARCHAR(80) NOT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_modlog_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` BIGINT UNSIGNED NOT NULL,
  `action` VARCHAR(120) NOT NULL,
  `target_type` VARCHAR(60) DEFAULT NULL,
  `target_id` BIGINT UNSIGNED DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `details` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_adminlog_admin` (`admin_id`),
  CONSTRAINT `fk_adminlog_user` FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(190) NOT NULL,
  `token` VARCHAR(128) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pr_email_token` (`email`, `token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `token` VARCHAR(128) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `verified_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ev_token` (`token`),
  CONSTRAINT `fk_ev_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `email` VARCHAR(190) DEFAULT NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_la_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Roles & Permissions
INSERT IGNORE INTO `roles` (`id`, `slug`, `name`, `description`) VALUES
(1, 'admin', 'Super Admin', 'Full administrative access across AmarGazipur'),
(2, 'editor', 'News Editor', 'Can approve or reject journalist news submissions'),
(3, 'moderator', 'Community Moderator', 'Can review reports and flagged content'),
(4, 'member', 'Community Member', 'Standard registered Gazipur resident account');

INSERT IGNORE INTO `permissions` (`id`, `slug`, `name`, `module`) VALUES
(1, 'manage_users', 'Approve, suspend, and edit users', 'users'),
(2, 'manage_categories', 'Approve or reject user account categories', 'users'),
(3, 'approve_news', 'Publish or reject journalist news articles', 'news'),
(4, 'moderate_content', 'Review flagged posts, listings, and reports', 'moderation'),
(5, 'manage_ads', 'Control advertisements and placements', 'ads'),
(6, 'manage_settings', 'Manage site configuration, SEO, and navigation', 'settings'),
(7, 'view_logs', 'Inspect admin activity and login history', 'security');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),
(2,3),(2,4),
(3,4),(3,7);

-- Seed Categories
INSERT IGNORE INTO `news_categories` (`id`, `slug`, `name_bn`, `name_en`) VALUES
(1, 'gazipur-city', 'গাজীপুর সিটি', 'Gazipur City'),
(2, 'development', 'উন্নয়ন ও অবকাঠামো', 'Development'),
(3, 'education', 'শিক্ষা ও ক্যাম্পাস', 'Education'),
(4, 'industry', 'শিল্প ও কর্মসংস্থান', 'Industry & Jobs'),
(5, 'environment', 'ভাওয়াল ও পরিবেশ', 'Environment');

INSERT IGNORE INTO `marketplace_categories` (`id`, `slug`, `name_bn`, `name_en`) VALUES
(1, 'electronics', 'ইলেকট্রনিক্স', 'Electronics'),
(2, 'mobile', 'মোবাইল ফোন', 'Mobile Phones'),
(3, 'computers', 'কম্পিউটার', 'Computers'),
(4, 'motorcycles', 'মোটরসাইকেল', 'Motorcycles'),
(5, 'cars', 'গাড়ি', 'Cars'),
(6, 'furniture', 'আসবাবপত্র', 'Furniture'),
(7, 'appliances', 'গৃহস্থালি যন্ত্রপাতি', 'Home Appliances'),
(8, 'clothing', 'পোশাক', 'Clothing'),
(9, 'property', 'জমি ও প্রপার্টি', 'Property'),
(10, 'other', 'অন্যান্য পণ্য', 'Other Products');

-- Seed Default Moderation Keywords
INSERT IGNORE INTO `moderation_rules` (`keyword`, `match_type`, `severity`, `is_active`) VALUES
('জুয়া', 'word', 'flag', 1),
('ক্যাসিনো', 'word', 'flag', 1),
('বেটিং', 'word', 'flag', 1),
('প্রতারণা', 'word', 'flag', 1),
('scam', 'word', 'flag', 1),
('casino', 'word', 'flag', 1),
('betting', 'word', 'flag', 1),
('spamlink', 'word', 'flag', 1);

-- Seed Default Site Settings
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'AmarGazipur'),
('site_tagline_bn', 'গাজীপুর জেলার পূর্ণাঙ্গ ডিজিটাল কমিউনিটি প্ল্যাটফর্ম'),
('site_tagline_en', 'Complete Digital Community Platform for Gazipur District'),
('contact_email', 'support@amargazipur.local'),
('contact_phone', '+880 1700-000000'),
('seo_meta_title', 'AmarGazipur — গাজীপুর সংবাদ, কমিউনিটি, বাসা ভাড়া ও মার্কেটপ্লেস'),
('seo_meta_description', 'গাজীপুর জেলার সকল নাগরিক সেবা, স্থানীয় সংবাদ, টু-লেট, মার্কেটপ্লেস, শিক্ষা ও রক্তদাতা ডিরেক্টরি এক প্ল্যাটফর্মে।'),
('footer_text', '© ২০২৬ AmarGazipur — গাজীপুর জেলা কমিউনিটি প্ল্যাটফর্ম। সর্বস্বত্ব সংরক্ষিত।');

SET FOREIGN_KEY_CHECKS = 1;
