<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::query();

        if (Auth::guard('admin')->check()) {
            $query->where('admin_id', Auth::guard('admin')->id());
        } else {
            $query->where('user_id', Auth::id());
        }

        $notifications = $query->orderBy('created_at', 'desc')
                             ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'notifications' => $notifications,
                'unread_count' => $query->where('status', 'unread')->count()
            ]);
        }

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request)
    {
        $notification = Notification::findOrFail($request->notification_id);
        
        if (Auth::guard('admin')->check()) {
            if ($notification->admin_id !== Auth::guard('admin')->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } else {
            if ($notification->user_id !== Auth::id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    public function markAllAsRead()
    {
        $query = Notification::query();

        if (Auth::guard('admin')->check()) {
            $query->where('admin_id', Auth::guard('admin')->id());
        } else {
            $query->where('user_id', Auth::id());
        }

        $query->where('status', 'unread')
              ->update([
                  'status' => 'read',
                  'read_at' => now()
              ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    public function getUnreadCount()
    {
        $query = Notification::query();

        if (Auth::guard('admin')->check()) {
            $query->where('admin_id', Auth::guard('admin')->id());
        } else {
            $query->where('user_id', Auth::id());
        }

        $count = $query->where('status', 'unread')->count();

        return response()->json([
            'unread_count' => $count
        ]);
    }
} 