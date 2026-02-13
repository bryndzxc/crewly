<?php

namespace App\Services;

use App\DTO\EmployeeIncidentData;
use App\DTO\EmployeeIncidentUpdateData;
use App\DTO\EmployeeNoteData;
use App\Models\Employee;
use App\Models\EmployeeIncident;
use App\Models\EmployeeNote;
use App\Models\EmployeeRelationAttachment;
use App\Repositories\EmployeeRelationRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EmployeeRelationService extends Service
{
    public function __construct(
        private readonly EmployeeRelationRepository $repository,
        private readonly EmployeeRelationAttachmentService $attachmentService,
        private readonly ActivityLogService $activity,
    ) {
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile> $attachments
     */
    public function addNote(Employee $employee, EmployeeNoteData $data, array $attachments, ?int $actorId): EmployeeNote
    {
        return DB::transaction(function () use ($employee, $data, $attachments, $actorId) {
            $note = $this->repository->createNote($employee, $data, $actorId);

            if (count($attachments) > 0) {
                $created = $this->attachmentService->uploadMany($note, $attachments, null, $actorId);
                foreach ($created as $attachment) {
                    $this->activity->log('rel_attachment_uploaded', $attachment, [
                        'employee_id' => (int) $employee->employee_id,
                        'attachable_type' => get_class($note),
                        'attachable_id' => (int) $note->id,
                    ], 'Employee relation attachment uploaded.');
                }
            }

            $this->activity->log('note_created', $note, [
                'employee_id' => (int) $employee->employee_id,
                'note_type' => $note->note_type,
                'follow_up_date' => $note->follow_up_date?->toDateString(),
            ], 'Employee note created.');

            app(\App\Services\AuditLogger::class)->log(
                'relations.note.created',
                $note,
                [],
                [
                    'employee_id' => (int) $employee->employee_id,
                    'note_type' => (string) $note->note_type,
                    'follow_up_date' => $note->follow_up_date?->toDateString(),
                ],
                [],
                'Employee note created.'
            );

            return $note;
        });
    }

    public function deleteNote(Employee $employee, EmployeeNote $note): void
    {
        DB::transaction(function () use ($employee, $note) {
            $attachments = $note->attachments()->get();
            foreach ($attachments as $attachment) {
                $this->attachmentService->delete($attachment);
                $this->activity->log('rel_attachment_deleted', $attachment, [
                    'employee_id' => (int) $employee->employee_id,
                    'attachable_type' => get_class($note),
                    'attachable_id' => (int) $note->id,
                ], 'Employee relation attachment deleted.');
            }

            $this->repository->deleteNote($note);

            $this->activity->log('note_deleted', $note, [
                'employee_id' => (int) $employee->employee_id,
            ], 'Employee note deleted.');

            app(\App\Services\AuditLogger::class)->log(
                'relations.note.deleted',
                $note,
                [
                    'employee_id' => (int) $employee->employee_id,
                    'note_type' => (string) ($note->note_type ?? ''),
                ],
                [],
                [],
                'Employee note deleted.'
            );
        });
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile> $attachments
     */
    public function createIncident(Employee $employee, EmployeeIncidentData $data, array $attachments, ?int $actorId): EmployeeIncident
    {
        return DB::transaction(function () use ($employee, $data, $attachments, $actorId) {
            $incident = $this->repository->createIncident($employee, $data, $actorId);

            if (count($attachments) > 0) {
                $created = $this->attachmentService->uploadMany($incident, $attachments, null, $actorId);
                foreach ($created as $attachment) {
                    $this->activity->log('rel_attachment_uploaded', $attachment, [
                        'employee_id' => (int) $employee->employee_id,
                        'attachable_type' => get_class($incident),
                        'attachable_id' => (int) $incident->id,
                    ], 'Employee relation attachment uploaded.');
                }
            }

            $this->activity->log('incident_created', $incident, [
                'employee_id' => (int) $employee->employee_id,
                'category' => $incident->category,
                'status' => $incident->status,
                'incident_date' => $incident->incident_date?->toDateString(),
            ], 'Employee incident created.');

            app(\App\Services\AuditLogger::class)->log(
                'relations.incident.created',
                $incident,
                [],
                [
                    'employee_id' => (int) $employee->employee_id,
                    'category' => (string) $incident->category,
                    'status' => (string) $incident->status,
                    'incident_date' => $incident->incident_date?->toDateString(),
                ],
                [],
                'Employee incident created.'
            );

            return $incident;
        });
    }

    public function updateIncident(Employee $employee, EmployeeIncident $incident, EmployeeIncidentUpdateData $data): EmployeeIncident
    {
        return DB::transaction(function () use ($employee, $incident, $data) {
            $before = $incident->only(['status', 'action_taken', 'follow_up_date', 'assigned_to']);

            $updated = $this->repository->updateIncident($incident, $data);

            $changes = Arr::only($updated->getChanges(), ['status', 'action_taken', 'follow_up_date', 'assigned_to']);
            $diff = [];
            foreach ($changes as $field => $to) {
                $diff[$field] = ['from' => $before[$field] ?? null, 'to' => $to];
            }

            $this->activity->log('incident_updated', $updated, [
                'employee_id' => (int) $employee->employee_id,
                'changes' => $diff,
            ], 'Employee incident updated.');

            app(\App\Services\AuditLogger::class)->log(
                'relations.incident.updated',
                $updated,
                [
                    'status' => $before['status'] ?? null,
                    'follow_up_date' => $before['follow_up_date'] ?? null,
                    'assigned_to' => $before['assigned_to'] ?? null,
                ],
                [
                    'status' => $updated->status,
                    'follow_up_date' => $updated->follow_up_date?->toDateString(),
                    'assigned_to' => $updated->assigned_to,
                ],
                ['employee_id' => (int) $employee->employee_id],
                'Employee incident updated.'
            );

            return $updated;
        });
    }

    public function deleteIncident(Employee $employee, EmployeeIncident $incident): void
    {
        DB::transaction(function () use ($employee, $incident) {
            $attachments = $incident->attachments()->get();
            foreach ($attachments as $attachment) {
                $this->attachmentService->delete($attachment);
                $this->activity->log('rel_attachment_deleted', $attachment, [
                    'employee_id' => (int) $employee->employee_id,
                    'attachable_type' => get_class($incident),
                    'attachable_id' => (int) $incident->id,
                ], 'Employee relation attachment deleted.');
            }

            $this->repository->deleteIncident($incident);

            $this->activity->log('incident_deleted', $incident, [
                'employee_id' => (int) $employee->employee_id,
                'category' => $incident->category,
            ], 'Employee incident deleted.');

            app(\App\Services\AuditLogger::class)->log(
                'relations.incident.deleted',
                $incident,
                [
                    'employee_id' => (int) $employee->employee_id,
                    'category' => (string) $incident->category,
                    'status' => (string) ($incident->status ?? ''),
                ],
                [],
                [],
                'Employee incident deleted.'
            );
        });
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile> $files
     * @return array<int, EmployeeRelationAttachment>
     */
    public function addAttachments(Model $attachable, array $files, ?string $type, ?int $actorId): array
    {
        return DB::transaction(function () use ($attachable, $files, $type, $actorId) {
            $created = $this->attachmentService->uploadMany($attachable, $files, $type, $actorId);
            foreach ($created as $attachment) {
                $this->activity->log('rel_attachment_uploaded', $attachment, [
                    'employee_id' => (int) ($attachable->employee_id ?? 0),
                    'attachable_type' => get_class($attachable),
                    'attachable_id' => (int) $attachable->getKey(),
                ], 'Employee relation attachment uploaded.');

                app(\App\Services\AuditLogger::class)->log(
                    'relations.attachment.uploaded',
                    $attachment,
                    [],
                    [
                        'employee_id' => (int) ($attachable->employee_id ?? 0),
                        'attachable_type' => get_class($attachable),
                        'attachable_id' => (int) $attachable->getKey(),
                        'type' => (string) ($attachment->type ?? ''),
                        'filename' => (string) ($attachment->original_name ?? ''),
                        'file_size' => (int) ($attachment->file_size ?? 0),
                    ],
                    [],
                    'Employee relation attachment uploaded.'
                );
            }

            return $created;
        });
    }

    public function deleteAttachment(EmployeeRelationAttachment $attachment, Model $attachable): void
    {
        DB::transaction(function () use ($attachment, $attachable) {
            $this->attachmentService->delete($attachment);
            $this->activity->log('rel_attachment_deleted', $attachment, [
                'employee_id' => (int) ($attachable->employee_id ?? 0),
                'attachable_type' => get_class($attachable),
                'attachable_id' => (int) $attachable->getKey(),
            ], 'Employee relation attachment deleted.');

            app(\App\Services\AuditLogger::class)->log(
                'relations.attachment.deleted',
                $attachment,
                [
                    'employee_id' => (int) ($attachable->employee_id ?? 0),
                    'attachable_type' => get_class($attachable),
                    'attachable_id' => (int) $attachable->getKey(),
                    'type' => (string) ($attachment->type ?? ''),
                    'filename' => (string) ($attachment->original_name ?? ''),
                    'file_size' => (int) ($attachment->file_size ?? 0),
                ],
                [],
                [],
                'Employee relation attachment deleted.'
            );
        });
    }
}
