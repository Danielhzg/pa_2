<!DOCTYPE html>
<html>
<head>
    <title>Verifikasi Email Bloom Bouquet</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            background-color: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        .logo {
            width: 150px;
            height: auto;
            margin-bottom: 20px;
        }
        .header h2 {
            color: #D46A9F;
            font-size: 24px;
            margin: 0;
        }
        .header p {
            color: #777;
            margin-top: 5px;
        }
        .otp-container {
            text-align: center;
            margin: 30px 0;
        }
        .otp-label {
            font-size: 16px;
            color: #555;
            margin-bottom: 10px;
        }
        .otp-code {
            background: linear-gradient(135deg, #FF87B2 0%, #D46A9F 100%);
            color: white;
            font-size: 32px;
            font-weight: bold;
            text-align: center;
            padding: 15px 25px;
            border-radius: 8px;
            margin: 10px auto;
            letter-spacing: 8px;
            display: inline-block;
        }
        .expiry-note {
            color: #FF87B2;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .warning {
            background-color: #FFF5F7;
            border-left: 4px solid #FF87B2;
            color: #333;
            font-size: 14px;
            margin: 25px 0;
            padding: 15px;
            border-radius: 4px;
        }
        .warning p {
            font-weight: bold;
            margin-top: 0;
        }
        .warning ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        .warning li {
            margin-bottom: 8px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #888;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .contact {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        .contact p {
            margin: 5px 0;
        }
        @media only screen and (max-width: 480px) {
            .container {
                padding: 20px;
                margin: 10px;
            }
            .otp-code {
                font-size: 28px;
                letter-spacing: 6px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://i.imgur.com/mHzXkJf.png" alt="Bloom Bouquet Logo" class="logo">
            <h2>Verifikasi Email Anda</h2>
            <p>Satu langkah lagi untuk menyelesaikan pendaftaran akun Anda</p>
        </div>
        
        <p>Halo Calon Pelanggan Bloom Bouquet!</p>
        <p>Terima kasih telah mendaftar di Bloom Bouquet. Untuk memastikan keamanan akun Anda, silakan masukkan kode verifikasi berikut di aplikasi atau website kami:</p>
        
        <div class="otp-container">
            <div class="otp-label">Kode Verifikasi OTP Anda:</div>
            <div class="otp-code">{{ $otp }}</div>
        </div>
        
        <p class="expiry-note">⏱️ Kode ini akan kadaluarsa dalam 3 menit.</p>
        
        <div class="warning">
            <p>⚠️ Penting:</p>
            <ul>
                <li>Jangan pernah membagikan kode OTP ini kepada siapapun termasuk pihak yang mengaku sebagai staf Bloom Bouquet</li>
                <li>Tim Bloom Bouquet tidak akan pernah meminta kode OTP Anda melalui telepon, email, atau pesan teks</li>
                <li>Pastikan Anda berada di aplikasi resmi atau website resmi Bloom Bouquet saat memasukkan kode ini</li>
            </ul>
        </div>
        
        <p>Jika Anda tidak merasa mendaftar di Bloom Bouquet, abaikan email ini atau hubungi tim support kami segera.</p>
        
        <div class="footer">
            <p>Email ini dikirim secara otomatis, mohon jangan membalas.</p>
            <p>© {{ date('Y') }} Bloom Bouquet. All rights reserved.</p>
            <div class="contact">
                <p><strong>Butuh bantuan?</strong></p>
                <p>Email: bloombouqet0@gmail.com</p>
                <p>WhatsApp: +62 812-3456-7890</p>
                <p>Instagram: @bloombouquet_id</p>
            </div>
        </div>
    </div>
</body>
</html>