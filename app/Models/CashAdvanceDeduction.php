<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\CashAdvance;

class CashAdvanceDeduction extends Model
{
    use BelongsToCompany;

    protected $casts = [
        'company_id' => 'integer',
        'cash_advance_id' => 'integer',
        'deducted_at' => 'date:Y-m-d',
        'amount' => 'decimal:2',
        'created_by' => 'integer',
        'payroll_run_id' => 'integer',
    ];

    public function cashAdvance(): BelongsTo
    {
        return $this->belongsTo(CashAdvance::class, 'cash_advance_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
