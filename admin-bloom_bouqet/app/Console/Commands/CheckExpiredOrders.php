<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckExpiredOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and cancel orders that have passed their payment deadline';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking for expired orders...');
        
        try {
            // Get orders that are waiting for payment, have a deadline, and deadline has passed
            $expiredOrders = Order::where('status', 'waiting_for_payment')
                ->where('payment_status', 'pending')
                ->whereNotNull('payment_deadline')
                ->where('payment_deadline', '<', Carbon::now())
                ->get();
            
            $count = $expiredOrders->count();
            $this->info("Found {$count} expired orders.");
            
            if ($count > 0) {
                $notificationService = new NotificationService();
                
                foreach ($expiredOrders as $order) {
                    try {
                        $oldStatus = $order->status;
                        $oldPaymentStatus = $order->payment_status;
                        
                        // Update order status to cancelled
                        $order->status = 'cancelled';
                        $order->payment_status = 'expired';
                        $order->cancelled_at = Carbon::now();
                        $order->save();
                        
                        // Send notifications
                        try {
                            $notificationService->sendOrderStatusNotification($order, $oldStatus, 'cancelled');
                            $notificationService->sendPaymentStatusNotification($order, $oldPaymentStatus, 'expired');
                            
                            $this->info("Order #{$order->id} has been cancelled due to payment deadline expiration.");
                        } catch (\Exception $e) {
                            $this->error("Failed to send notification for Order #{$order->id}: " . $e->getMessage());
                            Log::error("Failed to send notification for expired order #{$order->id}: " . $e->getMessage());
                        }
                    } catch (\Exception $e) {
                        $this->error("Error processing Order #{$order->id}: " . $e->getMessage());
                        Log::error("Error in CheckExpiredOrders processing order #{$order->id}: " . $e->getMessage());
                    }
                }
                
                $this->info("Successfully processed expired orders.");
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error checking expired orders: " . $e->getMessage());
            Log::error("Error in CheckExpiredOrders command: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 