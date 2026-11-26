<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Automatically initialize MySQL tables and sample Gazipur content on first visit
Database::ensureSchemaInitialized(__DIR__);

require_once APP_PATH . '/Core/Helpers.php';
require_once APP_PATH . '/Core/I18n.php';
require_once APP_PATH . '/Core/CSRF.php';
require_once APP_PATH . '/Core/Auth.php';
require_once APP_PATH . '/Core/Validator.php';
require_once APP_PATH . '/Core/Moderation.php';
require_once APP_PATH . '/Core/Uploader.php';
require_once APP_PATH . '/Core/RateLimiter.php';
require_once APP_PATH . '/Core/View.php';

foreach (glob(APP_PATH . '/Controllers/*.php') as $ctrlFile) {
    require_once $ctrlFile;
}

Auth::startSession();
I18n::init();

$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$rawUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($scriptDir !== '' && str_starts_with($rawUri, $scriptDir)) {
    $rawUri = substr($rawUri, strlen($scriptDir)) ?: '/';
}
$path = '/' . trim($rawUri, '/');
if ($path !== '/') {
    $path = rtrim($path, '/');
}
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// First-run Admin Claim (if no Admin account exists yet when visiting /admin)
if ($path === '/admin' || str_starts_with($path, '/admin/')) {
    $adminCount = (int)db()->query('SELECT COUNT(*) FROM users WHERE role_id = 1')->fetchColumn();
    if ($adminCount === 0) {
        if ($method === 'POST' && $path === '/admin/setup') {
            CSRF::requireValid();
            $name = Validator::cleanString($_POST['full_name'] ?? '', 150);
            $email = mb_strtolower(Validator::cleanString($_POST['email'] ?? '', 190));
            $pass = (string)($_POST['password'] ?? '');
            if ($name === '' || !Validator::isEmail($email) || strlen($pass) < 8) {
                flash('error', 'সঠিক নাম, ইমেইল এবং কমপক্ষে ৮ অক্ষরের পাসওয়ার্ড দিন।');
                redirect('/admin');
            }
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            db()->prepare('INSERT INTO users (role_id, full_name, email, password_hash, upazila, status, email_verified_at) VALUES (1, ?, ?, ?, "Gazipur Sadar", "active", NOW())')
                ->execute([$name, $email, $hash]);
            $newAdminId = (int)db()->lastInsertId();
            db()->prepare('INSERT IGNORE INTO user_categories (user_id, category_slug, status) VALUES (?, "general_user", "approved")')->execute([$newAdminId]);
            Auth::loginUser($newAdminId);
            flash('success', 'প্রধান অ্যাডমিন অ্যাকাউন্ট তৈরি সম্পন্ন হয়েছে!');
            redirect('/admin');
        }
        View::render('admin/setup', ['pageTitle' => 'প্রথম অ্যাডমিন অ্যাকাউন্ট সেটআপ — AmarGazipur']);
        exit;
    }
}

// Route Dispatch
if ($path === '/' && $method === 'GET') {
    (new HomeController())->index();
} elseif ($path === '/login' && $method === 'GET') {
    (new AuthController())->showLogin();
} elseif ($path === '/login' && $method === 'POST') {
    (new AuthController())->login();
} elseif ($path === '/register' && $method === 'GET') {
    (new AuthController())->showRegister();
} elseif ($path === '/register' && $method === 'POST') {
    (new AuthController())->register();
} elseif ($path === '/logout' && $method === 'POST') {
    (new AuthController())->logout();
} elseif ($path === '/verify-email' && $method === 'GET') {
    (new AuthController())->verifyEmail();
} elseif ($path === '/forgot-password' && $method === 'GET') {
    (new AuthController())->showForgot();
} elseif ($path === '/forgot-password' && $method === 'POST') {
    (new AuthController())->sendReset();
} elseif ($path === '/reset-password' && $method === 'GET') {
    (new AuthController())->showReset();
} elseif ($path === '/reset-password' && $method === 'POST') {
    (new AuthController())->resetPassword();
} elseif ($path === '/community' && $method === 'GET') {
    (new CommunityController())->index();
} elseif ($path === '/community/store' && $method === 'POST') {
    (new CommunityController())->store();
} elseif ($path === '/community/like' && $method === 'POST') {
    (new CommunityController())->toggleLike();
} elseif ($path === '/community/comment' && $method === 'POST') {
    (new CommunityController())->comment();
} elseif ($path === '/community/reply' && $method === 'POST') {
    (new CommunityController())->reply();
} elseif ($path === '/community/delete' && $method === 'POST') {
    (new CommunityController())->delete();
} elseif ($path === '/report' && $method === 'POST') {
    (new CommunityController())->report();
} elseif ($path === '/news' && $method === 'GET') {
    (new NewsController())->index();
} elseif ($path === '/news/store' && $method === 'POST') {
    (new NewsController())->store();
} elseif (preg_match('#^/news/([a-zA-Z0-9\-_]+)$#', $path, $m) && $method === 'GET') {
    (new NewsController())->show($m[1]);
} elseif ($path === '/marketplace' && $method === 'GET') {
    (new MarketplaceController())->index();
} elseif ($path === '/marketplace/store' && $method === 'POST') {
    (new MarketplaceController())->store();
} elseif ($path === '/rentals' && $method === 'GET') {
    (new RentalController())->index();
} elseif ($path === '/rentals/store' && $method === 'POST') {
    (new RentalController())->store();
} elseif ($path === '/jobs' && $method === 'GET') {
    (new JobController())->index();
} elseif ($path === '/jobs/store' && $method === 'POST') {
    (new JobController())->store();
} elseif ($path === '/education' && $method === 'GET') {
    (new EducationController())->index();
} elseif ($path === '/education/store' && $method === 'POST') {
    (new EducationController())->store();
} elseif ($path === '/blood-donors' && $method === 'GET') {
    (new BloodController())->index();
} elseif ($path === '/blood-donors/register' && $method === 'POST') {
    (new BloodController())->registerDonor();
} elseif ($path === '/blood-donors/request' && $method === 'POST') {
    (new BloodController())->storeRequest();
} elseif ($path === '/businesses' && $method === 'GET') {
    (new BusinessController())->index();
} elseif ($path === '/businesses/store' && $method === 'POST') {
    (new BusinessController())->store();
} elseif ($path === '/dashboard' && $method === 'GET') {
    (new DashboardController())->index();
} elseif ($path === '/dashboard/profile' && $method === 'POST') {
    (new DashboardController())->updateProfile();
} elseif ($path === '/dashboard/password' && $method === 'POST') {
    (new DashboardController())->updatePassword();
} elseif ($path === '/dashboard/notifications/read' && $method === 'POST') {
    (new DashboardController())->markNotificationsRead();
} elseif ($path === '/admin' && $method === 'GET') {
    (new AdminController())->index();
} elseif ($path === '/admin/create' && $method === 'GET') {
    (new AdminController())->createContent();
} elseif ($path === '/admin/create' && $method === 'POST') {
    (new AdminController())->storeContent();
} elseif ($path === '/admin/content' && $method === 'GET') {
    (new AdminController())->content();
} elseif ($path === '/admin/content/delete' && $method === 'POST') {
    (new AdminController())->deleteContent();
} elseif ($path === '/admin/users' && $method === 'GET') {
    (new AdminController())->users();
} elseif ($path === '/admin/users/status' && $method === 'POST') {
    (new AdminController())->updateUserStatus();
} elseif ($path === '/admin/users/category' && $method === 'POST') {
    (new AdminController())->reviewCategory();
} elseif ($path === '/admin/users/categories-bulk' && $method === 'POST') {
    (new AdminController())->saveUserCategoriesBulk();
} elseif ($path === '/admin/news' && $method === 'GET') {
    (new AdminController())->newsQueue();
} elseif ($path === '/admin/news/review' && $method === 'POST') {
    (new AdminController())->reviewNews();
} elseif ($path === '/admin/moderation' && $method === 'GET') {
    (new AdminController())->moderation();
} elseif ($path === '/admin/moderation/post' && $method === 'POST') {
    (new AdminController())->reviewPost();
} elseif ($path === '/admin/moderation/rule-add' && $method === 'POST') {
    (new AdminController())->addRule();
} elseif ($path === '/admin/moderation/rule-delete' && $method === 'POST') {
    (new AdminController())->deleteRule();
} elseif ($path === '/admin/moderation/report' && $method === 'POST') {
    (new AdminController())->resolveReport();
} elseif ($path === '/admin/ads' && $method === 'GET') {
    (new AdminController())->ads();
} elseif ($path === '/admin/ads/store' && $method === 'POST') {
    (new AdminController())->storeAd();
} elseif ($path === '/admin/settings' && $method === 'GET') {
    (new AdminController())->settings();
} elseif ($path === '/admin/settings' && $method === 'POST') {
    (new AdminController())->saveSettings();
} elseif ($path === '/sitemap.xml') {
    (new SeoController())->sitemap();
} elseif ($path === '/robots.txt') {
    (new SeoController())->robots();
} else {
    http_response_code(404);
    View::render('errors/404', ['pageTitle' => 'পৃষ্ঠা পাওয়া যায়নি — AmarGazipur']);
}
