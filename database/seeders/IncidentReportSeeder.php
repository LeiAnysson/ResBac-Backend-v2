<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\IncidentReport;
use Carbon\Carbon;

class IncidentReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $reports = [
            [
                'reported_by' => 5,
                'incident_type_id' => 1, 
                'caller_name' => 'Juan Dela Cruz',
                'latitude' => 14.7900000,
                'longitude' => 120.9600000,
                'landmark' => 'Near Duhat Elementary School',
                'status' => 'Requesting for backup',
                'reported_at' => Carbon::createFromFormat('m-d-Y h:ia', '02-31-2025 3:00pm'),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'reported_by' => 2,
                'incident_type_id' => 2, 
                'caller_name' => 'Maria Santos',
                'latitude' => 14.8200000,
                'longitude' => 120.8800000,
                'landmark' => 'Philippine Arena',
                'status' => 'Resolved',
                'reported_at' => Carbon::createFromFormat('m-d-Y h:ia', '02-31-2025 3:00pm'),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'reported_by' => 5,
                'incident_type_id' => 2,
                'caller_name' => 'Jose Ramos',
                'latitude' => 14.7000000,
                'longitude' => 120.9500000,
                'landmark' => 'Beside City Mall Bocaue',
                'status' => 'Resolved',
                'reported_at' => Carbon::createFromFormat('m-d-Y h:ia', '02-31-2025 3:00pm'),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($reports as $report) {
            IncidentReport::create($report);
        }
    }
}
