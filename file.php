<?php
require __DIR__ . '/includes/bootstrap.php';
$s = db()->prepare('SELECT * FROM pdfs WHERE slug = ?');
$s->execute([(string)($_GET['slug'] ?? '')]);
$p = $s->fetch();
$u = current_user();
$path = $p ? ROOT . '/storage/' . basename($p['file_key']) : '';
if (!$p || !is_file($path) || ($p['status'] !== 'published' && (!$u || ($u['role'] !== 'admin' && (int)$u['id'] !== (int)$p['user_id'])))) {
    http_response_code(404);
    exit('Bulunamadı');
}
$dl = isset($_GET['dl']);
if ($dl && $p['status'] === 'published') db()->prepare('UPDATE pdfs SET downloads = downloads + 1 WHERE id = ?')->execute([$p['id']]);
session_write_close();
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($dl ? 'attachment' : 'inline') . '; filename="' . $p['slug'] . '.pdf"');
header('X-Robots-Tag: noindex');
readfile($path);
