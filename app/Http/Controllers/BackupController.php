<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function backup()
    {
        $filename = 'resbac_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $path = storage_path('app/' . $filename);

        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            env('DB_USERNAME'),
            env('DB_PASSWORD'),
            env('DB_HOST'),
            env('DB_DATABASE'),
            $path
        );

        exec($command);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:sql,txt',
        ]);

        Artisan::call('down');

        $path = $request->file('backup_file')->getRealPath();

        $command = sprintf(
            'mysql --user=%s --password=%s --host=%s %s < %s',
            env('DB_USERNAME'),
            env('DB_PASSWORD'),
            env('DB_HOST'),
            env('DB_DATABASE'),
            $path
        );

        exec($command);

        Artisan::call('up');

        return response()->json(['message' => 'Database restored successfully!']);
    }
}
