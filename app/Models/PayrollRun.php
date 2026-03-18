<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use BelongsToCompany;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_FINALIZED = 'finalized';
    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'company_id',
        'period_start',
        'period_end',
        'pay_frequency',
        'status',
        'generated_by',
        'finalized_by',
        'finalized_at',
        'released_by',
        'released_at',
        'notes',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'generated_by' => 'integer',
        'finalized_by' => 'integer',
        'released_by' => 'integer',
        'period_start' => 'date:Y-m-d',
        'period_end' => 'date:Y-m-d',
        'finalized_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PayrollRunItem::class);
    }

    public function generatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function finalizedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function releasedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function isDraft(): bool
    {
        return (string) $this->status === self::STATUS_DRAFT;
    }

    public function isReviewed(): bool
    {
        return (string) $this->status === self::STATUS_REVIEWED;
    }

    public function isFinalized(): bool
    {
        return (string) $this->status === self::STATUS_FINALIZED;
    }

    public function isReleased(): bool
    {
        return (string) $this->status === self::STATUS_RELEASED;
    }
}
