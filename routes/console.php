<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\ResponseTeam;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\BackupController;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
*/

//------CRON JOB------ (if want to revert back to daily team rotation)
// 0 16 * * * /usr/bin/php /home/u470152037/domains/kiri8tives.com/public_html/resbac-server/artisan rotate:teams >> /home/u470152037/domains/kiri8tives.com/public_html/resbac-server/storage/logs/laravel.log 2>&1

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

Artisan::command('backup:scheduled', function () {
    $schedulePath = storage_path('app/private/backup_schedule.json');

    if (!file_exists($schedulePath)) {
        Log::info('No backup schedule set.');
        return;
    }

    $schedule = json_decode(file_get_contents($schedulePath), true);
    $now = Carbon::now('Asia/Manila');
    $currentTime = $now->format('H:i');

    if (!isset($schedule['time']) || $currentTime !== $schedule['time']) {
        return;
    }

    $shouldRun = match (strtolower($schedule['frequency'] ?? 'daily')) {
        'daily' => true,
        'weekly' => $now->isSunday(),
        'monthly' => $now->isSameDay($now->copy()->startOfMonth()),
        default => false,
    };

    if ($shouldRun) {
        Artisan::call('backup:create');
        Log::info("Scheduled {$schedule['frequency']} backup created at {$currentTime}");
    } else {
        Log::info("Skipping backup ({$schedule['frequency']} not due today)");
    }
});

Artisan::command('backup:create', function () {
    try {
        $controller = new BackupController();
        $filePath = (new \ReflectionClass($controller))
            ->getMethod('createBackupFile')
            ->invoke($controller, false); 

        if ($filePath) {
            Log::info("Manual backup created: {$filePath}");
            $this->info("Backup successfully created: {$filePath}");
        } else {
            Log::error("Backup creation failed.");
            $this->error("Backup creation failed.");
        }
    } catch (\Throwable $e) {
        Log::error("Backup command error: " . $e->getMessage());
        $this->error("Backup failed: " . $e->getMessage());
    }
});

