<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SssContributionTable extends Model
{
    protected $fillable = [
        'effective_from',
        'effective_to',
        'range_from',
        'range_to',
        'monthly_salary_credit',
        'employee_share',
        'employer_share',
        'ec_share',
        'notes',
        'source_label',
        'source_reference_url',
        'source_notes',
    ];

    protected $casts = [
        'effective_from' => 'date:Y-m-d',
        'effective_to' => 'date:Y-m-d',
        'range_from' => 'decimal:2',
        'range_to' => 'decimal:2',
        'monthly_salary_credit' => 'decimal:2',
        'employee_share' => 'decimal:2',
        'employer_share' => 'decimal:2',
        'ec_share' => 'decimal:2',
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

    public function scopeMatchesSalary(Builder $query, float $monthlySalary): Builder
    {
        return $query
            ->where('range_from', '<=', $monthlySalary)
            ->where('range_to', '>=', $monthlySalary);
    }
}
