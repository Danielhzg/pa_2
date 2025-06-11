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
        // For admin notifications, we can filter by type or show all
        // Since we removed admin_id, we'll show all notifications for admin view

        $notifications = $query->orderBy('created_at', 'desc')
                             ->paginate(10);

        if ($request->ajax()) {
            if ($request->has('ajax') && $request->ajax === 'true') {
                // Return HTML for the notifications dropdown
                return view('admin.notifications.partials.notification_list', compact('notifications'))->render();
            }

            return response()->json([
                'notifications' => $notifications,
                'unread_count' => $query->where('is_read', false)->count()
            ]);
        }

        return view('admin.notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request, $notificationId)
    {
        try {
            $notification = Notification::findOrFail($notificationId);

            // Since we removed admin_id, we'll allow admin to mark any notification as read
            // In production, you might want to add proper authorization logic here

            $notification->markAsRead();
            
            // Return additional data for notification redirects
            $responseData = [
                'success' => true,
                'message' => 'Notification marked as read'
            ];
            
            // Add type and URL information based on notification type
            $responseData['type'] = $notification->type;
            $responseData['url'] = $notification->getUrl();
            
            if ($notification->type === 'order' && isset($notification->data['order_id'])) {
                $responseData['order_id'] = $notification->data['order_id'];
            } elseif ($notification->type === 'payment' && isset($notification->data['order_id'])) {
                $responseData['order_id'] = $notification->data['order_id'];
            } elseif ($notification->type === 'product' && isset($notification->data['product_id'])) {
                $responseData['product_id'] = $notification->data['product_id'];
            } elseif ($notification->type === 'user' && isset($notification->data['user_id'])) {
                $responseData['user_id'] = $notification->data['user_id'];
            }

            return response()->json($responseData);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking notification as read: ' . $e->getMessage(),
                'url' => route('admin.notifications.index')
            ], 500);
        }
    }

    public function markAllAsRead()
    {
        try {
            $query = Notification::query();
            // For admin, mark all unread notifications as read

            $count = $query->where('is_read', false)->count();

            $query->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking all notifications as read: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUnreadCount()
    {
        try {
            $query = Notification::query();
            // For admin, count all unread notifications

            $count = $query->where('is_read', false)->count();

            // Calculate new notifications since last check
            $lastCheckTime = session('last_notification_check_time', now()->subMinutes(5));
            $newSinceLastCheck = $query->where('is_read', false)
                                    ->where('created_at', '>', $lastCheckTime)
                                    ->count();

            // Update last notification check time
            session(['last_notification_check_time' => now()]);

            return response()->json([
                'count' => $count,
                'new_since_last_check' => $newSinceLastCheck
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'count' => 0,
                'new_since_last_check' => 0,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get the latest notifications for the dropdown
     */
    public function getLatest()
    {
        try {
            $query = Notification::query();
            // For admin, get latest notifications

            $notifications = $query->orderBy('created_at', 'desc')
                                ->limit(5)
                                ->get();

            $formattedNotifications = $notifications->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'is_read' => $notification->is_read,
                    'url' => '#', // You can implement getUrl() method if needed
                    'time_ago' => $notification->created_at->diffForHumans(),
                    'type' => $notification->type
                ];
            });

            return response()->json([
                'notifications' => $formattedNotifications,
                'unread_count' => $query->where('is_read', false)->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'notifications' => [],
                'unread_count' => 0,
                'error' => $e->getMessage()
            ]);
        }
    }
} 