<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
if (!is_file(ROOT . '/config.php')) {
    header('Location: ' . (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin' ? '../' : '') . 'install.php');
    exit;
}
$config = require ROOT . '/config.php';

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

function db(): PDO
{
    static $pdo = null;
    if (!$pdo) {
        global $config;
        $pdo = new PDO(
            "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
            $config['db_user'], $config['db_pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
        );
    }
    return $pdo;
}

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function setting(string $k, string $default = ''): string
{
    static $all = null;
    if ($all === null) {
        $all = db()->query('SELECT k, v FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return isset($all[$k]) && $all[$k] !== '' ? (string)$all[$k] : $default;
}

function save_setting(string $k, string $v): void
{
    db()->prepare('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)')->execute([$k, $v]);
}

function base_url(): string
{
    $u = setting('site_url');
    if ($u !== '') return rtrim($u, '/');
    $scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    if (substr($dir, -6) === '/admin') $dir = substr($dir, 0, -6);
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $dir;
}

function redirect(string $url): void { header('Location: ' . $url); exit; }

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Geçersiz istek (CSRF).');
    }
}

function flash(?string $msg = null, string $type = 'success')
{
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function current_user(): ?array
{
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $s = db()->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
            $s->execute([$_SESSION['uid']]);
            $u = $s->fetch() ?: null;
        }
    }
    return $u;
}
function require_login(): array
{
    $u = current_user();
    if (!$u) { redirect(base_path() . '/login.php'); }
    return $u;
}
function require_admin(): array
{
    $u = current_user();
    if (!$u || $u['role'] !== 'admin') { http_response_code(403); exit('Yetkisiz erişim.'); }
    return $u;
}
function base_path(): string
{
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    return substr($dir, -6) === '/admin' ? substr($dir, 0, -6) : $dir;
}

function slugify(string $s): string
{
    $s = strtr($s, ['ç'=>'c','Ç'=>'c','ğ'=>'g','Ğ'=>'g','ı'=>'i','İ'=>'i','ö'=>'o','Ö'=>'o','ş'=>'s','Ş'=>'s','ü'=>'u','Ü'=>'u']);
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
    return $s !== '' ? substr($s, 0, 150) : 'pdf';
}

function human_size(int $b): string
{
    foreach (['B', 'KB', 'MB', 'GB'] as $i => $u) {
        if ($b < 1024 || $i === 3) return round($b, $i ? 1 : 0) . ' ' . $u;
        $b /= 1024;
    }
    return '';
}

/** Validates upload is strictly a PDF (extension, magic bytes, MIME) and stores it. Returns [file_key, size] or throws. */
function store_pdf(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Dosya yüklenemedi.');
    $max = (int)setting('max_upload_mb', '20') * 1048576;
    if ($file['size'] > $max) throw new RuntimeException('Dosya çok büyük.');
    if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') throw new RuntimeException('Yalnızca .pdf uzantılı dosyalar yüklenebilir.');
    $head = (string)file_get_contents($file['tmp_name'], false, null, 0, 1024);
    if (strpos($head, '%PDF-') === false) throw new RuntimeException('Dosya geçerli bir PDF değil.');
    if ((new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) !== 'application/pdf') throw new RuntimeException('Dosya geçerli bir PDF değil.');
    $file_key = bin2hex(random_bytes(16)) . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], ROOT . '/storage/' . $file_key)) throw new RuntimeException('Dosya kaydedilemedi.');
    return [$file_key, (int)$file['size']];
}

function unique_slug(string $title): string
{
    $base = slugify($title); $slug = $base; $i = 1;
    $s = db()->prepare('SELECT 1 FROM pdfs WHERE slug = ?');
    while (true) {
        $s->execute([$slug]);
        if (!$s->fetch()) return $slug;
        $slug = $base . '-' . (++$i);
    }
}

require_once __DIR__ . '/smtp.php';
require_once __DIR__ . '/layout.php';
