<?php

namespace App\Http\Requests;

use App\Models\CashAdvance;
use Illuminate\Foundation\Http\FormRequest;

class ApproveCashAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CashAdvance|null $cashAdvance */
        $cashAdvance = $this->route('cashAdvance');

        return $cashAdvance ? ($this->user()?->can('approve', $cashAdvance) ?? false) : false;
    }

    public function rules(): array
    {
        return [
            'decision_remarks' => ['nullable', 'string', 'max:5000'],
            'approved_at' => ['nullable', 'date'],
            'installment_amount' => ['required', 'numeric', 'min:0.01'],
            'installments_count' => ['required', 'integer', 'min:1', 'max:240'],
        ];
    }
}
