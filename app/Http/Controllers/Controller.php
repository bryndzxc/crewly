<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

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

    public function response($query, array $columns = ['*'], ?int $perPage = null)
    {
        if (method_exists($query->getModel(), 'scopePagination')) {
            return $query->pagination($perPage, $columns);
        }

        return $query->get($columns);
    }
}
