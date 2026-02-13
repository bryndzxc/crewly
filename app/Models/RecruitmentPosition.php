<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentPosition extends Model
{
    public const STATUS_OPEN = 'OPEN';
    public const STATUS_CLOSED = 'CLOSED';

    /**
     * @return HasMany<Applicant>
     */
    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class, 'position_id', 'id');
    }
}
