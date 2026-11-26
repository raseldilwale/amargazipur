<?php
declare(strict_types=1);

class AdminController
{
    private function logAction(int $adminId, string $action, string $targetType, int $targetId, string $details = ''): void
    {
        db()->prepare('INSERT INTO admin_logs (admin_id, action, target_type, target_id, ip_address, details) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$adminId, $action, $targetType, $targetId, $_SERVER['REMOTE_ADDR'] ?? '', $details]);
    }

    public function index(): void
    {
        $admin = Auth::requireAdmin();
        $pdo = db();

        $stats = [
            'total_users'      => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'active_users'     => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
            'pending_cats'     => (int)$pdo->query("SELECT COUNT(*) FROM user_categories WHERE status = 'pending'")->fetchColumn(),
            'total_posts'      => (int)$pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
            'pending_posts'    => (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'pending_moderation'")->fetchColumn(),
            'total_news'       => (int)$pdo->query('SELECT COUNT(*) FROM news')->fetchColumn(),
            'pending_news'     => (int)$pdo->query("SELECT COUNT(*) FROM news WHERE status = 'pending'")->fetchColumn(),
            'total_market'     => (int)$pdo->query('SELECT COUNT(*) FROM marketplace_listings')->fetchColumn(),
            'total_rentals'    => (int)$pdo->query('SELECT COUNT(*) FROM rental_listings')->fetchColumn(),
            'total_jobs'       => (int)$pdo->query('SELECT COUNT(*) FROM job_listings')->fetchColumn(),
            'open_reports'     => (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'open'")->fetchColumn(),
            'active_ads'       => (int)$pdo->query("SELECT COUNT(*) FROM advertisements WHERE status = 'active'")->fetchColumn(),
            'total_blood'      => (int)$pdo->query('SELECT COUNT(*) FROM blood_donors')->fetchColumn() + (int)$pdo->query('SELECT COUNT(*) FROM blood_requests')->fetchColumn(),
            'total_education'  => (int)$pdo->query('SELECT COUNT(*) FROM education_institutions')->fetchColumn(),
            'total_businesses' => (int)$pdo->query('SELECT COUNT(*) FROM businesses')->fetchColumn(),
        ];

        $recentLogs = $pdo->query('
            SELECT al.*, u.full_name AS admin_name
            FROM admin_logs al JOIN users u ON u.id = al.admin_id
            ORDER BY al.created_at DESC LIMIT 12
        ')->fetchAll();

        View::render('admin/index', [
            'pageTitle' => tr('অ্যাডমিন ওভারভিউ — AmarGazipur', 'Admin Overview — AmarGazipur'),
            'admin' => $admin,
            'stats' => $stats,
            'recentLogs' => $recentLogs,
        ], 'admin');
    }

    public function createContent(): void
    {
        Auth::requireAdmin(['admin', 'editor']);
        $pdo = db();
        $newsCategories = $pdo->query('SELECT * FROM news_categories ORDER BY id ASC')->fetchAll();
        $marketCategories = $pdo->query('SELECT * FROM marketplace_categories ORDER BY id ASC')->fetchAll();
        $defaultType = Validator::cleanString($_GET['type'] ?? 'post', 40);

        View::render('admin/create', [
            'pageTitle' => tr('নতুন পোস্ট / প্রকাশ — Admin', 'Create & Publish Content — Admin'),
            'newsCategories' => $newsCategories,
            'marketCategories' => $marketCategories,
            'defaultType' => $defaultType,
        ], 'admin');
    }

    public function storeContent(): void
    {
        $admin = Auth::requireAdmin(['admin', 'editor']);
        CSRF::requireValid();
        $pdo = db();

        $type = Validator::cleanString($_POST['content_type'] ?? 'post', 40);
        $title = Validator::cleanString($_POST['title'] ?? '', 255);
        $content = Validator::cleanString($_POST['content'] ?? '', 10000);
        $upazila = Validator::cleanString($_POST['upazila'] ?? 'Gazipur Sadar', 80);
        $location = Validator::cleanString($_POST['location'] ?? '', 200);
        $phone = Validator::cleanString($_POST['contact_phone'] ?? '', 40);
        if ($phone === '') {
            $phone = (string)($admin['phone'] ?: '01700-000000');
        }

        if ($title === '') {
            flash('error', tr('শিরোনাম বা নাম অবশ্যই লিখতে হবে।', 'Title or name is required.'));
            redirect('/admin/create?type=' . urlencode($type));
        }

        $subDirMap = [
            'post'          => 'posts',
            'news'          => 'news',
            'marketplace'   => 'marketplace',
            'rental'        => 'rentals',
            'job'           => 'jobs',
            'education'     => 'education',
            'business'      => 'businesses',
            'advertisement' => 'ads',
        ];
        $uploadedImage = null;
        if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $uploadedImage = Uploader::uploadImage($_FILES['image'], $subDirMap[$type] ?? 'general');
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
                redirect('/admin/create?type=' . urlencode($type));
            }
        }

        $adminId = (int)$admin['id'];

        if ($type === 'news') {
            $newsCatId = max(1, (int)($_POST['news_category_id'] ?? 1));
            $summary = Validator::cleanString($_POST['summary'] ?? '', 500);
            if ($summary === '') {
                $summary = mb_substr($content !== '' ? $content : $title, 0, 180, 'UTF-8');
            }
            $featured = isset($_POST['is_featured']) ? 1 : 0;
            $pdo->prepare('
                INSERT INTO news (category_id, author_id, slug, title, summary, content, featured_image, upazila, is_featured, status, approved_by, approved_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "published", ?, NOW())
            ')->execute([$newsCatId, $adminId, slugify($title), $title, $summary, $content !== '' ? $content : $summary, $uploadedImage, $upazila, $featured, $adminId]);
            $this->logAction($adminId, 'admin_publish_news', 'news', (int)$pdo->lastInsertId(), $title);
            flash('success', tr('সংবাদটি সরাসরি প্রকাশিত হয়েছে।', 'News article published successfully.'));
            redirect('/admin/news');
        } elseif ($type === 'marketplace') {
            $marketCatId = max(1, (int)($_POST['market_category_id'] ?? 1));
            $price = max(0, (float)($_POST['price'] ?? 0));
            $condition = Validator::cleanString($_POST['item_condition'] ?? 'used', 30);
            $imagesJson = json_encode($uploadedImage ? [$uploadedImage] : []);
            $pdo->prepare('
                INSERT INTO marketplace_listings (user_id, category_id, title, description, price, item_condition, location, upazila, contact_phone, images_json, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "published")
            ')->execute([$adminId, $marketCatId, $title, $content !== '' ? $content : $title, $price, $condition, $location !== '' ? $location : $upazila, $upazila, $phone, $imagesJson]);
            $this->logAction($adminId, 'admin_publish_marketplace', 'marketplace', (int)$pdo->lastInsertId(), $title);
            flash('success', tr('মার্কেটপ্লেস বিজ্ঞাপন প্রকাশিত হয়েছে।', 'Marketplace listing published.'));
            redirect('/admin/content?tab=marketplace');
        } elseif ($type === 'rental') {
            $rentCat = Validator::cleanString($_POST['rental_category'] ?? 'family_apartment', 40);
            $monthlyRent = max(100, (float)($_POST['monthly_rent'] ?? 10000));
            $advance = max(0, (float)($_POST['advance_amount'] ?? 0));
            $bedrooms = max(1, (int)($_POST['bedrooms'] ?? 2));
            $bathrooms = max(1, (int)($_POST['bathrooms'] ?? 2));
            $facilities = Validator::cleanString($_POST['facilities'] ?? '', 255);
            $imagesJson = json_encode($uploadedImage ? [$uploadedImage] : []);
            $pdo->prepare('
                INSERT INTO rental_listings (user_id, category, title, description, monthly_rent, advance_amount, location, upazila, bedrooms, bathrooms, facilities, contact_phone, hide_contact_public, images_json, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, "published")
            ')->execute([$adminId, $rentCat, $title, $content !== '' ? $content : $title, $monthlyRent, $advance, $location !== '' ? $location : $upazila, $upazila, $bedrooms, $bathrooms, $facilities, $phone, $imagesJson]);
            $this->logAction($adminId, 'admin_publish_rental', 'rental', (int)$pdo->lastInsertId(), $title);
            flash('success', tr('বাসা ভাড়া (টু-লেট) প্রকাশিত হয়েছে।', 'Rental listing published.'));
            redirect('/admin/content?tab=rentals');
        } elseif ($type === 'job') {
            $companyName = Validator::cleanString($_POST['company_name'] ?? '', 180) ?: 'AmarGazipur Employer';
            $jobType = Validator::cleanString($_POST['job_type'] ?? 'full_time', 30);
            if (!in_array($jobType, ['full_time', 'part_time', 'contract', 'internship', 'remote'], true)) {
                $jobType = 'full_time';
            }
            $salary = Validator::cleanString($_POST['salary'] ?? '', 120) ?: tr('আলোচনা সাপেক্ষে', 'Negotiable');
            $contactInfo = Validator::cleanString($_POST['contact_info'] ?? '', 160) ?: $phone;
            $deadline = Validator::cleanString($_POST['deadline'] ?? '', 40) ?: null;
            $pdo->prepare('
                INSERT INTO job_listings (user_id, company_name, job_title, job_type, salary, location, upazila, contact_info, deadline, description, image_path, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "published")
            ')->execute([$adminId, $companyName, $title, $jobType, $salary, $location !== '' ? $location : $upazila, $upazila, $contactInfo, $deadline, $content !== '' ? $content : ($title . ' — ' . $companyName), $uploadedImage]);
            $this->logAction($adminId, 'admin_publish_job', 'job', (int)$pdo->lastInsertId(), $companyName . ': ' . $title);
            flash('success', tr('চাকরির বিজ্ঞপ্তি সফলভাবে প্রকাশিত হয়েছে।', 'Job listing published successfully.'));
            redirect('/admin/content?tab=jobs');
        } elseif ($type === 'blood_request') {
            $group = Validator::cleanString($_POST['blood_group'] ?? 'O+', 10);
            $units = max(1, (int)($_POST['units_needed'] ?? 1));
            $neededDate = Validator::cleanString($_POST['needed_date'] ?? date('Y-m-d'), 20);
            $hospital = $location !== '' ? $location : 'Gazipur Sadar Hospital';
            $pdo->prepare('
                INSERT INTO blood_requests (user_id, patient_name, blood_group, units_needed, hospital_name, upazila, needed_date, contact_phone, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "open")
            ')->execute([$adminId, $title, $group, $units, $hospital, $upazila, $neededDate, $phone, mb_substr($content, 0, 250, 'UTF-8')]);
            $this->logAction($adminId, 'admin_publish_blood_request', 'blood_request', (int)$pdo->lastInsertId(), $title);
            flash('success', tr('জরুরি রক্তের অনুরোধ প্রকাশিত হয়েছে।', 'Urgent blood request published.'));
            redirect('/admin/content?tab=blood');
        } elseif ($type === 'blood_donor') {
            $group = Validator::cleanString($_POST['blood_group'] ?? 'O+', 10);
            $area = $location !== '' ? $location : $upazila;
            $pdo->prepare('
                INSERT INTO blood_donors (user_id, donor_name, blood_group, area, upazila, phone, contact_preference, consent_public_contact, is_available)
                VALUES (?, ?, ?, ?, ?, ?, "call", 1, 1)
            ')->execute([$adminId, $title, $group, $area, $upazila, $phone]);
            $this->logAction($adminId, 'admin_add_blood_donor', 'blood_donor', (int)$pdo->lastInsertId(), $title);
            flash('success', tr('রক্তদাতা যুক্ত করা হয়েছে।', 'Blood donor added.'));
            redirect('/admin/content?tab=blood');
        } elseif ($type === 'education') {
            $eduType = Validator::cleanString($_POST['edu_type'] ?? 'school', 40);
            $courses = Validator::cleanString($_POST['courses'] ?? '', 1000);
            $pdo->prepare('
                INSERT INTO education_institutions (user_id, type, name, upazila, address, phone, courses, description, cover_image, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "published")
            ')->execute([$adminId, $eduType, $title, $upazila, $location !== '' ? $location : $upazila, $phone, $courses, $content !== '' ? $content : $title, $uploadedImage]);
            $this->logAction($adminId, 'admin_publish_education', 'education', (int)$pdo->lastInsertId(), $title);
            flash('success', tr('শিক্ষা প্রতিষ্ঠান যুক্ত হয়েছে।', 'Educational institution published.'));
            redirect('/admin/content?tab=education');
        } elseif ($type === 'business') {
            $busCat = Validator::cleanString($_POST['business_category'] ?? 'services', 40);
            $hours = Validator::cleanString($_POST['hours'] ?? '9:00 AM - 9:00 PM', 120);
            $pdo->prepare('
                INSERT INTO businesses (user_id, category, name, address, upazila, phone, hours, description, cover_image, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "published")
            ')->execute([$adminId, $busCat, $title, $location !== '' ? $location : $upazila, $upazila, $phone, $hours, $content !== '' ? $content : $title, $uploadedImage]);
            $this->logAction($adminId, 'admin_publish_business', 'business', (int)$pdo->lastInsertId(), $title);
            flash('success', tr('ব্যবসা প্রতিষ্ঠান প্রকাশিত হয়েছে।', 'Business listing published.'));
            redirect('/admin/content?tab=businesses');
        } elseif ($type === 'advertisement') {
            $placement = Validator::cleanString($_POST['placement'] ?? 'home_banner', 40);
            $targetUrl = Validator::cleanString($_POST['target_url'] ?? '#', 255) ?: '#';
            $startDate = Validator::cleanString($_POST['start_date'] ?? date('Y-m-d'), 20);
            $endDate = Validator::cleanString($_POST['end_date'] ?? date('Y-m-d', strtotime('+30 days')), 20);
            $banner = $uploadedImage ?: 'assets/images/hero_gazipur_community.jpg';
            $pdo->prepare('
                INSERT INTO advertisements (advertiser_id, title, placement, banner_image, target_url, start_date, end_date, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, "active")
            ')->execute([$adminId, $title, $placement, $banner, $targetUrl, $startDate, $endDate]);
            $this->logAction($adminId, 'admin_publish_ad', 'advertisement', (int)$pdo->lastInsertId(), $title);
            flash('success', tr('বিজ্ঞাপনটি সক্রিয়ভাবে প্রকাশিত হয়েছে।', 'Advertisement published.'));
            redirect('/admin/ads');
        } else {
            // Default: Community / Job Circular / Student Post
            $postCategory = Validator::cleanString($_POST['post_category'] ?? 'সাধারণ আলোচনা', 80);
            $pdo->prepare('
                INSERT INTO posts (user_id, category, upazila, title, content, status)
                VALUES (?, ?, ?, ?, ?, "published")
            ')->execute([$adminId, $postCategory, $upazila, $title, $content !== '' ? $content : $title]);
            $postId = (int)$pdo->lastInsertId();
            if ($uploadedImage) {
                $pdo->prepare('INSERT INTO post_images (post_id, image_path, sort_order) VALUES (?, ?, 0)')->execute([$postId, $uploadedImage]);
            }
            $this->logAction($adminId, 'admin_publish_post', 'post', $postId, $postCategory . ': ' . $title);
            flash('success', tr('পোস্টটি সফলভাবে প্রকাশিত হয়েছে।', 'Post published successfully.'));
            redirect('/admin/content?tab=posts');
        }
    }

    public function content(): void
    {
        Auth::requireAdmin(['admin', 'editor', 'moderator']);
        $pdo = db();
        $tab = Validator::cleanString($_GET['tab'] ?? 'posts', 40);
        $items = [];

        if ($tab === 'marketplace') {
            $rows = $pdo->query('SELECT m.*, c.name_bn AS cat_name, u.full_name AS author_name FROM marketplace_listings m JOIN marketplace_categories c ON c.id = m.category_id JOIN users u ON u.id = m.user_id ORDER BY m.created_at DESC LIMIT 80')->fetchAll();
            foreach ($rows as $r) {
                $items[] = [
                    'id' => $r['id'],
                    'table_key' => 'marketplace_listings',
                    'title' => $r['title'],
                    'subtitle' => $r['location'] . ' · ' . $r['author_name'],
                    'meta' => $r['cat_name'] . ' · ' . format_bdt($r['price']),
                    'status' => $r['status'],
                ];
            }
        } elseif ($tab === 'rentals') {
            $rows = $pdo->query('SELECT r.*, u.full_name AS author_name FROM rental_listings r JOIN users u ON u.id = r.user_id ORDER BY r.created_at DESC LIMIT 80')->fetchAll();
            foreach ($rows as $r) {
                $items[] = [
                    'id' => $r['id'],
                    'table_key' => 'rental_listings',
                    'title' => $r['title'],
                    'subtitle' => $r['location'] . ' · ' . $r['author_name'],
                    'meta' => $r['category'] . ' · ' . format_bdt($r['monthly_rent']),
                    'status' => $r['status'],
                ];
            }
        } elseif ($tab === 'jobs') {
            $rows = $pdo->query('SELECT j.*, u.full_name AS author_name FROM job_listings j JOIN users u ON u.id = j.user_id ORDER BY j.created_at DESC LIMIT 80')->fetchAll();
            foreach ($rows as $r) {
                $items[] = [
                    'id' => $r['id'],
                    'table_key' => 'job_listings',
                    'title' => $r['job_title'] . ' — ' . $r['company_name'],
                    'subtitle' => $r['location'] . ' · ' . $r['contact_info'],
                    'meta' => $r['job_type'] . ' · ' . $r['salary'],
                    'status' => $r['status'],
                ];
            }
        } elseif ($tab === 'blood') {
            $donors = $pdo->query('SELECT * FROM blood_donors ORDER BY id DESC LIMIT 40')->fetchAll();
            foreach ($donors as $d) {
                $items[] = [
                    'id' => $d['id'],
                    'table_key' => 'blood_donors',
                    'title' => $d['blood_group'] . ' — ' . $d['donor_name'],
                    'subtitle' => $d['area'] . ', ' . $d['upazila'],
                    'meta' => tr('রক্তদাতা', 'Blood Donor') . ' · ' . $d['phone'],
                    'status' => $d['is_available'] ? 'available' : 'unavailable',
                ];
            }
            $reqs = $pdo->query('SELECT * FROM blood_requests ORDER BY id DESC LIMIT 40')->fetchAll();
            foreach ($reqs as $r) {
                $items[] = [
                    'id' => $r['id'],
                    'table_key' => 'blood_requests',
                    'title' => $r['blood_group'] . ' (' . $r['units_needed'] . ' units) — ' . $r['patient_name'],
                    'subtitle' => $r['hospital_name'] . ' · ' . $r['upazila'],
                    'meta' => tr('রক্তের অনুরোধ', 'Blood Request') . ' · ' . $r['needed_date'],
                    'status' => $r['status'],
                ];
            }
        } elseif ($tab === 'education') {
            $rows = $pdo->query('SELECT * FROM education_institutions ORDER BY id DESC LIMIT 80')->fetchAll();
            foreach ($rows as $r) {
                $items[] = [
                    'id' => $r['id'],
                    'table_key' => 'education_institutions',
                    'title' => $r['name'],
                    'subtitle' => $r['address'] . ' · ' . $r['upazila'],
                    'meta' => $r['type'],
                    'status' => $r['status'],
                ];
            }
        } elseif ($tab === 'businesses') {
            $rows = $pdo->query('SELECT * FROM businesses ORDER BY id DESC LIMIT 80')->fetchAll();
            foreach ($rows as $r) {
                $items[] = [
                    'id' => $r['id'],
                    'table_key' => 'businesses',
                    'title' => $r['name'],
                    'subtitle' => $r['address'] . ' · ' . $r['phone'],
                    'meta' => $r['category'],
                    'status' => $r['status'],
                ];
            }
        } else {
            $tab = 'posts';
            $rows = $pdo->query('SELECT p.*, u.full_name AS author_name FROM posts p JOIN users u ON u.id = p.user_id ORDER BY p.created_at DESC LIMIT 80')->fetchAll();
            foreach ($rows as $r) {
                $items[] = [
                    'id' => $r['id'],
                    'table_key' => 'posts',
                    'title' => $r['title'],
                    'subtitle' => $r['upazila'] . ' · ' . $r['author_name'],
                    'meta' => $r['category'],
                    'status' => $r['status'],
                ];
            }
        }

        View::render('admin/content', [
            'pageTitle' => tr('সকল কনটেন্ট ম্যানেজার — Admin', 'Content Manager — Admin'),
            'tab' => $tab,
            'items' => $items,
        ], 'admin');
    }

    public function deleteContent(): void
    {
        $admin = Auth::requireAdmin(['admin', 'moderator']);
        CSRF::requireValid();
        $table = Validator::cleanString($_POST['table'] ?? '', 40);
        $id = (int)($_POST['id'] ?? 0);
        $tab = Validator::cleanString($_POST['tab'] ?? 'posts', 40);

        $allowedTables = ['posts', 'marketplace_listings', 'rental_listings', 'job_listings', 'blood_donors', 'blood_requests', 'education_institutions', 'businesses'];
        if ($id > 0 && in_array($table, $allowedTables, true)) {
            db()->prepare("DELETE FROM `$table` WHERE id = ?")->execute([$id]);
            $this->logAction((int)$admin['id'], 'delete_content', $table, $id);
            flash('success', tr('আইটেমটি মুছে ফেলা হয়েছে।', 'Item deleted successfully.'));
        }
        redirect('/admin/content?tab=' . urlencode($tab));
    }

    public function users(): void
    {
        Auth::requireAdmin(['admin']);
        $q = Validator::cleanString($_GET['q'] ?? '', 120);
        $filter = Validator::cleanString($_GET['filter'] ?? '', 30);
        $pdo = db();

        $clauses = ['1=1'];
        $params = [];
        if ($q !== '') {
            $clauses[] = '(u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)';
            $params[] = "%$q%";
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        if ($filter === 'pending') {
            $clauses[] = "EXISTS (SELECT 1 FROM user_categories uc WHERE uc.user_id = u.id AND uc.status = 'pending')";
        }
        $where = implode(' AND ', $clauses);

        $stmt = $pdo->prepare("
            SELECT u.*, r.name AS role_name, r.slug AS role_slug
            FROM users u JOIN roles r ON r.id = u.role_id
            WHERE $where ORDER BY u.created_at DESC LIMIT 100
        ");
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        foreach ($users as &$u) {
            $cStmt = $pdo->prepare('SELECT * FROM user_categories WHERE user_id = ?');
            $cStmt->execute([(int)$u['id']]);
            $u['categories'] = $cStmt->fetchAll();
        }

        $roles = $pdo->query('SELECT * FROM roles ORDER BY id ASC')->fetchAll();

        View::render('admin/users', [
            'pageTitle' => tr('ব্যবহারকারী ও ক্যাটাগরি অনুমোদন — Admin', 'Users & Category Approval — Admin'),
            'users' => $users,
            'roles' => $roles,
            'q' => $q,
            'filter' => $filter,
        ], 'admin');
    }

    public function updateUserStatus(): void
    {
        $admin = Auth::requireAdmin(['admin']);
        CSRF::requireValid();
        $userId = (int)($_POST['user_id'] ?? 0);
        $status = Validator::cleanString($_POST['status'] ?? 'active', 20);
        $roleId = (int)($_POST['role_id'] ?? 4);

        if ($userId > 0 && in_array($status, ['active', 'pending', 'suspended', 'rejected'], true)) {
            db()->prepare('UPDATE users SET status = ?, role_id = ? WHERE id = ?')->execute([$status, $roleId, $userId]);
            $this->logAction((int)$admin['id'], 'user_status_' . $status, 'user', $userId, "Role ID: $roleId");
            flash('success', tr('ব্যবহারকারীর অবস্থা হালনাগাদ হয়েছে।', 'User status updated.'));
        }
        redirect('/admin/users');
    }

    public function saveUserCategoriesBulk(): void
    {
        $admin = Auth::requireAdmin(['admin']);
        CSRF::requireValid();
        $userId = (int)($_POST['user_id'] ?? 0);
        $approvedSlugs = (array)($_POST['approved_categories'] ?? []);
        $allSlugs = array_keys(account_categories());
        $approvedSlugs = array_values(array_intersect($approvedSlugs, $allSlugs));

        if ($userId > 0) {
            $pdo = db();
            $existingRows = $pdo->prepare('SELECT id, category_slug FROM user_categories WHERE user_id = ?');
            $existingRows->execute([$userId]);
            $existingMap = [];
            foreach ($existingRows->fetchAll() as $row) {
                $existingMap[$row['category_slug']] = (int)$row['id'];
            }

            foreach ($allSlugs as $slug) {
                $isApproved = in_array($slug, $approvedSlugs, true);
                if ($isApproved) {
                    if (isset($existingMap[$slug])) {
                        $pdo->prepare("UPDATE user_categories SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
                            ->execute([(int)$admin['id'], $existingMap[$slug]]);
                    } else {
                        $pdo->prepare("INSERT INTO user_categories (user_id, category_slug, status, reviewed_by, reviewed_at) VALUES (?, ?, 'approved', ?, NOW())")
                            ->execute([$userId, $slug, (int)$admin['id']]);
                    }
                } elseif (isset($existingMap[$slug])) {
                    $pdo->prepare("UPDATE user_categories SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
                        ->execute([(int)$admin['id'], $existingMap[$slug]]);
                }
            }

            $pdo->prepare('INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)')
                ->execute([
                    $userId,
                    'ক্যাটাগরি অনুমোদন আপডেট',
                    'অ্যাডমিন আপনার অ্যাকাউন্ট ক্যাটাগরি তালিকা হালনাগাদ করেছেন।',
                    '/dashboard',
                ]);

            $this->logAction((int)$admin['id'], 'bulk_category_update', 'user', $userId, implode(',', $approvedSlugs));
            flash('success', tr('ব্যবহারকারীর ক্যাটাগরি অনুমোদন সংরক্ষিত হয়েছে।', 'User category approvals saved.'));
        }
        redirect('/admin/users');
    }

    public function reviewCategory(): void
    {
        $admin = Auth::requireAdmin(['admin']);
        CSRF::requireValid();
        $catId = (int)($_POST['category_row_id'] ?? 0);
        $decision = Validator::cleanString($_POST['decision'] ?? 'approved', 20);
        if ($catId > 0 && in_array($decision, ['approved', 'rejected'], true)) {
            $stmt = db()->prepare('SELECT user_id, category_slug FROM user_categories WHERE id = ?');
            $stmt->execute([$catId]);
            $row = $stmt->fetch();
            if ($row) {
                db()->prepare('UPDATE user_categories SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?')
                    ->execute([$decision, (int)$admin['id'], $catId]);
                db()->prepare('INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)')
                    ->execute([
                        (int)$row['user_id'],
                        'ক্যাটাগরি পর্যালোচনা সম্পন্ন',
                        'আপনার "' . $row['category_slug'] . '" ক্যাটাগরিটি ' . ($decision === 'approved' ? 'অনুমোদিত' : 'প্রত্যাখ্যাত') . ' হয়েছে।',
                        '/dashboard',
                    ]);
                $this->logAction((int)$admin['id'], 'category_' . $decision, 'user_category', $catId, $row['category_slug']);
                flash('success', tr('ক্যাটাগরি সিদ্ধান্ত সংরক্ষিত হয়েছে।', 'Category decision saved.'));
            }
        }
        redirect('/admin/users');
    }

    public function newsQueue(): void
    {
        Auth::requireAdmin(['admin', 'editor']);
        $articles = db()->query("
            SELECT n.*, c.name_bn AS category_bn, c.name_en AS category_en, u.full_name AS author_name
            FROM news n
            JOIN news_categories c ON c.id = n.category_id
            JOIN users u ON u.id = n.author_id
            ORDER BY FIELD(n.status, 'pending', 'published', 'rejected'), n.created_at DESC
            LIMIT 60
        ")->fetchAll();

        View::render('admin/news', [
            'pageTitle' => tr('সংবাদ অনুমোদন কিউ — Admin', 'News Approval Queue — Admin'),
            'articles' => $articles,
        ], 'admin');
    }

    public function reviewNews(): void
    {
        $admin = Auth::requireAdmin(['admin', 'editor']);
        CSRF::requireValid();
        $newsId = (int)($_POST['news_id'] ?? 0);
        $decision = Validator::cleanString($_POST['decision'] ?? 'published', 20);
        $featured = isset($_POST['is_featured']) ? 1 : 0;

        if ($newsId > 0 && in_array($decision, ['published', 'rejected'], true)) {
            db()->prepare('UPDATE news SET status = ?, is_featured = ?, approved_by = ?, approved_at = NOW() WHERE id = ?')
                ->execute([$decision, $featured, (int)$admin['id'], $newsId]);
            $this->logAction((int)$admin['id'], 'news_' . $decision, 'news', $newsId);
            flash('success', tr('সংবাদ স্ট্যাটাস হালনাগাদ হয়েছে।', 'News status updated.'));
        }
        redirect('/admin/news');
    }

    public function moderation(): void
    {
        Auth::requireAdmin(['admin', 'moderator', 'editor']);
        $pdo = db();

        $flaggedPosts = $pdo->query("
            SELECT p.*, u.full_name AS author_name
            FROM posts p JOIN users u ON u.id = p.user_id
            WHERE p.status IN ('pending_moderation', 'hidden')
            ORDER BY p.created_at DESC
        ")->fetchAll();

        $reports = $pdo->query("
            SELECT r.*, u.full_name AS reporter_name
            FROM reports r JOIN users u ON u.id = r.reporter_id
            ORDER BY FIELD(r.status, 'open', 'resolved', 'dismissed'), r.created_at DESC LIMIT 50
        ")->fetchAll();

        $rules = $pdo->query('SELECT * FROM moderation_rules ORDER BY id DESC')->fetchAll();

        View::render('admin/moderation', [
            'pageTitle' => tr('কনটেন্ট মডারেশন ও নিষিদ্ধ শব্দ — Admin', 'Moderation & Keywords — Admin'),
            'flaggedPosts' => $flaggedPosts,
            'reports' => $reports,
            'rules' => $rules,
        ], 'admin');
    }

    public function reviewPost(): void
    {
        $admin = Auth::requireAdmin(['admin', 'moderator']);
        CSRF::requireValid();
        $postId = (int)($_POST['post_id'] ?? 0);
        $status = Validator::cleanString($_POST['status'] ?? 'published', 30);
        if ($postId > 0 && in_array($status, ['published', 'hidden', 'rejected'], true)) {
            db()->prepare('UPDATE posts SET status = ? WHERE id = ?')->execute([$status, $postId]);
            Moderation::log('post', $postId, (int)$admin['id'], null, 'admin_' . $status);
            $this->logAction((int)$admin['id'], 'post_' . $status, 'post', $postId);
            flash('success', tr('পোস্ট মডারেশন সম্পন্ন হয়েছে।', 'Post moderation updated.'));
        }
        redirect('/admin/moderation');
    }

    public function addRule(): void
    {
        $admin = Auth::requireAdmin(['admin', 'moderator']);
        CSRF::requireValid();
        $keyword = Validator::cleanString($_POST['keyword'] ?? '', 120);
        $matchType = Validator::cleanString($_POST['match_type'] ?? 'word', 20);
        $severity = Validator::cleanString($_POST['severity'] ?? 'flag', 20);
        if ($keyword !== '') {
            db()->prepare('INSERT IGNORE INTO moderation_rules (keyword, match_type, severity, is_active) VALUES (?, ?, ?, 1)')
                ->execute([$keyword, $matchType, $severity]);
            $this->logAction((int)$admin['id'], 'add_moderation_rule', 'moderation_rule', 0, $keyword);
            flash('success', tr('নিষিদ্ধ শব্দ/নিয়ম যুক্ত হয়েছে।', 'Moderation rule added.'));
        }
        redirect('/admin/moderation');
    }

    public function deleteRule(): void
    {
        $admin = Auth::requireAdmin(['admin', 'moderator']);
        CSRF::requireValid();
        $ruleId = (int)($_POST['rule_id'] ?? 0);
        if ($ruleId > 0) {
            db()->prepare('DELETE FROM moderation_rules WHERE id = ?')->execute([$ruleId]);
            $this->logAction((int)$admin['id'], 'delete_moderation_rule', 'moderation_rule', $ruleId);
            flash('success', tr('নিয়মটি মুছে ফেলা হয়েছে।', 'Moderation rule deleted.'));
        }
        redirect('/admin/moderation');
    }

    public function resolveReport(): void
    {
        $admin = Auth::requireAdmin(['admin', 'moderator']);
        CSRF::requireValid();
        $reportId = (int)($_POST['report_id'] ?? 0);
        $status = Validator::cleanString($_POST['status'] ?? 'resolved', 20);
        if ($reportId > 0 && in_array($status, ['resolved', 'dismissed'], true)) {
            db()->prepare('UPDATE reports SET status = ?, resolved_by = ?, resolved_at = NOW() WHERE id = ?')
                ->execute([$status, (int)$admin['id'], $reportId]);
            $this->logAction((int)$admin['id'], 'report_' . $status, 'report', $reportId);
            flash('success', tr('রিপোর্টটি নিষ্পত্তি করা হয়েছে।', 'Report resolved.'));
        }
        redirect('/admin/moderation');
    }

    public function ads(): void
    {
        Auth::requireAdmin(['admin']);
        $ads = db()->query('SELECT * FROM advertisements ORDER BY id DESC')->fetchAll();
        View::render('admin/ads', [
            'pageTitle' => tr('বিজ্ঞাপন ব্যবস্থাপনা — Admin', 'Advertisements — Admin'),
            'ads' => $ads,
        ], 'admin');
    }

    public function storeAd(): void
    {
        $admin = Auth::requireAdmin(['admin']);
        CSRF::requireValid();
        $title = Validator::cleanString($_POST['title'] ?? '', 180);
        $placement = Validator::cleanString($_POST['placement'] ?? 'home_banner', 40);
        $targetUrl = Validator::cleanString($_POST['target_url'] ?? '#', 255);
        $startDate = Validator::cleanString($_POST['start_date'] ?? date('Y-m-d'), 20);
        $endDate = Validator::cleanString($_POST['end_date'] ?? date('Y-m-d', strtotime('+30 days')), 20);
        $status = Validator::cleanString($_POST['status'] ?? 'active', 20);
        $bannerPath = 'assets/images/hero_gazipur_community.jpg';

        if (!empty($_FILES['banner_image'])) {
            try {
                $uploaded = Uploader::uploadImage($_FILES['banner_image'], 'ads');
                if ($uploaded) {
                    $bannerPath = $uploaded;
                }
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
                redirect('/admin/ads');
            }
        }

        db()->prepare('INSERT INTO advertisements (advertiser_id, title, placement, banner_image, target_url, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([(int)$admin['id'], $title, $placement, $bannerPath, $targetUrl, $startDate, $endDate, $status]);

        $this->logAction((int)$admin['id'], 'create_ad', 'advertisement', (int)db()->lastInsertId(), $title);
        flash('success', tr('বিজ্ঞাপনটি যুক্ত হয়েছে।', 'Advertisement published.'));
        redirect('/admin/ads');
    }

    public function settings(): void
    {
        Auth::requireAdmin(['admin']);
        $rows = db()->query('SELECT * FROM settings')->fetchAll();
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }
        View::render('admin/settings', [
            'pageTitle' => tr('ওয়েবসাইট সেটিংস ও SEO — Admin', 'Settings & SEO — Admin'),
            'settings' => $settings,
        ], 'admin');
    }

    public function saveSettings(): void
    {
        $admin = Auth::requireAdmin(['admin']);
        CSRF::requireValid();
        $keys = ['site_name', 'site_tagline_bn', 'site_tagline_en', 'contact_email', 'contact_phone', 'seo_meta_title', 'seo_meta_description', 'footer_text'];
        $stmt = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($keys as $key) {
            $val = Validator::cleanString($_POST[$key] ?? '', 500);
            $stmt->execute([$key, $val]);
        }
        $this->logAction((int)$admin['id'], 'update_settings', 'settings', 0);
        flash('success', tr('ওয়েবসাইট সেটিংস সংরক্ষিত হয়েছে।', 'Website settings saved.'));
        redirect('/admin/settings');
    }
}
