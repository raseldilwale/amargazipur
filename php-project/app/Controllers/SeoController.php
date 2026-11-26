<?php
declare(strict_types=1);

class SeoController
{
    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        $urls = [
            url('/'),
            url('/news'),
            url('/community'),
            url('/marketplace'),
            url('/rentals'),
            url('/jobs'),
            url('/education'),
            url('/blood-donors'),
            url('/businesses'),
        ];

        $newsRows = db()->query("SELECT slug, updated_at FROM news WHERE status = 'published' ORDER BY created_at DESC LIMIT 200")->fetchAll();
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $u) {
            echo '<url><loc>' . e($u) . '</loc><changefreq>daily</changefreq><priority>0.8</priority></url>';
        }
        foreach ($newsRows as $row) {
            echo '<url><loc>' . e(url('/news/' . $row['slug'])) . '</loc><lastmod>' . date('c', strtotime($row['updated_at'])) . '</lastmod><priority>0.7</priority></url>';
        }
        echo '</urlset>';
        exit;
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n";
        echo "Disallow: /admin/\n";
        echo "Disallow: /dashboard/\n";
        echo "Disallow: /install/\n";
        echo "Sitemap: " . url('/sitemap.xml') . "\n";
        exit;
    }
}
