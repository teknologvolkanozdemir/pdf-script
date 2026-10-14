<?php
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
echo '<url><loc>' . e(base_url() . '/') . '</loc></url>' . "\n";
foreach (db()->query("SELECT slug, created_at FROM pdfs WHERE status = 'published' ORDER BY id DESC LIMIT 50000") as $r) {
    echo '<url><loc>' . e(base_url() . '/pdf/' . $r['slug']) . '</loc><lastmod>' . e(substr($r['created_at'], 0, 10)) . "</lastmod></url>\n";
}
echo '</urlset>';
