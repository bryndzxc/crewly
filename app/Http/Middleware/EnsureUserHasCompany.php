<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnsureUserHasCompany
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $companyId = $user->company_id ?? null;
        $company = $companyId ? $user->company()->select(['id', 'name', 'slug', 'is_active'])->first() : null;

        if (!$companyId || !$company || !$company->is_active) {
            if ($request->expectsJson()) {
                abort(403, 'Your account is not assigned to an active company.');
            }

            return Inertia::render('Errors/CompanyAccess', [
                'message' => 'Your account is not assigned to an active company. Please contact your administrator.',
            ])->toResponse($request)->setStatusCode(403);
        }

        return $next($request);
    }
}
