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

        // Developer/super-admin bypass.
        if (method_exists($user, 'isDeveloper') && $user->isDeveloper()) {
            return $next($request);
        }

        // Allow the billing-required page itself to render even when suspended.
        if ($request->routeIs('billing.required')) {
            return $next($request);
        }

        // Allow routing to support chat even when suspended; further restrictions
        // are enforced below once the company status is known.
        if ($request->routeIs('chat.support')) {
            return $next($request);
        }

        $companyId = $user->company_id ?? null;
        $company = $companyId
            ? $user->company()->select(['id', 'name', 'slug', 'is_active', 'subscription_status'])->first()
            : null;

        if (!$companyId || !$company || !$company->is_active) {
            if ($request->expectsJson()) {
                abort(403, 'Your account is not assigned to an active company.');
            }

            return Inertia::render('Errors/CompanyAccess', [
                'message' => 'Your account is not assigned to an active company. Please contact your administrator.',
            ])->toResponse($request)->setStatusCode(403);
        }

        $status = strtolower(trim((string) ($company->subscription_status ?? 'trial')));
        if ($status === 'suspended') {
            $supportId = (int) $request->session()->get('support_conversation_id', 0);

            if ($supportId > 0) {
                if ($request->routeIs('chat.index')) {
                    $conversationId = (int) $request->integer('conversation_id');
                    if ($conversationId === $supportId) {
                        return $next($request);
                    }
                }

                if ($request->routeIs('chat.conversations.show', 'chat.messages.store', 'chat.conversations.read')) {
                    $routeId = (int) $request->route('id');
                    if ($routeId === $supportId) {
                        return $next($request);
                    }
                }
            }

            if ($request->expectsJson()) {
                abort(402, 'Billing required. Your subscription is currently suspended.');
            }

            return redirect()->route('billing.required')->setStatusCode(303);
        }

        return $next($request);
    }
}
