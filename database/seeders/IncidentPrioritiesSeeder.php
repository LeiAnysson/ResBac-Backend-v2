<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IncidentPrioritiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('incident_priorities')->insert([
            [
                'priority_name'   => 'Low',
                'priority_level'  => 1,
                'description'     => 'Minor incidents, minimal risk',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'priority_name'   => 'Medium',
                'priority_level'  => 2,
                'description'     => 'Moderate incidents, requires attention',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'priority_name'   => 'High',
                'priority_level'  => 3,
                'description'     => 'Severe incidents, urgent response needed',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'priority_name'   => 'Critical',
                'priority_level'  => 4,
                'description'     => 'Life-threatening incidents, immediate response',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);
    }
}
