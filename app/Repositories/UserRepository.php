<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository extends BaseRepository
{
	public function createUser(array $validated): User
	{
		$attributes = [
			'name' => $validated['name'],
			'email' => $validated['email'],
			'role' => $validated['role'],
		];

		if (!empty($validated['password'])) {
			$attributes['password'] = Hash::make($validated['password']);
		}

		return User::create($attributes);
	}

	public function updateUser(User $user, array $validated): User
	{
		$attributes = [
			'name' => $validated['name'],
			'email' => $validated['email'],
			'role' => $validated['role'],
		];

		if (!empty($validated['password'])) {
			$attributes['password'] = Hash::make($validated['password']);
		}

		$user->update($attributes);

		return $user;
	}
}
