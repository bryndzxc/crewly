<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\password as prompt_password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CrewlyUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crewly:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a Crewly user for testing login';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = text(
            label: 'Name',
            required: true,
        );

        $email = text(
            label: 'Email',
            required: true,
            validate: fn (string $value) => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Please enter a valid email address.'
        );

        if (User::query()->where('email', $email)->exists()) {
            $this->components->error("A user with email '{$email}' already exists.");
            return self::FAILURE;
        }

        $plainPassword = prompt_password(
            label: 'Password',
            required: true,
        );

        $roleOptions = [];
        if (Schema::hasTable('roles')) {
            $roleOptions = Role::query()->orderBy('name')->pluck('name', 'key')->toArray();
        }

        if (empty($roleOptions)) {
            $roleOptions = [
                User::ROLE_ADMIN => 'Admin',
                User::ROLE_HR => 'HR',
                User::ROLE_MANAGER => 'Manager',
            ];
        }

        $defaultRole = array_key_exists(User::ROLE_ADMIN, $roleOptions)
            ? User::ROLE_ADMIN
            : array_key_first($roleOptions);

        $selected = select(
            label: 'Role',
            options: $roleOptions,
            default: $defaultRole,
        );

        $role = array_key_exists($selected, $roleOptions)
            ? $selected
            : User::ROLE_ADMIN;

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'password' => Hash::make($plainPassword),
        ]);

        $this->components->info('User created successfully.');
        $this->line('ID: ' . $user->id);
        $this->line('Name: ' . $user->name);
        $this->line('Email: ' . $user->email);
        $this->line('Role: ' . $user->role);

        return self::SUCCESS;
    }
}
