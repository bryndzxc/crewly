<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrewlyNotification extends Model
{
    public const SEVERITY_INFO = 'INFO';
    public const SEVERITY_WARNING = 'WARNING';
    public const SEVERITY_DANGER = 'DANGER';
    public const SEVERITY_SUCCESS = 'SUCCESS';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'url',
        'severity',
        'data',
        'dedupe_key',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markRead(): void
    {
        if ($this->read_at) {
            return;
        }

        $this->forceFill([
            'read_at' => now(),
        ])->save();
    }
}
