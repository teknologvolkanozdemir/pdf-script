<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/nav.php';
$me = require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)$_POST['id']; $a = $_POST['action'] ?? '';
    if ($id === (int)$me['id']) { flash('Kendi hesabınızı değiştiremezsiniz.', 'error'); redirect('users.php'); }
    if ($a === 'ban' || $a === 'unban') db()->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$a === 'ban' ? 'banned' : 'active', $id]);
    elseif ($a === 'admin' || $a === 'user') db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$a, $id]);
    elseif ($a === 'delete') {
        $s = db()->prepare('SELECT file_key FROM pdfs WHERE user_id = ?'); $s->execute([$id]);
        foreach ($s as $r) @unlink(ROOT . '/storage/' . basename($r['file_key']));
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }
    flash('Üye güncellendi.');
    redirect('users.php');
}
$rows = db()->query('SELECT * FROM users ORDER BY id DESC LIMIT 200')->fetchAll();
admin_header('Üyeler');
?>
<table><caption class="meta">Kayıtlı üyeler</caption><thead><tr><th scope="col">Kullanıcı</th><th scope="col">E-posta</th><th scope="col">Rol</th><th scope="col">Durum</th><th scope="col">İşlem</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['username']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['role']) ?></td><td><?= e($r['status']) ?></td>
<td><?php if ((int)$r['id'] !== (int)$me['id']): ?><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
<button name="action" value="<?= $r['status'] === 'active' ? 'ban' : 'unban' ?>" aria-label="<?= e($r['username']) ?> için <?= $r['status'] === 'active' ? 'engelle' : 'engeli kaldır' ?>"><?= $r['status'] === 'active' ? 'Engelle' : 'Engeli kaldır' ?></button>
<button name="action" value="<?= $r['role'] === 'admin' ? 'user' : 'admin' ?>" aria-label="<?= e($r['username']) ?> rolünü değiştir"><?= $r['role'] === 'admin' ? 'Yöneticiliği al' : 'Yönetici yap' ?></button>
<button name="action" value="delete" aria-label="<?= e($r['username']) ?> sil" onclick="return confirm('Üye ve PDF dosyaları silinsin mi?')">Sil</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table>
<?php page_footer();
