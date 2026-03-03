<?php

namespace App\Http\Requests;

use App\Models\CashAdvance;
use Illuminate\Foundation\Http\FormRequest;

class RejectCashAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CashAdvance|null $cashAdvance */
        $cashAdvance = $this->route('cashAdvance');

        return $cashAdvance ? ($this->user()?->can('reject', $cashAdvance) ?? false) : false;
    }

    public function rules(): array
    {
        return [
            'decision_remarks' => ['nullable', 'string', 'max:5000'],
            'rejected_at' => ['nullable', 'date'],
        ];
    }
}
