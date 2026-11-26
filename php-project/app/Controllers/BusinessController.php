<?php
declare(strict_types=1);

class BusinessController
{
    public function index(): void
    {
        $cat = Validator::cleanString($_GET['cat'] ?? '', 40);
        $upazila = Validator::cleanString($_GET['upazila'] ?? '', 80);

        $where = ["status = 'published'"];
        $params = [];
        if ($cat !== '') {
            $where[] = 'category = ?';
            $params[] = $cat;
        }
        if ($upazila !== '') {
            $where[] = 'upazila = ?';
            $params[] = $upazila;
        }
        $whereSql = implode(' AND ', $where);

        $stmt = db()->prepare("SELECT * FROM businesses WHERE $whereSql ORDER BY is_featured DESC, id DESC");
        $stmt->execute($params);

        $ads = db()->query("SELECT * FROM advertisements WHERE status = 'active' AND CURDATE() BETWEEN start_date AND end_date ORDER BY id DESC")->fetchAll();

        View::render('businesses/index', [
            'pageTitle' => __('businesses') . ' — AmarGazipur',
            'businesses' => $stmt->fetchAll(),
            'ads' => $ads,
            'cat' => $cat,
            'upazila' => $upazila,
        ]);
    }

    public function store(): void
    {
        $user = Auth::requireLogin();
        CSRF::requireValid();

        if (!Auth::hasApprovedCategory('business_owner') && !Auth::hasRole('admin')) {
            flash('error', 'ব্যবসা তালিকাভুক্ত করতে অনুমোদিত Business Owner ক্যাটাগরি প্রয়োজন।');
            redirect('/businesses');
        }

        $name = Validator::cleanString($_POST['name'] ?? '', 200);
        $category = Validator::cleanString($_POST['category'] ?? 'services', 40);
        $address = Validator::cleanString($_POST['address'] ?? '', 255);
        $upazila = Validator::cleanString($_POST['upazila'] ?? 'Gazipur Sadar', 80);
        $phone = Validator::cleanString($_POST['phone'] ?? '', 40);
        $hours = Validator::cleanString($_POST['hours'] ?? '', 120);
        $description = Validator::cleanString($_POST['description'] ?? '', 3000);
        $mapCoords = Validator::cleanString($_POST['map_coords'] ?? '', 120);

        if ($name === '' || $address === '' || !Validator::isPhone($phone)) {
            flash('error', 'ব্যবসার নাম, ঠিকানা ও সঠিক ফোন নম্বর প্রদান করুন।');
            redirect('/businesses');
        }

        db()->prepare('
            INSERT INTO businesses (user_id, category, name, address, upazila, phone, hours, description, map_coords, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "published")
        ')->execute([(int)$user['id'], $category, $name, $address, $upazila, $phone, $hours, $description, $mapCoords]);

        flash('success', 'ব্যবসা প্রতিষ্ঠানটি ডিরেক্টরিতে যুক্ত হয়েছে।');
        redirect('/businesses');
    }
}
