<!DOCTYPE html>
<html>
<head>
    <title>Verifikasi Email Bloom Bouquet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .otp-code {
            background-color: #FF87B2;
            color: white;
            font-size: 32px;
            font-weight: bold;
            text-align: center;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            letter-spacing: 4px;
        }
        .warning {
            color: #dc3545;
            font-size: 14px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/logo.png') }}" alt="Bloom Bouquet Logo" style="max-width: 150px;">
            <h2>Verifikasi Email Anda</h2>
        </div>
        
        <p>Halo!</p>
        <p>Terima kasih telah mendaftar di Bloom Bouquet. Gunakan kode OTP berikut untuk memverifikasi akun Anda:</p>
        
        <div class="otp-code">{{ $otp }}</div>
        
        <p><strong>Kode ini akan kadaluarsa dalam 5 menit.</strong></p>
        
        <div class="warning">
            <p>⚠️ Penting:</p>
            <ul>
                <li>Jangan bagikan kode ini kepada siapapun</li>
                <li>Tim Bloom Bouquet tidak akan pernah meminta kode OTP Anda</li>
                <li>Pastikan Anda berada di website/aplikasi resmi Bloom Bouquet</li>
            </ul>
        </div>
        
        <p>Jika Anda tidak merasa mendaftar di Bloom Bouquet, abaikan email ini atau hubungi tim support kami.</p>
        
        <div class="footer">
            <p>Email ini dikirim otomatis, mohon tidak membalas.</p>
            <p>© {{ date('Y') }} Bloom Bouquet. All rights reserved.</p>
            <p>
                Butuh bantuan? Hubungi kami di:<br>
                Email: support@bloombouquet.com<br>
                WhatsApp: +62 812-3456-7890
            </p>
        </div>
    </div>
</body>
</html>