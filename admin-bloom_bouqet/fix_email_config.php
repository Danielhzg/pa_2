<?php

// Bootstrap the Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Schema;

echo "=== PERBAIKAN PENGIRIMAN OTP BLOOM BOUQUET ===\n\n";

// Periksa konfigurasi SMTP saat ini
echo "Konfigurasi SMTP saat ini:\n";
echo "MAIL_MAILER: " . config('mail.default') . "\n";
echo "MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
echo "MAIL_PORT: " . config('mail.mailers.smtp.port') . "\n";
echo "MAIL_USERNAME: " . config('mail.mailers.smtp.username') . "\n";
echo "MAIL_PASSWORD: " . (empty(config('mail.mailers.smtp.password')) ? "Kosong / Tidak terkonfigurasi" : "Terkonfigurasi") . "\n";
echo "MAIL_ENCRYPTION: " . config('mail.mailers.smtp.encryption') . "\n";
echo "MAIL_FROM_ADDRESS: " . config('mail.from.address') . "\n";
echo "MAIL_FROM_NAME: " . config('mail.from.name') . "\n\n";

echo "Mencari .env file...\n";
$envFile = __DIR__ . '/.env';

if (file_exists($envFile)) {
    echo "File .env ditemukan.\n";
    $envContent = file_get_contents($envFile);
    
    if (empty(config('mail.mailers.smtp.password')) || strpos($envContent, 'MAIL_PASSWORD=') === false) {
        echo "MAIL_PASSWORD tidak terkonfigurasi atau kosong dalam .env\n";
    }
} else {
    echo "File .env tidak ditemukan. Membuat file .env baru...\n";
    
    // Cek .env.example sebagai template
    if (file_exists(__DIR__ . '/.env.example')) {
        copy(__DIR__ . '/.env.example', $envFile);
        echo "File .env dibuat dari .env.example\n";
    } else {
        // Buat .env minimal jika .env.example tidak ditemukan
        file_put_contents($envFile, "APP_NAME=\"Bloom Bouquet\"\nAPP_ENV=local\nAPP_KEY=\nAPP_DEBUG=true\nAPP_URL=http://localhost\n\n");
        echo "File .env minimal dibuat\n";
    }
}

echo "\nMengatasi masalah pengiriman OTP...\n";

// 1. Periksa apakah App\Models\User memiliki kolom otp dan otp_expires_at
echo "Memeriksa struktur tabel users...\n";
$hasOtpColumn = Schema::hasColumn('users', 'otp');
$hasOtpExpiresColumn = Schema::hasColumn('users', 'otp_expires_at');

if (!$hasOtpColumn || !$hasOtpExpiresColumn) {
    echo "Tabel users perlu ditambahkan kolom otp dan/atau otp_expires_at.\n";
    echo "Jalankan perintah migrasi berikut untuk menambahkan kolom tersebut:\n";
    echo "php artisan make:migration add_otp_columns_to_users_table\n";
    
    echo "Isi file migrasi dengan kode berikut:\n";
    echo "```php\n";
    echo "public function up() {\n";
    echo "    Schema::table('users', function (Blueprint \$table) {\n";
    if (!$hasOtpColumn) echo "        \$table->string('otp')->nullable();\n";
    if (!$hasOtpExpiresColumn) echo "        \$table->timestamp('otp_expires_at')->nullable();\n";
    echo "        \$table->integer('otp_attempts')->default(0);\n";
    echo "    });\n";
    echo "}\n";
    echo "```\n\n";
}

// 2. Coba kirim email test untuk verifikasi konfigurasi
echo "Verifikasi Konfigurasi Email\n";
echo "===========================\n";
echo "Untuk mengaktifkan pengiriman OTP, Anda perlu mengikuti langkah-langkah berikut:\n\n";
echo "1. Buka akun Gmail bloombouqet0@gmail.com\n";
echo "2. Kunjungi https://myaccount.google.com/security\n";
echo "3. Aktifkan 2-Step Verification\n";
echo "4. Buat App Password baru untuk aplikasi 'Mail'\n";
echo "5. Salin App Password yang dihasilkan\n";
echo "6. Tambahkan konfigurasi berikut ke file .env Anda:\n\n";

echo "MAIL_MAILER=smtp\n";
echo "MAIL_HOST=smtp.gmail.com\n";
echo "MAIL_PORT=587\n";
echo "MAIL_USERNAME=bloombouqet0@gmail.com\n";
echo "MAIL_PASSWORD=app_password_dari_gmail\n";
echo "MAIL_ENCRYPTION=tls\n";
echo "MAIL_FROM_ADDRESS=\"bloombouqet0@gmail.com\"\n";
echo "MAIL_FROM_NAME=\"Bloom Bouquet\"\n\n";

echo "7. Setelah mengupdate file .env, jalankan perintah:\n";
echo "   php artisan config:clear\n\n";

// Opsi untuk menguji pengiriman email
echo "Apakah Anda ingin menguji pengiriman email OTP sekarang? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);

if (trim($line) === 'y') {
    echo "Masukkan email untuk pengujian: ";
    $testEmail = trim(fgets($handle));
    
    if (filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        echo "Mengirim email pengujian ke $testEmail...\n";
        
        try {
            // Buat kode OTP pengujian
            $testOtp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Kirim email pengujian
            Mail::to($testEmail)->send(new OtpMail($testOtp));
            
            echo "✓ Email OTP berhasil dikirim ke $testEmail!\n";
            echo "Kode OTP: $testOtp\n";
            echo "Periksa folder inbox dan spam untuk email dengan subject 'Your OTP Code for Bloom Bouquet'\n";
            
            echo "\nKonfigurasi email Anda berfungsi dengan baik!\n";
            echo "Sekarang OTP akan terkirim saat pengguna melakukan registrasi.\n";
        } catch (\Exception $e) {
            echo "❌ Gagal mengirim email: " . $e->getMessage() . "\n\n";
            echo "Kemungkinan penyebab:\n";
            echo "1. MAIL_PASSWORD tidak benar (pastikan menggunakan App Password dari Google)\n";
            echo "2. Akun Gmail belum mengaktifkan 2-Step Verification\n";
            echo "3. Layanan Gmail sedang mengalami gangguan\n";
            echo "4. Firewall atau koneksi internet menghalangi akses ke SMTP Gmail\n\n";
            
            echo "Solusi:\n";
            echo "1. Pastikan Anda menggunakan App Password yang dihasilkan oleh Google, bukan password Gmail biasa\n";
            echo "2. Tambahkan 'MAIL_PASSWORD=app_password_anda' ke file .env\n";
            echo "3. Jalankan 'php artisan config:clear' setelah mengubah file .env\n";
        }
    } else {
        echo "❌ Email tidak valid. Silakan jalankan script ini lagi untuk mencoba kembali.\n";
    }
}

echo "\n=== PERBAIKAN KODE UNTUK MEMASTIKAN OTP TERKIRIM ===\n";
echo "Script ini telah diperbarui untuk menghapus fitur fallback yang membuat\n";
echo "pengguna tetap terdaftar meskipun OTP tidak terkirim.\n";
echo "Sekarang pengguna HANYA akan terdaftar setelah OTP berhasil diverifikasi.\n\n";

echo "Tips tambahan untuk mengoptimalkan pengiriman email:\n";
echo "1. Tambahkan domain bloombouquet.com ke SPF dan DKIM records\n";
echo "2. Gunakan antrian (queue) untuk pengiriman email agar tidak memblokir proses utama\n";
echo "3. Pertimbangkan alternatif lain seperti Mailgun atau SendGrid jika Gmail bermasalah\n";

echo "\n=== SELESAI ===\n"; 