<?php

namespace App\Console\Commands;

use App\Models\EmployeeDocument;
use App\Models\User;
use App\Notifications\DocumentExpiringSoon;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DocumentRemindersCommand extends Command
{
    protected $signature = 'crewly:document-reminders';

    protected $description = 'Create in-app notifications for HR/Admin for documents expiring soon.';

    public function handle(): int
    {
        $today = Carbon::today();
        $end = $today->copy()->addDays(7);

        $docs = EmployeeDocument::query()
            ->select(['id', 'employee_id', 'type', 'expiry_date'])
            ->with([
                'employee' => function ($query) {
                    $query->select([
                        'employee_id',
                        'employee_code',
                        'first_name',
                        'middle_name',
                        'last_name',
                        'suffix',
                    ]);
                },
            ])
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today->toDateString(), $end->toDateString()])
            ->orderBy('expiry_date', 'asc')
            ->get();

        if ($docs->isEmpty()) {
            $this->info('No documents expiring in the next 7 days.');
            return self::SUCCESS;
        }

        $recipients = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_HR])
            ->get();

        if ($recipients->isEmpty()) {
            $this->warn('No HR/Admin users found to notify.');
            return self::SUCCESS;
        }

        $count = 0;

        foreach ($docs as $doc) {
            $doc->append(['days_to_expiry']);

            $employee = $doc->employee;
            $employeeLabel = $employee
                ? trim(implode(' ', array_filter([
                    (string) ($employee->first_name ?? ''),
                    (string) ($employee->middle_name ?? ''),
                    (string) ($employee->last_name ?? ''),
                    (string) ($employee->suffix ?? ''),
                ])))
                : 'Employee';

            $title = 'Document expiring soon';
            $body = sprintf(
                '%s (%s) — %s expires on %s (%s days remaining).',
                $employeeLabel,
                (string) ($employee->employee_code ?? '—'),
                (string) $doc->type,
                (string) ($doc->expiry_date?->toDateString() ?? '—'),
                (string) ($doc->days_to_expiry ?? '—')
            );

            $payload = [
                'title' => $title,
                'body' => $body,
                'link' => route('documents.expiring', ['days' => 30], false),
                'document_id' => $doc->id,
                'employee_id' => $doc->employee_id,
                'type' => $doc->type,
                'expiry_date' => $doc->expiry_date?->toDateString(),
                'days_to_expiry' => $doc->days_to_expiry,
            ];

            foreach ($recipients as $user) {
                $user->notify(new DocumentExpiringSoon($payload));
                $count++;
            }
        }

        $this->info("Created {$count} notifications.");

        return self::SUCCESS;
    }
}
