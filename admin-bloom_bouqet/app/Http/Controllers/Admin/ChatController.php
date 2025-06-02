<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Use eager loading with proper relationship paths
            $chats = Chat::with(['user', 'lastMessage', 'messages'])
                ->orderBy('updated_at', 'desc')
                ->get();
            
            // Add unread count and other details
            $chats->each(function($chat) {
                // Compute unread count from the already loaded messages
                if ($chat->messages) {
                    $chat->unread_count = $chat->messages
                        ->where('is_admin', false)
                        ->whereNull('read_at')
                        ->count();
                } else {
                    $chat->unread_count = 0;
                }
                
                // Add last message for preview
                if ($chat->lastMessage) {
                    $chat->last_message = $chat->lastMessage->message;
                    $chat->last_message_time = $chat->lastMessage->created_at->format('H:i');
                }
                
                // Get user's orders if available
                if ($chat->user) {
                    $chat->user_orders = Order::where('user_id', $chat->user->id)
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get();
                }
                
                // Always set admin as online
                $chat->admin_online = true;
            });
            
            // If request AJAX, return JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'chats' => $chats
                ]);
            }

            return view('admin.chats.index', compact('chats'));
        } catch (\Exception $e) {
            Log::error('Error in chat index: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load chats: ' . $e->getMessage()
                ], 500);
            }
            
            return view('admin.chats.index')->with('error', 'Failed to load chats: ' . $e->getMessage());
        }
    }

    public function show(Chat $chat)
    {
        try {
            // Load messages with eager loading
            $chat->load(['user', 'messages' => function($query) {
                $query->orderBy('created_at', 'asc');
            }]);
            
            // Load user's orders
            if ($chat->user) {
                $userOrders = Order::where('user_id', $chat->user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            } else {
                $userOrders = collect();
            }

            // Mark unread messages as read
            $this->markMessagesAsRead($chat);
            
            // Set admin ID for this chat
            $adminId = Auth::guard('admin')->id();
            if ($adminId) {
                $chat->update([
                    'admin_id' => $adminId,
                    'status' => 'active'
                ]);
            }
                
            if (request()->has('partial')) {
                return view('admin.chats.partial', compact('chat', 'userOrders'));
            }

            return view('admin.chats.show', compact('chat', 'userOrders'));
        } catch (\Exception $e) {
            Log::error('Error showing chat: ' . $e->getMessage());
            
            if (request()->has('partial')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load chat: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->route('admin.chats.index')->with('error', 'Failed to load chat: ' . $e->getMessage());
        }
    }

    public function sendMessage(Chat $chat, Request $request)
    {
        try {
            $request->validate([
                'message' => 'required|string|max:1000',
                'order_id' => 'nullable|exists:orders,id',
            ]);

            // Get the admin ID
            $adminId = auth()->guard('admin')->id();

            $message = new ChatMessage([
                'chat_id' => $chat->id,
                'message' => $request->message,
                'is_admin' => true,
                'is_from_user' => false,
                'admin_id' => $adminId, // Add admin ID
                'read_at' => now(),
                'order_id' => $request->order_id,
                'timestamp' => now(),
            ]);

            $message->save();

            // Update the chat with admin ID and set status to active
            $chat->update([
                'updated_at' => now(),
                'admin_id' => $adminId,
                'status' => 'active'
            ]);
            
            // If this is an order-related message, update the order
            if ($request->order_id) {
                try {
                    $order = Order::find($request->order_id);
                    if ($order) {
                        // Update order with chat reference
                        $order->update([
                            'has_chat_activity' => true,
                            'last_chat_message_id' => $message->id,
                            'last_chat_update' => now(),
                        ]);
                        
                        // If the message contains status update information, update the order status
                        $this->checkForOrderStatusUpdate($message, $order);
                    }
                } catch (\Exception $orderEx) {
                    Log::error('Error updating order with chat info: ' . $orderEx->getMessage());
                    // Continue processing even if order update fails
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending message: ' . $e->getMessage());
            
            // Try to save the message even if validation failed
            try {
                $adminId = auth()->guard('admin')->id();
                
                if ($adminId) {
                    $fallbackMessage = new ChatMessage([
                        'chat_id' => $chat->id,
                        'message' => $request->message ?? 'Message could not be processed',
                        'is_admin' => true,
                        'is_from_user' => false,
                        'admin_id' => $adminId,
                        'read_at' => now(),
                        'order_id' => $request->order_id,
                        'timestamp' => now(),
                    ]);
                    
                    $fallbackMessage->save();
                    
                    // Update the chat
                    $chat->update([
                        'updated_at' => now(),
                        'admin_id' => $adminId,
                        'status' => 'active'
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'message' => $fallbackMessage,
                        'warning' => 'Message saved with fallback method',
                    ]);
                }
            } catch (\Exception $innerException) {
                Log::error('Error in fallback message save: ' . $innerException->getMessage());
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message. Please try again.',
            ], 500);
        }
    }
    
    /**
     * Check if the message contains order status update information
     */
    private function checkForOrderStatusUpdate($message, $order)
    {
        // Check for status update keywords
        $statusKeywords = [
            'processed' => 'processing',
            'shipped' => 'shipped',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'completed' => 'completed',
            'diproses' => 'processing',
            'dikirim' => 'shipped',
            'terkirim' => 'delivered',
            'dibatalkan' => 'cancelled',
            'selesai' => 'completed',
        ];
        
        $messageText = strtolower($message->message);
        $updatedStatus = null;
        
        foreach ($statusKeywords as $keyword => $status) {
            if (strpos($messageText, $keyword) !== false) {
                $updatedStatus = $status;
                break;
            }
        }
        
        if ($updatedStatus && $updatedStatus != $order->status) {
            // Update the order status
            $order->update([
                'status' => $updatedStatus,
                'status_updated_at' => now(),
            ]);
            
            // Add a system message about the status update
            ChatMessage::create([
                'chat_id' => $message->chat_id,
                'message' => "Order status has been updated to: $updatedStatus",
                'is_admin' => true,
                'is_from_user' => false,
                'admin_id' => $message->admin_id,
                'order_id' => $order->id,
                'is_system' => true,
                'read_at' => now(),
                'timestamp' => now(),
            ]);
        }
    }

    public function getUnreadCount()
    {
        try {
            $unreadCount = ChatMessage::where('is_admin', false)
                ->whereNull('read_at')
                ->count();

            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting unread count: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread count.',
            ], 500);
        }
    }

    /**
     * Get new messages for a chat
     */
    public function getNewMessages(Chat $chat)
    {
        try {
            $lastMessageId = request()->get('last_message_id', 0);
            
            \Log::info('Fetching new messages', [
                'chat_id' => $chat->id,
                'last_message_id' => $lastMessageId
            ]);
            
            // Query messages newer than the last message ID
            $newMessages = ChatMessage::where('chat_id', $chat->id)
                ->where('id', '>', $lastMessageId)
                ->orderBy('created_at', 'asc') // Oldest first to maintain chronological order
                ->get();

            \Log::info('Found new messages', [
                'count' => $newMessages->count(),
                'message_ids' => $newMessages->pluck('id')->toArray()
            ]);
            
            // Mark any user messages as read
            $unreadCount = ChatMessage::where('chat_id', $chat->id)
                ->where('is_admin', false)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
            
            \Log::info('Marked messages as read', [
                'unread_count' => $unreadCount
            ]);
            
            // Update chat status
            $chat->update([
                'is_read' => true,
                'updated_at' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'messages' => $newMessages
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting new messages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get new messages: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mark messages as read
     */
    public function markMessagesAsRead(Chat $chat, $messageId = null)
    {
        try {
            $query = ChatMessage::where('chat_id', $chat->id)
                ->where('is_admin', false)
                ->whereNull('read_at');
            
            // If specific message ID is provided, only mark that one as read
            if ($messageId) {
                $query->where('id', $messageId);
            }
            
            $markedCount = $query->update(['read_at' => now()]);
            
            // Log the action
            \Log::info('Marked messages as read', [
                'chat_id' => $chat->id,
                'message_id' => $messageId,
                'count' => $markedCount
            ]);
            
            // Update admin's last active timestamp
            $adminId = Auth::guard('admin')->id();
            if ($adminId) {
                \App\Models\Admin::where('id', $adminId)->update([
                    'last_active' => now(),
                    'is_online' => true,
                ]);
            }
            
            // Update chat status
            $chat->update([
                'updated_at' => now(),
                'is_read' => true,
                'has_unread' => false
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read',
                'count' => $markedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking messages as read: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark messages as read: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mark all chats as read
     */
    public function markAllAsRead()
    {
        try {
            // Get all chats with unread messages
            $chats = Chat::whereHas('messages', function($query) {
                $query->where('is_admin', false)->whereNull('read_at');
            })->get();
            
            \Log::info('Marking all chats as read', [
                'chat_count' => $chats->count(),
                'chat_ids' => $chats->pluck('id')->toArray()
            ]);
            
            $totalMarked = 0;
            foreach ($chats as $chat) {
                $count = ChatMessage::where('chat_id', $chat->id)
                    ->where('is_admin', false)
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);
                    
                $totalMarked += $count;
                
                // Update chat status
                $chat->update([
                    'is_read' => true,
                    'has_unread' => false,
                    'updated_at' => now()
                ]);
            }
            
            \Log::info('Marked messages as read', [
                'total_messages_marked' => $totalMarked
            ]);
            
            // Update admin's last active timestamp
            $adminId = Auth::guard('admin')->id();
            if ($adminId) {
                \App\Models\Admin::where('id', $adminId)->update([
                    'last_active' => now(),
                    'is_online' => true,
                ]);
                
                // Clear admin notifications for chat
                if (class_exists('\\App\\Models\\AdminNotification')) {
                    \App\Models\AdminNotification::where('admin_id', $adminId)
                        ->where('type', 'chat')
                        ->update(['is_read' => true]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'All messages marked as read',
                'count' => $totalMarked
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking all messages as read: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all messages as read: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function checkNewMessages(Chat $chat)
    {
        try {
            $unreadCount = ChatMessage::where('chat_id', $chat->id)
                ->where('is_admin', false)
                ->whereNull('read_at')
                ->count();
                
            $lastMessage = $chat->messages()->latest()->first();
            
            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount,
                'last_message' => $lastMessage ? \Str::limit($lastMessage->message, 40) : null,
                'last_message_id' => $lastMessage ? $lastMessage->id : 0,
                'last_message_time' => $lastMessage ? $lastMessage->created_at->format('H:i') : null,
                'admin_online' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking new messages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check new messages.',
            ], 500);
        }
    }
    
    public function clearChat(Chat $chat)
    {
        try {
            // Delete all messages in this chat
            $chat->messages()->delete();
            
            // Add system message
            $message = new ChatMessage([
                'chat_id' => $chat->id,
                'message' => 'Chat history has been cleared by admin',
                'is_admin' => true,
                'is_from_user' => false,
                'is_system' => true,
                'admin_id' => Auth::guard('admin')->id(),
                'read_at' => now(),
                'timestamp' => now(),
            ]);
            
            $message->save();
            
            $chat->update(['updated_at' => now()]);
            
            return response()->json([
                'success' => true,
                'message' => 'Chat history cleared successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing chat: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear chat history.',
            ], 500);
        }
    }
    
    public function checkAllChatsForNewMessages()
    {
        try {
            // Get all chats with unread messages
            $chats = Chat::with(['user', 'lastMessage'])
                ->get()
                ->map(function($chat) {
                    $unreadCount = ChatMessage::where('chat_id', $chat->id)
                        ->where('is_admin', false)
                        ->whereNull('read_at')
                        ->count();
                    
                    $lastMessage = $chat->lastMessage;
                    
                    return [
                        'id' => $chat->id,
                        'unread_count' => $unreadCount,
                        'last_message' => $lastMessage ? [
                            'id' => $lastMessage->id,
                            'message' => $lastMessage->message,
                            'preview' => \Str::limit($lastMessage->message, 30),
                            'time' => $lastMessage->created_at->format('H:i'),
                            'is_admin' => $lastMessage->is_admin,
                            'order_id' => $lastMessage->order_id,
                        ] : null,
                        'user' => $chat->user ? [
                            'id' => $chat->user->id,
                            'name' => $chat->user->name,
                            'email' => $chat->user->email,
                        ] : null,
                        'admin_online' => true,
                    ];
                });
            
            // Get the most recent unread message for notification
            $newMessage = ChatMessage::where('is_admin', false)
                ->whereNull('read_at')
                ->with('chat.user')
                ->latest()
                ->first();
                
            $newMessageData = null;
            if ($newMessage) {
                $newMessageData = [
                    'sender' => $newMessage->chat->user->name ?? 'User',
                    'message' => \Str::limit($newMessage->message, 50),
                    'time' => $newMessage->created_at->format('H:i'),
                    'order_id' => $newMessage->order_id,
                    'chat_id' => $newMessage->chat_id,
                ];
                
                // Add order information if available
                if ($newMessage->order_id) {
                    $order = Order::find($newMessage->order_id);
                    if ($order) {
                        $newMessageData['order_info'] = [
                            'id' => $order->id,
                            'order_id' => $order->order_id,
                            'status' => $order->status,
                        ];
                    }
                }
            }
            
            // Update admin online status
            $adminId = Auth::guard('admin')->id();
            if ($adminId) {
                \App\Models\Admin::where('id', $adminId)->update([
                    'last_active' => now(),
                    'is_online' => true,
                ]);
            }
            
            return response()->json([
                'success' => true,
                'chats' => $chats,
                'new_message' => $newMessageData,
                'admin_online' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking all chats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check chats for new messages.',
            ], 500);
        }
    }
    
    public function getOrderMessages($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            
            $messages = ChatMessage::where('order_id', $orderId)
                ->orderBy('created_at', 'asc')
                ->get();
                
            return response()->json([
                'success' => true,
                'order' => $order,
                'messages' => $messages,
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
     * Send typing indicator to user
     */
    public function sendTypingIndicator(Chat $chat)
    {
        try {
            $adminId = Auth::guard('admin')->id();
            
            // Create a typing indicator message (will be temporary)
            $typingMessage = new ChatMessage([
                'chat_id' => $chat->id,
                'message' => '...',
                'is_admin' => true,
                'is_from_user' => false,
                'admin_id' => $adminId,
                'is_typing' => true,
                'timestamp' => now(),
            ]);
            
            $typingMessage->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Typing indicator sent.',
                'typing_message_id' => $typingMessage->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending typing indicator: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send typing indicator.',
            ], 500);
        }
    }
    
    /**
     * Remove typing indicator
     */
    public function removeTypingIndicator(Chat $chat, $typingMessageId)
    {
        try {
            // Delete the typing indicator message
            ChatMessage::where('id', $typingMessageId)
                ->where('chat_id', $chat->id)
                ->where('is_typing', true)
                ->delete();
                
            return response()->json([
                'success' => true,
                'message' => 'Typing indicator removed.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error removing typing indicator: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove typing indicator.',
            ], 500);
        }
    }
} 