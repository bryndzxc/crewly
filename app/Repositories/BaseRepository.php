<?php

namespace App\Repositories;

use Illuminate\Http\Request;

abstract class BaseRepository
{
    protected function resolveSearch(Request $request, string $key = 'q'): string
    {
        return trim((string) $request->query($key, ''));
    }

    protected function resolvePerPage(Request $request, int $default = 10, array $allowed = [10, 25, 50, 100], string $key = 'per_page'): int
    {
        $perPage = (int) $request->query($key, $default);

        if (!in_array($perPage, $allowed, true)) {
            return $default;
        }

        return $perPage;
    }
}
