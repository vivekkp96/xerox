<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class NotificationController extends Controller
{
    /**
     * Count unread notifications for the authenticated user.
     */
    public function countUnread(Request $request)
    {
        $query = Notification::query();
        if (Auth::check()) {
            $query->where('user_id', Auth::id());
        }
        $count = $query->where('user_read_flag', 0)->count();
        return response()->json(['unread_count' => $count]);
    }

    /**
     * Get notifications for the authenticated user with optional filters.
     */
    public function index(Request $request)
    {
        // Start query, ensuring we only get notifications for the current user
        $query = Notification::query();

        if (Auth::check()) {
            $query->where('user_id', Auth::id());
        }

        // Filter by order_id if provided
        if ($request->has('order_id')) {
            $query->where('order_id', $request->input('order_id'));
        }

        // Filter by user_read_flag if provided
        if ($request->has('user_read_flag')) {
            $query->where('user_read_flag', filter_var($request->input('user_read_flag'), FILTER_VALIDATE_BOOLEAN));
        }

        // Return paginated results, ordered by newest first
        return response()->json($query->orderBy('created_at', 'desc')->paginate(20));
    }

    /**
     * Update the user_read_flag for a notification.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'user_read_flag' => 'required|boolean',
        ]);
        $query = Notification::query();
        if (Auth::check()) {
            $query->where('user_id', Auth::id());
        }
        $query->where('id', $id);
        $notification = $query->firstOrFail();
        $notification->update(['user_read_flag' => $request->input('user_read_flag')]);

        return response()->json($notification);
    }
}
