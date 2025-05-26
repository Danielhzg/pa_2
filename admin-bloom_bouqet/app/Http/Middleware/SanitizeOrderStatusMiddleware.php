<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitizeOrderStatusMiddleware
{
    protected $validStatuses = [
        'waiting_for_payment',
        'processing',
        'shipping',
        'delivered',
        'cancelled'
    ];

    protected $validPaymentStatuses = [
        'pending',
        'paid',
        'failed',
        'expired',
        'refunded'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Jika permintaan adalah untuk membuat atau memperbarui pesanan
        if ($request->isMethod('post') || $request->isMethod('put')) {
            \Log::debug('SanitizeOrderStatusMiddleware: Processing request', [
                'path' => $request->path(),
                'method' => $request->method(),
                'has_status' => $request->has('status'),
                'has_payment_status' => $request->has('payment_status'),
            ]);
            
            // Jika status ada, pastikan valid atau ubah ke default
            if ($request->has('status')) {
                $status = $request->input('status');
                \Log::debug('SanitizeOrderStatusMiddleware: Checking status', [
                    'original_status' => $status,
                    'is_valid' => in_array($status, $this->validStatuses),
                ]);
                
                if (!in_array($status, $this->validStatuses)) {
                    \Log::debug('SanitizeOrderStatusMiddleware: Fixing invalid status', [
                        'from' => $status,
                        'to' => 'waiting_for_payment',
                    ]);
                    $request->merge(['status' => 'waiting_for_payment']);
                }
            }
            
            // Jika payment_status ada, pastikan valid atau ubah ke default
            if ($request->has('payment_status')) {
                $paymentStatus = $request->input('payment_status');
                \Log::debug('SanitizeOrderStatusMiddleware: Checking payment_status', [
                    'original_payment_status' => $paymentStatus,
                    'is_valid' => in_array($paymentStatus, $this->validPaymentStatuses),
                ]);
                
                if (!in_array($paymentStatus, $this->validPaymentStatuses)) {
                    \Log::debug('SanitizeOrderStatusMiddleware: Fixing invalid payment_status', [
                        'from' => $paymentStatus,
                        'to' => 'pending',
                    ]);
                    $request->merge(['payment_status' => 'pending']);
                }
            }
        }
        
        return $next($request);
    }
} 