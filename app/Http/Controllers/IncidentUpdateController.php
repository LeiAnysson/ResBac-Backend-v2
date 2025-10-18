<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncidentUpdate;
use App\Models\IncidentReport;
use Illuminate\Support\Facades\Auth;
use Ably\AblyRest;
use App\Events\IncidentDetailsUpdated;
use Illuminate\Support\Facades\Log;

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
        Log::info("IncidentUpdate store method HIT", [
            'incident_id' => $incidentId,
            'user_id' => Auth::id(),
            'payload' => $request->all()
        ]);

        try {
            $incident = IncidentReport::findOrFail($incidentId);
            Log::info("Incident found", ['incident' => $incident]);

            $validated = $request->validate([
                'update_details' => 'required|string',
                'landmark' => 'nullable|string',
            ]);
            Log::info("Request validated", ['validated' => $validated]);

            $incident->description = $validated['update_details'];
            if (isset($validated['landmark'])) {
                $incident->landmark = $validated['landmark'];
            }
            $incident->save();
            Log::info("Incident updated", ['incident' => $incident]);

            $update = IncidentUpdate::create([
                'incident_id'    => $incident->id,
                'updated_by'     => Auth::id(),
                'update_details' => $validated['update_details'],
            ]);
            Log::info("IncidentUpdate created", ['update' => $update]);

            if ($incident->latitude && $incident->longitude && $incident->status !== 'On Scene') {
                $assignedTeam = \App\Models\ResponseTeam::whereHas('assignments', function ($query) use ($incident) {
                    $query->where('incident_id', $incident->id);
                })->first();
                Log::info("Assigned team fetched", ['team' => $assignedTeam]);

                if ($assignedTeam && $assignedTeam->latitude && $assignedTeam->longitude) {
                    $distance = $this->calculateDistance(
                        $incident->latitude,
                        $incident->longitude,
                        $assignedTeam->latitude,
                        $assignedTeam->longitude
                    );
                    Log::info("Distance calculated", ['distance' => $distance]);

                    if ($distance <= 50) {
                        $incident->status = 'On Scene';
                        $incident->save();
                        Log::info("Incident status updated to On Scene");
                    }
                }
            }

            try {
                broadcast(new IncidentDetailsUpdated($incident))->toOthers();
                Log::info("Broadcast successful");
            } catch (\Exception $e) {
                Log::error("Broadcast failed", ['error' => $e->getMessage()]);
            }

            return response()->json([
                'message' => 'Incident details updated successfully',
                'data'    => $update,
            ], 201);

        } catch (\Exception $e) {
            Log::error("IncidentUpdate store failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Failed to update incident',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $earthRadius * $angle;
    }
}
