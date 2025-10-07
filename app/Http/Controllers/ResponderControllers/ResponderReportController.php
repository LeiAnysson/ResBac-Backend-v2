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
            'team_id' => 'required|exists:ResponseTeam,Team_id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $team = ResponseTeam::find($request->team_id);
        $team->latitude = $request->latitude;
        $team->longitude = $request->longitude;
        $team->save();

        $ably = new AblyRest(env('ABLY_API_KEY'));
        $ably->channel('responder-location')->publish('update', [
            'team_id' => $team->id,
            'latitude' => $team->latitude,
            'longitude' => $team->longitude,
            'timestamp' => now()->toDateTimeString()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Team location updated'
        ]);
    }


    public function updateStatus(Request $request, $incidentId)
    {   
        $request->validate([
            'status' => 'required|in:En Route,On Scene,Resolved'
        ]);

        $user = Auth::user();
        $teamId = ResponseTeamMember::where('User_id', $user->User_id)->value('Team_id');

        $assignment = ResponseTeamAssignment::where('team_id', $teamId)
            ->where('incident_id', $incidentId)
            ->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found or not assigned to your team.'
            ], 404);
        }

        $assignment->Status = $request->status;
        $assignment->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'status' => $assignment->Status
        ]);
    }

    public function requestBackup(Request $request, $incidentId)
    {
        $request->validate([
            'backup_type' => 'required|string',
            'reason' => 'required|string',
        ]);

        $user = Auth::user();
        $teamId = ResponseTeamMember::where('User_id', $user->User_id)->value('Team_id');

        $backup = BackupRequest::create([
            'responder_id' => $user->User_id,
            'incident_id' => $incidentId,
            'backup_type' => $request->backup_type,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Backup request sent successfully',
            'backup' => $backup
        ]);
    }

}
