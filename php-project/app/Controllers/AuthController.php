<?php
declare(strict_types=1);

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }
        View::render('auth/login', ['pageTitle' => __('login') . ' — AmarGazipur']);
    }

    public function login(): void
    {
        CSRF::requireValid();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (RateLimiter::tooManyLoginAttempts($ip)) {
            flash('error', 'অতিরিক্ত ভুল চেষ্টার কারণে ১৫ মিনিটের জন্য লগইন সাময়িকভাবে সীমাবদ্ধ করা হয়েছে।');
            redirect('/login');
        }

        $identifier = mb_strtolower(Validator::cleanString($_POST['email'] ?? $_POST['username'] ?? '', 190));
        $password = (string)($_POST['password'] ?? '');

        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1');
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            RateLimiter::recordLoginAttempt($ip, $identifier, false);
            flash('error', 'ইউজারনেম/ইমেইল অথবা পাসওয়ার্ড সঠিক নয়।');
            redirect('/login');
        }

        if ($user['status'] === 'suspended') {
            flash('error', 'আপনার অ্যাকাউন্টটি সাময়িকভাবে স্থগিত করা হয়েছে। অ্যাডমিনের সাথে যোগাযোগ করুন।');
            redirect('/login');
        }
        if ($user['status'] === 'rejected') {
            flash('error', 'আপনার অ্যাকাউন্টটি অনুমোদিত হয়নি।');
            redirect('/login');
        }

        RateLimiter::recordLoginAttempt($ip, $identifier, true);
        Auth::loginUser((int)$user['id']);
        flash('success', 'স্বাগতম, ' . $user['full_name'] . '!');
        redirect(Auth::canAccessAdmin() ? '/admin' : '/dashboard');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }
        View::render('auth/register', ['pageTitle' => __('register') . ' — AmarGazipur']);
    }

    public function register(): void
    {
        CSRF::requireValid();

        $fullName = Validator::cleanString($_POST['full_name'] ?? '', 150);
        $rawUsername = mb_strtolower(Validator::cleanString($_POST['username'] ?? '', 60));
        $username = $rawUsername !== '' ? preg_replace('/[^a-z0-9._-]/', '', $rawUsername) : null;
        if ($username === '') {
            $username = null;
        }
        $email = mb_strtolower(Validator::cleanString($_POST['email'] ?? '', 190));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');
        $upazila = Validator::cleanString($_POST['upazila'] ?? 'Gazipur Sadar', 80);
        $selectedCategories = $_POST['categories'] ?? [];
        $terms = isset($_POST['terms_accepted']);

        if ($fullName === '' || !Validator::isEmail($email)) {
            flash('error', 'সঠিক নাম ও ইমেইল ঠিকানা প্রদান করুন।');
            redirect('/register');
        }
        if (strlen($password) < 8 || $password !== $confirm) {
            flash('error', 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে এবং উভয় পাসওয়ার্ড মিলতে হবে।');
            redirect('/register');
        }
        if (!$terms) {
            flash('error', 'শর্তাবলী ও গোপনীয়তা নীতিতে সম্মতি প্রদান করতে হবে।');
            redirect('/register');
        }

        $allowedCats = array_keys(account_categories());
        $cats = array_values(array_intersect((array)$selectedCategories, $allowedCats));
        if (empty($cats)) {
            $cats = ['general_user'];
        }

        $pdo = db();
        $exists = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $exists->execute([$email]);
        if ($exists->fetch()) {
            flash('error', 'এই ইমেইল ঠিকানা দিয়ে ইতিমধ্যে অ্যাকাউন্ট খোলা হয়েছে।');
            redirect('/register');
        }
        if ($username !== null) {
            $uExists = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $uExists->execute([$username]);
            if ($uExists->fetch()) {
                flash('error', 'এই ইউজারনেমটি ইতিমধ্যে ব্যবহৃত হয়েছে। অন্য ইউজারনেম দিন।');
                redirect('/register');
            }
        }

        $pdo->beginTransaction();
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('
                INSERT INTO users (role_id, full_name, username, email, password_hash, upazila, status, terms_accepted_at)
                VALUES (4, ?, ?, ?, ?, ?, "active", NOW())
            ');
            $stmt->execute([$fullName, $username, $email, $hash, $upazila]);
            $userId = (int)$pdo->lastInsertId();

            $catStmt = $pdo->prepare('INSERT INTO user_categories (user_id, category_slug, status) VALUES (?, ?, "pending")');
            foreach ($cats as $cat) {
                $catStmt->execute([$userId, $cat]);
            }

            $verifyToken = bin2hex(random_bytes(32));
            $pdo->prepare('INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))')
                ->execute([$userId, $verifyToken]);

            $pdo->prepare('INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)')
                ->execute([
                    $userId,
                    'অ্যাকাউন্ট তৈরি সম্পন্ন হয়েছে',
                    'আপনার নির্বাচিত অ্যাকাউন্ট ক্যাটাগরিগুলো অ্যাডমিন অনুমোদনের জন্য অপেক্ষমাণ রয়েছে।',
                    '/dashboard',
                ]);

            $pdo->commit();
            Auth::loginUser($userId);
            flash('success', 'নিবন্ধন সফল হয়েছে! আপনার নির্বাচিত ক্যাটাগরিগুলো অ্যাডমিন অনুমোদনের পর সক্রিয় হবে।');
            redirect('/dashboard');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'নিবন্ধন প্রক্রিয়ায় সমস্যা হয়েছে।');
            redirect('/register');
        }
    }

    public function verifyEmail(): void
    {
        $token = Validator::cleanString($_GET['token'] ?? '', 128);
        if ($token === '') {
            redirect('/');
        }
        $stmt = db()->prepare('SELECT * FROM email_verifications WHERE token = ? AND verified_at IS NULL AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        if (!$row) {
            flash('error', 'ভেরিফিকেশন টোকেন সঠিক নয় বা মেয়াদ শেষ হয়েছে।');
            redirect('/dashboard');
        }
        db()->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = ?')->execute([(int)$row['user_id']]);
        db()->prepare('UPDATE email_verifications SET verified_at = NOW() WHERE id = ?')->execute([(int)$row['id']]);
        flash('success', 'আপনার ইমেইল সফলভাবে ভেরিফাই হয়েছে।');
        redirect('/dashboard');
    }

    public function showForgot(): void
    {
        View::render('auth/forgot', ['pageTitle' => 'পাসওয়ার্ড পুনরুদ্ধার — AmarGazipur']);
    }

    public function sendReset(): void
    {
        CSRF::requireValid();
        $email = mb_strtolower(Validator::cleanString($_POST['email'] ?? '', 190));
        if (Validator::isEmail($email)) {
            $token = bin2hex(random_bytes(32));
            db()->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))')
                ->execute([$email, $token]);
            // Note: In cPanel PHP mail() or SMTP can send the link; store token securely
            $_SESSION['_demo_reset_link'] = url('/reset-password?token=' . $token . '&email=' . urlencode($email));
        }
        flash('success', 'যদি এই ইমেইলটি নিবন্ধিত থাকে, তবে পাসওয়ার্ড রিসেট নির্দেশনা পাঠানো হয়েছে।');
        redirect('/forgot-password');
    }

    public function showReset(): void
    {
        $token = Validator::cleanString($_GET['token'] ?? '', 128);
        $email = Validator::cleanString($_GET['email'] ?? '', 190);
        View::render('auth/reset', [
            'pageTitle' => 'নতুন পাসওয়ার্ড সেট করুন — AmarGazipur',
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function resetPassword(): void
    {
        CSRF::requireValid();
        $email = mb_strtolower(Validator::cleanString($_POST['email'] ?? '', 190));
        $token = Validator::cleanString($_POST['token'] ?? '', 128);
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if (strlen($password) < 8 || $password !== $confirm) {
            flash('error', 'নতুন পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে এবং মিলতে হবে।');
            redirect('/reset-password?token=' . urlencode($token) . '&email=' . urlencode($email));
        }

        $stmt = db()->prepare('SELECT * FROM password_resets WHERE email = ? AND token = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$email, $token]);
        $row = $stmt->fetch();
        if (!$row) {
            flash('error', 'রিসেট লিংকটি অবৈধ অথবা মেয়াদোত্তীর্ণ।');
            redirect('/forgot-password');
        }

        db()->prepare('UPDATE users SET password_hash = ? WHERE email = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $email]);
        db()->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([(int)$row['id']]);
        flash('success', 'পাসওয়ার্ড সফলভাবে পরিবর্তন হয়েছে। এখন লগইন করুন।');
        redirect('/login');
    }

    public function logout(): void
    {
        CSRF::requireValid();
        Auth::logout();
        redirect('/');
    }
}
