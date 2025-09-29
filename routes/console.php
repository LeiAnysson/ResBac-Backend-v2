<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\ResponseTeam;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
*/

Artisan::command('rotate:teams', function () {
    $startDateStr = Cache::get('rotation_start_date', Carbon::today()->toDateString());
    $startTeam = Cache::get('rotation_start_team', 'Alpha');

    $startDate = Carbon::parse($startDateStr)->startOfDay();
    $teamsOrder = ['Alpha','Bravo','Charlie'];

    $startIndex = array_search($startTeam, $teamsOrder);
    if ($startIndex === false) $startIndex = 0;

    $daysPassed = $startDate->diffInDays(Carbon::today()) + 1;
    $currentIndex = ($startIndex + $daysPassed) % count($teamsOrder);
    $currentTeamName = $teamsOrder[$currentIndex];

    ResponseTeam::whereIn('team_name', $teamsOrder)->update(['status' => 'unavailable']);

    $currentTeam = ResponseTeam::where('team_name', $currentTeamName)->first();
    if ($currentTeam) {
        $currentTeam->update(['status' => 'available']);
    }

    Log::info("Team rotation applied. Available team: {$currentTeamName}");
    $this->info("Team rotation applied. Available team: {$currentTeamName}");
});

