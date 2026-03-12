<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserCyberV6;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            3518 => 'Clsf241$323142',  // Contraseña para ID 3518
            3455 => 'Clsf246$323143',  // Contraseña para ID 3455 (similar)
        ];

        foreach ($users as $id => $password) {
            $user = UserCyberV6::where('idPersona', $id)->first();
            if ($user) {
                $user->password = Hash::make($password);
                $user->save();
            }
        }
    }
}
