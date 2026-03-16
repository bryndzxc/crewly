<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PhilhealthContributionSetting extends Model
{
    protected $fillable = [
        'effective_from',
        'effective_to',
        'premium_rate',
        'salary_floor',
        'salary_ceiling',
        'employee_share_percent',
        'employer_share_percent',
        'notes',
        'source_label',
        'source_reference_url',
        'source_notes',
    ];

    protected $casts = [
        'effective_from' => 'date:Y-m-d',
        'effective_to' => 'date:Y-m-d',
        'premium_rate' => 'decimal:4',
        'salary_floor' => 'decimal:2',
        'salary_ceiling' => 'decimal:2',
        'employee_share_percent' => 'decimal:4',
        'employer_share_percent' => 'decimal:4',
    ];

    public function scopeActiveOn(Builder $query, Carbon|string $date): Builder
    {
        $d = $date instanceof Carbon ? $date->toDateString() : Carbon::parse((string) $date)->toDateString();

        return $query
            ->whereDate('effective_from', '<=', $d)
            ->where(function (Builder $q) use ($d) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $d);
            });
    }
}
