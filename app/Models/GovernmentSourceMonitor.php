<?php

namespace App\Models;

use App\Models\GovernmentUpdateDraft;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GovernmentSourceMonitor extends Model
{
    public const TYPE_SSS = 'sss';
    public const TYPE_PHILHEALTH = 'philhealth';
    public const TYPE_PAGIBIG = 'pagibig';

    public const STATUS_OK = 'ok';
    public const STATUS_CHANGED = 'changed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PENDING_REVIEW = 'pending_review';

    protected $fillable = [
        'source_type',
        'source_url',
        'last_checked_at',
        'last_hash',
        'last_status',
        'last_error',
        'raw_snapshot_path',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
    ];

    public function drafts(): HasMany
    {
        return $this->hasMany(GovernmentUpdateDraft::class, 'source_type', 'source_type');
    }

    public function latestDraft(): HasOne
    {
        return $this->hasOne(GovernmentUpdateDraft::class, 'source_type', 'source_type')->latest('detected_at');
    }
}
