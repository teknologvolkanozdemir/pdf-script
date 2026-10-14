<?php
require __DIR__ . '/includes/bootstrap.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $_SESSION['tries'] = $_SESSION['tries'] ?? [];
    $_SESSION['tries'] = array_filter($_SESSION['tries'], fn($t) => $t > time() - 300);
    if (count($_SESSION['tries']) >= 5) {
        $err = 'Çok fazla deneme. Lütfen birkaç dakika sonra tekrar deneyin.';
    } else {
        $s = db()->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
        $s->execute([trim((string)$_POST['login']), trim((string)$_POST['login'])]);
        $u = $s->fetch();
        if ($u && $u['status'] === 'active' && password_verify((string)$_POST['password'], $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$u['id'];
            $_SESSION['tries'] = [];
            redirect($u['role'] === 'admin' ? base_path() . '/admin/' : base_path() . '/');
        }
        $_SESSION['tries'][] = time();
        $err = 'Kullanıcı adı/e-posta veya parola hatalı.';
    }
}
page_header(['title' => 'Giriş', 'robots' => 'noindex,follow']);
?>
<h1>Giriş yap</h1>
<?php if ($err): ?><div class="alert error" role="alert"><?= e($err) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<label for="login">Kullanıcı adı veya e-posta</label><input id="login" name="login" required autocomplete="username">
<label for="password">Parola</label><input id="password" name="password" type="password" required autocomplete="current-password">
<button type="submit">Giriş</button></form>
<?php page_footer();
