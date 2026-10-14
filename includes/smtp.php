<?php
/** Minimal SMTP client (AUTH LOGIN, SSL/TLS/STARTTLS). Returns true or an error string. */
function send_mail(string $to, string $subject, string $body)
{
    $host = setting('smtp_host');
    if ($host === '') return 'SMTP sunucusu tanımlı değil.';
    $port = (int)setting('smtp_port', '587');
    $enc = setting('smtp_encryption', 'tls');
    $user = setting('smtp_user');
    $pass = setting('smtp_pass');
    $from = setting('smtp_from', $user);
    $fromName = setting('smtp_from_name', setting('site_name', 'PDF'));
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) return 'Geçersiz e-posta adresi.';

    $fp = @stream_socket_client(($enc === 'ssl' ? 'ssl://' : 'tcp://') . "$host:$port", $en, $es, 15);
    if (!$fp) return "Bağlantı hatası: $es";
    stream_set_timeout($fp, 15);
    $read = function () use ($fp) {
        $r = '';
        while (($l = fgets($fp, 515)) !== false) { $r .= $l; if (isset($l[3]) && $l[3] === ' ') break; }
        return $r;
    };
    $cmd = function (string $c, string $ok) use ($fp, $read) {
        fwrite($fp, $c . "\r\n");
        $r = $read();
        return strpos($r, $ok) === 0 ? true : trim($r);
    };
    $clean = fn($s) => str_replace(["\r", "\n"], '', $s);
    $steps = [];
    try {
        if (strpos($read(), '220') !== 0) throw new RuntimeException('Sunucu yanıt vermedi.');
        $ehlo = $_SERVER['SERVER_NAME'] ?? 'localhost';
        $r = $cmd("EHLO $ehlo", '250'); if ($r !== true) throw new RuntimeException($r);
        if ($enc === 'tls') {
            $r = $cmd('STARTTLS', '220'); if ($r !== true) throw new RuntimeException($r);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('TLS başlatılamadı.');
            $r = $cmd("EHLO $ehlo", '250'); if ($r !== true) throw new RuntimeException($r);
        }
        if ($user !== '') {
            $r = $cmd('AUTH LOGIN', '334'); if ($r !== true) throw new RuntimeException($r);
            $r = $cmd(base64_encode($user), '334'); if ($r !== true) throw new RuntimeException($r);
            $r = $cmd(base64_encode($pass), '235'); if ($r !== true) throw new RuntimeException($r);
        }
        $r = $cmd('MAIL FROM:<' . $clean($from) . '>', '250'); if ($r !== true) throw new RuntimeException($r);
        $r = $cmd('RCPT TO:<' . $clean($to) . '>', '250'); if ($r !== true) throw new RuntimeException($r);
        $r = $cmd('DATA', '354'); if ($r !== true) throw new RuntimeException($r);
        $headers = 'From: =?UTF-8?B?' . base64_encode($clean($fromName)) . '?= <' . $clean($from) . ">\r\n"
            . 'To: <' . $clean($to) . ">\r\n"
            . 'Subject: =?UTF-8?B?' . base64_encode($clean($subject)) . "?=\r\n"
            . 'Date: ' . date('r') . "\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n";
        $data = $headers . "\r\n" . chunk_split(base64_encode($body));
        $r = $cmd($data . "\r\n.", '250'); if ($r !== true) throw new RuntimeException($r);
        fwrite($fp, "QUIT\r\n");
        fclose($fp);
        return true;
    } catch (Throwable $ex) {
        @fclose($fp);
        return $ex->getMessage();
    }
}
