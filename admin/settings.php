<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/nav.php';
$me = require_admin();
$text = ['site_name', 'site_tagline', 'site_description', 'site_keywords', 'site_url', 'per_page', 'max_upload_mb',
    'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_user', 'smtp_from', 'smtp_from_name'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'test') {
        $r = send_mail($me['email'], 'SMTP test', 'SMTP ayarlarınız çalışıyor.');
        $r === true ? flash('Test e-postası ' . $me['email'] . ' adresine gönderildi.') : flash('SMTP hatası: ' . $r, 'error');
    } else {
        foreach ($text as $k) save_setting($k, trim((string)($_POST[$k] ?? '')));
        save_setting('per_page', (string)max(1, min(100, (int)$_POST['per_page'])));
        save_setting('max_upload_mb', (string)max(1, min(500, (int)$_POST['max_upload_mb'])));
        if (!in_array($_POST['smtp_encryption'] ?? '', ['tls', 'ssl', 'none'], true)) save_setting('smtp_encryption', 'tls');
        save_setting('registration', isset($_POST['registration']) ? '1' : '0');
        save_setting('auto_publish', isset($_POST['auto_publish']) ? '1' : '0');
        if ((string)($_POST['smtp_pass'] ?? '') !== '') save_setting('smtp_pass', (string)$_POST['smtp_pass']);
        flash('Ayarlar kaydedildi.');
    }
    redirect('settings.php');
}
admin_header('Ayarlar');
$f = fn($k, $label, $type = 'text', $d = '') => print('<label for="' . $k . '">' . e($label) . '</label><input id="' . $k . '" name="' . $k . '" type="' . $type . '" value="' . e(setting($k, $d)) . '">');
?>
<form method="post"><?= csrf_field() ?>
<fieldset><legend>Site ve SEO</legend>
<?php $f('site_name', 'Site adı', 'text', 'PDF Arşivi'); $f('site_tagline', 'Slogan (başlık eki)'); $f('site_description', 'Meta açıklama (en fazla 160 karakter)'); $f('site_keywords', 'Anahtar kelimeler'); $f('site_url', 'Site adresi (https://ornek.com, boşsa otomatik)', 'url'); ?>
</fieldset>
<fieldset><legend>Yayın</legend>
<?php $f('per_page', 'Sayfa başına PDF', 'number', '12'); $f('max_upload_mb', 'Azami yükleme boyutu (MB)', 'number', '20'); ?>
<p><label><input type="checkbox" name="registration" <?= setting('registration', '1') === '1' ? 'checked' : '' ?>> Yeni üyeliğe izin ver</label></p>
<p><label><input type="checkbox" name="auto_publish" <?= setting('auto_publish', '0') === '1' ? 'checked' : '' ?>> Üye PDF'lerini onaysız yayınla</label></p>
</fieldset>
<fieldset><legend>SMTP</legend>
<?php $f('smtp_host', 'SMTP sunucusu'); $f('smtp_port', 'Port', 'number', '587'); ?>
<label for="smtp_encryption">Şifreleme</label>
<select id="smtp_encryption" name="smtp_encryption"><?php foreach (['tls' => 'STARTTLS', 'ssl' => 'SSL', 'none' => 'Yok'] as $k => $l): ?><option value="<?= $k ?>" <?= setting('smtp_encryption', 'tls') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
<?php $f('smtp_user', 'Kullanıcı adı'); ?>
<label for="smtp_pass">Parola (boş bırakılırsa değişmez)</label><input id="smtp_pass" name="smtp_pass" type="password" autocomplete="new-password">
<?php $f('smtp_from', 'Gönderen e-posta', 'email'); $f('smtp_from_name', 'Gönderen adı'); ?>
</fieldset>
<button type="submit">Kaydet</button>
</form>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="test"><button type="submit">Test e-postası gönder (<?= e($me['email']) ?>)</button></form>
<?php page_footer();
