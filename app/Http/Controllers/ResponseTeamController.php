<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ResponseTeam;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\ResponseTeamMember;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ResponseTeamController extends Controller
{
    public function index(Request $request)
    {
        $paginated = ResponseTeam::with(['members.user'])->paginate(10);

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
            'name' => 'required|string|unique:response_teams,name',
            'member_ids' => 'array', // optional
            'member_ids.*' => 'exists:users,id'
        ]);

        DB::transaction(function () use ($request, &$team) {
            $team = ResponseTeam::create([
                'name' => $request->name,
            ]);

            if ($request->has('member_ids')) {
                foreach ($request->member_ids as $userId) {
                    ResponseTeamMember::create([
                        'response_team_id' => $team->id,
                        'user_id' => $userId,
                    ]);
                }
            }

            recordActivity('created team', 'ResponseTeam', $team->id);
        });

        return response()->json([
            'message' => 'Response team created successfully.',
            'team' => $team->load('members.user')
        ]);
    }


    // FRONTEND HANDLES THE DATE CHANGES FOR TEAM ROTATION!!!
    public function show($id)
    {
        $team = ResponseTeam::with('members.user')->findOrFail($id);

        return response()->json([
            'id' => $team->id,
            'name' => $team->name,
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

            if ($team->members()->where('user_id', $request->user_id)->exists()) {
                return response()->json(['message' => 'User already in this team'], 400);
            }

            $member = ResponseTeamMember::create([
                'team_id' => $teamId,
                'user_id' => $request->user_id,
            ]);

            $userId = Auth::id();
            recordActivity("Added user {$request->user_id} to team {$team->team_name}", 'ResponseTeam',$userId);

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

        Cache::put('rotation_start_date', $data['rotation_start_date']);
        if (!empty($data['rotation_start_team'])) {
            Cache::put('rotation_start_team', $data['rotation_start_team']);
        }

        return response()->json(['message' => 'Rotation start date updated.']);
    }
}
