<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncidentCaller;
use App\Models\IncidentReport;

class IncidentCallerController extends Controller
{
    public function index($incidentId) {
        $updates = IncidentCaller::where('incident_id', $incidentId)
                    ->with('dispatcher')
                    ->latest()
                    ->get();
        return response()->json($updates);
    }

    public function store(Request $request, $incidentId)
    {
        $incident = IncidentReport::findOrFail($incidentId);

        $validated = $request->validate([
            'caller_name'  => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'latitude'     => 'nullable|numeric',
            'longitude'    => 'nullable|numeric',
            'landmark'     => 'nullable|string|max:255',
        ]);

        $validated['incident_id'] = $incident->id;

        $caller = IncidentCaller::create($validated);

        return response()->json([
            'message' => 'Caller added successfully',
            'data'    => $caller
        ], 201);
    }
}
