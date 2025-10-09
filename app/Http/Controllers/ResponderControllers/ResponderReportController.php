<?php

namespace App\Http\Controllers\ResponderControllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\IncidentReport; 
use Illuminate\Support\Facades\Auth;
use App\Models\ResponseTeamAssignment;
use App\Models\ResponseTeamMember;
use App\Models\ResponseTeam;
use App\Models\BackupRequest;
use Ably\AblyRest;
use Illuminate\Support\Facades\Log;
use App\Events\IncidentUpdated;

class ResponderReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $teamId = ResponseTeamMember::where('user_id', $user->id)->value('team_id');
        Log::info('Responder team_id: ' . $teamId);

        $reports = ResponseTeamAssignment::where('team_id', $teamId)
            ->whereIn('status', ['assigned', 'accepted', 'enroute', 'resolved'])
            ->with('incident.incidentType')
            ->orderBy('assigned_at', 'desc')
            ->get()
            ->map(function ($assignment) {
                $report = $assignment->incident;
                return [
                    'id' => $report->id,
                    'type' => $report->incidentType->name ?? 'Unknown',
                    'status' => $assignment->status,
                    'landmark' => $report->landmark,
                    'date' => \Carbon\Carbon::parse($report->reported_at)->format('M d, Y h:i A'),
                ];
            });

        return response()->json($reports);

    }

    public function show($id)
    {
        $user = Auth::user();
        $teamId = ResponseTeamMember::where('user_id', $user->id)->value('team_id');

        $assignment = ResponseTeamAssignment::where('team_id', $teamId)
            ->where('incident_id', $id)
            ->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found or not assigned to your team.'
            ], 404);
        }

        $report = $assignment->incident()->with('incidentType', 'reporter')->first();

        return response()->json([
            'success' => true,
            'report' => [
                'id' => $report->id,
                'type' => $report->incidentType->name ?? 'Unknown',
                'reporterName' => $report->reporter->first_name . ' ' . $report->reporter->last_name,
                'landmark' => $report->landmark,
                'latitude' => $report->latitude,
                'longitude' => $report->longitude,
                'address' => $report->location ?? null,
                'status' => $assignment->status,
                'dateTime' => \Carbon\Carbon::parse($report->reported_at)->format('M d, Y h:i A'),
                'description' => $report->description,
            ]
        ]);
    }

    public function getLocation($teamId)
    {
        $team = ResponseTeam::find($teamId);

        if (!$team) {
            return response()->json([
                'success' => false,
                'message' => 'Team not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'team_id' => $team->id,
            'team_name' => $team->team_name,
            'latitude' => $team->latitude ?? null,
            'longitude' => $team->longitude ?? null
        ]);
    }


    public function updateLocation(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:response_teams,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $team = ResponseTeam::findOrFail($request->team_id);
        $team->latitude = $request->latitude;
        $team->longitude = $request->longitude;
        $team->save();

        $assignment = ResponseTeamAssignment::where('team_id', $team->id)
            ->whereHas('incident', function ($query) {
                $query->whereIn('status', ['Assigned', 'En Route']);
            })
            ->latest()
            ->first();

        if ($assignment && $assignment->incident) {
            $incident = $assignment->incident;

            $distance = $this->calculateDistance(
                $incident->latitude,
                $incident->longitude,
                $team->latitude,
                $team->longitude
            );

            if ($distance <= 50 && $incident->status !== 'On Scene') {
                $incident->status = 'On Scene';
                $incident->save();

                broadcast(new IncidentUpdated($incident))->toOthers();
            }
        }

        $ably = new AblyRest(env('ABLY_API_KEY'));
        $ably->channel('responder-location')->publish('update', [
            'team_id' => $team->id,
            'latitude' => $team->latitude,
            'longitude' => $team->longitude,
            'timestamp' => now()->toDateTimeString()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Team location updated successfully'
        ]);
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters
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

    public function updateStatus(Request $request, $incidentId)
    {   
        $request->validate([
            'status' => 'required|in:En Route,On Scene,Resolved'
        ]);

        $user = Auth::user();
        $teamId = ResponseTeamMember::where('user_id', $user->user_id)->value('team_id');

        $assignment = ResponseTeamAssignment::where('team_id', $teamId)
            ->where('incident_id', $incidentId)
            ->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found or not assigned to your team.'
            ], 404);
        }

        $assignment->status = $request->status;
        $assignment->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'status' => $assignment->status
        ]);
    }

    public function requestBackup(Request $request, $incidentId)
    {
        $request->validate([
            'backup_type' => 'required|string',
            'reason' => 'required|string',
        ]);

        $user = Auth::user();

        $backup = BackupRequest::create([
            'responder_id' => $user->user_id,
            'incident_id' => $incidentId,
            'backup_type' => $request->backup_type,
            'reason' => $request->reason,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $incident = IncidentReport::find($incidentId);
        if ($incident) {
            $incident->status = 'Requesting Backup';
            $incident->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Backup request sent successfully',
            'backup' => $backup
        ]);
    }
}
