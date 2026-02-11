<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserService extends Service
{
	private UserRepository $userRepository;

	public function __construct(UserRepository $userRepository)
	{
		$this->userRepository = $userRepository;
	}

	public function index(Request $request): array
	{
		$q = trim((string) $request->query('q', ''));
		$perPage = (int) $request->query('per_page', 10);

		if (!in_array($perPage, [10, 25, 50, 100], true)) {
			$perPage = 10;
		}

		return [
			'users' => User::query()
				->searchable($q)
				->sortable()
				->pagination($perPage, ['id', 'name', 'email', 'role']),
			'filters' => [
				'q' => $q,
				'per_page' => $perPage,
			],
		];
	}

	public function rolesForSelect()
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

	public function create(array $validated): User
	{
		return DB::transaction(function () use ($validated) {
			return $this->userRepository->createUser($validated);
		});
	}

	public function update(User $user, array $validated): User
	{
		return DB::transaction(function () use ($user, $validated) {
			return $this->userRepository->updateUser($user, $validated);
		});
	}

}