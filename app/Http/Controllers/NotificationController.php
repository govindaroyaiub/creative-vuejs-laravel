<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Check if user has notification permission
     */
    private function checkNotificationAccess()
    {
        $user = Auth::user();
        $hasPermission = is_array($user->permissions) &&
            (in_array('*', $user->permissions) || in_array('/notifications', $user->permissions));

        if (!$hasPermission) {
            abort(403, 'You do not have permission to access notifications');
        }
    }

    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request)
    {
        $this->checkNotificationAccess();

        $query = Auth::user()->notifications()->with(['preview:id,name,slug', 'actor:id,name']);

        // Filter by read status
        if ($request->has('filter')) {
            if ($request->filter === 'unread') {
                $query->unread();
            } elseif ($request->filter === 'read') {
                $query->read();
            }
        }

        $notifications = $query->latest()->paginate(20);

        return response()->json($notifications);
    }

    /**
     * Get unread count
     */
    public function unreadCount()
    {
        $this->checkNotificationAccess();

        $count = Auth::user()->notifications()->unread()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead($id)
    {
        $this->checkNotificationAccess();

        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    /**
     * Mark a notification as unread
     */
    public function markAsUnread($id)
    {
        $this->checkNotificationAccess();

        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsUnread();

        return response()->json(['message' => 'Notification marked as unread']);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $this->checkNotificationAccess();

        Auth::user()->notifications()->unread()->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        $this->checkNotificationAccess();

        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'Notification deleted']);
    }

    /**
     * Delete all read notifications
     */
    public function deleteAllRead()
    {
        $this->checkNotificationAccess();

        Auth::user()->notifications()->read()->delete();

        return response()->json(['message' => 'All read notifications deleted']);
    }

    /**
     * Delete all notifications
     */
    public function deleteAll()
    {
        $this->checkNotificationAccess();

        Auth::user()->notifications()->delete();

        return response()->json(['message' => 'All notifications deleted']);
    }
}
