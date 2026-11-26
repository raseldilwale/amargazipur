<?php
declare(strict_types=1);

class EducationController
{
    public function index(): void
    {
        $type = Validator::cleanString($_GET['type'] ?? '', 40);
        $upazila = Validator::cleanString($_GET['upazila'] ?? '', 80);

        $where = ["status = 'published'"];
        $params = [];
        if ($type !== '') {
            $where[] = 'type = ?';
            $params[] = $type;
        }
        if ($upazila !== '') {
            $where[] = 'upazila = ?';
            $params[] = $upazila;
        }
        $whereSql = implode(' AND ', $where);

        $stmt = db()->prepare("SELECT * FROM education_institutions WHERE $whereSql ORDER BY id DESC");
        $stmt->execute($params);

        View::render('education/index', [
            'pageTitle' => __('education') . ' — AmarGazipur',
            'institutions' => $stmt->fetchAll(),
            'type' => $type,
            'upazila' => $upazila,
        ]);
    }

    public function store(): void
    {
        $user = Auth::requireLogin();
        CSRF::requireValid();

        if (!Auth::hasApprovedCategory('educational_institution') && !Auth::hasRole('admin')) {
            flash('error', 'শিক্ষা প্রতিষ্ঠানের প্রোফাইল যুক্ত করতে অনুমোদিত Educational Institution ক্যাটাগরি প্রয়োজন।');
            redirect('/education');
        }

        $name = Validator::cleanString($_POST['name'] ?? '', 200);
        $type = Validator::cleanString($_POST['type'] ?? 'school', 40);
        $upazila = Validator::cleanString($_POST['upazila'] ?? 'Gazipur Sadar', 80);
        $address = Validator::cleanString($_POST['address'] ?? '', 255);
        $phone = Validator::cleanString($_POST['phone'] ?? '', 50);
        $website = Validator::cleanString($_POST['website'] ?? '', 255);
        $courses = Validator::cleanString($_POST['courses'] ?? '', 1000);
        $description = Validator::cleanString($_POST['description'] ?? '', 3000);

        if ($name === '' || $address === '' || $description === '') {
            flash('error', 'প্রতিষ্ঠানের নাম, ঠিকানা ও বিবরণ প্রদান করুন।');
            redirect('/education');
        }

        $coverPath = null;
        if (!empty($_FILES['cover_image'])) {
            try {
                $coverPath = Uploader::uploadImage($_FILES['cover_image'], 'education');
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
                redirect('/education');
            }
        }

        db()->prepare('
            INSERT INTO education_institutions (user_id, type, name, upazila, address, phone, website, courses, description, cover_image, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "published")
        ')->execute([(int)$user['id'], $type, $name, $upazila, $address, $phone, $website, $courses, $description, $coverPath]);

        flash('success', 'শিক্ষা প্রতিষ্ঠানটি সফলভাবে যুক্ত হয়েছে।');
        redirect('/education');
    }
}
