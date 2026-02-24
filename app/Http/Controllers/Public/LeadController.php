<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Mail\NewLeadSubmitted;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $lead = Lead::create([
            'full_name' => $validated['full_name'],
            'company_name' => $validated['company_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'company_size' => $validated['company_size'] ?? null,
            'message' => $validated['message'] ?? null,
            'source_page' => $validated['source_page'] ?? null,
        ]);

        $demoEmail = strtolower((string) config('crewly.demo.email', 'demo@crewly.test'));
        $submittedEmail = strtolower((string) $lead->email);
        $actorEmail = strtolower((string) ($request->user()?->email ?? ''));

        $isDemo = $demoEmail !== ''
            && ($submittedEmail === $demoEmail || $actorEmail === $demoEmail);

        if (!$isDemo) {
            $to = config('crewly.leads.admin_email') ?: config('mail.from.address');
            if ($to) {
                try {
                    Mail::to($to)->send(new NewLeadSubmitted($lead));
                } catch (\Throwable $e) {
                    Log::warning('Lead saved but admin email failed to send.', [
                        'lead_id' => $lead->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                Log::warning('Lead saved but no ADMIN_LEADS_EMAIL or mail.from.address configured.', [
                    'lead_id' => $lead->id,
                ]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back(303)->with('success', 'Thanks — we received your demo request and will reach out shortly.');
    }
}
