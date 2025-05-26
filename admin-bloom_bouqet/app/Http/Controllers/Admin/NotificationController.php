<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::query();
        $query->where('admin_id', Auth::guard('admin')->id());

        $notifications = $query->orderBy('created_at', 'desc')
                             ->paginate(10);

        if ($request->ajax()) {
            if ($request->has('ajax') && $request->ajax === 'true') {
                // Return HTML for the notifications dropdown
                return view('admin.notifications.partials.notification_list', compact('notifications'))->render();
            }
            
            return response()->json([
                'notifications' => $notifications,
                'unread_count' => $query->where('status', 'unread')->count()
            ]);
        }

        return view('admin.notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request, $notificationId)
    {
        $notification = Notification::findOrFail($notificationId);
        
        if ($notification->admin_id !== Auth::guard('admin')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();
        
        // Return additional data for order notifications
        $responseData = [
            'success' => true,
            'message' => 'Notification marked as read'
        ];
        
        // If this is an order notification, add order data
        if ($notification->type === 'order' && isset($notification->data['order_id'])) {
            $responseData['type'] = 'order';
            $responseData['order_id'] = $notification->data['order_id'];
        }

        return response()->json($responseData);
    }

    public function markAllAsRead()
    {
        $query = Notification::query();
        $query->where('admin_id', Auth::guard('admin')->id());

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
        $query->where('admin_id', Auth::guard('admin')->id());

        $count = $query->where('status', 'unread')->count();
        
        // Calculate new notifications since last check
        $lastCheckTime = session('last_notification_check_time', now()->subMinutes(5));
        $newSinceLastCheck = $query->where('status', 'unread')
                                  ->where('created_at', '>', $lastCheckTime)
                                  ->count();
        
        // Update last notification check time
        session(['last_notification_check_time' => now()]);

        return response()->json([
            'count' => $count,
            'new_since_last_check' => $newSinceLastCheck
        ]);
    }
} 