<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncidentReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\IncidentCaller;
use App\Models\ResponseTeam;
use App\Models\ResponseTeamAssignment;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\GeocodeController;
use App\Events\IncidentCallCreated;
use App\Events\CallAccepted;

class IncidentReportController extends Controller
{
    public function index(Request $request)
    {
        $query = IncidentReport::with('incidentType');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('incident_type_id')) {
            $query->where('incident_type_id', $request->incident_type_id);
        }

        return response()->json($query->paginate(10));
    }
    public function show($id)
    {
        $incident = IncidentReport::with(['incidentType', 'user']) 
            ->where('id', $id)
            ->first();

        if (!$incident){
            return response()->json(['message' => 'Incident Report not found'], 404);
        }

        return response()->json($incident);
    }

    public function acceptCall($incidentId)
    {
        try {
            $userId = Auth::id(); 
            $incident = IncidentReport::findOrFail($incidentId);

            $incident->status = 'Accepted';
            $incident->save();

            broadcast(new CallAccepted($incident))->toOthers();

            return response()->json([
                'message' => 'Call accepted successfully',
                'incident' => $incident,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeFromResident(Request $request)
    {
        DB::beginTransaction();

        try {
            $userId = Auth::id(); 

            $validated = $request->validate([
                'incident_type_id' => 'required|exists:incident_types,id',
                'reporter_type'    => 'required|in:witness,victim',
                'latitude'         => 'nullable|numeric',
                'longitude'        => 'nullable|numeric',
                'landmark'         => 'nullable|string|max:255',
                'description'      => 'nullable|string',
            ]);

            $latitude  = $validated['latitude'] ?? null;
            $longitude = $validated['longitude'] ?? null;

            $incident = IncidentReport::create([
                'incident_type_id' => $validated['incident_type_id'],
                'description'      => $validated['description'] ?? null,
                'latitude'         => $latitude,
                'longitude'        => $longitude,
                'landmark'         => $validated['landmark'] ?? null,
                'status'           => 'Pending',
                'reported_by'      => $userId,
                'priority_id'      => null,
            ]);

            event(new IncidentCallCreated($incident));

            $team = ResponseTeam::where('status', 'Available')->first();
            if ($team) {
                ResponseTeamAssignment::create([
                    'incident_id' => $incident->id,
                    'team_id'     => $team->id,
                    'assigned_by' => $userId,
                ]);
            }

            DB::commit();

            return response()->json([
                'message'  => 'Incident reported successfully',
                'incident' => $incident,
                'team'     => $team,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getActiveIncidents()
    {
        $incidents = IncidentReport::with(['incidentType','user'])
            ->where('status', 'Ongoing')
            ->latest()
            ->get();

        return response()->json($incidents);
    }

    public function reportsResolvedThisWeek()
    {
        $count = IncidentReport::where('status', 'resolved')
            ->whereBetween('updated_at', [
                Carbon::now()->startOfWeek(), 
                Carbon::now()->endOfWeek(),
            ])
            ->count();

        return response()->json(['weekly_reports' => $count]);
    }
}
