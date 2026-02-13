<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantInterview extends Model
{
    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Applicant, ApplicantInterview>
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class, 'applicant_id', 'id');
    }
}
