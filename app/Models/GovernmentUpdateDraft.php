<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentUpdateDraft extends Model
{
    public const TYPE_SSS = 'sss';
    public const TYPE_PHILHEALTH = 'philhealth';
    public const TYPE_PAGIBIG = 'pagibig';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'source_type',
        'detected_at',
        'source_url',
        'content_hash',
        'status',
        'parsed_payload',
        'parse_error',
        'reviewed_by',
        'reviewed_at',
        'notes',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'parsed_payload' => 'array',
    ];

    public function isApprovable(): bool
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return false;
        }

        if (!empty($this->parse_error)) {
            return false;
        }

        $payload = $this->parsed_payload;

        return is_array($payload) && count($payload) > 0;
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
