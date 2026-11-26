<?php
declare(strict_types=1);

class HomeController
{
    public function index(): void
    {
        $pdo = db();

        $featuredNews = $pdo->query("
            SELECT n.*, c.name_bn AS category_bn, c.name_en AS category_en, u.full_name AS author_name
            FROM news n
            JOIN news_categories c ON c.id = n.category_id
            JOIN users u ON u.id = n.author_id
            WHERE n.status = 'published'
            ORDER BY n.is_featured DESC, n.created_at DESC
            LIMIT 4
        ")->fetchAll();

        $latestPosts = $pdo->query("
            SELECT p.*, u.full_name AS author_name,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS likes_count,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'published') AS comments_count,
                   (SELECT image_path FROM post_images WHERE post_id = p.id ORDER BY sort_order ASC LIMIT 1) AS cover_image
            FROM posts p
            JOIN users u ON u.id = p.user_id
            WHERE p.status = 'published'
            ORDER BY p.created_at DESC
            LIMIT 4
        ")->fetchAll();

        $latestRentals = $pdo->query("
            SELECT * FROM rental_listings
            WHERE status = 'published'
            ORDER BY created_at DESC
            LIMIT 3
        ")->fetchAll();

        $latestMarket = $pdo->query("
            SELECT m.*, c.name_bn AS category_bn
            FROM marketplace_listings m
            JOIN marketplace_categories c ON c.id = m.category_id
            WHERE m.status = 'published'
            ORDER BY m.created_at DESC
            LIMIT 4
        ")->fetchAll();

        $openBloodRequests = $pdo->query("
            SELECT * FROM blood_requests
            WHERE status = 'open'
            ORDER BY created_at DESC
            LIMIT 3
        ")->fetchAll();

        $latestJobs = $pdo->query("
            SELECT * FROM job_listings
            WHERE status = 'published'
            ORDER BY created_at DESC
            LIMIT 4
        ")->fetchAll();

        $activeAds = $pdo->query("
            SELECT * FROM advertisements
            WHERE status = 'active' AND CURDATE() BETWEEN start_date AND end_date
            ORDER BY id DESC LIMIT 2
        ")->fetchAll();

        View::render('home', [
            'pageTitle' => get_setting('seo_meta_title', 'AmarGazipur — গাজীপুর জেলার ডিজিটাল কমিউনিটি'),
            'metaDesc' => get_setting('seo_meta_description', ''),
            'featuredNews' => $featuredNews,
            'latestPosts' => $latestPosts,
            'latestRentals' => $latestRentals,
            'latestJobs' => $latestJobs,
            'latestMarket' => $latestMarket,
            'openBloodRequests' => $openBloodRequests,
            'activeAds' => $activeAds,
        ]);
    }
}
