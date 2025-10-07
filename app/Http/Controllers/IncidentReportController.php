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
use Illuminate\Support\Facades\Log;
use App\Events\NotificationEvent;
use App\Events\CallEnded;
use Peterujah\Agora\Agora;
use Peterujah\Agora\User;
use Peterujah\Agora\Roles;
use Peterujah\Agora\Builders\RtcToken;
use App\Helpers\IncidentHelper;
use App\Events\DuplicateReportCreated;
use App\Events\IncidentAssigned;

class IncidentReportController extends Controller
{
    public function index(Request $request)
    {
        $query = IncidentReport::with('incidentType')->orderBy('reported_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('incident_type_id')) {
            $query->where('incident_type_id', $request->incident_type_id);
        }

        if ($request->has('search') && $request->search !== "") {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                ->orWhereHas('incidentType', function($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })
                ->orWhere('landmark', 'like', "%{$search}%");
            });
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
            $incident = IncidentReport::with('incidentType', 'user')->findOrFail($incidentId);
            $incident->status = 'Accepted';
            $incident->save();

            $channelName = "resident";
            $appID = env('AGORA_APP_ID');
            $appCertificate = env('AGORA_APP_CERTIFICATE');
            $expirySeconds = 3600;
            $privilegeExpiredTs = time() + $expirySeconds;

            $uidDispatcher = random_int(100000, 999999);
            $uidResident = random_int(100000, 999999);
            while ($uidResident === $uidDispatcher) {
                $uidResident = random_int(100000, 999999);
            }

            $client = new Agora($appID, $appCertificate);
            $client->setExpiration($privilegeExpiredTs);

            $dispatcherUser = (new User($uidDispatcher))
                ->setChannel($channelName)
                ->setRole(Roles::RTC_ATTENDEE)
                ->setPrivilegeExpire($privilegeExpiredTs);

            $tokenDispatcher = RtcToken::buildTokenWithUid($client, $dispatcherUser);

            $residentUser = (new User($uidResident))
                ->setChannel($channelName)
                ->setRole(Roles::RTC_ATTENDEE)
                ->setPrivilegeExpire($privilegeExpiredTs);

            $tokenResident = RtcToken::buildTokenWithUid($client, $residentUser);

            $agoraResident = [
                'appID' => $appID,
                'token' => $tokenResident,
                'channelName' => $channelName,
                'uid' => $uidResident,
            ];
            broadcast(new CallAccepted($incident, $agoraResident));

            return response()->json([
                'message' => 'Call accepted successfully',
                'incident' => $incident,
                'agora' => [
                    'appID' => $appID,
                    'token' => $tokenDispatcher,
                    'channelName' => $channelName,
                    'uid' => $uidDispatcher,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function storeFromResident(Request $request)
    {
        DB::beginTransaction();
        Log::info("storeFromResident method HIT");

        try {
            $userId = Auth::id();
            $user = Auth::user();

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

            $incidentType = \App\Models\IncidentType::findOrFail($validated['incident_type_id']);
            $priorityId   = $incidentType->priority_id ?? null;

            Log::info('Payload:', $validated);
            Log::info('IncidentType:', ['id' => $incidentType->id, 'priority_id' => $priorityId]);
            
            $duplicateIncident = IncidentHelper::checkDuplicateReport(
                $validated['incident_type_id'], 
                $latitude, 
                $longitude
            );

            if ($duplicateIncident) {
                IncidentHelper::addDuplicateReporter($duplicateIncident, $userId);

                $dupPayload = [
                    'incident_id'     => $duplicateIncident->id,
                    'incident_type'   => $duplicateIncident->incidentType,
                    'duplicate_count' => $duplicateIncident->duplicates 
                        ? count(json_decode($duplicateIncident->duplicates, true))
                        : 1,
                ];

                try {
                    broadcast(new DuplicateReportCreated($dupPayload));
                } catch (\Exception $e) {
                    Log::error('DuplicateReportCreated broadcast failed: ' . $e->getMessage());
                }

                return response()->json([
                    'message' => 'Duplicate report detected. Dispatcher notified.',
                    'duplicate_of' => $duplicateIncident->id,
                    'duplicates' => $duplicateIncident->duplicates 
                        ? json_decode($duplicateIncident->duplicates, true) 
                        : []
                ], 200);
            }


            $incident = IncidentReport::create([
                'incident_type_id' => $validated['incident_type_id'],
                'description'      => $validated['description'] ?? null,
                'latitude'         => $latitude,
                'longitude'        => $longitude,
                'landmark'         => $validated['landmark'] ?? null,
                'status'           => 'Pending',
                'reported_by'      => $userId,
                'caller_name'      => $user->first_name . ' ' . $user->last_name,
                'priority_id'      => $priorityId,
            ]);

            try {
                broadcast(new NotificationEvent(
                    "dispatcher",
                    "Incoming emergency call: {$incidentType->name} reported"
                ));
            } catch (\Exception $e) {
                Log::error('NotificationEvent broadcast failed: ' . $e->getMessage());
            }

            try {
                broadcast(new IncidentCallCreated($incident));
            } catch (\Exception $e) {
                Log::error('IncidentCallCreated broadcast failed: ' . $e->getMessage());
            }

            $assignedTeam = null;

            if (strtolower($incidentType->name) !== 'medical') {
                $assignedTeam = ResponseTeam::where('status', 'available')->first();
            } else {
                $assignedTeam = ResponseTeam::where('team_name', 'Medical')->first();
            }

            $teamData = null;

            if ($assignedTeam) {
                try {
                    ResponseTeamAssignment::create([
                        'incident_id'   => $incident->id,
                        'team_id'       => $assignedTeam->id,
                        'dispatcher_id' => $userId,
                        'status'        => 'assigned',
                    ]);

                    $teamData = $assignedTeam->only(['id', 'team_name', 'status']);
                } catch (\Exception $e) {
                    Log::error('ResponseTeamAssignment creation failed: ' . $e->getMessage());
                }
            }

            try {
                broadcast(new IncidentAssigned($incident, $assignedTeam));
            } catch (\Exception $e) {
                Log::error('IncidentAssigned broadcast failed: ' . $e->getMessage());
            }

            DB::commit();

            return response()->json([
                'message'  => 'Incident reported successfully',
                'incident' => $incident,
                'team'     => $teamData,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('storeFromResident failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to report incident. ' . $e->getMessage()
            ], 500);
        }
    }


    public function endCall(Request $request, $incidentId)
    {
        $incident = IncidentReport::findOrFail($incidentId);
        $user = Auth::user();

        $endedByRole = $user->role_id === 2 ? 'dispatcher' : 'resident';
        $endedById   = $user->id;

        $reporterId = $incident->reported_by ?? $incident->user->id ?? null;

        broadcast(new CallEnded($incidentId, $endedByRole, $endedById, $reporterId));

        return response()->json(['message' => ucfirst($endedByRole).' ended the call']);
    }

    public function markInvalid($id)
    {
        $report = IncidentReport::findOrFail($id);
        $report->status = 'Invalid';
        $report->save();

        return response()->json([
            'message' => 'Incident marked as invalid',
            'data' => $report
        ]);
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
