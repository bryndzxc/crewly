<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = (string) env('SEED_DEFAULT_PASSWORD', 'password');

        $users = [
            [
                'name' => 'Brynd Benosa',
                'email' => 'benosa.brynd18@gmail.com',
                'role' => User::ROLE_ADMIN,
            ],
            [
                'name' => 'Joanna Rebote',
                'email' => 'rebotejoanna05@gmail.com',
                'role' => User::ROLE_ADMIN,
            ],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => Hash::make($defaultPassword),
                    'email_verified_at' => now(),
                ]
            );
        }
    }

}
