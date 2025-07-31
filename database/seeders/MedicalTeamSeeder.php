<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\ResponseTeamMember;
use Illuminate\Support\Facades\Hash;

class MedicalTeamSeeder extends Seeder
{
    public function run(): void
    {
        $responderRole = Role::where('name', 'Responder')->first();

        if (!$responderRole) {
            $this->command->error('Responder role not found!');
            return;
        }

        $teamId = 4; // Medical Team's ID
        $start = 16;
        $end = 20;

        for ($i = $start; $i <= $end; $i++) {
            $nameWord = $this->numberToWords($i);
            $email = "responder{$i}@resbac.com";

            if (User::where('email', $email)->exists()) {
                $this->command->warn("User with email {$email} already exists. Skipping...");
                continue;
            }

            $user = User::create([
                'first_name' => 'Responder',
                'last_name' => $nameWord,
                'email' => $email,
                'password' => Hash::make('password'),
                'age' => 30,
                'birthdate' => '1995-01-01',
                'address' => 'Binang 1st, Bocaue',
                'contact_num' => '0919000000' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'role_id' => $responderRole->id,
            ]);

            ResponseTeamMember::create([
                'team_id' => $teamId,
                'user_id' => $user->id,
            ]);

            $this->command->info("Created Responder {$nameWord} and added to Medical Team");
        }
    }

    private function numberToWords($number): string
    {
        $words = [
            1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty'
        ];

        return $words[$number] ?? (string)$number;
    }
}
