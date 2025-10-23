<?php

namespace App\Http\Controllers\ResidentControllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\IncidentReport; 
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ResidentReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $reports = IncidentReport::where('reported_by', $user->id)
            ->with('incidentType')
            ->orderBy('reported_at', 'desc') 
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'type' => $report->incidentType->name ?? 'Unknown',
                    'status' => $report->status,
                    'landmark' => $report->landmark,
                    'date' => \Carbon\Carbon::parse($report->reported_at)->format('M d, Y h:i A'),
                    'latitude' => $report->latitude,
                    'longitude' => $report->longitude, 
                ];
            });

        return response()->json($reports);
    }

    public function indexWithTeam(Request $request)
    {
        $user = Auth::user();

        $reports = IncidentReport::where('reported_by', $user->id)
            ->with(['incidentType', 'latestTeamAssignment.team'])
            ->orderBy('reported_at', 'desc')
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'type' => $report->incidentType->name ?? 'Unknown',
                    'status' => $report->status,
                    'landmark' => $report->landmark,
                    'date' => Carbon::parse($report->reported_at)->format('M d, Y h:i A'),
                    'latitude' => $report->latitude,
                    'longitude' => $report->longitude,
                    'responder_team' => $report->latestTeamAssignment?->team?->team_name,
                ];
            });

        return response()->json($reports);
    }
}
