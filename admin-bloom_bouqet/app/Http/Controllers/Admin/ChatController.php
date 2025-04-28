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
    public function index()
    {
        try {
            $chats = Chat::with(['user', 'lastMessage'])
                ->orderBy('updated_at', 'desc')
                ->get();

            return view('admin.chats.index', compact('chats'));
        } catch (\Exception $e) {
            Log::error('Error fetching chats: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to fetch chats. Please try again.');
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
                ->where('read_at', null)
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
                    ->where('read_at', null)
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
} 