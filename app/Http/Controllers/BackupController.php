<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    public function backup()
    {
        $filePath = $this->createBackupFile(true);

        if (!$filePath || !file_exists($filePath)) {
            return response()->json(['message' => 'Backup failed!'], 500);
        }

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function scheduledBackup()
    {
        $filePath = $this->createBackupFile(false);

        if (!$filePath || !file_exists($filePath)) {
            return response()->json(['message' => 'Scheduled backup failed!'], 500);
        }

        return response()->json([
            'message' => 'Scheduled backup saved successfully!',
            'file' => basename($filePath),
        ]);
    }

    /**
     *
     * @param  bool  $temporary 
     * @return string|false
     */
    private function createBackupFile(bool $temporary = true)
    {
        try {
            $databaseName = env('DB_DATABASE');
            $folder = $temporary ? 'app' : 'app/backups';
            $storagePath = storage_path($folder);

            if (!is_dir($storagePath)) {
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
                $sqlScript .= $createSql . ";\n\n";

                $rows = DB::table($tableName)->get();
                foreach ($rows as $row) {
                    $values = array_map(function ($value) {
                        if (is_null($value)) {
                            return 'NULL';
                        }
                        $v = str_replace(['\\', "'"], ['\\\\', "\\'"], (string)$value);
                        return "'" . $v . "'";
                    }, (array) $row);

                    $sqlScript .= "INSERT INTO `$tableName` VALUES (" . implode(',', $values) . ");\n";
                }

                $sqlScript .= "\n\n";
            }

            file_put_contents($filePath, $sqlScript);

            return $filePath;
        } catch (\Throwable $e) {
            Log::error('Backup generation error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;
        }
    }

    public function restore(Request $request)
    {
        $request->validate([
            'backupFile' => 'required|file|mimes:sql,txt',
        ]);

        try {
            $sql = file_get_contents($request->file('backupFile')->getRealPath());

            DB::beginTransaction();
            DB::unprepared($sql);
            DB::commit();

            return response()->json(['message' => 'Database restored successfully!']);
        } catch (\Throwable $e) {
            DB::rollBack();

            $errorMessage = $e->getMessage();

            if (str_contains(strtolower($errorMessage), 'already exists')) {
                return response()->json([
                    'message' => 'Restore failed — some tables already exist in the database.',
                    'error' => $errorMessage,
                    'code' => 'TABLE_EXISTS'
                ], 400);
            }

            Log::error('Restore error: ' . $errorMessage);
            return response()->json([
                'message' => 'Restore failed!',
                'error' => $errorMessage,
            ], 500);
        }
    }

    public function saveSchedule(Request $request)
    {
        $data = $request->validate([
            'frequency' => 'required|string|in:daily,weekly,monthly',
            'time' => 'required|date_format:H:i',
        ]);

        $path = storage_path('app/private/backup_schedule.json');
        file_put_contents($path, json_encode($data));

        return response()->json(['message' => 'Backup schedule saved.', 'data' => $data]);
    }
}
