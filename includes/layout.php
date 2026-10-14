<?php
/**
 * $meta: title, description, canonical, robots, type, jsonld (array)
 */
function page_header(array $meta = []): void
{
    $site = setting('site_name', 'PDF Arşivi');
    $title = ($meta['title'] ?? '') !== '' ? $meta['title'] . ' | ' . $site : $site . ' - ' . setting('site_tagline', 'Ücretsiz PDF dosyaları');
    $desc = $meta['description'] ?? setting('site_description', 'PDF dosyalarını görüntüleyin ve indirin.');
    $desc = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($desc))), 0, 160);
    $canon = $meta['canonical'] ?? base_url() . strtok($_SERVER['REQUEST_URI'], '?');
    $robots = $meta['robots'] ?? 'index,follow';
    $u = current_user();
    $bp = base_path();
    $f = flash();
    ?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="robots" content="<?= e($robots) ?>">
<?php if (setting('site_keywords') !== ''): ?><meta name="keywords" content="<?= e(setting('site_keywords')) ?>"><?php endif; ?>

<link rel="canonical" href="<?= e($canon) ?>">
<meta property="og:site_name" content="<?= e($site) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:type" content="<?= e($meta['type'] ?? 'website') ?>">
<meta property="og:url" content="<?= e($canon) ?>">
<meta name="twitter:card" content="summary">
<link rel="stylesheet" href="<?= e($bp) ?>/assets/style.css">
<?php if (!empty($meta['jsonld'])): ?><script type="application/ld+json"><?= json_encode($meta['jsonld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script><?php endif; ?>
</head>
<body>
<a class="skip" href="#main">İçeriğe geç</a>
<header class="site-header">
  <div class="wrap">
    <a class="brand" href="<?= e($bp) ?>/"><?= e($site) ?></a>
    <nav aria-label="Ana menü">
      <ul>
        <li><a href="<?= e($bp) ?>/">Ana sayfa</a></li>
        <?php if ($u): ?>
          <li><a href="<?= e($bp) ?>/upload.php">PDF yükle</a></li>
          <li><a href="<?= e($bp) ?>/profile.php">Hesabım</a></li>
          <?php if ($u['role'] === 'admin'): ?><li><a href="<?= e($bp) ?>/admin/">Yönetim</a></li><?php endif; ?>
          <li><a href="<?= e($bp) ?>/logout.php">Çıkış</a></li>
        <?php else: ?>
          <li><a href="<?= e($bp) ?>/login.php">Giriş</a></li>
          <li><a href="<?= e($bp) ?>/register.php">Üye ol</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</header>
<main id="main" class="wrap" tabindex="-1">
<?php if ($f): ?><div class="alert <?= e($f[1]) ?>" role="<?= $f[1] === 'error' ? 'alert' : 'status' ?>"><?= e($f[0]) ?></div><?php endif; ?>
<?php
}

function page_footer(): void
{
    ?>
</main>
<footer class="site-footer"><div class="wrap"><p>&copy; <?= date('Y') ?> <?= e(setting('site_name', 'PDF Arşivi')) ?>. Yalnızca .pdf dosyaları yayınlanır.</p></div></footer>
</body>
</html>
<?php
}

function pdf_card(array $p): void
{
    $url = base_path() . '/pdf/' . $p['slug'];
    ?>
<article class="card">
  <h2><a href="<?= e($url) ?>"><?= e($p['title']) ?></a></h2>
  <p><?= e(mb_substr(strip_tags((string)$p['description']), 0, 140)) ?></p>
  <p class="meta"><?= e(human_size((int)$p['size'])) ?> · <?= (int)$p['downloads'] ?> indirme · <time datetime="<?= e(substr($p['created_at'], 0, 10)) ?>"><?= e(substr($p['created_at'], 0, 10)) ?></time></p>
</article>
<?php
}
