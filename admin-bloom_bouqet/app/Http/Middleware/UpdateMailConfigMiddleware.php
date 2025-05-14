<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;

class UpdateMailConfigMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Override mail configuration at runtime to ensure OTP emails work
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'smtp.gmail.com');
        Config::set('mail.mailers.smtp.port', 587);
        Config::set('mail.mailers.smtp.encryption', 'tls');
        Config::set('mail.mailers.smtp.username', 'bloombouqet0@gmail.com');
        
        // App Password for Gmail - correct App Password from smtp_setup.txt
        Config::set('mail.mailers.smtp.password', 'gjvzzmdggtclntno');
        
        // From address and name
        Config::set('mail.from.address', 'bloombouqet0@gmail.com');
        Config::set('mail.from.name', 'Bloom Bouquet');

        return $next($request);
    }
} 