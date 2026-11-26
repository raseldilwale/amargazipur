<?php
declare(strict_types=1);

class BloodController
{
    public function index(): void
    {
        $group = Validator::cleanString($_GET['group'] ?? '', 10);
        $upazila = Validator::cleanString($_GET['upazila'] ?? '', 80);

        $where = ['1=1'];
        $params = [];
        if ($group !== '') {
            $where[] = 'blood_group = ?';
            $params[] = $group;
        }
        if ($upazila !== '') {
            $where[] = 'upazila = ?';
            $params[] = $upazila;
        }
        $whereSql = implode(' AND ', $where);

        $stmt = db()->prepare("SELECT * FROM blood_donors WHERE $whereSql ORDER BY is_available DESC, id DESC");
        $stmt->execute($params);

        $reqStmt = db()->query("SELECT * FROM blood_requests WHERE status = 'open' ORDER BY needed_date ASC, id DESC LIMIT 20");

        View::render('blood/index', [
            'pageTitle' => __('blood') . ' — AmarGazipur',
            'donors' => $stmt->fetchAll(),
            'requests' => $reqStmt->fetchAll(),
            'group' => $group,
            'upazila' => $upazila,
        ]);
    }

    public function registerDonor(): void
    {
        $user = Auth::requireLogin();
        CSRF::requireValid();

        $donorName = Validator::cleanString($_POST['donor_name'] ?? $user['full_name'], 140);
        $bloodGroup = Validator::cleanString($_POST['blood_group'] ?? 'O+', 10);
        $area = Validator::cleanString($_POST['area'] ?? '', 120);
        $upazila = Validator::cleanString($_POST['upazila'] ?? 'Gazipur Sadar', 80);
        $phone = Validator::cleanString($_POST['phone'] ?? '', 40);
        $pref = Validator::cleanString($_POST['contact_preference'] ?? 'call', 20);
        $consent = isset($_POST['consent_public_contact']) ? 1 : 0;
        $lastDonation = Validator::cleanString($_POST['last_donation_date'] ?? '', 20) ?: null;

        if ($area === '' || !Validator::isPhone($phone)) {
            flash('error', 'সঠিক এলাকা ও যোগাযোগ নম্বর প্রদান করুন।');
            redirect('/blood-donors');
        }

        db()->prepare('
            INSERT INTO blood_donors (user_id, donor_name, blood_group, area, upazila, phone, contact_preference, consent_public_contact, is_available, last_donation_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
        ')->execute([(int)$user['id'], $donorName, $bloodGroup, $area, $upazila, $phone, $pref, $consent, $lastDonation]);

        flash('success', 'রক্তদাতা হিসেবে আপনার তথ্য সংরক্ষিত হয়েছে।');
        redirect('/blood-donors');
    }

    public function storeRequest(): void
    {
        $user = Auth::requireLogin();
        CSRF::requireValid();

        $patient = Validator::cleanString($_POST['patient_name'] ?? '', 140);
        $group = Validator::cleanString($_POST['blood_group'] ?? 'O+', 10);
        $units = max(1, (int)($_POST['units_needed'] ?? 1));
        $hospital = Validator::cleanString($_POST['hospital_name'] ?? '', 180);
        $upazila = Validator::cleanString($_POST['upazila'] ?? 'Gazipur Sadar', 80);
        $neededDate = Validator::cleanString($_POST['needed_date'] ?? date('Y-m-d'), 20);
        $phone = Validator::cleanString($_POST['contact_phone'] ?? '', 40);
        $notes = Validator::cleanString($_POST['notes'] ?? '', 255);

        if ($patient === '' || $hospital === '' || !Validator::isPhone($phone)) {
            flash('error', 'রোগীর নাম, হাসপাতালের নাম ও সঠিক নম্বর দিন।');
            redirect('/blood-donors');
        }

        db()->prepare('
            INSERT INTO blood_requests (user_id, patient_name, blood_group, units_needed, hospital_name, upazila, needed_date, contact_phone, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ')->execute([(int)$user['id'], $patient, $group, $units, $hospital, $upazila, $neededDate, $phone, $notes]);

        flash('success', 'জরুরি রক্তের অনুরোধ প্রকাশিত হয়েছে।');
        redirect('/blood-donors');
    }
}
