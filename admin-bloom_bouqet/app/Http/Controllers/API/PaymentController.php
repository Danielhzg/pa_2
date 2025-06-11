<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Order;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    protected $serverKey;
    protected $clientKey;
    protected $isProduction;
    protected $isSanitized;
    protected $is3ds;

    public function __construct()
    {
        // Set Midtrans configuration
        $this->serverKey = 'SB-Mid-server-xkWYB70njNQ8ETfGJj_lhcry';
        $this->clientKey = 'SB-Mid-client-LqPJ6nGv11G9ceCF';
        $this->isProduction = false;
        $this->isSanitized = true;
        $this->is3ds = true;

        // Set Midtrans API URL based on environment
        \Midtrans\Config::$serverKey = $this->serverKey;
        \Midtrans\Config::$clientKey = $this->clientKey;
        \Midtrans\Config::$isProduction = $this->isProduction;
        \Midtrans\Config::$isSanitized = $this->isSanitized;
        \Midtrans\Config::$is3ds = $this->is3ds;
    }

    /**
     * Create a payment
     */
    public function createPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|string',
                'items' => 'required|array',
                'shipping_address' => 'required|string',
                'phone_number' => 'required|string',
                'total_amount' => 'required|numeric',
                'shipping_cost' => 'required|numeric',
                'payment_method' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get user information if authenticated
            $user = $request->user();
            $email = $user ? $user->email : 'guest@example.com';
            $name = $user ? $user->full_name : 'Guest Customer';

            // Extract name from shipping address if available
            $addressParts = explode(',', $request->shipping_address);
            $customerName = count($addressParts) > 0 ? trim($addressParts[0]) : $name;
            
            // Split name into first and last name for Midtrans
            $nameParts = explode(' ', $customerName);
            $firstName = $nameParts[0];
            $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

            // Prepare transaction details
            $transaction_details = [
                'order_id' => $request->order_id,
                'gross_amount' => (int)$request->total_amount,
            ];

            // Prepare customer details
            $customer_details = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $request->phone_number,
                'billing_address' => [
                    'address' => $request->shipping_address,
                ],
                'shipping_address' => [
                    'address' => $request->shipping_address,
                ],
            ];

            // Prepare item details
            $item_details = [];
            foreach ($request->items as $item) {
                $item_details[] = [
                    'id' => $item['id'],
                    'price' => (int)$item['price'],
                    'quantity' => (int)$item['quantity'],
                    'name' => $item['name'],
                ];
            }

            // Add shipping as a separate item
            $item_details[] = [
                'id' => 'shipping',
                'price' => (int)$request->shipping_cost,
                'quantity' => 1,
                'name' => 'Shipping Cost',
            ];

            // Set enabled payment methods based on request or default
            $enabled_payments = [
                'credit_card',
                'bca_va',
                'bni_va',
                'bri_va',
                'echannel', // Mandiri
                'permata_va',
                'qris', // QRIS/QR Code
                'gopay',
                'shopeepay',
            ];

            // Create transaction payload
            $payload = [
                'transaction_details' => $transaction_details,
                'customer_details' => $customer_details,
                'item_details' => $item_details,
                'enabled_payments' => $enabled_payments,
            ];

            Log::info('Midtrans Payment Payload', $payload);

            // Generate Snap Token
            $snapToken = \Midtrans\Snap::getSnapToken($payload);
            $redirectUrl = "https://app.sandbox.midtrans.com/snap/v2/vtweb/$snapToken";

            // Generate QR code data for QRIS payment method
            $qrCodeData = null;
            $qrCodeUrl = null;
            
            if ($request->payment_method === 'qris' || $request->payment_method === 'qr_code') {
                // Generate QR code data
                $qrCodeData = "QRIS.ID|MERCHANT.{$request->order_id}|AMOUNT.{$request->total_amount}|"
                            . "DATETIME." . date('YmdHis') . "|EXPIRE." . date('YmdHis', strtotime('+24 hours'));
                
                // Generate QR code image if QrCode library is available
                if (class_exists('SimpleSoftwareIO\QrCode\Facades\QrCode')) {
                    $qrCodeSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                                ->size(300)
                                ->errorCorrection('H')
                                ->generate($qrCodeData);
                    
                    $qrCodeUrl = 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg);
                }
            }

            // Save QR data to the order if exists
            if ($qrCodeData) {
                \App\Models\Order::where('order_id', $request->order_id)
                    ->update([
                        'qr_code_data' => $qrCodeData,
                        'qr_code_url' => $qrCodeUrl,
                    ]);
            }

            // Save transaction to database if needed
            // DB::table('transactions')->insert([...]);

            return response()->json([
                'success' => true,
                'message' => 'Payment token generated successfully',
                'data' => [
                    'order_id' => $request->order_id,
                    'token' => $snapToken,
                    'redirect_url' => $redirectUrl,
                    'qr_code_data' => $qrCodeData,
                    'qr_code_url' => $qrCodeUrl,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Payment Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check transaction status
     */
    public function checkStatus($orderId)
    {
        try {
            $status = \Midtrans\Transaction::status($orderId);
            
            return response()->json([
                'success' => true,
                'data' => $status
            ], 200);
        } catch (\Exception $e) {
            Log::error('Check Status Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle payment notification from the payment gateway.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function notification(Request $request)
    {
        try {
            $notification = new \Midtrans\Notification();
            
            $transactionStatus = $notification->transaction_status;
            $orderId = $notification->order_id;
            $paymentType = $notification->payment_type;
            $fraudStatus = $notification->fraud_status;
            
            Log::info('Payment Notification Received', [
                'order_id' => $orderId,
                'transaction_status' => $transactionStatus,
                'payment_type' => $paymentType,
                'fraud_status' => $fraudStatus
            ]);
            
            // Get the order
            $order = Order::where('order_id', $orderId)->first();
            
            if (!$order) {
                Log::error("Order {$orderId} not found for payment notification");
                return response('Order not found', 404);
            }
            
            // Determine payment status based on transaction status
            $paymentStatus = 'pending';
            $message = '';

            if ($transactionStatus == 'capture') {
                // For credit card payment that has been captured
                if ($fraudStatus == 'accept') {
                    $paymentStatus = 'paid';
                    $message = 'Pembayaran berhasil, pesanan Anda sedang diproses.';
                }
            } else if ($transactionStatus == 'settlement') {
                // For bank transfer, GoTo payments, etc.
                $paymentStatus = 'paid';
                $message = 'Pembayaran berhasil, pesanan Anda sedang diproses.';
            } else if ($transactionStatus == 'pending') {
                // Payment is pending
                $paymentStatus = 'pending';
                $message = 'Menunggu pembayaran Anda.';
            } else if ($transactionStatus == 'deny') {
                // Payment denied
                $paymentStatus = 'failed';
                $message = 'Pembayaran ditolak. Silakan coba lagi.';
            } else if ($transactionStatus == 'expire') {
                // Payment expired
                $paymentStatus = 'expired';
                $message = 'Waktu pembayaran telah habis. Silakan coba lagi.';
            } else if ($transactionStatus == 'cancel') {
                // Payment cancelled
                $paymentStatus = 'failed';
                $message = 'Pembayaran dibatalkan.';
            }

            // Use the enhanced updatePaymentStatus method which automatically handles order status
            $result = $order->updatePaymentStatus($paymentStatus, 'midtrans_notification');

            Log::info('Midtrans payment notification processed for order ' . $orderId, [
                'old_payment_status' => $result['old_payment_status'],
                'new_payment_status' => $result['new_payment_status'],
                'old_order_status' => $result['old_order_status'],
                'new_order_status' => $result['new_order_status'],
                'order_status_changed' => $result['status_changed'],
                'payment_status_changed' => $result['payment_status_changed'],
                'transaction_status' => $transactionStatus,
                'payment_type' => $paymentType,
                'fraud_status' => $fraudStatus
            ]);

            // Save payment details for audit trail
            $paymentDetails = json_decode($order->payment_details) ?: [];
            $paymentDetails[] = [
                'timestamp' => now()->toIso8601String(),
                'source' => 'midtrans_notification',
                'transaction_status' => $transactionStatus,
                'payment_type' => $paymentType,
                'fraud_status' => $fraudStatus,
                'gross_amount' => $notification->gross_amount ?? null,
                'transaction_id' => $notification->transaction_id ?? null,
                'transaction_time' => $notification->transaction_time ?? null,
                'settlement_time' => $notification->settlement_time ?? null,
                'raw_data' => $request->all()
            ];

            $order->payment_details = json_encode($paymentDetails);
            $order->save();

            // Send notification to the Flutter app using the current order status
            $this->sendOrderStatusUpdate($orderId, $order->status, $message);

            // If order status changed, send additional notification
            if ($result['status_changed']) {
                $statusMessage = $this->getOrderStatusMessage($order->status);
                $this->sendOrderStatusUpdate($orderId, $order->status, $statusMessage);
            }
            
            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Payment Notification Error: ' . $e->getMessage());
            return response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate QR code for payment
     */
    public function generateQRCode($orderId)
    {
        try {
            // Find the order
            $order = Order::where('order_id', $orderId)->first();
            
            // If order not found, create a temporary QR code with the provided order ID
            if (!$order) {
                $qrData = "QRIS.ID|MERCHANT.{$orderId}|AMOUNT.0|"
                    . "DATETIME." . date('YmdHis') . "|EXPIRE." . date('YmdHis', strtotime('+15 minutes'));

                // Generate QR code image as SVG and convert to base64
                $qrCodeSvg = QrCode::format('svg')
                        ->size(300)
                        ->errorCorrection('H')
                        ->generate($qrData);
                
                $qrCodeBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg);

                return response()->json([
                    'success' => true,
                    'message' => 'Temporary QR code generated',
                    'data' => [
                        'order_id' => $orderId,
                        'qr_code_data' => $qrData,
                        'qr_code_url' => $qrCodeBase64,
                        'amount' => 0,
                        'expires_at' => date('Y-m-d H:i:s', strtotime('+15 minutes')),
                    ]
                ], 200);
            }

            // Create QR code data based on payment method
            $qrData = '';
            $expiryTime = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            // Set payment deadline if not already set
            if (!$order->payment_deadline) {
                $order->payment_deadline = $expiryTime;
            } else {
                $expiryTime = $order->payment_deadline;
            }
            
            if ($order->payment_method === 'qris' || $order->payment_method === 'qr_code') {
                // For QRIS (Indonesian standard QR code payments)
                // Create a standardized string containing payment info
                $expiryTimeFormat = date('YmdHis', strtotime($expiryTime));
                $qrData = "QRIS.ID|MERCHANT.{$order->order_id}|AMOUNT.{$order->total_amount}|"
                        . "DATETIME." . date('YmdHis') . "|EXPIRE.{$expiryTimeFormat}";
            } else {
                // For Midtrans payment methods (using their token)
                $qrData = "MIDTRANS|{$order->midtrans_token}|{$order->order_id}|{$order->total_amount}";
            }

            // Store the QR code data on the order
            $order->qr_code_data = $qrData;
            
            // Generate QR code image as SVG and convert to base64
            $qrCodeSvg = QrCode::format('svg')
                        ->size(300)
                        ->errorCorrection('H')
                        ->generate($qrData);
            
            $qrCodeBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg);
            
            // Store the QR code image URL on the order (optional if you save images to disk)
            $order->qr_code_url = $qrCodeBase64;
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'QR code generated successfully',
                'data' => [
                    'order_id' => $order->order_id,
                    'qr_code_data' => $qrData,
                    'qr_code_url' => $qrCodeBase64,
                    'amount' => $order->total_amount,
                    'expires_at' => $expiryTime,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('QR Code Generation Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send order status update notification to the Flutter app
     * @param string $orderId
     * @param string $status
     * @param string $message
     * @return void
     */
    public function sendOrderStatusUpdate($orderId, $status, $message = null)
    {
        try {
            // Get order details
            $order = DB::table('orders')->where('order_id', $orderId)->first();
            
            if (!$order) {
                Log::error("Cannot send notification: Order {$orderId} not found");
                return;
            }
            
            // Get user details
            $user = DB::table('users')->where('id', $order->user_id)->first();
            
            if (!$user) {
                Log::error("Cannot send notification: User for order {$orderId} not found");
                return;
            }
            
            // Default message if not provided
            if (!$message) {
                switch ($status) {
                    case Order::STATUS_WAITING_FOR_PAYMENT:
                        $message = 'Pesanan Anda sedang menunggu pembayaran.';
                        break;
                    case Order::STATUS_PROCESSING:
                        $message = 'Pesanan Anda sedang diproses.';
                        break;
                    case Order::STATUS_SHIPPING:
                        $message = 'Pesanan Anda telah dikirim.';
                        break;
                    case Order::STATUS_DELIVERED:
                        $message = 'Pesanan Anda telah selesai.';
                        break;
                    case Order::STATUS_CANCELLED:
                        $message = 'Pesanan Anda telah dibatalkan.';
                        break;
                    default:
                        $message = 'Status pesanan Anda telah diperbarui.';
                }
            }
            
            // Prepare notification data
            $notificationData = [
                'order_id' => $orderId,
                'status' => $status,
                'message' => $message,
                'time' => now()->toIso8601String()
            ];
            
            // Log notification for debugging
            Log::info('Sending order status notification', $notificationData);
            
            /**
             * In a real implementation, you would send this notification to the 
             * Flutter app using Firebase FCM, a WebSocket, or another mechanism.
             * 
             * Example using Firebase Cloud Messaging (FCM):
             * 
             * $fcmToken = $user->fcm_token;
             * 
             * if ($fcmToken) {
             *    $client = new \GuzzleHttp\Client();
             *    $response = $client->post(
             *        'https://fcm.googleapis.com/fcm/send',
             *        [
             *            'headers' => [
             *                'Authorization' => 'key=YOUR_SERVER_KEY',
             *                'Content-Type' => 'application/json',
             *            ],
             *            'json' => [
             *                'to' => $fcmToken,
             *                'notification' => [
             *                    'title' => 'Status Pesanan Diperbarui',
             *                    'body' => $message,
             *                ],
             *                'data' => $notificationData,
             *            ],
             *        ]
             *    );
             *    
             *    Log::info('FCM Response: ' . $response->getBody());
             * }
             */
            
            // For now, we'll just log the notification
            Log::info('Order status notification would be sent', $notificationData);
            
            // Store notification in database for in-app notifications
            DB::table('notifications')->insert([
                'user_id' => $order->user_id,
                'type' => 'order_status',
                'data' => json_encode($notificationData),
                'read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending order status notification: ' . $e->getMessage());
        }
    }

    /**
     * Get order status message for notifications
     */
    private function getOrderStatusMessage($status)
    {
        $messages = [
            Order::STATUS_WAITING_FOR_PAYMENT => 'Pesanan Anda sedang menunggu pembayaran.',
            Order::STATUS_PROCESSING => 'Pesanan Anda sedang diproses.',
            Order::STATUS_SHIPPING => 'Pesanan Anda telah dikirim.',
            Order::STATUS_DELIVERED => 'Pesanan Anda telah selesai.',
            Order::STATUS_CANCELLED => 'Pesanan Anda telah dibatalkan.'
        ];

        return $messages[$status] ?? 'Status pesanan Anda telah diperbarui.';
    }
}