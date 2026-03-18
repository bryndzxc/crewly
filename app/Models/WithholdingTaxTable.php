<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class WithholdingTaxTable extends Model
{
    protected $fillable = [
        'payroll_frequency',
        'effective_from',
        'effective_to',
        'compensation_from',
        'compensation_to',
        'base_tax',
        'percentage',
        'excess_over',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'compensation_from' => 'decimal:2',
        'compensation_to' => 'decimal:2',
        'base_tax' => 'decimal:2',
        'percentage' => 'decimal:4',
        'excess_over' => 'decimal:2',
    ];

    public function scopeActiveOn($query, Carbon $date): void
    {
        $query->where('effective_from', '<=', $date->toDateString())
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date->toDateString());
            });
    }

    public function scopeForFrequency($query, string $frequency): void
    {
        $query->where('payroll_frequency', $frequency);
    }
}
