<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = (string) env('SEED_DEFAULT_PASSWORD', 'password');

        $companyId = (int) (Company::query()->orderBy('id')->value('id') ?? 0);
        if ($companyId <= 0) {
            $companyId = (int) Company::query()->create([
                'name' => 'Default Company',
                'slug' => 'default',
                'timezone' => (string) config('app.timezone', 'Asia/Manila'),
                'is_active' => true,
            ])->id;
        }

        $users = [
            [
                'name' => 'Brynd Benosa',
                'email' => 'benosa.brynd18@gmail.com',
                'role' => User::ROLE_ADMIN,
            ],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'company_id' => $companyId,
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => Hash::make($defaultPassword),
                    'email_verified_at' => now(),
                ]
            );
        }
    }

}
