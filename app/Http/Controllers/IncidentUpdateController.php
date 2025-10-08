<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncidentUpdate;
use App\Models\IncidentReport;
use Illuminate\Support\Facades\Auth;
use Ably\AblyRest;

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
            'update_details' => 'required|string',
            'landmark' => 'nullable|string',
        ]);

        $incident->description = $validated['update_details'];
        if (isset($validated['landmark'])) {
            $incident->landmark = $validated['landmark'];
        }
        $incident->save();

        $update = IncidentUpdate::create([
            'incident_id'    => $incident->id,
            'updated_by'     => Auth::id(),
            'update_details' => $validated['update_details'],
        ]);

        $ably = new AblyRest(env('ABLY_API_KEY'));
        $channel = $ably->channel('responder-updates');
        $channel->publish('update', [
            'incident_id' => $incident->id,
            'description' => $incident->description,
            'landmark'    => $incident->landmark,
            'updated_at'  => now()->toDateTimeString(),
        ]);

        return response()->json([
            'message' => 'Incident updated successfully',
            'data'    => $update,
        ], 201);
    }
}
