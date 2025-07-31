<?php

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

function recordActivity(string $action, string $entity, $userId = null)
{
    try {
        ActivityLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action'  => $action,
            'entity'  => $entity,
        ]);
    } catch (\Exception $e) {
        Log::error('Activity logging failed: ' . $e->getMessage());
    }
}
