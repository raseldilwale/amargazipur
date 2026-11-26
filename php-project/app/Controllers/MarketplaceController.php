<?php
declare(strict_types=1);

class MarketplaceController
{
    public function index(): void
    {
        $pdo = db();
        $catSlug = Validator::cleanString($_GET['cat'] ?? '', 80);
        $q = Validator::cleanString($_GET['q'] ?? '', 120);
        $upazila = Validator::cleanString($_GET['upazila'] ?? '', 80);

        $where = ["m.status = 'published'"];
        $params = [];
        if ($catSlug !== '') {
            $where[] = 'c.slug = ?';
            $params[] = $catSlug;
        }
        if ($q !== '') {
            $where[] = '(m.title LIKE ? OR m.description LIKE ?)';
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        if ($upazila !== '') {
            $where[] = 'm.upazila = ?';
            $params[] = $upazila;
        }
        $whereSql = implode(' AND ', $where);

        $stmt = $pdo->prepare("
            SELECT m.*, c.name_bn AS category_bn, u.full_name AS seller_name
            FROM marketplace_listings m
            JOIN marketplace_categories c ON c.id = m.category_id
            JOIN users u ON u.id = m.user_id
            WHERE $whereSql
            ORDER BY m.created_at DESC
        ");
        $stmt->execute($params);

        $categories = $pdo->query('SELECT * FROM marketplace_categories ORDER BY id ASC')->fetchAll();

        View::render('marketplace/index', [
            'pageTitle' => __('marketplace') . ' — AmarGazipur',
            'listings' => $stmt->fetchAll(),
            'categories' => $categories,
            'catSlug' => $catSlug,
            'q' => $q,
            'upazila' => $upazila,
        ]);
    }

    public function store(): void
    {
        $user = Auth::requireLogin();
        CSRF::requireValid();

        $title = Validator::cleanString($_POST['title'] ?? '', 220);
        $description = Validator::cleanString($_POST['description'] ?? '', 4000);
        $price = (float)($_POST['price'] ?? 0);
        $condition = Validator::cleanString($_POST['item_condition'] ?? 'used', 30);
        $categoryId = (int)($_POST['category_id'] ?? 1);
        $location = Validator::cleanString($_POST['location'] ?? '', 150);
        $upazila = Validator::cleanString($_POST['upazila'] ?? 'Gazipur Sadar', 80);
        $phone = Validator::cleanString($_POST['contact_phone'] ?? '', 40);

        if ($title === '' || $description === '' || $location === '' || !Validator::isPhone($phone)) {
            flash('error', 'পণ্যের সকল আবশ্যকীয় তথ্য ও সঠিক ফোন নম্বর প্রদান করুন।');
            redirect('/marketplace');
        }

        $images = [];
        if (!empty($_FILES['images'])) {
            try {
                $images = Uploader::uploadMultiple($_FILES['images'], 'marketplace', 4);
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
                redirect('/marketplace');
            }
        }

        $mod = Moderation::checkContent($title . "\n" . $description);
        $status = $mod['flagged'] ? 'pending_moderation' : 'published';

        $stmt = db()->prepare('
            INSERT INTO marketplace_listings (user_id, category_id, title, description, price, item_condition, location, upazila, contact_phone, images_json, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            (int)$user['id'],
            $categoryId,
            $title,
            $description,
            $price,
            $condition,
            $location,
            $upazila,
            $phone,
            json_encode($images),
            $status,
        ]);

        flash('success', $mod['flagged'] ? 'বিজ্ঞাপনটি মডারেশন পর্যালোচনায় রয়েছে।' : 'আপনার পণ্যটি মার্কেটপ্লেসে প্রকাশিত হয়েছে।');
        redirect('/marketplace');
    }
}
