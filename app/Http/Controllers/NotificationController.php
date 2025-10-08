<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Get notifications for the authenticated user.
     */
    public function getNotifications(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $notifications->map(function ($notification) {
            return [
                'uuid' => $notification->id,
                'user_id' => $notification->notifiable_id,
                'data' => $notification->data,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
            ];
        });

        return response()->json($data, 200);
    }

    /**
     * Mark notifications as read.
     */
    public function markNotificationAsRead($notificationId)
    {
        $notification = DatabaseNotification::find($notificationId);

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read'], 200);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllNotificationsAsRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications()->update(['read_at' => now()]);
        return response()->json(['message' => 'All notifications marked as read'], 200);
    }

    /**
     * Mark notifications as unread.
     */
    public function markNotificationAsUnread($notificationId)
    {
        $notification = DatabaseNotification::find($notificationId);

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->markAsUnread();

        return response()->json(['message' => 'Notification marked as unread'], 200);
    }

    /**
     * Delete notifications.
     */
    public function destroy($notificationId)
    {
        $notification = DatabaseNotification::find($notificationId);

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->delete();

        return response()->json(['message' => 'Notification deleted'], 200);
    }

    /**
     * Delete multiple notifications.
     */
    public function destroyMultiple(Request $request)
    {
        $validated = $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:notifications,id',
        ]);
        DatabaseNotification::whereIn('id', $validated['notification_ids'])->delete();
        return response()->json(['message' => 'Notifications deleted'], 200);
    }
}
