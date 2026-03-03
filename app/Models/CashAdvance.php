<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\CashAdvanceDeduction;

class CashAdvance extends Model
{
    use BelongsToCompany;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_COMPLETED = 'COMPLETED';

    protected $casts = [
        'company_id' => 'integer',
        'employee_id' => 'integer',
        'amount' => 'decimal:2',
        'requested_at' => 'date:Y-m-d',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'installment_amount' => 'decimal:2',
        'installments_count' => 'integer',
        'completed_at' => 'datetime',
        'attachment_is_encrypted' => 'boolean',
        'attachment_key_version' => 'integer',
        'attachment_size' => 'integer',
    ];

    protected $appends = [
        'total_deducted',
        'remaining_balance',
        'has_attachment',
        'attachment_download_url',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(CashAdvanceDeduction::class, 'cash_advance_id');
    }

    public function getTotalDeductedAttribute(): float
    {
        // When queried with ->withSum('deductions', 'amount') Laravel exposes `deductions_sum_amount`.
        if (array_key_exists('deductions_sum_amount', $this->attributes)) {
            return (float) ($this->attributes['deductions_sum_amount'] ?? 0);
        }

        if ($this->relationLoaded('deductions')) {
            return (float) $this->deductions->sum(fn ($d) => (float) ($d->amount ?? 0));
        }

        return (float) $this->deductions()->sum('amount');
    }

    public function getRemainingBalanceAttribute(): float
    {
        $amount = (float) ($this->amount ?? 0);
        $remaining = $amount - $this->total_deducted;

        return max(0.0, $remaining);
    }

    public function getHasAttachmentAttribute(): bool
    {
        return (string) ($this->attributes['attachment_path'] ?? '') !== '';
    }

    public function getAttachmentDownloadUrlAttribute(): ?string
    {
        if (!$this->has_attachment || !$this->getKey()) {
            return null;
        }

        return route('cash_advances.attachment', $this->getKey(), false);
    }

    public function isActive(): bool
    {
        return in_array((string) $this->status, [self::STATUS_APPROVED], true) && $this->remaining_balance > 0;
    }
}
