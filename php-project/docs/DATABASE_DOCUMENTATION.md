# AmarGazipur Database Documentation (MySQL / MariaDB)

Engine: `InnoDB` · Charset: `utf8mb4_unicode_ci`

## Tables Overview (28 Tables)
1. `roles` & `permissions` & `role_permissions`: Role-based access control (`admin`, `editor`, `moderator`, `member`).
2. `users`: Core user profiles, `password_hash`, `status`, `upazila`, `email_verified_at`.
3. `user_categories`: Multi-category assignments per user with `pending`/`approved`/`rejected` status and `reviewed_by`.
4. `posts`, `post_images`, `comments`, `comment_replies`, `likes`: Community forum posts, attached images, nested replies, and unique per-user likes.
5. `news_categories`, `news`: Gazipur local news with `slug`, SEO metadata, `is_featured`, and mandatory `pending` approval workflow for journalists.
6. `marketplace_categories`, `marketplace_listings`: Local classifieds with price, condition, location, JSON image list.
7. `rental_listings`: House/Flat To-Let listings with rent, advance, rooms, facilities, and `hide_contact_public` privacy flag.
8. `education_institutions`: Schools, colleges, universities, madrasas, coaching, technical institutes.
9. `blood_donors`, `blood_requests`: Blood donation directory with explicit `consent_public_contact` privacy gate and urgent blood requests.
10. `businesses`, `advertisements`: Business directory and Admin-controlled ad campaigns with placement and active dates.
11. `notifications`, `reports`, `moderation_rules`, `moderation_logs`, `admin_logs`, `settings`, `password_resets`, `email_verifications`, `login_attempts`: Notifications, moderation engine, audit trail, site settings, recovery tokens, and brute-force protection.
