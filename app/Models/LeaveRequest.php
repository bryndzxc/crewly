<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class LeaveRequest extends Model
{
    use BelongsToCompany;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_DENIED = 'DENIED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $casts = [
        'company_id' => 'integer',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'is_half_day' => 'boolean',
        'approved_at' => 'datetime',
        'denied_at' => 'datetime',
    ];

    protected $appends = [
        'total_days',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function deniedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'denied_by');
    }

    public function getTotalDaysAttribute(): float
    {
        return $this->calculateTotalDays();
    }

    public function calculateTotalDays(): float
    {
        if ($this->is_half_day) {
            return 0.5;
        }

        $start = $this->start_date instanceof Carbon ? $this->start_date : Carbon::parse($this->start_date);
        $end = $this->end_date instanceof Carbon ? $this->end_date : Carbon::parse($this->end_date);

        if ($start->greaterThan($end)) {
            return 0.0;
        }

        return (float) ($start->diffInDays($end) + 1);
    }

    public function scopeOverlaps(Builder $query, int $employeeId, string $startDate, string $endDate): Builder
    {
        return $query
            ->where('employee_id', $employeeId)
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);
    }
}
