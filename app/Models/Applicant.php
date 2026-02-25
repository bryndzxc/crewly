<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Applicant extends Model
{
    use BelongsToCompany;

    public const STAGE_APPLIED = 'APPLIED';
    public const STAGE_SCREENING = 'SCREENING';
    public const STAGE_INTERVIEW = 'INTERVIEW';
    public const STAGE_OFFER = 'OFFER';
    public const STAGE_HIRED = 'HIRED';
    public const STAGE_REJECTED = 'REJECTED';
    public const STAGE_WITHDRAWN = 'WITHDRAWN';

    /**
     * IMPORTANT: use Laravel encrypted casts for Applicant PII (NOT custom AES).
     * Columns must be TEXT to support ciphertext payload length.
     */
    protected $casts = [
        'company_id' => 'integer',
        'first_name' => 'encrypted',
        'middle_name' => 'encrypted',
        'last_name' => 'encrypted',
        'email' => 'encrypted',
        'mobile_number' => 'encrypted',
        'applied_at' => 'date',
        'last_activity_at' => 'datetime',
    ];

    public static function stages(): array
    {
        return [
            self::STAGE_APPLIED,
            self::STAGE_SCREENING,
            self::STAGE_INTERVIEW,
            self::STAGE_OFFER,
            self::STAGE_HIRED,
            self::STAGE_REJECTED,
            self::STAGE_WITHDRAWN,
        ];
    }

    /**
     * @return BelongsTo<RecruitmentPosition, Applicant>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(RecruitmentPosition::class, 'position_id', 'id');
    }

    /**
     * @return HasMany<ApplicantDocument>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ApplicantDocument::class, 'applicant_id', 'id');
    }

    /**
     * @return HasMany<ApplicantInterview>
     */
    public function interviews(): HasMany
    {
        return $this->hasMany(ApplicantInterview::class, 'applicant_id', 'id');
    }

    public function fullName(): string
    {
        $parts = [
            (string) ($this->first_name ?? ''),
            (string) ($this->middle_name ?? ''),
            (string) ($this->last_name ?? ''),
            (string) ($this->suffix ?? ''),
        ];

        $parts = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $parts)));

        return implode(' ', $parts);
    }
}
