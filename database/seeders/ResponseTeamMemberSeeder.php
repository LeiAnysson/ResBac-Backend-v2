<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\ResponseTeam;
use App\Models\ResponseTeamMember;
use Illuminate\Support\Facades\Hash;

class ResponseTeamMemberSeeder extends Seeder
{
    public function run(): void
    {
        $teamNames = ['Alpha', 'Bravo', 'Charlie'];

        $responderRole = Role::where('name', 'Responder')->first();

        $responderCounter = 2;

        foreach ($teamNames as $teamName) {
            $team = ResponseTeam::firstOrCreate(
                ['team_name' => $teamName],
                ['status' => 'Active']
            );

            $existingCount = ResponseTeamMember::where('team_id', $team->id)->count();

            for ($i = $existingCount + 1; $i <= 5; $i++) {
                $nameWord = $this->numberToWords($responderCounter);
                $email = "responder$responderCounter@resbac.com";

                if (User::where('email', $email)->exists()) {
                    $responderCounter++;
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
                    'contact_num' => '0919000000' . str_pad($responderCounter, 2, '0', STR_PAD_LEFT),
                    'role_id' => $responderRole->id,
                ]);

                ResponseTeamMember::create([
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                ]);

                $responderCounter++;
            }
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