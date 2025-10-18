<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    public function backup()
    {
        $filePath = $this->createBackupFile();

        if (!$filePath || !file_exists($filePath)) {
            return response()->json(['message' => 'Backup failed!'], 500);
        }

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function scheduledBackup()
    {
        $filePath = $this->createBackupFile(false);

        if (!$filePath) {
            return response()->json(['message' => 'Scheduled backup failed!'], 500);
        }

        return response()->json([
            'message' => 'Scheduled backup saved successfully!',
            'file' => basename($filePath),
        ]);
    }

    private function createBackupFile($temporary = true)
    {
        try {
            $databaseName = env('DB_DATABASE');
            $folder = $temporary ? 'app' : 'app/backups';
            $storagePath = storage_path($folder);

            if (!file_exists($storagePath)) {
                mkdir($storagePath, 0777, true);
            }

            $filename = 'resbac_backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
            $filePath = $storagePath . '/' . $filename;

            $sqlScript = "-- ResBac Database Backup\n";
            $sqlScript .= "-- Generated on " . now() . "\n\n";

            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . $databaseName;

            foreach ($tables as $table) {
                $tableName = $table->$tableKey;

                $create = DB::select("SHOW CREATE TABLE `$tableName`");
                $createSql = $create[0]->{'Create Table'} ?? '';
                $sqlScript .= "$createSql;\n\n";

                $rows = DB::table($tableName)->get();
                foreach ($rows as $row) {
                    $values = array_map(function ($value) {
                        return isset($value)
                            ? "'" . str_replace("'", "''", $value) . "'"
                            : "NULL";
                    }, (array) $row);

                    $sqlScript .= "INSERT INTO `$tableName` VALUES (" . implode(',', $values) . ");\n";
                }

                $sqlScript .= "\n\n";
            }

            Storage::put(str_replace('app/', '', $folder) . '/' . $filename, $sqlScript);

            return $filePath;
        } catch (\Throwable $e) {
            Log::error('Backup generation error: ' . $e->getMessage());
            return null;
        }
    }

    public function restore(Request $request)
    {
        $request->validate([
            'backupFile' => 'required|file|mimes:sql,txt',
        ]);

        try {
            $sql = file_get_contents($request->file('backupFile')->getRealPath());
            DB::unprepared($sql);

            return response()->json(['message' => 'Database restored successfully!']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Restore failed!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
