<?php
declare(strict_types=1);

class DashboardController
{
    public function index(): void
    {
        $user = Auth::requireLogin();
        $uid = (int)$user['id'];
        $pdo = db();

        $myPosts = $pdo->prepare('SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC LIMIT 20');
        $myPosts->execute([$uid]);

        $myMarket = $pdo->prepare('SELECT * FROM marketplace_listings WHERE user_id = ? ORDER BY created_at DESC LIMIT 20');
        $myMarket->execute([$uid]);

        $myRentals = $pdo->prepare('SELECT * FROM rental_listings WHERE user_id = ? ORDER BY created_at DESC LIMIT 20');
        $myRentals->execute([$uid]);

        $myJobs = $pdo->prepare('SELECT * FROM job_listings WHERE user_id = ? ORDER BY created_at DESC LIMIT 20');
        $myJobs->execute([$uid]);

        $myComments = $pdo->prepare('
            SELECT c.*, p.title AS post_title
            FROM comments c JOIN posts p ON p.id = c.post_id
            WHERE c.user_id = ? ORDER BY c.created_at DESC LIMIT 20
        ');
        $myComments->execute([$uid]);

        $myLikes = $pdo->prepare('
            SELECT l.created_at AS liked_at, p.id, p.title
            FROM likes l JOIN posts p ON p.id = l.post_id
            WHERE l.user_id = ? ORDER BY l.created_at DESC LIMIT 20
        ');
        $myLikes->execute([$uid]);

        $notifications = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20');
        $notifications->execute([$uid]);

        View::render('dashboard/index', [
            'pageTitle' => __('dashboard') . ' — AmarGazipur',
            'user' => $user,
            'myPosts' => $myPosts->fetchAll(),
            'myMarket' => $myMarket->fetchAll(),
            'myRentals' => $myRentals->fetchAll(),
            'myJobs' => $myJobs->fetchAll(),
            'myComments' => $myComments->fetchAll(),
            'myLikes' => $myLikes->fetchAll(),
            'notifications' => $notifications->fetchAll(),
        ]);
    }

    public function updateProfile(): void
    {
        $user = Auth::requireLogin();
        CSRF::requireValid();

        $fullName = Validator::cleanString($_POST['full_name'] ?? '', 150);
        $phone = Validator::cleanString($_POST['phone'] ?? '', 30);
        $upazila = Validator::cleanString($_POST['upazila'] ?? 'Gazipur Sadar', 80);
        $bio = Validator::cleanString($_POST['bio'] ?? '', 1000);

        if ($fullName === '') {
            flash('error', 'নাম খালি রাখা যাবে না।');
            redirect('/dashboard');
        }

        db()->prepare('UPDATE users SET full_name = ?, phone = ?, upazila = ?, bio = ? WHERE id = ?')
            ->execute([$fullName, $phone, $upazila, $bio, (int)$user['id']]);

        flash('success', 'প্রোফাইল তথ্য সফলভাবে হালনাগাদ হয়েছে।');
        redirect('/dashboard');
    }

    public function updatePassword(): void
    {
        $user = Auth::requireLogin();
        CSRF::requireValid();

        $current = (string)($_POST['current_password'] ?? '');
        $newPass = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if (!password_verify($current, $user['password_hash'])) {
            flash('error', 'বর্তমান পাসওয়ার্ড সঠিক নয়।');
            redirect('/dashboard');
        }
        if (strlen($newPass) < 6 || $newPass !== $confirm) {
            flash('error', 'নতুন পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে এবং উভয় পাসওয়ার্ড মিলতে হবে।');
            redirect('/dashboard');
        }

        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($newPass, PASSWORD_DEFAULT), (int)$user['id']]);

        flash('success', 'পাসওয়ার্ড সফলভাবে পরিবর্তন হয়েছে।');
        redirect('/dashboard');
    }

    public function markNotificationsRead(): void
    {
        $user = Auth::requireLogin();
        CSRF::requireValid();
        db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([(int)$user['id']]);
        redirect('/dashboard');
    }
}
