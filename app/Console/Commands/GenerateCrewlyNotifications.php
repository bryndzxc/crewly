<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeIncident;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateCrewlyNotifications extends Command
{
    protected $signature = 'crewly:generate-notifications';

    protected $description = 'Generate Crewly in-app notifications (expiry, probation, incidents).';

    public function __construct(private readonly NotificationService $notifications)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = Carbon::today();
        $offsets = [30, 15, 7];

        $created = 0;

        foreach ($offsets as $days) {
            $target = $today->copy()->addDays($days)->toDateString();

            $docs = EmployeeDocument::query()
                ->select(['id', 'company_id', 'employee_id', 'type', 'expiry_date'])
                ->with(['employee:employee_id,company_id,employee_code'])
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', $target)
                ->orderBy('expiry_date', 'asc')
                ->get();

            foreach ($docs as $doc) {
                $created += $this->notifications->notifyDocumentExpiring($doc, $days);
            }

            $employees = Employee::query()
                ->select(['employee_id', 'company_id', 'employee_code', 'regularization_date'])
                ->whereNotNull('regularization_date')
                ->whereDate('regularization_date', $target)
                ->orderBy('regularization_date', 'asc')
                ->get();

            foreach ($employees as $employee) {
                $created += $this->notifications->notifyProbationEnding($employee, $days);
            }
        }

        $incidentWindowEnd = $today->copy()->addDays(7)->toDateString();

        $incidents = EmployeeIncident::query()
            ->select(['id', 'company_id', 'employee_id', 'category', 'status', 'follow_up_date'])
            ->with(['employee:employee_id,company_id,employee_code'])
            ->whereNotNull('follow_up_date')
            ->whereBetween('follow_up_date', [$today->toDateString(), $incidentWindowEnd])
            ->whereNotIn('status', [EmployeeIncident::STATUS_CLOSED])
            ->orderBy('follow_up_date', 'asc')
            ->get();

        foreach ($incidents as $incident) {
            $created += $this->notifications->notifyIncidentFollowup($incident);
        }

        $this->info("Generated {$created} notifications.");

        return self::SUCCESS;
    }
}
