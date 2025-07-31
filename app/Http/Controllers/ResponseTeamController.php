<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ResponseTeam;
use Illuminate\Support\Str;

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
                'members' => $team->members->map(function ($member) {
                    return [
                        'first_name' => $member->user->first_name ?? '',
                        'last_name' => $member->user->last_name ?? '',
                    ];
                }),
            ];
        });

        return response()->json($paginated);
    }
}
