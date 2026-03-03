<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashAdvanceDeductionRequest;
use App\Models\CashAdvance;
use App\Services\CashAdvanceService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class CashAdvanceDeductionController extends Controller
{
    public function __construct(private readonly CashAdvanceService $cashAdvanceService) {}

    public function store(StoreCashAdvanceDeductionRequest $request, CashAdvance $cashAdvance): RedirectResponse
    {
        $this->authorize('addDeduction', $cashAdvance);

        try {
            $this->cashAdvanceService->addDeduction($cashAdvance, $request->validated(), $request->user()?->id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->setStatusCode(303);
        }

        return to_route('cash_advances.show', $cashAdvance)
            ->with('success', 'Deduction recorded.')
            ->setStatusCode(303);
    }
}
