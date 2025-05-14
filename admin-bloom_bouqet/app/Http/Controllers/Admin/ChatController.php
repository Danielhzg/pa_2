<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Use eager loading with proper relationship paths
            $chats = Chat::with(['user', 'lastMessage', 'messages'])
                ->orderBy('updated_at', 'desc')
                ->get();
            
            // Tambahkan jumlah pesan yang belum dibaca
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
                }
            });
            
            // Jika request AJAX, return JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'chats' => $chats
                ]);
            }

            return view('admin.chats.index', compact('chats'));
        } catch (\Exception $e) {
            Log::error('Error fetching chats: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memuat daftar chat: ' . $e->getMessage()
                ], 500);
            }
            
            return view('admin.chats.index', ['chats' => collect(), 'error' => 'Gagal memuat daftar chat. Silakan coba lagi.']);
        }
    }

    public function show(Chat $chat)
    {
        try {
            $chat->load(['user', 'messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }]);

            // Mark unread messages as read
            ChatMessage::where('chat_id', $chat->id)
                ->where('is_admin', false)
                ->where('read_at', null)
                ->update(['read_at' => now()]);

            // Check if it's a partial request
            if (request()->has('partial')) {
                return view('admin.chats.show', compact('chat'))->render();
            }

            return view('admin.chats.show', compact('chat'));
        } catch (\Exception $e) {
            Log::error('Error showing chat: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load chat. Please try again.');
        }
    }

    public function sendMessage(Request $request, Chat $chat)
    {
        try {
            $request->validate([
                'message' => 'required|string|max:1000',
            ]);

            $message = new ChatMessage([
                'chat_id' => $chat->id,
                'message' => $request->message,
                'is_admin' => true,
                'read_at' => now(),
            ]);

            $message->save();

            $chat->update(['updated_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending message: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message. Please try again.',
            ], 500);
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

    public function getNewMessages(Chat $chat)
    {
        try {
            $lastMessageId = request('last_message_id', 0);
            
            $messages = ChatMessage::where('chat_id', $chat->id)
                ->where('id', '>', $lastMessageId)
                ->orderBy('created_at', 'asc')
                ->get();

            // Mark new messages as read
            if ($messages->isNotEmpty()) {
                ChatMessage::where('chat_id', $chat->id)
                    ->where('is_admin', false)
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);
            }

            return response()->json([
                'success' => true,
                'messages' => $messages,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting new messages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get new messages.',
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
            // Delete all messages from this chat
            ChatMessage::where('chat_id', $chat->id)->delete();
            
            // Add a system message indicating the chat was cleared
            $systemMessage = new ChatMessage([
                'chat_id' => $chat->id,
                'message' => 'Riwayat chat telah dihapus oleh admin',
                'is_admin' => true,
                'read_at' => now(),
                'is_system' => true,
            ]);
            $systemMessage->save();
            
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
    
    /**
     * Mark all messages as read for the admin
     */
    public function markAllAsRead()
    {
        try {
            // Update all unread messages from users (non-admin messages)
            $count = ChatMessage::where('is_admin', false)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
                
            return response()->json([
                'success' => true,
                'message' => 'All messages marked as read.',
                'count' => $count
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking all messages as read: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark messages as read.',
            ], 500);
        }
    }

    /**
     * Poll for updates across all chats
     */
    public function poll()
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
                        ] : null
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
                    'time' => $newMessage->created_at->format('H:i')
                ];
            }
            
            return response()->json([
                'success' => true,
                'chats' => $chats,
                'new_message' => $newMessageData
            ]);
        } catch (\Exception $e) {
            Log::error('Error polling chats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to poll for chat updates.',
            ], 500);
        }
    }
} 