<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForcePasswordChange
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        $mustChange = (bool) ($user->must_change_password ?? false);
        if (!$mustChange) {
            return $next($request);
        }

        if (
            $request->routeIs('password.change') ||
            $request->routeIs('my.profile') ||
            $request->routeIs('profile.edit') ||
            $request->routeIs('profile.update') ||
            $request->routeIs('profile.photo') ||
            $request->routeIs('profile.photo.show') ||
            $request->routeIs('password.update') ||
            $request->routeIs('logout')
        ) {
            return $next($request);
        }

        return redirect()
            ->route('password.change')
            ->with('warning', 'Please change your password before continuing.')
            ->setStatusCode(303);
    }
}
