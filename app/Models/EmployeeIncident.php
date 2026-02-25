<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EmployeeIncident extends Model
{
    use BelongsToCompany;
    public const STATUS_OPEN = 'OPEN';
    public const STATUS_UNDER_REVIEW = 'UNDER_REVIEW';
    public const STATUS_RESOLVED = 'RESOLVED';
    public const STATUS_CLOSED = 'CLOSED';

    protected $fillable = [
        'company_id',
        'employee_id',
        'category',
        'incident_date',
        'description',
        'status',
        'action_taken',
        'follow_up_date',
        'created_by',
        'assigned_to',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'incident_date' => 'date:Y-m-d',
        'follow_up_date' => 'date:Y-m-d',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_RESOLVED,
            self::STATUS_CLOSED,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to', 'id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(EmployeeRelationAttachment::class, 'attachable');
    }
}
