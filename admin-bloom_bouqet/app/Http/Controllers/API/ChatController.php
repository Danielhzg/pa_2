<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

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
                    'read_at' => now(),
                ]);
            }
            
            // Load the messages
            $messages = $chat->messages()->orderBy('created_at', 'asc')->get();
            
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
     * Send a new message.
     */
    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
                'message' => 'required|string|max:1000',
                'product_image_url' => 'nullable|string',
                'product_name' => 'nullable|string',
            ]);
            
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
                    'read_at' => now(),
                ]);
            }
            
            $message = ChatMessage::create([
                'chat_id' => $chat->id,
                'message' => $request->message,
                'is_admin' => false,
                'attachment_url' => $request->product_image_url,
            ]);
            
            // Update chat's updated_at timestamp
            $chat->update(['updated_at' => now()]);
            
            // Update user's last active status
            $user->update(['last_active' => now()]);
            
            return response()->json([
                'success' => true,
                'data' => $message,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error sending message: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
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
            ChatMessage::where('chat_id', $chat->id)
                ->where('is_admin', true)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
            
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
} 