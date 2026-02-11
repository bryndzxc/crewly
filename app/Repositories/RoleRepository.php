<?php

namespace App\Repositories;

use App\Models\Role;
use Illuminate\Support\Facades\Schema;

class RoleRepository extends BaseRepository
{
    public function forSelect()
    {
        if (!Schema::hasTable('roles')) {
            return collect([
                ['key' => 'admin', 'name' => 'Admin'],
                ['key' => 'hr', 'name' => 'HR'],
                ['key' => 'manager', 'name' => 'Manager'],
            ]);
        }

        return Role::query()->orderBy('name')->get(['key', 'name']);
    }

    public function createRole(array $attributes): Role
    {
        return Role::create($attributes);
    }

    public function updateRole(Role $role, array $attributes): Role
    {
        $role->update($attributes);

        return $role;
    }

    public function deleteRole(Role $role): void
    {
        $role->delete();
    }
}
