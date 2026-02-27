<?php

namespace App\Services;

use App\Models\CrewlyNotification;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeIncident;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class NotificationService extends Service
{
    /**
     * @return Collection<int, User>
     */
    public function hrAdminRecipients(): Collection
    {
        $companyId = (int) (Auth::user()?->company_id ?? 0);
        if ($companyId < 1) {
            return collect();
        }

        return $this->hrAdminRecipientsForCompany($companyId);
    }

    /**
     * @return Collection<int, User>
     */
    public function hrAdminRecipientsForCompany(int $companyId): Collection
    {
        if ($companyId < 1) {
            return collect();
        }

        return User::query()
            ->where('company_id', $companyId)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_HR])
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, int>
     */
    public function hrAdminRecipientIds(): array
    {
        return $this->hrAdminRecipients()->pluck('id')->map(fn ($v) => (int) $v)->values()->all();
    }

    /**
     * @return array<int, int>
     */
    public function hrAdminRecipientIdsForCompany(int $companyId): array
    {
        return $this->hrAdminRecipientsForCompany($companyId)
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();
    }

    public function unreadCountFor(User $user): int
    {
        return CrewlyNotification::query()
            ->where('user_id', (int) $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function latestFor(User $user, int $limit = 5): array
    {
        return CrewlyNotification::query()
            ->where('user_id', (int) $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get([
                'id',
                'type',
                'title',
                'body',
                'url',
                'severity',
                'read_at',
                'created_at',
            ])
            ->map(fn (CrewlyNotification $n) => $this->toListItem($n))
            ->values()
            ->all();
    }

    /**
     * @param array{status?:string,type?:string,per_page?:int} $filters
     */
    public function paginateFor(User $user, array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 15), 5), 100);

        $query = CrewlyNotification::query()
            ->where('user_id', (int) $user->id);

        if (($filters['status'] ?? '') === 'unread') {
            $query->whereNull('read_at');
        }

        $type = trim((string) ($filters['type'] ?? ''));
        if ($type !== '') {
            $query->where('type', $type);
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (CrewlyNotification $n) => $this->toListItem($n));
    }

    public function markReadForUser(CrewlyNotification $notification, User $user): void
    {
        if ((int) $notification->user_id !== (int) $user->id) {
            abort(403);
        }

        if ($notification->read_at) {
            return;
        }

        $notification->markRead();

        app(AuditLogger::class)->log(
            'notification.read',
            $notification,
            [],
            ['read_at' => $notification->read_at?->format('Y-m-d H:i:s')],
            ['notification_id' => (int) $notification->id, 'type' => (string) $notification->type],
            'Notification marked as read.'
        );
    }

    public function markAllReadForUser(User $user): int
    {
        $updated = CrewlyNotification::query()
            ->where('user_id', (int) $user->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        if ($updated > 0) {
            app(AuditLogger::class)->log(
                'notification.read_all',
                null,
                [],
                [],
                ['user_id' => (int) $user->id, 'count' => (int) $updated],
                'Notifications marked as read (all).'
            );
        }

        return (int) $updated;
    }

    public function dedupeKey(string $type, string $entityType, int|string $entityId, string $targetDate): string
    {
        return hash('sha256', implode('|', [$type, $entityType, (string) $entityId, $targetDate]));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createForUser(
        int $userId,
        string $type,
        string $title,
        ?string $body,
        ?string $url,
        string $severity,
        array $data = [],
        ?string $dedupeKey = null
    ): ?CrewlyNotification {
        $payload = [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'severity' => $severity,
            'data' => empty($data) ? null : $data,
            'dedupe_key' => $dedupeKey,
        ];

        try {
            /** @var CrewlyNotification $created */
            $created = CrewlyNotification::query()->create($payload);
        } catch (QueryException $e) {
            // De-dupe via unique constraint.
            if ($dedupeKey && ((string) $e->getCode() === '23000' || str_contains(strtolower($e->getMessage()), 'crewly_notifications_user_dedupe_unique'))) {
                return null;
            }
            throw $e;
        }

        app(AuditLogger::class)->log(
            'notification.created',
            $created,
            [],
            [
                'notification_id' => (int) $created->id,
                'type' => (string) $created->type,
                'severity' => (string) $created->severity,
                'url' => (string) ($created->url ?? ''),
            ],
            ['user_id' => (int) $userId],
            'Notification created.'
        );

        return $created;
    }

    public function notifyLeaveSubmitted(LeaveRequest $leaveRequest): int
    {
        $companyId = (int) ($leaveRequest->company_id ?? 0);
        $ids = $this->hrAdminRecipientIdsForCompany($companyId);
        if (count($ids) === 0) {
            return 0;
        }

        $employeeId = (int) $leaveRequest->employee_id;
        $title = 'Leave request submitted';
        $body = sprintf(
            'Employee #%d submitted a leave request (%s to %s).',
            $employeeId,
            (string) ($leaveRequest->start_date?->format('Y-m-d') ?? '—'),
            (string) ($leaveRequest->end_date?->format('Y-m-d') ?? '—')
        );

        $url = route('leave.requests.show', $leaveRequest->id, false);
        $targetDate = (string) ($leaveRequest->created_at?->toDateString() ?? Carbon::today()->toDateString());

        $created = 0;

        foreach ($ids as $userId) {
            $n = $this->createForUser(
                userId: (int) $userId,
                type: 'LEAVE_PENDING',
                title: $title,
                body: $body,
                url: $url,
                severity: CrewlyNotification::SEVERITY_INFO,
                data: [
                    'leave_request_id' => (int) $leaveRequest->id,
                    'employee_id' => $employeeId,
                    'start_date' => $leaveRequest->start_date?->format('Y-m-d'),
                    'end_date' => $leaveRequest->end_date?->format('Y-m-d'),
                    'status' => (string) $leaveRequest->status,
                ],
                dedupeKey: $this->dedupeKey('LEAVE_PENDING', 'leave_request', (int) $leaveRequest->id, $targetDate)
            );

            if ($n) {
                $created++;
            }
        }

        return $created;
    }

    public function notifyDocumentExpiring(EmployeeDocument $doc, int $days): int
    {
        $companyId = (int) ($doc->company_id ?? 0);
        $ids = $this->hrAdminRecipientIdsForCompany($companyId);
        if (count($ids) === 0) {
            return 0;
        }

        $employee = $doc->employee;
        $employeeCode = (string) ($employee?->employee_code ?? '—');

        $title = "Document expiring in {$days} days";
        $body = sprintf(
            'Employee %s — %s expires on %s.',
            $employeeCode,
            (string) $doc->type,
            (string) ($doc->expiry_date?->toDateString() ?? '—')
        );

        $severity = $days <= 7 ? CrewlyNotification::SEVERITY_DANGER : ($days <= 15 ? CrewlyNotification::SEVERITY_WARNING : CrewlyNotification::SEVERITY_INFO);
        $url = route('documents.expiring', ['days' => $days], false);
        $targetDate = (string) ($doc->expiry_date?->toDateString() ?? Carbon::today()->toDateString());

        $created = 0;

        foreach ($ids as $userId) {
            $n = $this->createForUser(
                userId: (int) $userId,
                type: 'DOC_EXPIRING',
                title: $title,
                body: $body,
                url: $url,
                severity: $severity,
                data: [
                    'employee_id' => (int) $doc->employee_id,
                    'employee_code' => $employeeCode,
                    'employee_document_id' => (int) $doc->id,
                    'document_type' => (string) $doc->type,
                    'expiry_date' => $doc->expiry_date?->toDateString(),
                    'days' => $days,
                ],
                dedupeKey: $this->dedupeKey('DOC_EXPIRING', 'employee_document', (int) $doc->id, $targetDate)
            );

            if ($n) {
                $created++;
            }
        }

        return $created;
    }

    public function notifyProbationEnding(Employee $employee, int $days): int
    {
        $companyId = (int) ($employee->company_id ?? 0);
        $ids = $this->hrAdminRecipientIdsForCompany($companyId);
        if (count($ids) === 0) {
            return 0;
        }

        $employeeCode = (string) ($employee->employee_code ?? '—');
        $title = "Probation ending in {$days} days";
        $body = sprintf(
            'Employee %s regularization date is %s.',
            $employeeCode,
            (string) ($employee->regularization_date?->format('Y-m-d') ?? '—')
        );

        $severity = $days <= 7 ? CrewlyNotification::SEVERITY_DANGER : ($days <= 15 ? CrewlyNotification::SEVERITY_WARNING : CrewlyNotification::SEVERITY_INFO);
        $url = route('employees.show', $employee->employee_id, false);
        $targetDate = (string) ($employee->regularization_date?->format('Y-m-d') ?? Carbon::today()->toDateString());

        $created = 0;

        foreach ($ids as $userId) {
            $n = $this->createForUser(
                userId: (int) $userId,
                type: 'PROBATION_ENDING',
                title: $title,
                body: $body,
                url: $url,
                severity: $severity,
                data: [
                    'employee_id' => (int) $employee->employee_id,
                    'employee_code' => $employeeCode,
                    'regularization_date' => $employee->regularization_date?->format('Y-m-d'),
                    'days' => $days,
                ],
                dedupeKey: $this->dedupeKey('PROBATION_ENDING', 'employee', (int) $employee->employee_id, $targetDate)
            );

            if ($n) {
                $created++;
            }
        }

        return $created;
    }

    public function notifyIncidentFollowup(EmployeeIncident $incident): int
    {
        $companyId = (int) ($incident->company_id ?? 0);
        $ids = $this->hrAdminRecipientIdsForCompany($companyId);
        if (count($ids) === 0) {
            return 0;
        }

        $followUp = $incident->follow_up_date;
        if (!$followUp) {
            return 0;
        }

        $days = Carbon::today()->diffInDays($followUp, false);
        $employeeCode = (string) ($incident->employee?->employee_code ?? '—');

        $title = $days === 0 ? 'Incident follow-up due today' : "Incident follow-up due in {$days} days";
        $body = sprintf(
            'Employee %s — %s (status: %s) follow-up on %s.',
            $employeeCode,
            (string) $incident->category,
            (string) $incident->status,
            (string) $followUp->toDateString()
        );

        $severity = $days <= 0 ? CrewlyNotification::SEVERITY_DANGER : ($days <= 3 ? CrewlyNotification::SEVERITY_WARNING : CrewlyNotification::SEVERITY_INFO);
        $url = route('employees.show', $incident->employee_id, false);
        $targetDate = (string) $followUp->toDateString();

        $created = 0;

        foreach ($ids as $userId) {
            $n = $this->createForUser(
                userId: (int) $userId,
                type: 'INCIDENT_FOLLOWUP',
                title: $title,
                body: $body,
                url: $url,
                severity: $severity,
                data: [
                    'employee_id' => (int) $incident->employee_id,
                    'employee_code' => $employeeCode,
                    'incident_id' => (int) $incident->id,
                    'category' => (string) $incident->category,
                    'status' => (string) $incident->status,
                    'follow_up_date' => $followUp->toDateString(),
                    'days' => $days,
                ],
                dedupeKey: $this->dedupeKey('INCIDENT_FOLLOWUP', 'employee_incident', (int) $incident->id, $targetDate)
            );

            if ($n) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * @return array<string, mixed>
     */
    private function toListItem(CrewlyNotification $n): array
    {
        return [
            'id' => (int) $n->id,
            'type' => (string) $n->type,
            'title' => (string) $n->title,
            'body' => $n->body,
            'url' => $n->url,
            'severity' => (string) $n->severity,
            'read_at' => $n->read_at?->format('Y-m-d H:i:s'),
            'created_at' => $n->created_at?->format('Y-m-d H:i:s'),
            'created_at_human' => $n->created_at?->diffForHumans(),
        ];
    }
}
