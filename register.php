<?php
require __DIR__ . '/includes/bootstrap.php';
if (setting('registration', '1') !== '1') { http_response_code(403); page_header(['title' => 'Kayıt kapalı', 'robots' => 'noindex']); echo '<h1>Üyelik alımı kapalı</h1>'; page_footer(); exit; }
$err = []; $v = ['username' => '', 'email' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $v['username'] = trim((string)$_POST['username']); $v['email'] = trim((string)$_POST['email']);
    $pw = (string)$_POST['password'];
    if (!preg_match('/^[A-Za-z0-9_]{3,40}$/', $v['username'])) $err['username'] = 'Kullanıcı adı 3-40 karakter; harf, rakam ve _ içerebilir.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $err['email'] = 'Geçerli bir e-posta girin.';
    if (strlen($pw) < 8) $err['password'] = 'Parola en az 8 karakter olmalı.';
    if (!$err) {
        $c = db()->prepare('SELECT 1 FROM users WHERE username = ? OR email = ?');
        $c->execute([$v['username'], $v['email']]);
        if ($c->fetch()) $err['username'] = 'Kullanıcı adı veya e-posta zaten kayıtlı.';
    }
    if (!$err) {
        db()->prepare("INSERT INTO users (username,email,password,created_at) VALUES (?,?,?,NOW())")->execute([$v['username'], $v['email'], password_hash($pw, PASSWORD_DEFAULT)]);
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)db()->lastInsertId();
        if (setting('smtp_host') !== '') send_mail($v['email'], setting('site_name') . ' - Hoş geldiniz', "Merhaba {$v['username']},\n\nÜyeliğiniz oluşturuldu.");
        flash('Üyeliğiniz oluşturuldu.');
        redirect(base_path() . '/');
    }
}
page_header(['title' => 'Üye ol', 'robots' => 'noindex,follow']);
?>
<h1>Üye ol</h1>
<?php if ($err): ?><div class="alert error" role="alert"><ul><?php foreach ($err as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<label for="username">Kullanıcı adı</label><input id="username" name="username" required autocomplete="username" value="<?= e($v['username']) ?>">
<label for="email">E-posta</label><input id="email" name="email" type="email" required autocomplete="email" value="<?= e($v['email']) ?>">
<label for="password">Parola (en az 8 karakter)</label><input id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
<button type="submit">Üye ol</button></form>
<?php page_footer();
