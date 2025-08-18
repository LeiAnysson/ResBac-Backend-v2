<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {   
        $perPage = $request->query('per_page', 5);

        $logs = ActivityLog::latest()->paginate($perPage);

        return response()->json($logs);
    }
    public function all()
    {
        return response()->json(ActivityLog::latest()->get());
    }
}
