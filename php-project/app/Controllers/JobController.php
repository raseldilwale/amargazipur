<?php
declare(strict_types=1);

class JobController
{
    public function index(): void
    {
        $pdo = db();
        $q = Validator::cleanString($_GET['q'] ?? '', 120);
        $jobType = Validator::cleanString($_GET['job_type'] ?? '', 30);
        $upazila = Validator::cleanString($_GET['upazila'] ?? '', 80);

        $where = ["j.status = 'published'"];
        $params = [];
        if ($q !== '') {
            $where[] = '(j.job_title LIKE ? OR j.company_name LIKE ? OR j.location LIKE ? OR j.description LIKE ?)';
            $params[] = "%$q%";
            $params[] = "%$q%";
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        if ($jobType !== '' && in_array($jobType, ['full_time', 'part_time', 'contract', 'internship', 'remote'], true)) {
            $where[] = 'j.job_type = ?';
            $params[] = $jobType;
        }
        if ($upazila !== '') {
            $where[] = 'j.upazila = ?';
            $params[] = $upazila;
        }
        $whereSql = implode(' AND ', $where);

        $stmt = $pdo->prepare("
            SELECT j.*, u.full_name AS poster_name
            FROM job_listings j
            JOIN users u ON u.id = j.user_id
            WHERE $whereSql
            ORDER BY j.created_at DESC
        ");
        $stmt->execute($params);

        View::render('jobs/index', [
            'pageTitle' => __('jobs') . ' — AmarGazipur',
            'jobs' => $stmt->fetchAll(),
            'q' => $q,
            'jobType' => $jobType,
            'upazila' => $upazila,
        ]);
    }

    public function store(): void
    {
        $user = Auth::requireLogin();
        CSRF::requireValid();

        if (!Auth::hasApprovedCategory('job_poster') && !Auth::hasRole('admin')) {
            flash('error', tr(
                'চাকরির বিজ্ঞপ্তি পোস্ট করতে অ্যাডমিন কর্তৃক অনুমোদিত চাকরিদাতা (Job Poster) রোল/ক্যাটাগরি প্রয়োজন।',
                'An approved Job Poster role/category is required to publish job listings.'
            ));
            redirect('/jobs');
        }

        $companyName = Validator::cleanString($_POST['company_name'] ?? '', 180);
        $jobTitle = Validator::cleanString($_POST['job_title'] ?? '', 180);
        $jobType = Validator::cleanString($_POST['job_type'] ?? 'full_time', 30);
        if (!in_array($jobType, ['full_time', 'part_time', 'contract', 'internship', 'remote'], true)) {
            $jobType = 'full_time';
        }
        $salary = Validator::cleanString($_POST['salary'] ?? '', 120);
        $location = Validator::cleanString($_POST['location'] ?? '', 180);
        $upazila = Validator::cleanString($_POST['upazila'] ?? 'Gazipur Sadar', 80);
        $contactInfo = Validator::cleanString($_POST['contact_info'] ?? '', 160);
        $deadline = Validator::cleanString($_POST['deadline'] ?? '', 40);
        $description = Validator::cleanString($_POST['description'] ?? '', 4000);

        $isValidContact = Validator::isPhone($contactInfo) || Validator::isEmail($contactInfo) || mb_strlen($contactInfo, 'UTF-8') >= 6;
        if ($companyName === '' || $jobTitle === '' || $salary === '' || $location === '' || !$isValidContact) {
            flash('error', tr(
                'কোম্পানির নাম, পদবি, বেতন, লোকেশন ও যোগাযোগ নম্বর/ইমেইল সঠিকভাবে পূরণ করুন।',
                'Please enter company name, position, salary, location, and contact phone/email.'
            ));
            redirect('/jobs');
        }

        $imagePath = null;
        if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $imagePath = Uploader::uploadImage($_FILES['image'], 'jobs');
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
                redirect('/jobs');
            }
        }

        $mod = Moderation::checkContent($companyName . "\n" . $jobTitle . "\n" . $description);
        $status = $mod['flagged'] ? 'pending_moderation' : 'published';

        $stmt = db()->prepare('
            INSERT INTO job_listings (user_id, company_name, job_title, job_type, salary, location, upazila, contact_info, deadline, description, image_path, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            (int)$user['id'],
            $companyName,
            $jobTitle,
            $jobType,
            $salary,
            $location,
            $upazila,
            $contactInfo,
            $deadline !== '' ? $deadline : null,
            $description !== '' ? $description : ($jobTitle . ' — ' . $companyName),
            $imagePath,
            $status,
        ]);

        flash('success', $mod['flagged']
            ? tr('চাকরির বিজ্ঞাপনটি মডারেশন পর্যালোচনায় রয়েছে।', 'Job posting is pending moderation review.')
            : tr('আপনার চাকরির বিজ্ঞাপনটি সফলভাবে প্রকাশিত হয়েছে।', 'Your job listing has been published.'));
        redirect('/jobs');
    }
}
