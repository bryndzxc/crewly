<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('CREWLY_DEMO_EMAIL', 'demo@crewly.test');
        $password = (string) env('CREWLY_DEMO_PASSWORD', 'demo-password');
        $role = (string) env('CREWLY_DEMO_ROLE', 'manager');

        $attributes = [
            'name' => 'Crewly Demo',
            'email' => $email,
            'password' => Hash::make($password),
        ];

        if (Schema::hasColumn('users', 'role')) {
            $attributes['role'] = $role;
        }

        if (Schema::hasColumn('users', 'email_verified_at')) {
            $attributes['email_verified_at'] = now();
        }

        if (Schema::hasColumn('users', 'must_change_password')) {
            $attributes['must_change_password'] = false;
        }

        if (Schema::hasColumn('users', 'chat_sound_enabled')) {
            $attributes['chat_sound_enabled'] = true;
        }

        $user = User::withTrashed()->where('email', $email)->first();
        if ($user && method_exists($user, 'restore') && $user->trashed()) {
            $user->restore();
        }

        User::query()->updateOrCreate(['email' => $email], $attributes);
    }
}
