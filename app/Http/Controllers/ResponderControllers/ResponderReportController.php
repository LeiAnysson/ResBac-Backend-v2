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
use App\Events\IncidentStatusUpdated;
use App\Events\BackupRequestCreated;
use App\Events\BackupAutomaticallyAssigned;
use App\Events\IncidentAssigned;
use App\Models\Image;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\User;
use App\Models\Notification;

class ResponderReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $teamId = ResponseTeamMember::where('user_id', $user->id)->value('team_id');
        Log::info('Responder team_id: ' . $teamId);

        $reports = ResponseTeamAssignment::where('team_id', $teamId)
            ->whereIn('status', ['Unanswered', 'Assigned', 'En Route', 'On Scene', 'Requesting Backup', 'Resolved'])
            ->with('incident.incidentType')
            ->orderBy('assigned_at', 'desc')
            ->get()
            ->filter(function ($assignment) {
                return $assignment->incident !== null;
            })
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

                broadcast(new IncidentStatusUpdated($incident))->toOthers();
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
        $teamId = ResponseTeamMember::where('user_id', $user->id)->value('team_id');

        $assignment = ResponseTeamAssignment::where('team_id', $teamId)
            ->where('incident_id', $incidentId)
            ->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found or not assigned to your team.'
            ], 404);
        }

        if ($request->status === 'Resolved') {
            $onSceneTime = DB::table('incident_status_logs')
                ->where('incident_id', $incidentId)
                ->where('new_status', 'On Scene')
                ->latest('created_at')
                ->value('created_at');

            if (!$onSceneTime) {
                $onSceneTime = DB::table('incident_status_logs')
                    ->where('incident_id', $incidentId)
                    ->where('new_status', 'En Route')
                    ->latest('created_at')
                    ->value('created_at');
            }

            if (!$onSceneTime) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incident cannot be resolved because it was never marked On Scene or En Route.'
                ], 403);
            }

            Log::info("Checking resolve wait for incident {$incidentId}");
            Log::info("On Scene timestamp used: " . ($onSceneTime ?? 'none'));
            Log::info("Minutes since On Scene: " . now()->diffInMinutes($onSceneTime ?? now()));

            $minutesSinceOnScene = abs(now()->diffInMinutes($onSceneTime));

            if ($minutesSinceOnScene < 30) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incident can only be resolved at least 30 minutes after being On Scene.'
                ], 403);
            }
        }

        $oldStatus = $assignment->status;

        $assignment->update(['status' => $request->status]);
        $incident = $assignment->incident;
        $incident->update(['status' => $assignment->status]);

        DB::table('incident_status_logs')->insert([
            'incident_id' => $incident->id,
            'old_status' => $oldStatus,
            'new_status' => $assignment->status,
            'updated_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $roles = Role::whereIn('name', ['MDRRMO', 'Admin'])->pluck('id');
            $recipients = User::whereIn('role_id', $roles)->get();

            foreach ($recipients as $recipient) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'message' => "Incident #{$incident->id} status updated to {$assignment->status} by Team {$assignment->team->team_name}.",
                    'is_read' => false,
                ]);
            }

            $reporter = User::find($incident->reported_by);
            if ($reporter && $reporter->role && $reporter->role->name === 'Resident') {
                Notification::create([
                    'user_id' => $reporter->id,
                    'message' => "Your reported incident (#{$incident->id}) has been updated to {$assignment->status}.",
                    'is_read' => false,
                ]);
            }

            broadcast(new IncidentStatusUpdated($incident))->toOthers();

        } catch (\Exception $e) {
            Log::error('Incident status notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'status' => $assignment->status
        ]);
    }

    public function requestBackup(Request $request, $incidentId)
    {
        $request->validate([
            'backup_type' => 'required|string|in:medic,lgu',
            'reason' => 'required|string',
        ]);

        $user = Auth::user();

        $teamId = ResponseTeamMember::where('user_id', $user->id)->value('team_id');

        $backup = BackupRequest::create([
            'response_team_id' => $teamId, 
            'incident_id' => $incidentId,
            'backup_type' => $request->backup_type,
            'reason' => $request->reason,
            'status' => 'Pending',
            'requested_at' => now(),
        ]);

        $incident = IncidentReport::find($incidentId);
        if ($incident) {
            $incident->status = 'Requesting Backup';
            $incident->save();
        }

        $assignment = ResponseTeamAssignment::where('incident_id', $incidentId)
            ->where('team_id', $teamId)
            ->first();

        if ($assignment) {
            $assignment->status = 'Requesting Backup';
            $assignment->save();
        }

        if ($request->backup_type === 'medic') {
            Log::info('Medic backup type detected.');
            $medicTeam = ResponseTeam::where('team_name', 'Medical')->first();

            if ($medicTeam) {
                Log::info('Found medic team: ' . $medicTeam->id);
                ResponseTeamAssignment::create([
                    'incident_id' => $incidentId,
                    'team_id' => $medicTeam->id,
                    'dispatcher_id' => Auth::id(),
                    'status' => 'Assigned',
                ]);

                $teamData = $medicTeam->only(['id', 'team_name', 'status']);
                broadcast(new IncidentAssigned($incident, $teamData));

                $incident->status = 'Backup Assigned';
                $incident->save();

                broadcast(new BackupAutomaticallyAssigned($incident, $backup, $medicTeam))->toOthers();

                $backup->status = 'Assigned';
                $backup->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Medical backup automatically assigned.',
                    'backup' => $backup,
                ]);
            } else {
                Log::warning('No Medical team found.');
            }
        } else if ($request->backup_type === 'lgu') {
            Log::info('LGU backup type detected.');

            $requestingTeam = ResponseTeam::find($teamId);

            broadcast(new BackupRequestCreated($incident, $backup, $requestingTeam))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'LGU backup request sent successfully.',
                'backup' => $backup,
            ]);
        }
    }

    public function storeProof(Request $request, $incidentId)
    {
        $request->validate([
            'proofs.*' => 'required|image|mimes:jpeg,png,jpg|max:51200',
        ]);

        DB::beginTransaction();

        try {
            foreach ($request->file('proofs') as $file) {
                $path = $file->store('incident_proofs', 'public');

                $image = Image::create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => "/storage/$path",
                    'uploaded_by' => Auth::id(),
                ]);

                DB::table('incident_proof_images')->insert([
                    'incident_id' => $incidentId,
                    'image_id' => $image->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'All proofs uploaded successfully',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);

            return response()->json([
                'error' => 'Failed to upload proof',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function getProofs($incidentId)
    {
        $proofs = DB::table('incident_proof_images')
            ->join('images', 'incident_proof_images.image_id', '=', 'images.id')
            ->where('incident_proof_images.incident_id', $incidentId)
            ->select('images.file_path')
            ->get();

        return response()->json(['proofs' => $proofs]);
    }
}
