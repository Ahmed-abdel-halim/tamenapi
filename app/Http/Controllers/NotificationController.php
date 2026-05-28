<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get user's recent notifications.
     */
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()
            ->limit(40)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->data['type'] ?? 'info',
                    'title' => $n->data['title'] ?? '',
                    'message' => $n->data['message'] ?? '',
                    'action_url' => $n->data['action_url'] ?? null,
                    'read_at' => $n->read_at,
                    'created_at' => $n->created_at,
                ];
            });

        return response()->json($notifications);
    }

    /**
     * Get unread notifications count.
     */
    public function unreadCount(Request $request)
    {
        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count()
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الإشعار كمقروء'
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'تم قراءة جميع الإشعارات'
        ]);
    }
}
