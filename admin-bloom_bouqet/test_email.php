<?php

// Bootstrap the Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

echo "=== TEST EMAIL CONFIGURATION FOR BLOOM BOUQUET ===\n\n";

// Override mail configuration at runtime (same as middleware)
Config::set('mail.default', 'smtp');
Config::set('mail.mailers.smtp.host', 'smtp.gmail.com');
Config::set('mail.mailers.smtp.port', 587);
Config::set('mail.mailers.smtp.encryption', 'tls');
Config::set('mail.mailers.smtp.username', 'bloombouqet0@gmail.com');
Config::set('mail.mailers.smtp.password', 'gjvzzmdggtclntno'); // App Password
Config::set('mail.from.address', 'bloombouqet0@gmail.com');
Config::set('mail.from.name', 'Bloom Bouquet');

echo "Current mail configuration:\n";
echo "MAIL_MAILER: " . config('mail.default') . "\n";
echo "MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
echo "MAIL_PORT: " . config('mail.mailers.smtp.port') . "\n";
echo "MAIL_USERNAME: " . config('mail.mailers.smtp.username') . "\n";
echo "MAIL_ENCRYPTION: " . config('mail.mailers.smtp.encryption') . "\n";
echo "MAIL_FROM_ADDRESS: " . config('mail.from.address') . "\n";
echo "MAIL_FROM_NAME: " . config('mail.from.name') . "\n\n";

// Send test email
echo "Enter email address to send test OTP: ";
$handle = fopen("php://stdin", "r");
$testEmail = trim(fgets($handle));

if (filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
    echo "Sending test OTP email to $testEmail...\n\n";
    
    // Generate test OTP
    $testOtp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    try {
        // Send email
        Mail::to($testEmail)->send(new OtpMail($testOtp));
        
        echo "✅ SUCCESS: Test email sent successfully!\n";
        echo "OTP code: $testOtp\n";
        echo "Check your inbox for the verification email.\n\n";
        
        echo "This confirms that your email configuration is working correctly.\n";
        echo "Users should now be able to register properly with email verification.\n";
    } catch (\Exception $e) {
        echo "❌ ERROR: Failed to send email: " . $e->getMessage() . "\n\n";
        
        echo "Troubleshooting steps:\n";
        echo "1. Verify that the Gmail account bloombouqet0@gmail.com exists\n";
        echo "2. Make sure 2-Step Verification is enabled for the Gmail account\n";
        echo "3. Check if the App Password 'gjvzzmdggtclntno' is correct and valid\n";
        echo "4. Try generating a new App Password in Google Account settings\n";
        echo "5. Check if there are firewall or network issues blocking SMTP\n";
        echo "6. Verify that the Gmail account hasn't reached sending limits\n\n";
        
        echo "Full error details:\n";
        echo $e->getTraceAsString() . "\n";
    }
} else {
    echo "❌ Invalid email address format. Please try again with a valid email.\n";
}

echo "\n=== TEST COMPLETED ===\n"; 