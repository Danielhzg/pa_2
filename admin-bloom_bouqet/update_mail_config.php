<?php

// Bootstrap the Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

echo "=== UPDATING MAIL CONFIGURATION FOR BLOOM BOUQUET ===\n\n";

// Override mail configuration at runtime
Config::set('mail.default', 'smtp');
Config::set('mail.mailers.smtp.host', 'smtp.gmail.com');
Config::set('mail.mailers.smtp.port', 587);
Config::set('mail.mailers.smtp.encryption', 'tls');
Config::set('mail.mailers.smtp.username', 'bloombouqet0@gmail.com');
Config::set('mail.mailers.smtp.password', 'gjvzzmdggtclntno'); // Replace with real App Password
Config::set('mail.from.address', 'bloombouqet0@gmail.com');
Config::set('mail.from.name', 'Bloom Bouquet');

echo "Mail configuration updated successfully!\n";
echo "Current settings:\n";
echo "MAIL_MAILER: " . config('mail.default') . "\n";
echo "MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
echo "MAIL_PORT: " . config('mail.mailers.smtp.port') . "\n";
echo "MAIL_USERNAME: " . config('mail.mailers.smtp.username') . "\n";
echo "MAIL_ENCRYPTION: " . config('mail.mailers.smtp.encryption') . "\n";
echo "MAIL_FROM_ADDRESS: " . config('mail.from.address') . "\n";
echo "MAIL_FROM_NAME: " . config('mail.from.name') . "\n\n";

// Test email sending
echo "Would you like to test the mail configuration by sending a test email? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);

if (trim($line) === 'y') {
    echo "Enter email address to send test to: ";
    $testEmail = trim(fgets($handle));
    
    if (filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        echo "Sending test email to $testEmail...\n";
        
        try {
            // Create test OTP
            $testOtp = "123456";
            
            // Send test email
            Mail::to($testEmail)->send(new OtpMail($testOtp));
            
            echo "✓ Test email sent successfully!\n";
            echo "Check your inbox for the email with OTP code.\n";
            
            // Create .env configuration instructions
            echo "\nTo make this configuration permanent, add the following to your .env file:\n\n";
            echo "MAIL_MAILER=smtp\n";
            echo "MAIL_HOST=smtp.gmail.com\n";
            echo "MAIL_PORT=587\n";
            echo "MAIL_USERNAME=bloombouqet0@gmail.com\n";
            echo "MAIL_PASSWORD=gjvzzmdggtclntno\n";
            echo "MAIL_ENCRYPTION=tls\n";
            echo "MAIL_FROM_ADDRESS=\"bloombouqet0@gmail.com\"\n";
            echo "MAIL_FROM_NAME=\"Bloom Bouquet\"\n\n";
            
            echo "After updating .env, run: php artisan config:clear\n";
        } catch (\Exception $e) {
            echo "❌ Failed to send email: " . $e->getMessage() . "\n";
            
            echo "\nTroubleshooting steps:\n";
            echo "1. Check if 'less secure app access' is enabled in Gmail account\n";
            echo "2. Generate an App Password in Google account security settings\n";
            echo "3. Check if the Gmail account has 2-factor authentication enabled\n";
            echo "4. Try with a different email service provider\n";
        }
    } else {
        echo "❌ Invalid email address. Please try again.\n";
    }
}

echo "\n=== ADDITIONAL SOLUTIONS ===\n";
echo "1. Add UpdateMailConfigMiddleware to your Laravel app:\n\n";
echo "Create app/Http/Middleware/UpdateMailConfigMiddleware.php with:\n";
echo "<?php\n";
echo "namespace App\Http\Middleware;\n\n";
echo "use Closure;\n";
echo "use Illuminate\Support\Facades\Config;\n\n";
echo "class UpdateMailConfigMiddleware\n";
echo "{\n";
echo "    public function handle(\$request, Closure \$next)\n";
echo "    {\n";
echo "        Config::set('mail.default', 'smtp');\n";
echo "        Config::set('mail.mailers.smtp.host', 'smtp.gmail.com');\n";
echo "        Config::set('mail.mailers.smtp.port', 587);\n";
echo "        Config::set('mail.mailers.smtp.encryption', 'tls');\n";
echo "        Config::set('mail.mailers.smtp.username', 'bloombouqet0@gmail.com');\n";
echo "        Config::set('mail.mailers.smtp.password', 'gjvzzmdggtclntno');\n";
echo "        Config::set('mail.from.address', 'bloombouqet0@gmail.com');\n";
echo "        Config::set('mail.from.name', 'Bloom Bouquet');\n\n";
echo "        return \$next(\$request);\n";
echo "    }\n";
echo "}\n\n";

echo "2. Register middleware in app/Http/Kernel.php:\n";
echo "protected \$middleware = [\n";
echo "    // other middleware...\n";
echo "    \App\Http\Middleware\UpdateMailConfigMiddleware::class,\n";
echo "];\n\n";

echo "3. Alternative solution: Use SendGrid or Mailgun service provider instead of Gmail\n";
echo "4. Create a custom Mailable class that handles failures gracefully\n";
echo "5. Use queue for sending emails to prevent timeouts\n\n";

echo "=== SCRIPT COMPLETED ===\n"; 