<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExpireQRPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:expire-qr-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for QR payments past their deadline and marks them as expired';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired QR payments...');

        try {
            // Find orders with QRIS payment that are waiting for payment
            // and have passed their payment deadline
            $expiredOrders = Order::where('payment_method', 'like', '%qris%')
                ->where('status', Order::STATUS_WAITING_FOR_PAYMENT)
                ->where('payment_status', Order::PAYMENT_PENDING)
                ->whereNotNull('payment_deadline')
                ->where('payment_deadline', '<', Carbon::now())
                ->get();

            $count = $expiredOrders->count();
            $this->info("Found {$count} expired QR payments");

            foreach ($expiredOrders as $order) {
                $order->updatePaymentStatus(Order::PAYMENT_EXPIRED);
                $order->status = Order::STATUS_CANCELLED;
                $order->cancelled_at = Carbon::now();
                $order->save();

                $this->info("Expired order: {$order->order_id}");
                
                // Record payment details
                $paymentDetails = json_decode($order->payment_details) ?: [];
                $paymentDetails[] = [
                    'time' => Carbon::now()->toIso8601String(),
                    'status' => 'expired',
                    'type' => 'qris',
                    'note' => 'Payment deadline expired automatically'
                ];
                
                $order->payment_details = json_encode($paymentDetails);
                $order->save();
            }

            $this->info('Finished processing expired QR payments');
            return 0;
        } catch (\Exception $e) {
            $this->error("Error processing expired payments: {$e->getMessage()}");
            Log::error("ExpireQRPayments Command Error: {$e->getMessage()}");
            return 1;
        }
    }
} 