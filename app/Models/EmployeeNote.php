<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EmployeeNote extends Model
{
    public const TYPE_GENERAL = 'GENERAL';
    public const TYPE_COACHING = 'COACHING';
    public const TYPE_COMMENDATION = 'COMMENDATION';
    public const TYPE_WARNING = 'WARNING';
    public const TYPE_OTHER = 'OTHER';

    public const VISIBILITY_HR_ONLY = 'HR_ONLY';

    protected $fillable = [
        'employee_id',
        'note_type',
        'note',
        'follow_up_date',
        'created_by',
        'visibility',
    ];

    protected $casts = [
        'follow_up_date' => 'date:Y-m-d',
    ];

    public static function noteTypes(): array
    {
        return [
            self::TYPE_GENERAL,
            self::TYPE_COACHING,
            self::TYPE_COMMENDATION,
            self::TYPE_WARNING,
            self::TYPE_OTHER,
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

    public function attachments(): MorphMany
    {
        return $this->morphMany(EmployeeRelationAttachment::class, 'attachable');
    }
}
