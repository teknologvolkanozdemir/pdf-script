<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/nav.php';
require_admin();
$n = fn($sql) => (int)db()->query($sql)->fetchColumn();
admin_header('Panel');
?>
<table><caption class="meta">Site istatistikleri</caption><tbody>
<tr><th scope="row">Üye sayısı</th><td><?= $n('SELECT COUNT(*) FROM users') ?></td></tr>
<tr><th scope="row">Yayındaki PDF</th><td><?= $n("SELECT COUNT(*) FROM pdfs WHERE status='published'") ?></td></tr>
<tr><th scope="row">Onay bekleyen PDF</th><td><?= $n("SELECT COUNT(*) FROM pdfs WHERE status='pending'") ?></td></tr>
<tr><th scope="row">Toplam indirme</th><td><?= $n('SELECT COALESCE(SUM(downloads),0) FROM pdfs') ?></td></tr>
</tbody></table>
<?php page_footer();
