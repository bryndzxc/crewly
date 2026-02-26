<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            // CompanyDemoSeeder::class,
            UserSeeder::class,
            DepartmentSeeder::class,
            LeaveTypeSeeder::class,
            RecruitmentPositionSeeder::class,
            ChatSeeder::class,
            MemoTemplateSeeder::class,
        ]);
    }
}
