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
    $teamsOrder = ['Alpha', 'Bravo', 'Charlie'];

    $startDateStr = Cache::get('rotation_start_date');
    $startTeam = Cache::get('rotation_start_team', 'Alpha');

    if (!$startDateStr) {
        $startDateStr = Carbon::today()->toDateString();
        Cache::forever('rotation_start_date', $startDateStr);
        Cache::forever('rotation_start_team', $startTeam);
    }

    $startDate = Carbon::parse($startDateStr)->startOfDay();
    $startIndex = array_search($startTeam, $teamsOrder);
    if ($startIndex === false) $startIndex = 0;

    $daysPassed = (int) $startDate->diffInDays(Carbon::today());
    Log::info("DEBUG rotation_start_date={$startDateStr}, startTeam={$startTeam}, daysPassed={$daysPassed}");
    
    $currentIndex = ($startIndex + $daysPassed) % count($teamsOrder);
    $currentTeamName = $teamsOrder[$currentIndex];

    ResponseTeam::whereIn('team_name', $teamsOrder)->update(['status' => 'unavailable']);
    ResponseTeam::where('team_name', $currentTeamName)->update(['status' => 'available']);

    if ($daysPassed >= 1) {
        Cache::forever('rotation_start_date', Carbon::today()->toDateString());
        Cache::forever('rotation_start_team', $currentTeamName);
    }

    Log::info("Team rotation applied. Available team: {$currentTeamName}");
    $this->info("Team rotation applied. Available team: {$currentTeamName}");
});
