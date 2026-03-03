<?php

namespace App\Http\Requests;

use App\Models\CashAdvance;
use Illuminate\Foundation\Http\FormRequest;

class StoreCashAdvanceDeductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CashAdvance|null $cashAdvance */
        $cashAdvance = $this->route('cashAdvance');

        return $cashAdvance ? ($this->user()?->can('addDeduction', $cashAdvance) ?? false) : false;
    }

    public function rules(): array
    {
        return [
            'deducted_at' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'payroll_run_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
