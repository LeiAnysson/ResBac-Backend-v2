<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationsController extends Controller
{
    public function store(Request $request)
    {
        $notification = Notification::create([
            'user_id' => $request->user_id,
            'message' => $request->message,
            'is_read' => false,
        ]);

        return response()->json($notification, 201);
    }

    public function index($user_id)
    {
        $notifications = Notification::where('user_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->is_read = true;
        $notification->save();

        return response()->json($notification);
    }
}

