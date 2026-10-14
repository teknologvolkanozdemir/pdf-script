# pdf-script

PHP + MySQL PDF yayın scripti. Yalnızca `.pdf` dosyaları kabul edilir (uzantı, `%PDF-` imzası ve MIME kontrolü).

## Özellikler
- Üyelik sistemi (kayıt, giriş, parola değiştirme, kendi PDF'lerini yönetme)
- Yönetim paneli (`/admin/`): PDF onay/red/silme, üye yönetimi, site/SEO/SMTP ayarları, SMTP test e-postası
- SEO: title/description/canonical, Open Graph, JSON-LD, `sitemap.php`, `robots.txt`, temiz `/pdf/slug` adresleri
- Erişilebilirlik: semantik HTML, `lang`, "içeriğe geç" bağlantısı, etiketli formlar, odak göstergesi, ARIA rolleri
- Güvenlik: PDO hazırlıklı sorgular, CSRF koruması, `password_hash`, PDF'ler yalnızca `file.php` üzerinden sunulur

## Kurulum
1. Boş bir MySQL veritabanı oluşturun, dosyaları Apache (mod_rewrite, PHP 8+) sunucusuna yükleyin.
2. `/install.php` adresini açın, formu doldurun (şema ve yönetici hesabı oluşur), sonra `install.php` dosyasını silin.
3. `storage/` klasörü PHP tarafından yazılabilir olmalıdır.
