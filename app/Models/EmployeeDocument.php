<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class EmployeeDocument extends Model
{
    protected $fillable = [
        'employee_id',
        'type',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'issue_date',
        'expiry_date',
        'notes',
        'uploaded_by',
        'is_encrypted',
        'encryption_algo',
        'encryption_iv',
        'encryption_tag',
        'key_version',
    ];

    protected $casts = [
        'issue_date' => 'date:Y-m-d',
        'expiry_date' => 'date:Y-m-d',
        'is_encrypted' => 'boolean',
        'file_size' => 'integer',
        'key_version' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id');
    }

    public function getDaysToExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        $today = Carbon::today();
        $expiry = $this->expiry_date;

        return $today->diffInDays($expiry, false);
    }

    public function getExpiryStatusAttribute(): string
    {
        if (!$this->expiry_date) {
            return 'OK';
        }

        $today = Carbon::today();
        $expiry = $this->expiry_date;

        if ($expiry->lt($today)) {
            return 'EXPIRED';
        }

        $windowDays = (int) config('crewly.documents.expiring_soon_days', 30);
        $windowEnd = $today->copy()->addDays($windowDays);

        if ($expiry->lte($windowEnd)) {
            return 'EXPIRING';
        }

        return 'OK';
    }
}
