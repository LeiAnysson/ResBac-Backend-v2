<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\User;

class NotificationsController extends Controller
{
    public function store(Request $request)
    {
        if (isset($request->user_id)) {
            $notification = Notification::create([
                'user_id' => $request->user_id,
                'message' => $request->message,
                'is_read' => false,
            ]);
            return response()->json($notification, 201);
        }

        if (isset($request->team_id)) {
            $users = User::where('team_id', $request->team_id)->get();
            $notifications = [];

            foreach ($users as $user) {
                $notifications[] = Notification::create([
                    'user_id' => $user->id,
                    'message' => $request->message,
                    'is_read' => false,
                ]);
            }

            return response()->json($notifications, 201);
        }

        return response()->json(['error' => 'No user_id or team_id provided'], 400);
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

