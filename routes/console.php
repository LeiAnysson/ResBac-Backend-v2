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
    $teams = ResponseTeam::where('team_name', '!=', 'Medical')
        ->whereNull('deleted_at')
        ->orderBy('rotation_index')
        ->get();

    if ($teams->isEmpty()) {
        $this->error("No active teams found for rotation.");
        return;
    }

    $currentTeam = $teams->firstWhere('status', 'available');
    $currentIndex = $currentTeam ? $currentTeam->rotation_index : 0;

    $nextIndex = ($currentIndex + 1) % $teams->count();
    $nextTeam = $teams[$nextIndex];

    ResponseTeam::whereIn('id', $teams->pluck('id'))
        ->update(['status' => 'unavailable']);

    $nextTeam->update(['status' => 'available']);

    $nextTeam->rotation_index = $nextIndex;
    $nextTeam->save();

    ResponseTeam::where('team_name', 'Medical')->update(['status' => 'available']);
    
    Log::info("Team rotation applied. Available team: {$nextTeam->team_name}");
    $this->info("Rotation complete. Available team: {$nextTeam->team_name}");
});
