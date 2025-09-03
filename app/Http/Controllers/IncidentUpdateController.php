<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncidentUpdate;
use App\Models\IncidentReport;
use Illuminate\Support\Facades\Auth;

class IncidentUpdateController extends Controller
{
    public function index($incidentId) {
        $updates = IncidentUpdate::where('incident_id', $incidentId)
                    ->with('dispatcher')
                    ->latest()
                    ->get();
        return response()->json($updates);
    }

    public function store(Request $request, $incidentId)
    {
        $incident = IncidentReport::findOrFail($incidentId);

        $validated = $request->validate([
            'update_message' => 'required|string',
        ]);

        $validated['incident_id'] = $incident->id;
        $validated['dispatcher_id'] = Auth::id();

        $update = IncidentUpdate::create($validated);

        return response()->json([
            'message' => 'Incident updated successfully',
            'data'    => $update
        ], 201);
    }
}
