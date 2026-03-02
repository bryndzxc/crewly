<?php

namespace App\Services;

use App\DTO\LeadCreateData;
use App\Models\Lead;
use App\Models\CrewlyNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LeadService extends Service
{
    public function submit(LeadCreateData $dto, ?string $actorEmail = null): Lead
    {
        /** @var Lead $lead */
        $lead = Lead::create($dto->toCreateAttributes());




            // In-app developer notification (bell icon) for new demo requests.
            if (config('app.developer_bypass', false)) {
                $developerEmails = array_values(array_filter(array_map(
                    fn ($v) => Str::lower(trim((string) $v)),
                    (array) config('app.developer_emails', [])
                )));

                if (count($developerEmails) > 0) {
                    $demoEmail = Str::lower((string) config('crewly.demo.email', 'demo@crewly.test'));
                    $submittedEmail = Str::lower((string) ($lead->email ?? ''));
                    $actorEmail = Str::lower((string) ($actorEmail ?? ''));

                    // Avoid noisy notifications when seeding/testing with the demo account.
                    $isDemo = $demoEmail !== ''
                        && ($submittedEmail === $demoEmail || $actorEmail === $demoEmail);

                    if (!$isDemo) {
                        $devUsers = User::query()
                            ->whereIn('email', $developerEmails)
                            ->get(['id', 'company_id', 'email']);

                        if ($devUsers->count() === 0) {
                            Log::warning('Lead saved but no developer users found for configured developer_emails.', [
                                'lead_id' => (int) $lead->id,
                            ]);
                        }

                        $title = 'New demo request';
                        $body = trim(implode(' — ', array_values(array_filter([
                            trim((string) ($lead->company_name ?? '')),
                            trim((string) ($lead->full_name ?? '')),
                            trim((string) ($lead->email ?? '')),
                        ]))));

                        $url = route('developer.demo_requests.index');
                        $dedupeKey = hash('sha256', 'demo_request|'.$lead->id);

                        foreach ($devUsers as $dev) {
                            $companyId = (int) ($dev->company_id ?? 0);
                            if ($companyId < 1) {
                                continue;
                            }

                            app(NotificationService::class)->createForUser(
                                (int) $dev->id,
                                $companyId,
                                'demo_request',
                                $title,
                                $body !== '' ? $body : null,
                                $url,
                                CrewlyNotification::SEVERITY_INFO,
                                [
                                    'lead_id' => (int) $lead->id,
                                    'source_page' => (string) ($lead->source_page ?? ''),
                                ],
                                $dedupeKey,
                            );
                        }
                    }
                }
            }

        return $lead;
    }
}
