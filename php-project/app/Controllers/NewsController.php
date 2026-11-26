<?php
declare(strict_types=1);

class NewsController
{
    public function index(): void
    {
        $pdo = db();
        $catSlug = Validator::cleanString($_GET['cat'] ?? '', 80);
        $q = Validator::cleanString($_GET['q'] ?? '', 120);

        $where = ["n.status = 'published'"];
        $params = [];
        if ($catSlug !== '') {
            $where[] = 'c.slug = ?';
            $params[] = $catSlug;
        }
        if ($q !== '') {
            $where[] = '(n.title LIKE ? OR n.summary LIKE ?)';
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        $whereSql = implode(' AND ', $where);

        $stmt = $pdo->prepare("
            SELECT n.*, c.name_bn AS category_bn, c.name_en AS category_en, c.slug AS category_slug, u.full_name AS author_name
            FROM news n
            JOIN news_categories c ON c.id = n.category_id
            JOIN users u ON u.id = n.author_id
            WHERE $whereSql
            ORDER BY n.is_featured DESC, n.created_at DESC
            LIMIT 24
        ");
        $stmt->execute($params);

        $popular = $pdo->query("
            SELECT id, slug, title, views_count, created_at
            FROM news WHERE status = 'published'
            ORDER BY views_count DESC LIMIT 5
        ")->fetchAll();

        $categories = $pdo->query('SELECT * FROM news_categories ORDER BY id ASC')->fetchAll();

        View::render('news/index', [
            'pageTitle' => __('news') . ' — AmarGazipur',
            'articles' => $stmt->fetchAll(),
            'popular' => $popular,
            'categories' => $categories,
            'catSlug' => $catSlug,
            'q' => $q,
        ]);
    }

    public function show(string $slug): void
    {
        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT n.*, c.name_bn AS category_bn, c.slug AS category_slug, u.full_name AS author_name, u.bio AS author_bio, u.upazila AS author_upazila
            FROM news n
            JOIN news_categories c ON c.id = n.category_id
            JOIN users u ON u.id = n.author_id
            WHERE n.slug = ? AND n.status = 'published' LIMIT 1
        ");
        $stmt->execute([$slug]);
        $article = $stmt->fetch();
        if (!$article) {
            http_response_code(404);
            View::render('errors/404', ['pageTitle' => 'সংবাদ পাওয়া যায়নি']);
            return;
        }

        $pdo->prepare('UPDATE news SET views_count = views_count + 1 WHERE id = ?')->execute([(int)$article['id']]);

        $relStmt = $pdo->prepare("
            SELECT id, slug, title, featured_image, created_at
            FROM news
            WHERE category_id = ? AND id != ? AND status = 'published'
            ORDER BY created_at DESC LIMIT 3
        ");
        $relStmt->execute([(int)$article['category_id'], (int)$article['id']]);

        View::render('news/show', [
            'pageTitle' => ($article['meta_title'] ?: $article['title']) . ' — AmarGazipur',
            'metaDesc' => $article['meta_description'] ?: $article['summary'],
            'article' => $article,
            'related' => $relStmt->fetchAll(),
        ]);
    }

    public function store(): void
    {
        $user = Auth::requireLogin();
        CSRF::requireValid();

        if (!Auth::hasApprovedCategory('journalist') && !Auth::hasRole(['admin', 'editor'])) {
            flash('error', 'সংবাদ জমা দিতে অনুমোদিত সাংবাদিক (Journalist) অ্যাকাউন্ট প্রয়োজন।');
            redirect('/news');
        }

        $title = Validator::cleanString($_POST['title'] ?? '', 255);
        $summary = Validator::cleanString($_POST['summary'] ?? '', 500);
        $content = Validator::cleanString($_POST['content'] ?? '', 20000);
        $categoryId = (int)($_POST['category_id'] ?? 1);
        $upazila = Validator::cleanString($_POST['upazila'] ?? 'Gazipur Sadar', 80);

        if ($title === '' || $summary === '' || $content === '') {
            flash('error', 'সংবাদের শিরোনাম, সারাংশ ও বিস্তারিত প্রদান করুন।');
            redirect('/news');
        }

        $imagePath = null;
        if (!empty($_FILES['featured_image'])) {
            try {
                $imagePath = Uploader::uploadImage($_FILES['featured_image'], 'news');
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
                redirect('/news');
            }
        }

        // CRITICAL BUSINESS RULE: Journalist submissions ALWAYS remain 'pending' until Admin/Editor approves
        $stmt = db()->prepare("
            INSERT INTO news (category_id, author_id, slug, title, summary, content, featured_image, meta_title, meta_description, upazila, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $categoryId,
            (int)$user['id'],
            slugify($title),
            $title,
            $summary,
            $content,
            $imagePath,
            $title,
            mb_substr($summary, 0, 160, 'UTF-8'),
            $upazila,
        ]);

        flash('success', 'আপনার সংবাদটি সফলভাবে জমা হয়েছে এবং অ্যাডমিন/সম্পাদকের অনুমোদনের জন্য অপেক্ষমাণ (Pending) রয়েছে।');
        redirect('/news');
    }
}
