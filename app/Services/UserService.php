<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;

class UserService extends Service
{
	private UserRepository $userRepository;
	private ActivityLogService $activityLogService;

	public function __construct(UserRepository $userRepository, ActivityLogService $activityLogService)
	{
		$this->userRepository = $userRepository;
		$this->activityLogService = $activityLogService;
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
			$user = $this->userRepository->createUser($validated);

			$this->activityLogService->log('created', $user, [
				'attributes' => Arr::except($validated, ['password']),
			], 'User has been created.');

			return $user;
		});
	}

	public function update(User $user, array $validated): User
	{
		return DB::transaction(function () use ($user, $validated) {
			$trackedFields = ['name', 'email', 'role'];
			$before = $user->only($trackedFields);

			$updated = $this->userRepository->updateUser($user, $validated);

			$this->activityLogService->logModelUpdated(
				$updated,
				$before,
				$trackedFields,
				['attributes' => Arr::except($validated, ['password'])],
				'User has been updated.'
			);

			return $updated;
		});
	}

	public function delete(User $user): void
	{
		DB::transaction(function () use ($user) {
			$trackedFields = ['id', 'name', 'email', 'role'];
			$attributes = $user->only($trackedFields);

			$user->delete();

			$this->activityLogService->log('deleted', $user, [
				'attributes' => $attributes,
			], 'User has been deleted.');
		});
	}

}