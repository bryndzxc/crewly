<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantDocument extends Model
{
    use BelongsToCompany;

    protected $casts = [
        'company_id' => 'integer',
    ];

    /**
     * @return BelongsTo<Applicant, ApplicantDocument>
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class, 'applicant_id', 'id');
    }
}
