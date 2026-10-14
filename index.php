<?php
require __DIR__ . '/includes/bootstrap.php';
$per = max(1, (int)setting('per_page', '12'));
$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$where = "status = 'published'"; $args = [];
if ($q !== '') { $where .= ' AND (title LIKE ? OR description LIKE ?)'; $like = '%' . addcslashes($q, '%_\\') . '%'; $args = [$like, $like]; }
$c = db()->prepare("SELECT COUNT(*) FROM pdfs WHERE $where"); $c->execute($args);
$total = (int)$c->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page = min($page, $pages);
$s = db()->prepare("SELECT * FROM pdfs WHERE $where ORDER BY created_at DESC, id DESC LIMIT " . $per . ' OFFSET ' . (($page - 1) * $per));
$s->execute($args);
$rows = $s->fetchAll();
$meta = ['title' => $q !== '' ? 'Arama: ' . $q : '', 'robots' => $q !== '' ? 'noindex,follow' : 'index,follow',
    'canonical' => base_url() . '/' . ($page > 1 && $q === '' ? '?page=' . $page : ''),
    'jsonld' => ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => setting('site_name', 'PDF Arşivi'), 'url' => base_url() . '/',
        'potentialAction' => ['@type' => 'SearchAction', 'target' => base_url() . '/?q={q}', 'query-input' => 'required name=q']]];
page_header($meta);
?>
<h1><?= e(setting('site_name', 'PDF Arşivi')) ?></h1>
<form role="search" method="get" action="<?= e(base_path()) ?>/">
  <label for="q">PDF ara</label>
  <input id="q" type="search" name="q" value="<?= e($q) ?>">
  <button type="submit">Ara</button>
</form>
<h2><?= $q !== '' ? e($total . ' sonuç: ' . $q) : 'Son eklenen PDF dosyaları' ?></h2>
<?php if (!$rows): ?><p>Henüz PDF bulunamadı.</p><?php else: ?>
<div class="grid"><?php foreach ($rows as $p) pdf_card($p); ?></div>
<?php if ($pages > 1): ?>
<nav aria-label="Sayfalama"><ul class="pager">
<?php for ($i = 1; $i <= $pages; $i++): $href = '?' . http_build_query(array_filter(['q' => $q, 'page' => $i > 1 ? $i : null])); ?>
  <li><?php if ($i === $page): ?><span aria-current="page"><?= $i ?></span><?php else: ?><a href="<?= e($href === '?' ? './' : $href) ?>"><?= $i ?></a><?php endif; ?></li>
<?php endfor; ?>
</ul></nav>
<?php endif; endif;
page_footer();
