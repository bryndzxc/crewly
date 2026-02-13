<?php

namespace Database\Seeders;

use App\Models\CrewlyNotification;
use App\Models\User;
use Illuminate\Database\Seeder;

class CrewlyNotificationSeeder extends Seeder
{
    public function run(): void
    {
        /** @var User|null $user */
        $user = User::query()->orderBy('id')->first();
        if (!$user) {
            return;
        }

        $items = [
            [
                'type' => 'DOC_EXPIRING',
                'title' => 'Document expiring in 7 days',
                'body' => 'Employee EMP-2026-0001 — Passport expires on 2026-02-20.',
                'severity' => CrewlyNotification::SEVERITY_DANGER,
                'url' => '/documents/expiring?days=7',
            ],
            [
                'type' => 'PROBATION_ENDING',
                'title' => 'Probation ending in 15 days',
                'body' => 'Employee EMP-2026-0002 regularization date is 2026-02-28.',
                'severity' => CrewlyNotification::SEVERITY_WARNING,
                'url' => '/employees',
            ],
            [
                'type' => 'INCIDENT_FOLLOWUP',
                'title' => 'Incident follow-up due today',
                'body' => 'Employee EMP-2026-0003 — Attendance issue follow-up on 2026-02-13.',
                'severity' => CrewlyNotification::SEVERITY_DANGER,
                'url' => '/employees',
            ],
            [
                'type' => 'LEAVE_PENDING',
                'title' => 'Leave request submitted',
                'body' => 'Employee #1 submitted a leave request (2026-02-15 to 2026-02-16).',
                'severity' => CrewlyNotification::SEVERITY_INFO,
                'url' => '/leave/requests',
            ],
        ];

        foreach ($items as $it) {
            CrewlyNotification::query()->create([
                'user_id' => (int) $user->id,
                'type' => (string) $it['type'],
                'title' => (string) $it['title'],
                'body' => (string) $it['body'],
                'url' => (string) $it['url'],
                'severity' => (string) $it['severity'],
                'data' => null,
            ]);
        }
    }
}
