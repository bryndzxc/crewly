<?php

namespace App\Services;

use App\DTO\LeadCreateData;
use App\Mail\NewLeadSubmitted;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LeadService extends Service
{
    public function submit(LeadCreateData $dto, ?string $actorEmail = null): Lead
    {
        /** @var Lead $lead */
        $lead = Lead::create($dto->toCreateAttributes());

        // $demoEmail = strtolower((string) config('crewly.demo.email', 'demo@crewly.test'));
        // $submittedEmail = strtolower((string) $lead->email);
        // $actorEmail = strtolower((string) ($actorEmail ?? ''));

        // $isDemo = $demoEmail !== ''
        //     && ($submittedEmail === $demoEmail || $actorEmail === $demoEmail);

        // if (!$isDemo) {
        //     $to = config('crewly.leads.admin_email') ?: config('mail.from.address');
        //     if ($to) {
        //         try {
        //             Mail::to($to)->send(new NewLeadSubmitted($lead));
        //         } catch (\Throwable $e) {
        //             Log::warning('Lead saved but admin email failed to send.', [
        //                 'lead_id' => $lead->id,
        //                 'error' => $e->getMessage(),
        //             ]);
        //         }
        //     } else {
        //         Log::warning('Lead saved but no ADMIN_LEADS_EMAIL or mail.from.address configured.', [
        //             'lead_id' => $lead->id,
        //         ]);
        //     }
        // }

        return $lead;
    }
}
