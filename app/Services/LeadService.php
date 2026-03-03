<?php

namespace App\Services;

use App\DTO\LeadCreateData;
use App\Models\Lead;
use App\Models\CrewlyNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LeadService extends Service
{
    public function submitDemoRequest(LeadCreateData $dto, ?string $actorEmail = null): Lead
    {
        return $this->submitLead(
            $dto,
            leadType: Lead::TYPE_DEMO,
            actorEmail: $actorEmail,
            pendingMessage: 'You have already requested a demo. Please wait for approval.',
            approvedMessage: 'Your demo request has already been approved. Please check your email for login details.',
            notificationType: 'demo_request',
            notificationTitle: 'New demo request',
            notificationUrl: route('developer.demo_requests.index'),
            ignoreEmailForNotifications: Str::lower((string) config('crewly.demo.email', 'demo@crewly.test')),
        );
    }

    public function submitAccessRequest(LeadCreateData $dto, ?string $actorEmail = null): Lead
    {
        return $this->submitLead(
            $dto,
            leadType: Lead::TYPE_ACCESS,
            actorEmail: $actorEmail,
            pendingMessage: 'You have already requested access. Please wait for approval.',
            approvedMessage: 'Your access request has already been approved. Please check your email for login details.',
            notificationType: 'access_request',
            notificationTitle: 'New access request',
            notificationUrl: route('developer.access_requests.index'),
            ignoreEmailForNotifications: null,
        );
    }

    private function submitLead(
        LeadCreateData $dto,
        string $leadType,
        ?string $actorEmail,
        string $pendingMessage,
        string $approvedMessage,
        string $notificationType,
        string $notificationTitle,
        string $notificationUrl,
        ?string $ignoreEmailForNotifications,
    ): Lead {
        $submittedEmail = Str::lower(trim((string) ($dto->email ?? '')));
        if ($submittedEmail !== '') {
            // If a real user already exists, treat this as a login issue rather than a new lead.
            if (User::withTrashed()->where('email', $submittedEmail)->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'An account with this email already exists. Please log in instead.',
                ]);
            }

            $existingQuery = Lead::query()->where('email', $submittedEmail);
            if ($leadType === Lead::TYPE_DEMO) {
                // Backwards compatibility: older demo leads may have lead_type = NULL.
                $existingQuery->where(function ($q) {
                    $q->whereNull('lead_type')->orWhere('lead_type', Lead::TYPE_DEMO);
                });
            } else {
                $existingQuery->where('lead_type', $leadType);
            }

            $existing = $existingQuery->orderByDesc('id')->first();

            if ($existing) {
                $status = (string) ($existing->status ?? Lead::STATUS_PENDING);

                if ($status === Lead::STATUS_PENDING) {
                    throw ValidationException::withMessages([
                        'email' => $pendingMessage,
                    ]);
                }

                if ($status === Lead::STATUS_APPROVED) {
                    throw ValidationException::withMessages([
                        'email' => $approvedMessage,
                    ]);
                }
            }
        }

        $createAttributes = $dto->toCreateAttributes();
        $createAttributes['lead_type'] = $leadType;

        /** @var Lead $lead */
        $lead = Lead::create($createAttributes);

        // In-app developer notification (bell icon) for new requests.
        if (config('app.developer_bypass', false)) {
            $developerEmails = array_values(array_filter(array_map(
                fn ($v) => Str::lower(trim((string) $v)),
                (array) config('app.developer_emails', [])
            )));

            if (count($developerEmails) > 0) {
                $leadEmail = Str::lower((string) ($lead->email ?? ''));
                $actorEmail = Str::lower((string) ($actorEmail ?? ''));

                // Avoid noisy notifications when seeding/testing with a known ignored email.
                $shouldIgnore = $ignoreEmailForNotifications !== null
                    && $ignoreEmailForNotifications !== ''
                    && ($leadEmail === $ignoreEmailForNotifications || $actorEmail === $ignoreEmailForNotifications);

                if (!$shouldIgnore) {
                    $devUsers = User::query()
                        ->whereIn('email', $developerEmails)
                        ->get(['id', 'company_id', 'email']);

                    if ($devUsers->count() === 0) {
                        Log::warning('Lead saved but no developer users found for configured developer_emails.', [
                            'lead_id' => (int) $lead->id,
                        ]);
                    }

                    $body = trim(implode(' — ', array_values(array_filter([
                        trim((string) ($lead->company_name ?? '')),
                        trim((string) ($lead->full_name ?? '')),
                        trim((string) ($lead->email ?? '')),
                    ]))));

                    $dedupeKey = hash('sha256', $notificationType.'|'.$lead->id);

                    foreach ($devUsers as $dev) {
                        $companyId = (int) ($dev->company_id ?? 0);
                        if ($companyId < 1) {
                            continue;
                        }

                        app(NotificationService::class)->createForUser(
                            (int) $dev->id,
                            $companyId,
                            $notificationType,
                            $notificationTitle,
                            $body !== '' ? $body : null,
                            $notificationUrl,
                            CrewlyNotification::SEVERITY_INFO,
                            [
                                'lead_id' => (int) $lead->id,
                                'lead_type' => $leadType,
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
