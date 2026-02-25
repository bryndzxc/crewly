<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantInterview extends Model
{
    use BelongsToCompany;

    protected $casts = [
        'company_id' => 'integer',
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
