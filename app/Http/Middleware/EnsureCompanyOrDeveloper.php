<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyOrDeveloper
{
    /**
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && method_exists($user, 'isDeveloper') && $user->isDeveloper()) {
            return $next($request);
        }

        return (new EnsureUserHasCompany())->handle($request, $next);
    }
}
