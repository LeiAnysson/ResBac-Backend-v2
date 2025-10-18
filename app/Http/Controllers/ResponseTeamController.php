<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ResponseTeam;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\ResponseTeamMember;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ResponseTeamController extends Controller
{
    public function index(Request $request)
    {
        $query = ResponseTeam::with(['members.user']);

        if ($request->has('search') && $request->search !== "") {
            $search = $request->search;
            $query->where('team_name', 'like', "%{$search}%");
        }

        $paginated = $query->paginate(10);

        $paginated->getCollection()->transform(function ($team) {
            return [
                'id' => $team->id,
                'team_name' => Str::startsWith($team->team_name, 'Team') || Str::endsWith($team->team_name, 'Team')
                    ? $team->team_name
                    : (Str::lower($team->team_name) === 'medical team' ? 'Medical Team' : 'Team ' . $team->team_name),
                'status' => $team->status,
            ];
        });

        return response()->json($paginated);
    }

    public function store(Request $request)
    {
        $request->validate([
            'team_name' => 'required|string|unique:response_teams,team_name',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:users,id',
            'status' => 'nullable|in:available,unavailable'
        ]);

        DB::transaction(function () use ($request, &$team) {
            $team = ResponseTeam::create([
                'team_name' => $request->team_name,
                'status' => $request->status ?? 'unavailable',
            ]);

            if (!empty($request->member_ids)) {
                foreach ($request->member_ids as $userId) {
                    ResponseTeamMember::updateOrCreate(
                        ['team_id' => $team->id, 'user_id' => $userId],
                        ['deleted_at' => null] 
                    );
                }
            }

            recordActivity('Created team', 'Response Team', $team->id);
        });

        return response()->json([
            'message' => 'Team created successfully',
            'team' => $team->load('members.user')
        ]);
    }

    public function update(Request $request, $id)
    {
        $team = ResponseTeam::with('members')->findOrFail($id);

        $validated = $request->validate([
            'team_name' => 'sometimes|required|string|unique:response_teams,team_name,' . $team->id,
            'member_ids' => 'sometimes|array',
            'member_ids.*' => 'exists:users,id',
            'status' => 'sometimes|in:available,unavailable'
        ]);

        DB::transaction(function () use ($team, $validated, $request) {
            $team->update([
                'team_name' => $validated['team_name'] ?? $team->team_name,
                'status' => $validated['status'] ?? $team->status,
            ]);

            $currentMemberIds = $team->members()->pluck('user_id')->toArray();
            $newMemberIds = $validated['member_ids'] ?? [];

            $toRemoveIds = $request->input('removed_member_ids', []);
            if (!empty($toRemoveIds)) {
                ResponseTeamMember::whereIn('id', $toRemoveIds)->delete();
            }

            $toAdd = array_diff($newMemberIds, $currentMemberIds);
            foreach ($toAdd as $userId) {
                ResponseTeamMember::updateOrCreate(
                    ['team_id' => $team->id, 'user_id' => $userId],
                );
            }

            recordActivity('Updated team', 'Response Team', $team->id);
        });

        return response()->json([
            'message' => 'Team updated successfully',
            'team' => $team->fresh()->load('members.user')
        ]);
    }

    public function show($id)
    {
        $team = ResponseTeam::with('members.user')->findOrFail($id);

        return response()->json([
            'id' => $team->id,
            'name' => $team->team_name,
            'status' => $team->status,
            'members' => $team->members->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->user->first_name . ' ' . $member->user->last_name,
                ];
            }),
        ]);
    }

    public function addMember(Request $request, $teamId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        return DB::transaction(function () use ($request, $teamId) {
            $team = ResponseTeam::findOrFail($teamId);

            $member = ResponseTeamMember::updateOrCreate(
                ['team_id' => $teamId, 'user_id' => $request->user_id],
                ['deleted_at' => null]
            );

            $userId = Auth::id();
            recordActivity("Added user {$request->user_id} to team {$team->team_name}", 'ResponseTeam', $userId);

            return response()->json([
                'message' => 'Member added successfully',
                'member' => $member,
            ]);
        });
    }

    public function removeMember($teamId, $memberId)
    {
        return DB::transaction(function () use ($teamId, $memberId) {
            $member = ResponseTeamMember::where('team_id', $teamId)->where('id', $memberId)->firstOrFail();
            $member->delete();

            $userId = Auth::id();
            recordActivity("Removed member {$memberId} from team {$teamId}", 'ResponseTeam', $userId);

            return response()->json([
                'message' => 'Member removed successfully',
            ]);
        });
    }

    public function setRotationStartDate(Request $req)
    {
        $data = $req->validate([
            'rotation_start_date' => 'required|date',
            'rotation_start_team' => 'nullable|string|in:Alpha,Bravo,Charlie',
        ]);

        Cache::forever('rotation_start_date', $data['rotation_start_date']);
        if (!empty($data['rotation_start_team'])) {
            Cache::forever('rotation_start_team', $data['rotation_start_team']);
        }

        return response()->json(['message' => 'Rotation start date updated.']);
    }

    public function destroy($id)
    {
        $team = ResponseTeam::findOrFail($id);
        $team->delete();

        return response()->json(['message' => 'Response team deleted successfully']);
    }

    public function availableTeams()
    {
        $teams = ResponseTeam::with(['members.user'])
            ->where('status', 'available') 
            ->get()
            ->map(function ($team) {
                return [
                    'id' => $team->id,
                    'team_name' => $team->team_name,
                    'status' => $team->status,
                    'members' => $team->members->map(function ($m) {
                        return [
                            'id' => $m->id,
                            'name' => $m->user->name ?? 'Unknown',
                            'type' => $m->type,
                            'location' => $m->location ?? 'Unknown',
                        ];
                    }),
                ];
            });

        return response()->json($teams);
    }
}
