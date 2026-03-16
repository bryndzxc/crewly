<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PagibigContributionSetting extends Model
{
    protected $fillable = [
        'effective_from',
        'effective_to',
        'employee_rate_below_threshold',
        'employee_rate_above_threshold',
        'employer_rate',
        'salary_threshold',
        'monthly_cap',
        'notes',
        'source_label',
        'source_reference_url',
        'source_notes',
    ];

    protected $casts = [
        'effective_from' => 'date:Y-m-d',
        'effective_to' => 'date:Y-m-d',
        'employee_rate_below_threshold' => 'decimal:4',
        'employee_rate_above_threshold' => 'decimal:4',
        'employer_rate' => 'decimal:4',
        'salary_threshold' => 'decimal:2',
        'monthly_cap' => 'decimal:2',
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
