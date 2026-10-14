<?php
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\nDisallow: /admin/\nDisallow: /login.php\nDisallow: /register.php\nDisallow: /file.php\n\nSitemap: " . base_url() . "/sitemap.php\n";
