<?php

namespace App\Repositories;

use App\DTO\EmployeeIncidentData;
use App\DTO\EmployeeIncidentUpdateData;
use App\DTO\EmployeeNoteData;
use App\Models\Employee;
use App\Models\EmployeeIncident;
use App\Models\EmployeeNote;

class EmployeeRelationRepository extends BaseRepository
{
    public function createNote(Employee $employee, EmployeeNoteData $data, ?int $createdBy): EmployeeNote
    {
        return EmployeeNote::query()->create([
            'employee_id' => (int) $employee->employee_id,
            'note_type' => $data->note_type,
            'note' => $data->note,
            'follow_up_date' => $data->follow_up_date,
            'created_by' => $createdBy,
            'visibility' => EmployeeNote::VISIBILITY_HR_ONLY,
        ]);
    }

    public function deleteNote(EmployeeNote $note): void
    {
        $note->delete();
    }

    public function createIncident(Employee $employee, EmployeeIncidentData $data, ?int $createdBy): EmployeeIncident
    {
        return EmployeeIncident::query()->create([
            'employee_id' => (int) $employee->employee_id,
            'category' => $data->category,
            'incident_date' => $data->incident_date,
            'description' => $data->description,
            'status' => EmployeeIncident::STATUS_OPEN,
            'follow_up_date' => $data->follow_up_date,
            'created_by' => $createdBy,
        ]);
    }

    public function updateIncident(EmployeeIncident $incident, EmployeeIncidentUpdateData $data): EmployeeIncident
    {
        $incident->update([
            'status' => $data->status,
            'action_taken' => $data->action_taken,
            'follow_up_date' => $data->follow_up_date,
            'assigned_to' => $data->assigned_to,
        ]);

        return $incident;
    }

    public function deleteIncident(EmployeeIncident $incident): void
    {
        $incident->delete();
    }
}
