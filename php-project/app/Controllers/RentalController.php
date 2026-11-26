<?php
declare(strict_types=1);

class RentalController
{
    public function index(): void
    {
        $pdo = db();
        $cat = Validator::cleanString($_GET['cat'] ?? '', 60);
        $upazila = Validator::cleanString($_GET['upazila'] ?? '', 80);
        $maxRent = (int)($_GET['max_rent'] ?? 0);

        $where = ["r.status = 'published'"];
        $params = [];
        if ($cat !== '') {
            $where[] = 'r.category = ?';
            $params[] = $cat;
        }
        if ($upazila !== '') {
            $where[] = 'r.upazila = ?';
            $params[] = $upazila;
        }
        if ($maxRent > 0) {
            $where[] = 'r.monthly_rent <= ?';
            $params[] = $maxRent;
        }
        $whereSql = implode(' AND ', $where);

        $stmt = $pdo->prepare("
            SELECT r.*, u.full_name AS landlord_name
            FROM rental_listings r
            JOIN users u ON u.id = r.user_id
            WHERE $whereSql
            ORDER BY r.created_at DESC
        ");
        $stmt->execute($params);

        View::render('rentals/index', [
            'pageTitle' => __('rentals') . ' — AmarGazipur',
            'rentals' => $stmt->fetchAll(),
            'cat' => $cat,
            'upazila' => $upazila,
            'maxRent' => $maxRent,
        ]);
    }

    public function store(): void
    {
        $user = Auth::requireLogin();
        CSRF::requireValid();

        if (!Auth::hasApprovedCategory('landlord') && !Auth::hasRole('admin')) {
            flash('error', 'বাসা ভাড়ার বিজ্ঞাপন দিতে অনুমোদিত বাড়িওয়ালা (Landlord) ক্যাটাগরি প্রয়োজন।');
            redirect('/rentals');
        }

        $title = Validator::cleanString($_POST['title'] ?? '', 220);
        $category = Validator::cleanString($_POST['category'] ?? 'family_apartment', 40);
        $monthlyRent = (float)($_POST['monthly_rent'] ?? 0);
        $advance = (float)($_POST['advance_amount'] ?? 0);
        $location = Validator::cleanString($_POST['location'] ?? '', 160);
        $upazila = Validator::cleanString($_POST['upazila'] ?? 'Gazipur Sadar', 80);
        $bedrooms = max(1, (int)($_POST['bedrooms'] ?? 1));
        $bathrooms = max(1, (int)($_POST['bathrooms'] ?? 1));
        $floor = Validator::cleanString($_POST['floor_number'] ?? '', 40);
        $facilities = Validator::cleanString($_POST['facilities'] ?? '', 255);
        $availableFrom = Validator::cleanString($_POST['available_from'] ?? date('Y-m-d'), 20);
        $phone = Validator::cleanString($_POST['contact_phone'] ?? '', 40);
        $hideContact = isset($_POST['hide_contact_public']) ? 1 : 0;
        $description = Validator::cleanString($_POST['description'] ?? '', 3000);

        if ($title === '' || $monthlyRent <= 0 || $location === '' || !Validator::isPhone($phone)) {
            flash('error', 'সঠিক ভাড়ার তথ্য ও যোগাযোগ নম্বর প্রদান করুন।');
            redirect('/rentals');
        }

        $images = [];
        if (!empty($_FILES['images'])) {
            try {
                $images = Uploader::uploadMultiple($_FILES['images'], 'rentals', 4);
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
                redirect('/rentals');
            }
        }

        $mod = Moderation::checkContent($title . "\n" . $description);
        $status = $mod['flagged'] ? 'pending_moderation' : 'published';

        $stmt = db()->prepare('
            INSERT INTO rental_listings (user_id, category, title, description, monthly_rent, advance_amount, location, upazila, bedrooms, bathrooms, floor_number, facilities, available_from, contact_phone, hide_contact_public, images_json, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            (int)$user['id'],
            $category,
            $title,
            $description,
            $monthlyRent,
            $advance,
            $location,
            $upazila,
            $bedrooms,
            $bathrooms,
            $floor,
            $facilities,
            $availableFrom,
            $phone,
            $hideContact,
            json_encode($images),
            $status,
        ]);

        flash('success', $mod['flagged'] ? 'টু-লেট বিজ্ঞাপনটি মডারেশন পর্যালোচনায় রয়েছে।' : 'আপনার টু-লেট বিজ্ঞাপনটি প্রকাশিত হয়েছে।');
        redirect('/rentals');
    }
}
