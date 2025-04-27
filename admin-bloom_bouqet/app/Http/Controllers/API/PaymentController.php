<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

            // Save transaction to database if needed
            // DB::table('transactions')->insert([...]);

            return response()->json([
                'success' => true,
                'message' => 'Payment token generated successfully',
                'data' => [
                    'order_id' => $request->order_id,
                    'token' => $snapToken,
                    'redirect_url' => $redirectUrl,
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
     * Handle notification from Midtrans
     */
    public function notification(Request $request)
    {
        try {
            $notification = new \Midtrans\Notification();
            
            $order_id = $notification->order_id;
            $status_code = $notification->status_code;
            $transaction_status = $notification->transaction_status;
            $fraud_status = $notification->fraud_status;
            
            Log::info('Midtrans Notification', [
                'order_id' => $order_id,
                'status_code' => $status_code,
                'transaction_status' => $transaction_status,
                'fraud_status' => $fraud_status,
            ]);
            
            // Handle different transaction status
            $payment_status = 'pending';
            
            if ($transaction_status == 'capture') {
                if ($fraud_status == 'challenge') {
                    $payment_status = 'challenge';
                } else if ($fraud_status == 'accept') {
                    $payment_status = 'success';
                }
            } else if ($transaction_status == 'settlement') {
                $payment_status = 'success';
            } else if ($transaction_status == 'cancel' || 
                      $transaction_status == 'deny' || 
                      $transaction_status == 'expire') {
                $payment_status = 'failed';
            } else if ($transaction_status == 'pending') {
                $payment_status = 'pending';
            }
            
            // Update order status in your database
            // DB::table('orders')->where('order_id', $order_id)->update(['payment_status' => $payment_status]);
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Notification Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process notification: ' . $e->getMessage(),
            ], 500);
        }
    }
} 