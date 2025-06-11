<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Order;
use App\Models\Admin;
use Carbon\Carbon;

class ChatController extends Controller
{
    /**
     * Get the user's chat or create a new one if not exists.
     */
    public function getChat(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Find or create a chat for this user
            $chat = Chat::where('user_id', $user->id)->first();
            
            if (!$chat) {
                $chat = Chat::create([
                    'user_id' => $user->id,
                    'status' => 'open',
                ]);
                
                // Add welcome message
                ChatMessage::create([
                    'chat_id' => $chat->id,
                    'message' => 'Selamat datang di Customer Support Bloom Bouquet! Ada yang bisa kami bantu?',
                    'is_admin' => true,
                    'is_from_user' => false,
                    'read_at' => now(),
                ]);
            }
            
            // Load the messages
            $messages = $chat->messages()->orderBy('created_at', 'desc')->get();
            
            // Mark all admin messages as read
            $this->markAdminMessagesAsRead($chat->id);
            
            // Get admin status (always online for better UX)
            $adminStatus = [
                'online' => true,
                'last_seen' => null
            ];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $chat->id,
                    'user_id' => $chat->user_id,
                    'admin_id' => $chat->admin_id,
                    'status' => $chat->status,
                    'created_at' => $chat->created_at,
                    'updated_at' => $chat->updated_at,
                    'messages' => $messages,
                    'admin_status' => $adminStatus,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting chat: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get chat: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a new message
     */
    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
                'message' => 'required|string|max:1000',
                'order_id' => 'nullable|exists:orders,id',
                'client_message_id' => 'nullable|string',
            ]);
            
            $user = Auth::user();
            
            // Find or create a chat for this user
            $chat = Chat::where('user_id', $user->id)->first();
            
            if (!$chat) {
                $chat = Chat::create([
                    'user_id' => $user->id,
                    'status' => 'open',
                ]);
            }
 
                
           
    
            $message = new ChatMessage([
                'chat_id' => $chat->id,
                'message' => $request->message,
                'is_admin' => false,
                'is_from_user' => true,
                'user_id' => $user->id,
                'client_message_id' => $request->client_message_id,
                'order_id' => $request->order_id,
                'created_at' => now(), // Explicitly set timestamp
                'updated_at' => now(),
            ]);
            
            // Add product info if provided
            if ($request->has('product_image_url') && $request->has('product_name')) {
                $message->product_images = [
                    [
                        'url' => $request->product_image_url,
                        'name' => $request->product_name,
                    ]
                ];
            }

            $message->save();
            
            // Debug log to verify message was saved correctly
            \Log::info('Customer message saved successfully', [
                'message_id' => $message->id,
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'message_text' => $request->message
            ]);
            
            // Update the chat's updated_at timestamp and set to unread
            $chat->update([
                'updated_at' => now(),
                'is_read' => false,
                'status' => 'open'  // Ensure the chat is marked as open
            ]);
            
            // Update user's last active status
            $user->update(['last_active' => now()]);
            
            // Notify all admins about new message
            $this->notifyAdminsAboutNewMessage($message);
            
            // If this is an order-related message, notify admins
            if ($request->order_id) {
                $this->notifyAdminsAboutOrderMessage($message, $request->order_id);
            }
            
            // Generate automatic response for better UX
            $autoResponse = $this->generateAutoResponse($message);
            if ($autoResponse) {
                // Add the auto response to the response
                return response()->json([
                    'success' => true,
                    'data' => $message,
                    'auto_response' => $autoResponse,
                ]);
            }
            
            return response()->json([
                'success' => true,
                'data' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending message: ' . $e->getMessage());
            
            // Try to save the message even if there was an error
            try {
                if (Auth::check()) {
                    $user = Auth::user();
                    $chat = Chat::where('user_id', $user->id)->first();
                    
                    if ($chat) {
                        $fallbackMessage = new ChatMessage([
                            'chat_id' => $chat->id,
                            'message' => $request->message ?? 'Message could not be processed',
                            'is_admin' => false,
                            'is_from_user' => true,
                            'user_id' => $user->id,
                            'client_message_id' => $request->client_message_id,
                            'order_id' => $request->order_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        
                        $fallbackMessage->save();
                        
                        // Debug log for fallback message
                        \Log::info('Fallback message saved', [
                            'message_id' => $fallbackMessage->id,
                            'chat_id' => $chat->id
                        ]);
                        
                        // Update the chat
                        $chat->update([
                            'updated_at' => now(),
                            'is_read' => false,
                            'status' => 'open'
                        ]);
                        
                        // Notify admins
                        $this->notifyAdminsAboutNewMessage($fallbackMessage);
                        
                        return response()->json([
                            'success' => true,
                            'data' => $fallbackMessage,
                            'warning' => 'Message saved with fallback method',
                        ]);
                    }
                }
            } catch (\Exception $innerException) {
                Log::error('Error in fallback message save: ' . $innerException->getMessage());
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Generate an automatic response based on message content
     */
    private function generateAutoResponse($message)
    {
        // Only generate auto responses for certain types of messages
        $keywords = ['halo', 'hai', 'hi', 'hello', 'selamat', 'pagi', 'siang', 'sore', 'malam'];
        
        $containsGreeting = false;
        foreach ($keywords as $keyword) {
            if (stripos(strtolower($message->message), $keyword) !== false) {
                $containsGreeting = true;
                break;
            }
        }
        
        if ($containsGreeting) {
            // Create an automatic greeting response
            $autoResponse = new ChatMessage([
                'chat_id' => $message->chat_id,
                'message' => 'Halo! Terima kasih telah menghubungi Bloom Bouquet. Customer service kami akan segera membalas pesan Anda. Ada yang bisa kami bantu?',
                'is_admin' => true,
                'is_from_user' => false,
                'admin_id' => 1, // Default admin ID
                'read_at' => null,
                'timestamp' => now()->addSeconds(2),
            ]);
            
            $autoResponse->save();
            return $autoResponse;
        }
        
        // If order-related, provide order-specific auto-response
        if ($message->order_id) {
            $order = Order::find($message->order_id);
            if ($order) {
                $autoResponse = new ChatMessage([
                    'chat_id' => $message->chat_id,
                    'message' => "Terima kasih telah menghubungi kami tentang pesanan #{$order->id}. Status pesanan Anda saat ini adalah '{$order->status}'. Customer service kami akan segera membalas pesan Anda.",
                    'is_admin' => true,
                    'is_from_user' => false,
                    'admin_id' => 1,
                    'order_id' => $message->order_id,
                    'read_at' => null,
                    'timestamp' => now()->addSeconds(2),
                ]);
                
                $autoResponse->save();
                return $autoResponse;
            }
        }
        
        return null;
    }
    
    /**
     * Notify admins about new order-related messages
     */
    private function notifyAdminsAboutOrderMessage($message, $orderId)
    {
        try {
            $order = Order::find($orderId);
            if (!$order) return;
            
            // Get all admins
            $admins = Admin::all();
            
            // Create a single notification for all admins (since we removed admin_id)
            \App\Models\Notification::create([
                'user_id' => null, // For admin notifications
                'order_id' => $orderId,
                'title' => 'New message about Order #' . $orderId,
                'message' => 'Customer sent a message regarding Order #' . $orderId,
                'type' => 'chat_message',
                'is_read' => false,
            ]);
        } catch (\Exception $e) {
            Log::error('Error notifying admins about order message: ' . $e->getMessage());
        }
    }
    
    /**
     * Notify admins about new messages
     */
    private function notifyAdminsAboutNewMessage($message)
    {
        try {
            // Get the user who sent the message
            $user = User::find($message->user_id);
            $userName = $user ? $user->name : 'Customer';
            
            // Create a notification for all admins
            $notification = new \App\Models\Notification([
                'type' => 'chat',
                'title' => 'New Message from ' . $userName,
                'message' => \Str::limit($message->message, 100),
                'data' => json_encode([
                    'chat_id' => $message->chat_id,
                    'message_id' => $message->id,
                    'user_id' => $message->user_id,
                    'user_name' => $userName,
                ]),
                'status' => 'unread',
                'url' => '/admin/chats/' . $message->chat_id,
            ]);
            
            $notification->save();
            
            // Log notification creation
            \Log::info('New chat notification created for admins', [
                'notification_id' => $notification->id,
                'chat_id' => $message->chat_id,
                'message_id' => $message->id
            ]);
            
            // Create a single notification for all admins (since we removed admin_id)
            \App\Models\Notification::create([
                'user_id' => null, // For admin notifications
                'order_id' => 'CHAT-' . $message->chat_id,
                'title' => 'New Message from ' . $userName,
                'message' => \Str::limit($message->message, 100),
                'type' => 'chat_message',
                'is_read' => false,
            ]);
            
            // Also create a record in the chat_admin_notifications table if it exists
            if (class_exists('\\App\\Models\\ChatAdminNotification')) {
                \App\Models\ChatAdminNotification::create([
                    'chat_id' => $message->chat_id,
                    'message_id' => $message->id,
                    'is_read' => false,
                    'created_at' => now(),
                ]);
            }
            
            // Update the chat to indicate there are unread messages
            \App\Models\Chat::where('id', $message->chat_id)->update([
                'is_read' => false,
                'updated_at' => now(),
                'has_unread' => true
            ]);
            
            // Broadcast the notification to admins via websocket if available
            if (class_exists('\\App\\Events\\NewChatMessage')) {
                event(new \App\Events\NewChatMessage($message));
            }
        } catch (\Exception $e) {
            Log::error('Error notifying admins about new message: ' . $e->getMessage());
        }
    }
    
    /**
     * Mark admin messages as read for a chat
     */
    private function markAdminMessagesAsRead($chatId)
    {
        try {
            ChatMessage::where('chat_id', $chatId)
                ->where('is_admin', true)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        } catch (\Exception $e) {
            Log::error('Error marking admin messages as read: ' . $e->getMessage());
        }
    }
    
    /**
     * Get new messages since the last message ID.
     */
    public function getNewMessages(Request $request)
    {
        try {
            $request->validate([
                'last_message_id' => 'required|integer',
            ]);
            
            $user = Auth::user();
            $afterId = $request->last_message_id;
            
            $chat = Chat::where('user_id', $user->id)->first();
            
            if (!$chat) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }
            
            $messages = ChatMessage::where('chat_id', $chat->id)
                ->where('id', '>', $afterId)
                ->orderBy('created_at', 'asc')
                ->get();
            
            // Mark messages as read
            $this->markAdminMessagesAsRead($chat->id);
            
            // Update user's last active status
            $user->update(['last_active' => now()]);
            
            return response()->json([
                'success' => true,
                'data' => $messages,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting new messages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get new messages: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Mark messages as read.
     */
    public function markAsRead(Request $request)
    {
        try {
            $user = Auth::user();
            
            $chat = Chat::where('user_id', $user->id)->first();
            
            if (!$chat) {
                return response()->json([
                    'success' => true,
                    'message' => 'No chat found.',
                ]);
            }
            
            // Mark all admin messages as read
            $this->markAdminMessagesAsRead($chat->id);
            
            // Update user's last active status
            $user->update(['last_active' => now()]);
            
            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking messages as read: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark messages as read: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Update user typing status
     */
    public function updateTypingStatus(Request $request)
    {
        try {
            $request->validate([
                'is_typing' => 'required|boolean',
            ]);
            
            $user = Auth::user();
            
            // Update user's typing status
            $user->update([
                'is_typing' => $request->is_typing,
                'last_active' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Typing status updated.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating typing status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update typing status: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get messages related to a specific order
     */
    public function getOrderMessages(Request $request, $orderId)
    {
        try {
            $user = Auth::user();
            
            // Verify the order belongs to this user
            $order = Order::where('id', $orderId)
                ->where('user_id', $user->id)
                ->first();
                
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or does not belong to you.',
                ], 404);
            }
            
            // Get messages related to this order
            $messages = ChatMessage::where('order_id', $orderId)
                ->orderBy('created_at', 'asc')
                ->get();
                
            return response()->json([
                'success' => true,
                'data' => $messages,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting order messages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get order messages: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if any admin is online
     */
    public function checkAdminStatus(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Find the chat for this user
            $chat = Chat::where('user_id', $user->id)->first();
            
            // Always return admin as online for better UX
            return response()->json([
                'success' => true,
                'data' => [
                    'admin_online' => true,
                    'last_seen' => null
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking admin status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check admin status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check for admin responses in real-time
     */
    public function checkAdminResponses(Request $request)
    {
        try {
            $request->validate([
                'last_checked' => 'required|date_format:Y-m-d\TH:i:s.u\Z|date_format:Y-m-d\TH:i:s\Z|date_format:Y-m-d H:i:s',
            ]);
            
            $user = Auth::user();
            $lastChecked = $request->last_checked;
            
            $chat = Chat::where('user_id', $user->id)->first();
            
            if (!$chat) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'has_new_messages' => false,
                        'messages' => []
                    ]
                ]);
            }
            
            // Get admin messages since last checked time
            $messages = ChatMessage::where('chat_id', $chat->id)
                ->where('is_admin', true)
                ->where('created_at', '>', $lastChecked)
                ->orderBy('created_at', 'asc')
                ->get();
            
            // Mark these messages as read
            $this->markAdminMessagesAsRead($chat->id);
            
            // Update user's last active status
            $user->update(['last_active' => now()]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'has_new_messages' => $messages->isNotEmpty(),
                    'messages' => $messages,
                    'admin_online' => true,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking admin responses: ' . $e->getMessage() . ' with data: ' . json_encode($request->all()));
            return response()->json([
                'success' => false,
                'message' => 'Failed to check admin responses: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if a specific message has been read by admin
     */
    public function getMessageStatus(Request $request, $messageId)
    {
        try {
            $user = Auth::user();
            
            // Find the message
            $message = ChatMessage::where('id', $messageId)
                ->where('is_from_user', true)
                ->first();
                
            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found.',
                ], 404);
            }
            
            // Check if the message belongs to this user
            $chat = Chat::where('id', $message->chat_id)
                ->where('user_id', $user->id)
                ->first();
                
            if (!$chat) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat not found or does not belong to you.',
                ], 404);
            }
            
            // Return message status
            return response()->json([
                'success' => true,
                'data' => [
                    'is_delivered' => true, // Always mark as delivered when checking
                    'is_read' => $message->read_at !== null,
                    'read_at' => $message->read_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting message status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get message status: ' . $e->getMessage(),
            ], 500);
        }
    }
} 