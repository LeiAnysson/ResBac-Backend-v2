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

class ResponderReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $teamId = ResponseTeamMember::where('User_id', $user->User_id)->value('Team_id');

        $reports = ResponseTeamAssignment::where('team_id', $teamId)
            ->whereIn('status', ['accepted', 'enroute', 'resolved'])
            ->with('incidentReport.incidentType')
            ->orderBy('assigned_at', 'desc')
            ->get()
            ->map(function ($assignment) {
                $report = $assignment->incidentReport;
                return [
                    'id' => $report->Incident_id,
                    'type' => $report->incidentType->Name ?? 'Unknown',
                    'status' => $assignment->Status,
                    'landmark' => $report->Landmark,
                    'date' => \Carbon\Carbon::parse($report->Reported_at)->format('M d, Y h:i A'),
                ];
            });

        return response()->json($reports);
    }

    public function show($id)
    {
        $user = Auth::user();
        $teamId = ResponseTeamMember::where('User_id', $user->User_id)->value('Team_id');

        $assignment = ResponseTeamAssignment::where('team_id', $teamId)
            ->where('incident_id', $id)
            ->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found or not assigned to your team.'
            ], 404);
        }

        $report = $assignment->incidentReport()->with('incidentType', 'reporter')->first();

        return response()->json([
            'success' => true,
            'report' => [
                'id' => $report->Incident_id,
                'type' => $report->incidentType->Name ?? 'Unknown',
                'reporterName' => $report->reporter->First_name . ' ' . $report->reporter->Last_name,
                'landmark' => $report->Landmark,
                'address' => $report->Location,
                'status' => $assignment->Status,
                'dateTime' => \Carbon\Carbon::parse($report->Reported_at)->format('M d, Y h:i A'),
                'description' => $report->Description,
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
