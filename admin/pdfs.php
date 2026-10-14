<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/nav.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)$_POST['id']; $a = $_POST['action'] ?? '';
    if (in_array($a, ['published', 'rejected', 'pending'], true)) {
        db()->prepare('UPDATE pdfs SET status = ? WHERE id = ?')->execute([$a, $id]);
        flash('Durum güncellendi.');
    } elseif ($a === 'delete') {
        $s = db()->prepare('SELECT file_key FROM pdfs WHERE id = ?'); $s->execute([$id]);
        if ($r = $s->fetch()) { @unlink(ROOT . '/storage/' . basename($r['file_key'])); db()->prepare('DELETE FROM pdfs WHERE id = ?')->execute([$id]); flash('PDF silindi.'); }
    }
    redirect('pdfs.php');
}
$rows = db()->query('SELECT p.*, u.username FROM pdfs p JOIN users u ON u.id = p.user_id ORDER BY p.status = \'pending\' DESC, p.id DESC LIMIT 200')->fetchAll();
admin_header("PDF'ler");
?>
<table><caption class="meta">Tüm PDF dosyaları</caption><thead><tr><th scope="col">Başlık</th><th scope="col">Yükleyen</th><th scope="col">Durum</th><th scope="col">İşlem</th></tr></thead><tbody>
<?php foreach ($rows as $p): ?><tr>
<td><a href="<?= e(base_path() . '/pdf/' . $p['slug']) ?>"><?= e($p['title']) ?></a></td><td><?= e($p['username']) ?></td><td><?= e($p['status']) ?></td>
<td><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
<?php if ($p['status'] !== 'published'): ?><button name="action" value="published" aria-label="<?= e($p['title']) ?> yayınla">Yayınla</button><?php endif; ?>
<?php if ($p['status'] !== 'rejected'): ?><button name="action" value="rejected" aria-label="<?= e($p['title']) ?> reddet">Reddet</button><?php endif; ?>
<button name="action" value="delete" aria-label="<?= e($p['title']) ?> sil">Sil</button></form></td></tr>
<?php endforeach; ?></tbody></table>
<?php page_footer();
