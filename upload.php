<?php
require __DIR__ . '/includes/bootstrap.php';
$u = require_login();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title = trim((string)$_POST['title']);
    try {
        if ($title === '' || mb_strlen($title) > 190) throw new RuntimeException('Başlık zorunludur (en fazla 190 karakter).');
        [$file_key, $size] = store_pdf($_FILES['pdf'] ?? []);
        $status = ($u['role'] === 'admin' || setting('auto_publish', '0') === '1') ? 'published' : 'pending';
        db()->prepare('INSERT INTO pdfs (user_id,title,slug,description,file_key,size,status,created_at) VALUES (?,?,?,?,?,?,?,NOW())')
            ->execute([$u['id'], $title, unique_slug($title), trim((string)$_POST['description']), $file_key, $size, $status]);
        flash($status === 'published' ? 'PDF yayınlandı.' : 'PDF yüklendi, yönetici onayından sonra yayınlanacak.');
        redirect(base_path() . '/profile.php');
    } catch (RuntimeException $ex) {
        $err = $ex->getMessage();
    }
}
page_header(['title' => 'PDF yükle', 'robots' => 'noindex,follow']);
?>
<h1>PDF yükle</h1>
<?php if ($err): ?><div class="alert error" role="alert"><?= e($err) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data"><?= csrf_field() ?>
<label for="title">Başlık</label><input id="title" name="title" required maxlength="190" value="<?= e($_POST['title'] ?? '') ?>">
<label for="description">Açıklama</label><textarea id="description" name="description" rows="5"><?= e($_POST['description'] ?? '') ?></textarea>
<label for="pdf">PDF dosyası (yalnızca .pdf, en fazla <?= (int)setting('max_upload_mb', '20') ?> MB)</label>
<input id="pdf" name="pdf" type="file" accept=".pdf,application/pdf" required>
<button type="submit">Yükle</button></form>
<?php page_footer();
