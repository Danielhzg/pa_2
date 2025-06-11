<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Services\OrderNotificationService;

class PaymentWebhookController extends Controller
{
    protected $notificationService;

    public function __construct(OrderNotificationService $notificationService = null)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle Midtrans payment notification webhook
     */
    public function handleMidtransNotification(Request $request)
    {
        try {
            Log::info('Midtrans webhook received', $request->all());

            $orderId = $request->order_id;
            $transactionStatus = $request->transaction_status;
            $fraudStatus = $request->fraud_status ?? null;
            $paymentType = $request->payment_type ?? null;

            // Find the order
            $order = Order::where('order_id', $orderId)->first();

            if (!$order) {
                Log::warning("Order not found for webhook: {$orderId}");
                return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
            }

            Log::info("Processing payment webhook for order: {$orderId}, status: {$transactionStatus}, payment_type: {$paymentType}");

            // Update payment status based on Midtrans response
            $newPaymentStatus = $this->mapMidtransStatus($transactionStatus, $fraudStatus);

            if ($newPaymentStatus) {
                $oldPaymentStatus = $order->payment_status;
                $oldOrderStatus = $order->status;

                // Use enhanced updatePaymentStatus method which automatically handles order status
                $result = $order->updatePaymentStatus($newPaymentStatus, 'midtrans_webhook');

                Log::info("Payment webhook processed for order: {$orderId}", [
                    'old_payment_status' => $result['old_payment_status'],
                    'new_payment_status' => $result['new_payment_status'],
                    'old_order_status' => $result['old_order_status'],
                    'new_order_status' => $result['new_order_status'],
                    'order_status_changed' => $result['status_changed'],
                    'payment_type' => $paymentType,
                    'transaction_status' => $transactionStatus
                ]);

                // Send notification to admin and customer
                if (isset($this->notificationService)) {
                    $this->notificationService->notifyPaymentStatusChange($order, $oldPaymentStatus, $newPaymentStatus);

                    // If order status changed, send order status notification too
                    if ($result['status_changed']) {
                        $this->notificationService->notifyOrderStatusChange($order, $oldOrderStatus, $order->status);
                    }
                }

                // Store payment details for audit trail
                $this->storePaymentDetails($order, $request->all());
            }

            return response()->json(['status' => 'success', 'message' => 'Payment notification processed successfully']);

        } catch (\Exception $e) {
            Log::error('Payment webhook error: ' . $e->getMessage(), [
                'order_id' => $request->order_id ?? 'unknown',
                'transaction_status' => $request->transaction_status ?? 'unknown',
                'request_data' => $request->all()
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Manual payment status update (for testing or manual processing)
     */
    public function updatePaymentStatus(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|string',
                'payment_status' => 'required|string|in:pending,paid,failed,expired',
            ]);

            $order = Order::where('order_id', $request->order_id)->first();
            
            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            $oldPaymentStatus = $order->payment_status;
            $order->updatePaymentStatus($request->payment_status);

            Log::info("Manual payment status update: Order {$request->order_id} from {$oldPaymentStatus} to {$request->payment_status}");

            return response()->json([
                'success' => true,
                'message' => 'Payment status updated successfully',
                'data' => [
                    'order_id' => $order->order_id,
                    'old_status' => $oldPaymentStatus,
                    'new_status' => $order->payment_status,
                    'order_status' => $order->status,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Manual payment status update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Simulate payment completion (for testing)
     */
    public function simulatePaymentSuccess(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|string',
            ]);

            $order = Order::where('order_id', $request->order_id)->first();
            
            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            // Simulate successful payment
            $oldPaymentStatus = $order->payment_status;
            $oldOrderStatus = $order->status;

            $order->updatePaymentStatus(Order::PAYMENT_PAID);

            Log::info("Simulated payment success for order: {$request->order_id}");
            Log::info("Order status changed from {$oldOrderStatus} to {$order->status}");
            Log::info("Payment status changed from {$oldPaymentStatus} to {$order->payment_status}");

            // Send notifications for both payment and order status changes
            if ($this->notificationService) {
                $this->notificationService->notifyPaymentStatusChange($order, $oldPaymentStatus, $order->payment_status);

                if ($oldOrderStatus !== $order->status) {
                    $this->notificationService->notifyOrderStatusChange($order, $oldOrderStatus, $order->status);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment simulation completed successfully',
                'data' => [
                    'order_id' => $order->order_id,
                    'old_payment_status' => $oldPaymentStatus,
                    'new_payment_status' => $order->payment_status,
                    'old_order_status' => $oldOrderStatus,
                    'order_status' => $order->status,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Payment simulation error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Map Midtrans transaction status to our payment status
     */
    private function mapMidtransStatus($transactionStatus, $fraudStatus = null)
    {
        switch ($transactionStatus) {
            case 'capture':
                return ($fraudStatus === 'challenge') ? Order::PAYMENT_PENDING : Order::PAYMENT_PAID;
            case 'settlement':
                return Order::PAYMENT_PAID;
            case 'pending':
                return Order::PAYMENT_PENDING;
            case 'deny':
            case 'cancel':
            case 'failure':
                return Order::PAYMENT_FAILED;
            case 'expire':
                return Order::PAYMENT_EXPIRED;
            default:
                Log::warning("Unknown Midtrans transaction status: {$transactionStatus}");
                return null;
        }
    }

    /**
     * Get payment status for an order
     */
    public function getPaymentStatus(Request $request, $orderId)
    {
        try {
            $order = Order::where('order_id', $orderId)->first();
            
            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $order->order_id,
                    'payment_status' => $order->payment_status,
                    'order_status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'payment_method' => $order->payment_method,
                    'paid_at' => $order->paid_at,
                    'payment_deadline' => $order->payment_deadline,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get payment status error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store payment details for audit trail
     */
    private function storePaymentDetails(Order $order, array $paymentData)
    {
        try {
            // Get existing payment details or initialize empty array
            $paymentDetails = $order->payment_details ?
                (is_string($order->payment_details) ? json_decode($order->payment_details, true) : $order->payment_details) :
                [];

            // Add new payment event
            $paymentDetails[] = [
                'timestamp' => now()->toIso8601String(),
                'source' => 'midtrans_webhook',
                'transaction_status' => $paymentData['transaction_status'] ?? null,
                'payment_type' => $paymentData['payment_type'] ?? null,
                'fraud_status' => $paymentData['fraud_status'] ?? null,
                'gross_amount' => $paymentData['gross_amount'] ?? null,
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'transaction_time' => $paymentData['transaction_time'] ?? null,
                'settlement_time' => $paymentData['settlement_time'] ?? null,
                'raw_data' => $paymentData
            ];

            // Update order with new payment details
            $order->payment_details = json_encode($paymentDetails);
            $order->save();

            Log::info("Payment details stored for order: {$order->order_id}");

        } catch (\Exception $e) {
            Log::error("Failed to store payment details for order {$order->order_id}: " . $e->getMessage());
        }
    }
}
