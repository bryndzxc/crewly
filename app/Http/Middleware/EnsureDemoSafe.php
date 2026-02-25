<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDemoSafe
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->company_id) {
            return $next($request);
        }

        $company = $user->company;
        if (! $company || ! $company->is_demo) {
            return $next($request);
        }

        $method = strtoupper((string) $request->method());

        // Block destructive actions in demo mode.
        if ($method === 'DELETE') {
            return $this->deny($request, 'Demo mode: delete actions are disabled.');
        }

        // Keep the demo stable by preventing settings mutations.
        if ($request->is('settings*') && ! in_array($method, ['GET', 'HEAD'], true)) {
            if ($request->routeIs('settings.chat.sound')) {
                return $next($request);
            }

            return $this->deny($request, 'Demo mode: settings changes are disabled.');
        }

        return $next($request);
    }

    private function deny(Request $request, string $message): Response
    {
        // Inertia requests should redirect back with a flash error.
        if ($request->header('X-Inertia')) {
            /** @var RedirectResponse $response */
            $response = redirect()->back()->with('error', $message);

            return $response->setStatusCode(303);
        }

        abort(403, $message);
    }
}
