<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ResponseTeamAssignment;
use App\Models\IncidentReport;
use Illuminate\Support\Facades\Auth;

class ResponseTeamAssignmentController extends Controller
{
    public function store(Request $request, $incidentId)
    {
        $validated = $request->validate([
            'team_id' => 'required|exists:response_teams,id',
            'status' => 'nullable|string|in:assigned,en_route,on_scene,completed',
        ]);

        $incident = IncidentReport::findOrFail($incidentId);

        $assignment = ResponseTeamAssignment::create([
            'incident_id'   => $incident->id,
            'dispatcher_id' => Auth::id(),
            'team_id'       => $validated['team_id'],
            'status'        => $validated['status'] ?? 'assigned',
            'assigned_at'   => now(),
        ]);

        return response()->json([
            'message' => 'Team assigned successfully',
            'data' => $assignment
        ], 201);
    }

    public function update(Request $request, $assignmentId)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:assigned,en_route,on_scene,completed',
        ]);

        $assignment = ResponseTeamAssignment::findOrFail($assignmentId);
        $assignment->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Assignment updated successfully',
            'data' => $assignment
        ]);
    }
}
