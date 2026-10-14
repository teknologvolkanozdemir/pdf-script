<?php
require __DIR__ . '/includes/bootstrap.php';
$u = require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'password') {
        if (!password_verify((string)$_POST['current'], $u['password'])) flash('Mevcut parola hatalı.', 'error');
        elseif (strlen((string)$_POST['new']) < 8) flash('Yeni parola en az 8 karakter olmalı.', 'error');
        else {
            db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash((string)$_POST['new'], PASSWORD_DEFAULT), $u['id']]);
            flash('Parola güncellendi.');
        }
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $s = db()->prepare('SELECT file_key FROM pdfs WHERE id = ? AND user_id = ?');
        $s->execute([(int)$_POST['id'], $u['id']]);
        if ($r = $s->fetch()) {
            @unlink(ROOT . '/storage/' . basename($r['file_key']));
            db()->prepare('DELETE FROM pdfs WHERE id = ? AND user_id = ?')->execute([(int)$_POST['id'], $u['id']]);
            flash('PDF silindi.');
        }
    }
    redirect(base_path() . '/profile.php');
}
$s = db()->prepare('SELECT * FROM pdfs WHERE user_id = ? ORDER BY id DESC');
$s->execute([$u['id']]);
$mine = $s->fetchAll();
page_header(['title' => 'Hesabım', 'robots' => 'noindex,follow']);
?>
<h1>Hesabım</h1>
<p><?= e($u['username']) ?> (<?= e($u['email']) ?>)</p>
<h2>PDF dosyalarım</h2>
<?php if (!$mine): ?><p>Henüz PDF yüklemediniz.</p><?php else: ?>
<table><caption class="meta">Yüklediğiniz PDF dosyaları</caption><thead><tr><th scope="col">Başlık</th><th scope="col">Durum</th><th scope="col">İşlem</th></tr></thead><tbody>
<?php foreach ($mine as $p): ?><tr><td><a href="<?= e(base_path() . '/pdf/' . $p['slug']) ?>"><?= e($p['title']) ?></a></td><td><?= e($p['status']) ?></td>
<td><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button type="submit" aria-label="<?= e($p['title']) ?> dosyasını sil">Sil</button></form></td></tr>
<?php endforeach; ?></tbody></table>
<?php endif; ?>
<h2>Parola değiştir</h2>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="password">
<label for="current">Mevcut parola</label><input id="current" name="current" type="password" required autocomplete="current-password">
<label for="new">Yeni parola</label><input id="new" name="new" type="password" required minlength="8" autocomplete="new-password">
<button type="submit">Güncelle</button></form>
<?php page_footer();
