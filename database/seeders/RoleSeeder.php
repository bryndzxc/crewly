<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['key' => User::ROLE_ADMIN, 'name' => 'Admin'],
            ['key' => User::ROLE_HR, 'name' => 'HR'],
            ['key' => User::ROLE_MANAGER, 'name' => 'Manager'],
            ['key' => User::ROLE_EMPLOYEE, 'name' => 'Employee'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(['key' => $role['key']], $role);
        }
    }
}
