<?php
declare(strict_types=1);

class CommunityController
{
    public function index(): void
    {
        $pdo = db();
        $q = Validator::cleanString($_GET['q'] ?? '', 120);
        $category = Validator::cleanString($_GET['category'] ?? '', 80);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $where = ["p.status = 'published'"];
        $params = [];
        if ($q !== '') {
            $where[] = '(p.title LIKE ? OR p.content LIKE ?)';
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        if ($category !== '') {
            $where[] = 'p.category = ?';
            $params[] = $category;
        }
        $whereSql = implode(' AND ', $where);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM posts p WHERE $whereSql");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "
            SELECT p.*, u.full_name AS author_name,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS likes_count,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'published') AS comments_count
            FROM posts p
            JOIN users u ON u.id = p.user_id
            WHERE $whereSql
            ORDER BY p.created_at DESC
            LIMIT $perPage OFFSET $offset
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $posts = $stmt->fetchAll();

        $userId = Auth::user()['id'] ?? 0;
        foreach ($posts as &$post) {
            $imgStmt = $pdo->prepare('SELECT image_path FROM post_images WHERE post_id = ? ORDER BY sort_order ASC');
            $imgStmt->execute([(int)$post['id']]);
            $post['images'] = array_column($imgStmt->fetchAll(), 'image_path');

            $cmtStmt = $pdo->prepare("
                SELECT c.*, u.full_name AS author_name
                FROM comments c
                JOIN users u ON u.id = c.user_id
                WHERE c.post_id = ? AND c.status = 'published'
                ORDER BY c.created_at ASC
            ");
            $cmtStmt->execute([(int)$post['id']]);
            $comments = $cmtStmt->fetchAll();
            foreach ($comments as &$c) {
                $rStmt = $pdo->prepare("
                    SELECT cr.*, u.full_name AS author_name
                    FROM comment_replies cr
                    JOIN users u ON u.id = cr.user_id
                    WHERE cr.comment_id = ? AND cr.status = 'published'
                    ORDER BY cr.created_at ASC
                ");
                $rStmt->execute([(int)$c['id']]);
                $c['replies'] = $rStmt->fetchAll();
            }
            $post['comments'] = $comments;

            $post['liked_by_me'] = false;
            if ($userId) {
                $lStmt = $pdo->prepare('SELECT 1 FROM likes WHERE post_id = ? AND user_id = ?');
                $lStmt->execute([(int)$post['id'], (int)$userId]);
                $post['liked_by_me'] = (bool)$lStmt->fetchColumn();
            }
        }

        View::render('community/index', [
            'pageTitle' => __('community') . ' — AmarGazipur',
            'posts' => $posts,
            'q' => $q,
            'category' => $category,
            'page' => $page,
            'totalPages' => max(1, (int)ceil($total / $perPage)),
        ]);
    }

    public function store(): void
    {
        $user = Auth::requireLogin();
        CSRF::requireValid();

        $title = Validator::cleanString($_POST['title'] ?? '', 220);
        $content = Validator::cleanString($_POST['content'] ?? '', 5000);
        $category = Validator::cleanString($_POST['category'] ?? 'সাধারণ আলোচনা', 80);
        $upazila = Validator::cleanString($_POST['upazila'] ?? 'Gazipur Sadar', 80);

        if ($title === '' || $content === '') {
            flash('error', 'পোস্টের শিরোনাম ও বিস্তারিত অবশ্যই লিখতে হবে।');
            redirect('/community');
        }

        $mod = Moderation::checkContent($title . "\n" . $content);
        $status = $mod['flagged'] ? 'pending_moderation' : 'published';
        $reason = $mod['flagged'] ? ('নিষিদ্ধ শব্দ বা নীতিমালা যাচাই: ' . $mod['keyword']) : null;

        $pdo = db();
        $stmt = $pdo->prepare('INSERT INTO posts (user_id, category, upazila, title, content, status, moderation_reason) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([(int)$user['id'], $category, $upazila, $title, $content, $status, $reason]);
        $postId = (int)$pdo->lastInsertId();

        if (!empty($_FILES['images'])) {
            try {
                $paths = Uploader::uploadMultiple($_FILES['images'], 'posts', 4);
                $imgStmt = $pdo->prepare('INSERT INTO post_images (post_id, image_path, sort_order) VALUES (?, ?, ?)');
                foreach ($paths as $idx => $path) {
                    $imgStmt->execute([$postId, $path, $idx]);
                }
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
            }
        }

        if ($mod['flagged']) {
            Moderation::log('post', $postId, (int)$user['id'], $mod['keyword'], 'flagged_pending', 'Auto-flagged on submission');
            flash('success', 'আপনার পোস্টটি মডারেশন পর্যালোচনার জন্য জমা হয়েছে। অনুমোদনের পর প্রকাশিত হবে।');
        } else {
            flash('success', 'আপনার পোস্ট সফলভাবে প্রকাশিত হয়েছে।');
        }
        redirect('/community');
    }

    public function toggleLike(): void
    {
        $user = Auth::requireLogin();
        CSRF::requireValid();
        $postId = (int)($_POST['post_id'] ?? 0);
        $pdo = db();

        $exists = $pdo->prepare('SELECT id FROM likes WHERE post_id = ? AND user_id = ?');
        $exists->execute([$postId, (int)$user['id']]);
        if ($like = $exists->fetch()) {
            $pdo->prepare('DELETE FROM likes WHERE id = ?')->execute([(int)$like['id']]);
            $liked = false;
        } else {
            $pdo->prepare('INSERT IGNORE INTO likes (post_id, user_id) VALUES (?, ?)')->execute([$postId, (int)$user['id']]);
            $liked = true;
        }

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE post_id = ?');
        $countStmt->execute([$postId]);
        $count = (int)$countStmt->fetchColumn();

        if (is_json_request()) {
            header('Content-Type: application/json');
            echo json_encode(['liked' => $liked, 'count' => $count]);
            exit;
        }
        redirect('/community');
    }

    public function comment(): void
    {
        $user = Auth::requireLogin();
        CSRF::requireValid();
        $postId = (int)($_POST['post_id'] ?? 0);
        $body = Validator::cleanString($_POST['body'] ?? '', 1500);
        if ($postId > 0 && $body !== '') {
            $mod = Moderation::checkContent($body);
            $status = $mod['flagged'] ? 'pending_moderation' : 'published';
            db()->prepare('INSERT INTO comments (post_id, user_id, body, status) VALUES (?, ?, ?, ?)')
                ->execute([$postId, (int)$user['id'], $body, $status]);
            if ($mod['flagged']) {
                flash('success', 'আপনার মন্তব্যটি পর্যালোচনার জন্য অপেক্ষমাণ রয়েছে।');
            }
        }
        redirect('/community');
    }

    public function reply(): void
    {
        $user = Auth::requireLogin();
        CSRF::requireValid();
        $commentId = (int)($_POST['comment_id'] ?? 0);
        $body = Validator::cleanString($_POST['body'] ?? '', 1000);
        if ($commentId > 0 && $body !== '') {
            db()->prepare('INSERT INTO comment_replies (comment_id, user_id, body) VALUES (?, ?, ?)')
                ->execute([$commentId, (int)$user['id'], $body]);
        }
        redirect('/community');
    }

    public function delete(): void
    {
        $user = Auth::requireLogin();
        CSRF::requireValid();
        $postId = (int)($_POST['post_id'] ?? 0);
        $stmt = db()->prepare('SELECT user_id FROM posts WHERE id = ?');
        $stmt->execute([$postId]);
        $post = $stmt->fetch();
        if ($post && ((int)$post['user_id'] === (int)$user['id'] || Auth::canAccessAdmin())) {
            db()->prepare('DELETE FROM posts WHERE id = ?')->execute([$postId]);
            flash('success', 'পোস্টটি মুছে ফেলা হয়েছে।');
        }
        redirect('/community');
    }

    public function report(): void
    {
        $user = Auth::requireLogin();
        CSRF::requireValid();
        $targetType = Validator::cleanString($_POST['target_type'] ?? 'post', 30);
        $targetId = (int)($_POST['target_id'] ?? 0);
        $reason = Validator::cleanString($_POST['reason'] ?? 'অনুপযুক্ত কনটেন্ট', 120);
        $details = Validator::cleanString($_POST['details'] ?? '', 1000);
        if ($targetId > 0) {
            db()->prepare('INSERT INTO reports (reporter_id, target_type, target_id, reason, details) VALUES (?, ?, ?, ?, ?)')
                ->execute([(int)$user['id'], $targetType, $targetId, $reason, $details]);
            flash('success', 'রিপোর্টটি সফলভাবে অ্যাডমিন মডারেশন টিমে পাঠানো হয়েছে।');
        }
        redirect($_SERVER['HTTP_REFERER'] ?? '/community');
    }
}
