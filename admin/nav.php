<?php
function admin_header(string $title): void
{
    page_header(['title' => 'Yönetim: ' . $title, 'robots' => 'noindex,nofollow']);
    $bp = base_path() . '/admin';
    ?>
<nav aria-label="Yönetim menüsü"><ul>
<li><a href="<?= e($bp) ?>/">Panel</a></li><li><a href="<?= e($bp) ?>/pdfs.php">PDF'ler</a></li>
<li><a href="<?= e($bp) ?>/users.php">Üyeler</a></li><li><a href="<?= e($bp) ?>/settings.php">Ayarlar</a></li></ul></nav>
<h1><?= e($title) ?></h1>
<?php
}
