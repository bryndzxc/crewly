<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use BelongsToCompany;

    protected $casts = [
        'company_id' => 'integer',
        'credits' => 'decimal:2',
        'used' => 'decimal:2',
        'year' => 'integer',
    ];

    protected $appends = [
        'remaining',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function getRemainingAttribute(): float
    {
        $credits = (float) ($this->credits ?? 0);
        $used = (float) ($this->used ?? 0);

        return max(0.0, $credits - $used);
    }
}
