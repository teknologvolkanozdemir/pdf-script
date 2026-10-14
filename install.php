<?php
declare(strict_types=1);
if (is_file(__DIR__ . '/config.php')) { exit('Kurulum zaten yapılmış. Güvenlik için install.php dosyasını silin.'); }
session_start();
$err = '';
$v = ['db_host' => 'localhost', 'db_name' => '', 'db_user' => '', 'site_name' => 'PDF Arşivi', 'admin_user' => 'admin', 'admin_email' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) exit('Geçersiz istek.');
    foreach ($v as $k => $_) $v[$k] = trim((string)($_POST[$k] ?? ''));
    $dbpass = (string)($_POST['db_pass'] ?? '');
    $apass = (string)($_POST['admin_pass'] ?? '');
    try {
        if (!filter_var($v['admin_email'], FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Geçerli bir yönetici e-postası girin.');
        if (!preg_match('/^[A-Za-z0-9_]{3,40}$/', $v['admin_user'])) throw new RuntimeException('Kullanıcı adı 3-40 harf/rakam/_ olmalı.');
        if (strlen($apass) < 8) throw new RuntimeException('Yönetici parolası en az 8 karakter olmalı.');
        $pdo = new PDO("mysql:host={$v['db_host']};dbname={$v['db_name']};charset=utf8mb4", $v['db_user'], $dbpass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        foreach (array_filter(array_map('trim', explode(';', file_get_contents(__DIR__ . '/schema.sql')))) as $sql) $pdo->exec($sql);
        $pdo->prepare("INSERT INTO users (username,email,password,role,created_at) VALUES (?,?,?,'admin',NOW())")
            ->execute([$v['admin_user'], $v['admin_email'], password_hash($apass, PASSWORD_DEFAULT)]);
        $pdo->prepare("INSERT INTO settings (k,v) VALUES ('site_name',?) ON DUPLICATE KEY UPDATE v=VALUES(v)")->execute([$v['site_name']]);
        $cfg = "<?php\nreturn " . var_export(['db_host' => $v['db_host'], 'db_name' => $v['db_name'], 'db_user' => $v['db_user'], 'db_pass' => $dbpass], true) . ";\n";
        if (file_put_contents(__DIR__ . '/config.php', $cfg) === false) throw new RuntimeException('config.php yazılamadı.');
        header('Location: admin/');
        exit;
    } catch (Throwable $ex) {
        $err = $ex->getMessage();
    }
}
$_SESSION['csrf'] = $_SESSION['csrf'] ?? bin2hex(random_bytes(32));
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kurulum</title><meta name="robots" content="noindex"><link rel="stylesheet" href="assets/style.css"></head>
<body><main class="wrap"><h1>Kurulum</h1>
<?php if ($err): ?><div class="alert error" role="alert"><?= $e($err) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>">
<label for="db_host">Veritabanı sunucusu</label><input id="db_host" name="db_host" required value="<?= $e($v['db_host']) ?>">
<label for="db_name">Veritabanı adı</label><input id="db_name" name="db_name" required value="<?= $e($v['db_name']) ?>">
<label for="db_user">Veritabanı kullanıcısı</label><input id="db_user" name="db_user" required value="<?= $e($v['db_user']) ?>">
<label for="db_pass">Veritabanı parolası</label><input id="db_pass" name="db_pass" type="password" autocomplete="off">
<label for="site_name">Site adı</label><input id="site_name" name="site_name" required value="<?= $e($v['site_name']) ?>">
<label for="admin_user">Yönetici kullanıcı adı</label><input id="admin_user" name="admin_user" required value="<?= $e($v['admin_user']) ?>">
<label for="admin_email">Yönetici e-posta</label><input id="admin_email" name="admin_email" type="email" required value="<?= $e($v['admin_email']) ?>">
<label for="admin_pass">Yönetici parolası (min. 8)</label><input id="admin_pass" name="admin_pass" type="password" required minlength="8" autocomplete="new-password">
<button type="submit">Kur</button></form></main></body></html>
