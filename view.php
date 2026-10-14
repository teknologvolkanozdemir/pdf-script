<?php
require __DIR__ . '/includes/bootstrap.php';
$s = db()->prepare('SELECT p.*, u.username FROM pdfs p JOIN users u ON u.id = p.user_id WHERE p.slug = ?');
$s->execute([(string)($_GET['slug'] ?? '')]);
$p = $s->fetch();
$u = current_user();
if (!$p || ($p['status'] !== 'published' && (!$u || ($u['role'] !== 'admin' && (int)$u['id'] !== (int)$p['user_id'])))) {
    http_response_code(404);
    page_header(['title' => 'Bulunamadı', 'robots' => 'noindex']);
    echo '<h1>PDF bulunamadı</h1><p><a href="' . e(base_path()) . '/">Ana sayfaya dön</a></p>';
    page_footer();
    exit;
}
if ($p['status'] === 'published') db()->prepare('UPDATE pdfs SET views = views + 1 WHERE id = ?')->execute([$p['id']]);
$url = base_url() . '/pdf/' . $p['slug'];
$file = base_path() . '/file.php?slug=' . urlencode($p['slug']);
page_header(['title' => $p['title'], 'description' => $p['description'] ?: $p['title'], 'canonical' => $url, 'type' => 'article',
    'robots' => $p['status'] === 'published' ? 'index,follow' : 'noindex,nofollow',
    'jsonld' => ['@context' => 'https://schema.org', '@type' => 'DigitalDocument', 'name' => $p['title'], 'description' => $p['description'],
        'datePublished' => substr($p['created_at'], 0, 10), 'encodingFormat' => 'application/pdf', 'url' => $url, 'author' => ['@type' => 'Person', 'name' => $p['username']]]]);
?>
<nav aria-label="Yol"><a href="<?= e(base_path()) ?>/">Ana sayfa</a> &rsaquo; <span aria-current="page"><?= e($p['title']) ?></span></nav>
<article>
<h1><?= e($p['title']) ?></h1>
<?php if ($p['status'] !== 'published'): ?><p class="alert error" role="status">Bu PDF henüz yayında değil (<?= e($p['status']) ?>).</p><?php endif; ?>
<p class="meta"><?= e(human_size((int)$p['size'])) ?> · <?= (int)$p['views'] ?> görüntülenme · <?= (int)$p['downloads'] ?> indirme · Yükleyen: <?= e($p['username']) ?></p>
<?php if ($p['description']): ?><p><?= nl2br(e($p['description'])) ?></p><?php endif; ?>
<p><a class="btn" href="<?= e($file) ?>&amp;dl=1">PDF'i indir</a></p>
<iframe class="viewer" src="<?= e($file) ?>" title="<?= e($p['title']) ?> PDF görüntüleyici">PDF görüntülenemiyor. <a href="<?= e($file) ?>&amp;dl=1">İndirin</a>.</iframe>
</article>
<?php page_footer();
